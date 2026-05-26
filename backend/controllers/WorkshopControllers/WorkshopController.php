<?php

declare(strict_types=1);

final class WorkshopController
{
    public function __construct(private readonly WorkshopModel $model)
    {
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function show(Request $request, string $id): array
    {
        $workshop = $this->model->find($id);
        if ($workshop === null) {
            throw new RuntimeException('Workshop not found', 404);
        }
        return $workshop;
    }

    public function store(Request $request): array
    {
        $payload = $request->body;
        $required = ['title', 'date', 'departement', 'description'];
        foreach ($required as $field) {
            if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                throw new RuntimeException("Missing required field: {$field}", 422);
            }
        }

        $created = $this->model->create([
            'title' => trim((string) $payload['title']),
            'date' => (string) $payload['date'],
            'departement' => trim((string) $payload['departement']),
            'description' => trim((string) $payload['description']),
            'videoUrl' => isset($payload['videoUrl']) ? (string) $payload['videoUrl'] : null,
        ]);

        return $created;
    }

    public function update(Request $request, string $id): array
    {
        $updated = $this->model->update($id, $request->body);
        if ($updated === null) {
            throw new RuntimeException('Workshop not found', 404);
        }
        return $updated;
    }

    public function destroy(Request $request, string $id): ?array
    {
        if (!$this->model->delete($id)) {
            throw new RuntimeException('Workshop not found', 404);
        }
        Response::noContent();
        return null;
    }
}
