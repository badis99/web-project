<?php

declare(strict_types=1);

final class WorkshopRegistrationController
{
    public function __construct(private readonly WorkshopRegistrationModel $model)
    {
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function store(Request $request): array
    {
        $payload = $request->body;
        $required = ['workshop', 'name', 'surname', 'identifier', 'rating'];
        foreach ($required as $field) {
            if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                throw new RuntimeException("Missing required field: {$field}", 422);
            }
        }

        $created = $this->model->create([
            'workshop' => trim((string) $payload['workshop']),
            'name' => trim((string) $payload['name']),
            'surname' => trim((string) $payload['surname']),
            'identifier' => trim((string) $payload['identifier']),
            'rating' => (int) $payload['rating'],
        ]);

        Response::json($created, 201);
        return [];
    }
}
