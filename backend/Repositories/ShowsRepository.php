<?php
declare(strict_types=1);

final class ShowsRepository
{
    private SupabaseClient $_client;

    public function __construct()
    {
        $this->_client = SupabaseClient::getInstance();
    }

    /**
     * @return Show[]
     */
    public function findAll(): array
    {
        $res = $this->_client->query(
            'GET',
            'shows',
            [],
            // NOTE: Supabase/PostgREST returns unquoted columns in lowercase.
            // In this project DB, the column is `startsat` (not `startsAt`).
            'select=id,name,description,startsat&order=startsat.asc.nullslast'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[ShowsRepository] findAll failed: ' . json_encode($res));
            return [];
        }

        return array_map(fn(array $r) => Show::fromArray($r), $res['data']);
    }

    public function findByName(string $name): ?Show
    {
        $res = $this->_client->query(
            'GET',
            'shows',
            [],
            'name=eq.' . rawurlencode($name) . '&select=id,name,description,startsat&limit=1'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[ShowsRepository] findByName failed: ' . json_encode($res));
            return null;
        }

        if (empty($res['data'][0])) {
            return null;
        }

        return Show::fromArray($res['data'][0]);
    }

    public function create(string $name, ?string $description, ?string $startsAt): ?Show
    {
        $payload = array_filter([
            'name' => $name,
            'description' => $description,
            'startsat' => $startsAt,
        ], fn($v) => $v !== null);

        $res = $this->_client->query('POST', 'shows', $payload);

        if (($res['status'] ?? 0) !== 201 || !is_array($res['data']) || empty($res['data'][0])) {
            error_log('[ShowsRepository] create failed: ' . json_encode($res));
            return null;
        }

        return Show::fromArray($res['data'][0]);
    }
}

