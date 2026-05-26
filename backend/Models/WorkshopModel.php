<?php

declare(strict_types=1);

/**
 * WorkshopModel — Supabase REST API implementation
 *
 * Uses supabase_query() from config/db.php (no direct PostgreSQL needed).
 * PostgREST column aliasing maps snake_case DB columns to camelCase.
 */
final class WorkshopModel
{
    private const TABLE  = 'workshops';
    private const SELECT = 'id,title,date,departement,description,videoUrl:video_url,createdAt:created_at,updatedAt:updated_at';

    public function all(): array
    {
        $res = supabase_query('GET', self::TABLE, [], 'select=' . self::SELECT . '&order=date.desc.nullslast');
        return $res['data'] ?? [];
    }

    public function find(string $id): ?array
    {
        $res  = supabase_query('GET', self::TABLE, [], 'id=eq.' . urlencode($id) . '&select=' . self::SELECT);
        $rows = $res['data'] ?? [];
        return $rows[0] ?? null;
    }

    public function create(array $data): array
    {
        $body = [
            'title'       => $data['title'],
            'date'        => $data['date'],
            'departement' => $data['departement'],
            'description' => $data['description'],
            'video_url'   => $data['videoUrl'] ?? null,
        ];

        $res  = supabase_query('POST', self::TABLE, $body);
        $rows = $res['data'] ?? [];
        $row  = is_array($rows) ? ($rows[0] ?? $rows) : [];
        return $this->mapRow(is_array($row) ? $row : []);
    }

    public function update(string $id, array $data): ?array
    {
        $current = $this->find($id);
        if ($current === null) {
            return null;
        }

        $body = [];
        if (isset($data['title']))       $body['title']       = trim((string) $data['title']);
        if (isset($data['date']))        $body['date']        = (string) $data['date'];
        if (isset($data['departement'])) $body['departement'] = trim((string) $data['departement']);
        if (isset($data['description'])) $body['description'] = trim((string) $data['description']);
        if (array_key_exists('videoUrl', $data)) {
            $body['video_url'] = $data['videoUrl'] === null ? null : (string) $data['videoUrl'];
        }

        $res  = supabase_query('PATCH', self::TABLE, $body, 'id=eq.' . urlencode($id));
        $rows = $res['data'] ?? [];
        $row  = is_array($rows) ? ($rows[0] ?? $rows) : [];

        // If Supabase returned the updated row, map it; otherwise re-fetch
        if (!empty($row) && isset($row['id'])) {
            return $this->mapRow($row);
        }
        return $this->find($id);
    }

    public function delete(string $id): bool
    {
        $res = supabase_query('DELETE', self::TABLE, [], 'id=eq.' . urlencode($id));
        return in_array($res['status'], [200, 204], true);
    }

    /** Normalise a raw Supabase row to the camelCase shape the controllers expect. */
    private function mapRow(array $row): array
    {
        return [
            'id'          => $row['id']          ?? null,
            'title'       => $row['title']        ?? null,
            'date'        => $row['date']          ?? null,
            'departement' => $row['departement']  ?? null,
            'description' => $row['description']  ?? null,
            'videoUrl'    => $row['videoUrl']     ?? $row['video_url']   ?? null,
            'createdAt'   => $row['createdAt']    ?? $row['created_at']  ?? null,
            'updatedAt'   => $row['updatedAt']    ?? $row['updated_at']  ?? null,
        ];
    }
}
