<?php

declare(strict_types=1);

/**
 * WorkshopRegistrationModel — Supabase REST API implementation
 *
 * Uses supabase_query() from config/db.php (no direct PostgreSQL needed).
 */
final class WorkshopRegistrationModel
{
    private const TABLE  = 'workshop_registrations';
    private const SELECT = 'id,workshop,name,surname,identifier,rating,createdAt:created_at';

    public function all(): array
    {
        $res = supabase_query('GET', self::TABLE, [], 'select=' . self::SELECT . '&order=created_at.desc');
        return $res['data'] ?? [];
    }

    public function create(array $data): array
    {
        $body = [
            'workshop'   => $data['workshop'],
            'name'       => $data['name'],
            'surname'    => $data['surname'],
            'identifier' => $data['identifier'],
            'rating'     => (int) $data['rating'],
        ];

        $res  = supabase_query('POST', self::TABLE, $body);
        $rows = $res['data'] ?? [];
        $row  = is_array($rows) ? ($rows[0] ?? $rows) : [];
        return is_array($row) ? $row : [];
    }
}
