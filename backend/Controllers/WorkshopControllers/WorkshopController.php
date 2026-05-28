<?php

declare(strict_types=1);

final class WorkshopController
{
    private WorkshopRepository $_workshopRepository;

    public function __construct(WorkshopRepository $workshopRepository)
    {
        $this->_workshopRepository = $workshopRepository;
    }

    public function index(): void
    {
        $this->requireMethod('GET');
        $workshops = $this->_workshopRepository->findAll();
        echo json_encode(array_map(fn(Workshop $w) => $w->toArray(), $workshops));
    }

    public function workshop(string $id): void
    {
        $this->requireMethod('GET');
        $id = $this->safeUuid($id);

        $workshop = $this->_workshopRepository->findById($id);
        if ($workshop === null) {
            $this->jsonError('Workshop not found.', 404);
        }

        echo json_encode($workshop->toArray());
    }

    public function store(): void
    {
        $this->requireMethod('POST');
        $body = $this->readJsonBody();

        $title = trim((string)($body['title'] ?? ''));
        $description = trim((string)($body['description'] ?? ''));
        $departement = trim((string)($body['departement'] ?? ''));
        $date = trim((string)($body['date'] ?? ''));
        $videoUrl = isset($body['videoUrl']) ? trim((string)$body['videoUrl']) : null;

        if ($title === '' || $description === '' || $departement === '' || $date === '') {
            $this->jsonError('Missing required fields: title, description, departement, date.', 422);
        }

        $created = $this->_workshopRepository->create(
            $title,
            $description,
            $departement,
            $date,
            $videoUrl !== '' ? $videoUrl : null
        );

        if ($created === null) {
            $this->jsonError('Failed to create workshop.', 502);
        }

        http_response_code(201);
        echo json_encode($created->toArray());
    }

    public function update(string $id): void
    {
        $this->requireMethod('PUT');
        $id = $this->safeUuid($id);
        $body = $this->readJsonBody();

        $updated = $this->_workshopRepository->update($id, $body);
        if ($updated === null) {
            $this->jsonError('Workshop not found.', 404);
        }

        echo json_encode($updated->toArray());
    }

    public function destroy(string $id): void
    {
        $this->requireMethod('DELETE');
        $id = $this->safeUuid($id);

        if (!$this->_workshopRepository->delete($id)) {
            $this->jsonError('Workshop not found.', 404);
        }

        http_response_code(204);
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
        $body = json_decode((string)$raw, true);
        if (!is_array($body)) {
            $this->jsonError('Invalid request body.', 400);
        }
        return $body;
    }

    private function safeUuid(string $value): string
    {
        $value = trim($value);
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $value)) {
            $this->jsonError('Invalid id format.', 400);
        }
        return $value;
    }

    private function jsonError(string $message, int $status): void
    {
        http_response_code($status);
        echo json_encode(['error' => $message]);
        exit;
    }
}
