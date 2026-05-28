<?php
declare(strict_types=1);

final class WorkshopRepository
{
    private const TABLE_NAME = 'workshops';
    private const SELECT_COLUMNS = 'id,title,description,departement,date,video_url,created_at,updated_at';

    private SupabaseClient $_client;

    public function __construct()
    {
        $this->_client = SupabaseClient::getInstance();
    }

    /**
     * @return Workshop[]
     */
    public function findAll(): array
    {
        $res = $this->_client->query(
            'GET',
            self::TABLE_NAME,
            [],
            'select=' . self::SELECT_COLUMNS . '&order=date.asc.nullslast'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[WorkshopRepository] findAll failed: ' . json_encode($res));
            return [];
        }

        return array_map(fn(array $r) => Workshop::fromArray($r), $res['data']);
    }

    public function findById(string $id): ?Workshop
    {
        $res = $this->_client->query(
            'GET',
            self::TABLE_NAME,
            [],
            'id=eq.' . rawurlencode($id) . '&select=' . self::SELECT_COLUMNS . '&limit=1'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[WorkshopRepository] findById failed: ' . json_encode($res));
            return null;
        }

        if (empty($res['data'][0])) {
            return null;
        }

        return Workshop::fromArray($res['data'][0]);
    }

    public function findByDepartement(string $departement): array
    {
        $res = $this->_client->query(
            'GET',
            self::TABLE_NAME,
            [],
            'departement=eq.' . rawurlencode($departement) . '&select=' . self::SELECT_COLUMNS . '&order=date.asc.nullslast'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[WorkshopRepository] findByDepartement failed: ' . json_encode($res));
            return [];
        }

        return array_map(fn(array $r) => Workshop::fromArray($r), $res['data']);
    }

    public function create(string $title, string $description, string $departement, string $date, ?string $videoUrl = null): ?Workshop
    {
        $payload = array_filter([
            'title'       => $title,
            'description' => $description,
            'departement' => $departement,
            'date'        => $date,
            'video_url'   => $videoUrl,
        ], fn($v) => $v !== null);

        $res = $this->_client->query('POST', self::TABLE_NAME, $payload, 'select=' . self::SELECT_COLUMNS);

        if (($res['status'] ?? 0) !== 201 || !is_array($res['data']) || empty($res['data'][0])) {
            error_log('[WorkshopRepository] create failed: ' . json_encode($res));
            return null;
        }

        return Workshop::fromArray($res['data'][0]);
    }

    public function update(string $id, array $data): ?Workshop
    {
        $fieldMap = [
            'title'       => 'title',
            'description' => 'description',
            'departement' => 'departement',
            'date'        => 'date',
            'videoUrl'    => 'video_url',
            'video_url'   => 'video_url',
        ];

        $payload = [];
        foreach ($data as $key => $value) {
            if (!isset($fieldMap[$key])) {
                continue;
            }
            $column = $fieldMap[$key];
            if ($column === 'video_url') {
                $payload[$column] = ($value === null || $value === '') ? null : (string)$value;
            } else {
                $payload[$column] = is_string($value) ? trim($value) : $value;
            }
        }

        if ($payload === []) {
            return $this->findById($id);
        }

        $payload['updated_at'] = date('c');

        $res = $this->_client->query(
            'PATCH',
            self::TABLE_NAME,
            $payload,
            'id=eq.' . rawurlencode($id) . '&select=' . self::SELECT_COLUMNS
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data']) || empty($res['data'][0])) {
            error_log('[WorkshopRepository] update failed: ' . json_encode($res));
            return null;
        }

        return Workshop::fromArray($res['data'][0]);
    }

    public function delete(string $id): bool
    {
        $res = $this->_client->query(
            'DELETE',
            self::TABLE_NAME,
            [],
            'id=eq.' . rawurlencode($id) . '&select=id'
        );

        $status = (int)($res['status'] ?? 0);
        if ($status !== 200) {
            error_log('[WorkshopRepository] delete failed: ' . json_encode($res));
            return false;
        }

        if (!is_array($res['data']) || empty($res['data'][0]) || !is_array($res['data'][0])) {
            return false;
        }

        return isset($res['data'][0]['id']) && (string)$res['data'][0]['id'] !== '';
    }
}
