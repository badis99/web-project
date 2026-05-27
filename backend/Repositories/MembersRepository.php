<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

class MembersRepository
{
    private string $url;
    private string $key;
    private string $storageBucket;
    private string $storageUrl;

    public function __construct()
    {
        $config = loadAppConfig();
        $this->url = $config['supabaseUrl'] . '/rest/v1/users';
        $this->key = $config['supabaseKey'];
        $this->storageBucket = $config['storageBucket'];
        $this->storageUrl = $config['supabaseUrl'] . '/storage/v1';
    }

    private function request(string $method, string $url, array $headers = [], mixed $body = null, bool $jsonBody = true): mixed
    {
        $ch = curl_init($url);
        $defaultHeaders = [
            "apikey: {$this->key}",
            "Authorization: Bearer {$this->key}",
        ];

        if ($jsonBody) {
            $defaultHeaders[] = "Content-Type: application/json";
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => array_merge($defaultHeaders, $headers),
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody ? json_encode($body) : $body);
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function findAll(): array
    {
        return $this->request('GET', $this->url . '?select=*') ?? [];
    }

    public function findById(string $id): array|false
    {
        $result = $this->request('GET', $this->url . "?id=eq.{$id}&select=*");
        return $result[0] ?? false;
    }

    public function deleteById(string $id): bool
    {
        $result = $this->request(
            'DELETE',
            $this->url . "?id=eq.{$id}",
            ['Prefer: return=representation']
        );
        return is_array($result);
    }

    public function updateById(string $id, array $data): bool
    {
        if (empty($data)) {
            return true;
        }

        $result = $this->request(
            'PATCH',
            $this->url . "?id=eq.{$id}",
            ['Prefer: return=representation'],
            $data,
            true
        );

        return is_array($result);
    }

    public function uploadProfilePicture(string $filename, string $content, string $contentType): bool
    {
        $uploadUrl = $this->storageUrl . '/object/' . rawurlencode($this->storageBucket) . '/' . rawurlencode($filename);
        $headers = ["Content-Type: {$contentType}"];
        $result = $this->request('PUT', $uploadUrl, $headers, $content, false);
        return $result === null || is_array($result);
    }

    public function findByFilter(string $column, string $value): array
    {
        $allowedColumns = ["department", "fieldofstudy", "yearOfStudy", "firstname", "lastname"];

        if (!in_array($column, $allowedColumns)) {
            return [];
        }

        if ($column === "yearOfStudy") {
            $filter = "{$column}=eq.{$value}";
        } else {
            $filter = "{$column}=ilike.{$value}";
        }

        return $this->request('GET', $this->url . "?{$filter}&select=*") ?? [];
    }
}