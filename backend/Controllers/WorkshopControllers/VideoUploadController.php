<?php

declare(strict_types=1);

/**
 * VideoUploadController
 *
 * POST /api/upload/video  (multipart/form-data, field name: "video")
 *
 * Uploads the file to Supabase Storage bucket "workshop-videos" and
 * returns { "url": "<public-url>" } so the caller can pass it as
 * the videoUrl when creating / updating a workshop.
 *
 * Requires in .env:
 *   SUPABASE_URL             (already used by db.php)
 *   SUPABASE_SERVICE_ROLE_KEY (already used by db.php → SUPABASE_KEY)
 */
final class VideoUploadController
{
    /** Allowed MIME types */
    private const ALLOWED_TYPES = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
        'video/x-msvideo',   // .avi
    ];

    /** Max upload size: 200 MB */
    private const MAX_BYTES = 200 * 1024 * 1024;

    public function upload(): void
    {
        $this->requireMethod('POST');

        // ── 1. Verify a file was sent ────────────────────────────
        if (empty($_FILES['video'])) {
            $this->jsonError('No video file received. Send the file as multipart/form-data with field name "video".', 422);
        }

        $file = $_FILES['video'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError($this->uploadErrorMessage($file['error']), 422);
        }

        // ── 2. Validate size ─────────────────────────────────────
        if ($file['size'] > self::MAX_BYTES) {
            $maxMb = self::MAX_BYTES / 1024 / 1024;
            $this->jsonError("Video exceeds the {$maxMb} MB limit.", 422);
        }

        if (!is_uploaded_file((string)$file['tmp_name'])) {
            $this->jsonError('Invalid uploaded file.', 400);
        }

        // ── 3. Validate MIME type (use finfo, not user-supplied) ─
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            $this->jsonError("Unsupported file type \"{$mimeType}\". Allowed: mp4, webm, ogg, mov, avi.", 415);
        }

        // ── 4. Build a unique storage path ───────────────────────
        $ext       = $this->mimeToExtension($mimeType);
        $uuid      = $this->generateUuid();
        $storagePath = "workshops/{$uuid}.{$ext}";
        $publicUrl = '';

        // ── 5. Upload to Supabase Storage ────────────────────────
        try {
            $publicUrl = $this->uploadToSupabase($file['tmp_name'], $storagePath, $mimeType);
        } catch (RuntimeException $exception) {
            $code = $exception->getCode();
            $status = ($code >= 400 && $code <= 599) ? $code : 500;
            $this->jsonError($exception->getMessage(), $status);
        }

        echo json_encode(['url' => $publicUrl]);
    }

    // ── Private helpers ──────────────────────────────────────────

    private function uploadToSupabase(
        string $tmpPath,
        string $storagePath,
        string $mimeType
    ): string {
        $supabaseUrl = rtrim(SUPABASE_URL, '/');
        $serviceKey  = SUPABASE_KEY;
        $bucket      = 'workshop-videos';

        $endpoint = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$storagePath}";

        $fileContents = file_get_contents($tmpPath);
        if ($fileContents === false) {
            throw new RuntimeException('Failed to read uploaded file from temp storage.', 500);
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,           // large files need time
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fileContents,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $serviceKey,
                'Content-Type: '         . $mimeType,
                'x-upsert: true',                    // overwrite if re-uploaded
            ],
            CURLOPT_SSL_VERIFYPEER => defined('LOCAL_DEV') ? !LOCAL_DEV : true,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new RuntimeException('Storage upload failed (cURL): ' . $curlErr, 502);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $detail = json_decode((string) $raw, true)['message'] ?? $raw;
            throw new RuntimeException("Storage upload failed (HTTP {$httpCode}): {$detail}", 502);
        }

        // Return the public URL
        return "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$storagePath}";
    }

    private function requireMethod(string $method): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
            $this->jsonError('Method not allowed.', 405);
        }
    }

    private function jsonError(string $message, int $status): void
    {
        http_response_code($status);
        echo json_encode(['error' => $message]);
        exit;
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function mimeToExtension(string $mime): string
    {
        return match ($mime) {
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/ogg'       => 'ogv',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            default           => 'bin',
        };
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds the maximum allowed size.',
            UPLOAD_ERR_PARTIAL   => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE   => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temp folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to write the file to disk.',
            default              => "Upload error (code {$code}).",
        };
    }
}
