<?php
/**
 * Service d'upload de fichiers.
 */

declare(strict_types=1);

/**
 * Gere l'upload securise des photos de profil.
 */
class UploadService
{
    /** @var array Types MIME autorises */
    private array $_allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** @var int Taille maximale en octets */
    private int $_maxSize = 5242880;

    /** @var SupabaseClient Client Supabase */
    private SupabaseClient $_client;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->_client = SupabaseClient::getInstance();
    }

    /**
     * Enregistre une photo de profil dans Supabase Storage.
     *
     * @param array $file Entree $_FILES
     * @return string|false URL publique Supabase ou false en cas d'erreur
     */
    public function saveProfilePicture(array $file): string|false
    {
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $this->_maxSize) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $this->_allowedTypes, true)) {
            return false;
        }

        $safeExtension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $filename = bin2hex(random_bytes(16)) . '.' . $safeExtension;
        $storagePath = 'pending/' . date('Y/m') . '/' . $filename;
        $uploadResult = $this->_client->uploadFile(
            SUPABASE_STORAGE_BUCKET,
            $storagePath,
            $file['tmp_name'],
            $mimeType
        );

        if (!in_array($uploadResult['status'], [200, 201], true)) {
            error_log('[UploadService] Supabase Storage upload failed: ' . json_encode($uploadResult));
            return false;
        }

        return $uploadResult['publicUrl'];
    }
}
