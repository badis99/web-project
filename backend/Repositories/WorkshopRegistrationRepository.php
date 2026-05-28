<?php

declare(strict_types=1);

final class WorkshopRegistrationRepository
{
    private const TABLE_NAME = 'workshop_registrations';
    private const SELECT_COLUMNS = 'id,id_user,id_workshop,rating,created_at';
    private const SELECT_DETAILS_COLUMNS = 'id,id_user,id_workshop,rating,created_at,workshops(title),users(firstname,lastname)';

    private SupabaseClient $_client;

    public function __construct()
    {
        $this->_client = SupabaseClient::getInstance();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllWithDetails(): array
    {
        $res = $this->_client->query(
            'GET',
            self::TABLE_NAME,
            [],
            'select=' . rawurlencode(self::SELECT_DETAILS_COLUMNS) . '&order=created_at.asc.nullslast'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[WorkshopRegistrationRepository] findAllWithDetails failed: ' . json_encode($res));
            return [];
        }

        return array_map(fn(array $row) => $this->withDetailsFromArray($row), $res['data']);
    }

    public function findById(string $id): ?WorkshopRegistration
    {
        $res = $this->_client->query(
            'GET',
            self::TABLE_NAME,
            [],
            'id=eq.' . rawurlencode($id) . '&select=' . rawurlencode(self::SELECT_COLUMNS) . '&limit=1'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[WorkshopRegistrationRepository] findById failed: ' . json_encode($res));
            return null;
        }

        if (empty($res['data'][0])) {
            return null;
        }

        return WorkshopRegistration::fromArray($res['data'][0]);
    }

    /**
     * @return WorkshopRegistration[]
     */
    public function findByWorkshop(string $idWorkshop): array
    {
        $res = $this->_client->query(
            'GET',
            self::TABLE_NAME,
            [],
            'id_workshop=eq.' . rawurlencode($idWorkshop) . '&select=' . self::SELECT_COLUMNS . '&order=created_at.asc.nullslast'
        );

        if (($res['status'] ?? 0) !== 200 || !is_array($res['data'])) {
            error_log('[WorkshopRegistrationRepository] findByWorkshop failed: ' . json_encode($res));
            return [];
        }

        return array_map(fn(array $row) => WorkshopRegistration::fromArray($row), $res['data']);
    }

    public function create(string $idUser, string $idWorkshop, int $rating): ?WorkshopRegistration
    {
        $payload = [
            'id_user' => $idUser,
            'id_workshop' => $idWorkshop,
            'rating' => $rating,
        ];

        $res = $this->_client->query('POST', self::TABLE_NAME, $payload, 'select=' . self::SELECT_COLUMNS);

        if (($res['status'] ?? 0) !== 201 || !is_array($res['data']) || empty($res['data'][0])) {
            error_log('[WorkshopRegistrationRepository] create failed: ' . json_encode($res));
            return null;
        }

        return WorkshopRegistration::fromArray($res['data'][0]);
    }

    /**
     * @return array<string, mixed>
     */
    private function withDetailsFromArray(array $row): array
    {
        $registration = WorkshopRegistration::fromArray($row)->toArray();
        $workshopJoin = is_array($row['workshops'] ?? null) ? $row['workshops'] : [];
        $userJoin = is_array($row['users'] ?? null) ? $row['users'] : [];

        return $registration + [
            'identifier' => $registration['idUser'],
            'workshop' => isset($workshopJoin['title']) ? (string) $workshopJoin['title'] : null,
            'firstname' => isset($userJoin['firstname']) ? (string) $userJoin['firstname'] : null,
            'lastname' => isset($userJoin['lastname']) ? (string) $userJoin['lastname'] : null,
        ];
    }
}
