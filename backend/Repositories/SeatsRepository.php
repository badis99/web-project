<?php
declare(strict_types=1);

final class SeatsRepository
{
    private SupabaseClient $_client;

    public function __construct()
    {
        $this->_client = SupabaseClient::getInstance();
    }

    /**
     * @return Seat[]
     */
    public function findAll(): array
    {
        $res = $this->_client->query(
            'GET',
            'seats',
            [],
            'select=id,section,row,number&order=section.asc,row.asc,number.asc'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[SeatsRepository] findAll failed: ' . json_encode($res));
            return [];
        }

        return array_map(fn(array $r) => Seat::fromArray($r), $res['data']);
    }

    public function findIdByCoordinates(string $section, string $row, int $number): ?int
    {
        $res = $this->_client->query(
            'GET',
            'seats',
            [],
            'section=eq.' . rawurlencode($section) .
            '&row=eq.' . rawurlencode($row) .
            '&number=eq.' . rawurlencode((string)$number) .
            '&select=id&limit=1'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[SeatsRepository] findIdByCoordinates failed: ' . json_encode($res));
            return null;
        }

        if (empty($res['data'][0]['id'])) {
            return null;
        }

        return (int)$res['data'][0]['id'];
    }
}

