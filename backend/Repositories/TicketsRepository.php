<?php
declare(strict_types=1);

final class TicketsRepository
{
    private SupabaseClient $_client;

    public function __construct()
    {
        $this->_client = SupabaseClient::getInstance();
    }

    /**
     * @return Ticket[]
     */
    public function findByShowId(string $showId): array
    {
        $res = $this->_client->query(
            'GET',
            'tickets',
            [],
            'show_id=eq.' . rawurlencode($showId) . '&select=id,show_id,seat_id,status'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[TicketsRepository] findByShowId failed: ' . json_encode($res));
            return [];
        }

        return array_map(fn(array $r) => Ticket::fromArray($r), $res['data']);
    }

    public function existsForShowAndSeat(string $showId, int $seatId): bool
    {
        $res = $this->_client->query(
            'GET',
            'tickets',
            [],
            'show_id=eq.' . rawurlencode($showId) .
            '&seat_id=eq.' . rawurlencode((string)$seatId) .
            '&select=id&limit=1'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[TicketsRepository] existsForShowAndSeat failed: ' . json_encode($res));
            return false;
        }

        return !empty($res['data']);
    }

    public function create(string $showId, int $seatId, string $status): bool
    {
        $res = $this->_client->query('POST', 'tickets', [
            'show_id' => $showId,
            'seat_id' => $seatId,
            'status' => $status,
        ]);

        return ($res['status'] ?? 0) === 201;
    }

    public function deleteByShowAndSeat(string $showId, int $seatId): bool
    {
        $res = $this->_client->query(
            'DELETE',
            'tickets',
            [],
            'show_id=eq.' . rawurlencode($showId) . '&seat_id=eq.' . rawurlencode((string)$seatId)
        );

        // PostgREST often returns 200 with representation, or 204 with empty body.
        $status = (int)($res['status'] ?? 0);
        return $status === 200 || $status === 204;
    }
}

