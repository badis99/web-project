<?php

declare(strict_types=1);

final class WorkshopRegistrationController
{
    private WorkshopRegistrationRepository $_registrationRepository;

    public function __construct(WorkshopRegistrationRepository $registrationRepository)
    {
        $this->_registrationRepository = $registrationRepository;
    }

    public function index(): void
    {
        $this->requireMethod('GET');
        $registrations = $this->_registrationRepository->findAllWithDetails();
        echo json_encode($registrations);
    }

    public function store(string $idUser): void
    {
        $this->requireMethod('POST');
        $body = $this->readJsonBody();

        $idWorkshop = trim((string) ($body['idWorkshop'] ?? ''));
        $rating = isset($body['rating']) ? (int) $body['rating'] : 0;

        if ($idWorkshop === '') {
            $this->jsonError('Missing required field: idWorkshop.', 422);
        }
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $idWorkshop)) {
            $this->jsonError('Invalid idWorkshop format.', 422);
        }
        if ($rating < 0 || $rating > 5) {
            $this->jsonError('Rating must be between 0 and 5.', 422);
        }

        $created = $this->_registrationRepository->create($idUser, $idWorkshop, $rating);

        if ($created === null) {
            $this->jsonError('Failed to create workshop registration.', 502);
        }

        http_response_code(201);
        echo json_encode($created->toArray());
    }

    private function requireMethod(string $method): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
            $this->jsonError('Method not allowed.', 405);
        }
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $body = json_decode((string) $raw, true);
        if (!is_array($body)) {
            $this->jsonError('Invalid request body.', 400);
        }
        return $body;
    }

    private function jsonError(string $message, int $status): void
    {
        http_response_code($status);
        echo json_encode(['error' => $message]);
        exit;
    }
}
