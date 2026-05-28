<?php

declare(strict_types=1);

final class WorkshopRegistration
{
    public string $id;
    public string $id_user;
    public string $id_workshop;
    public int $rating;
    public ?string $created_at;

    public function __construct(
        string $id,
        string $id_user,
        string $id_workshop,
        int $rating,
        ?string $created_at = null,
    ) {
        $this->id = $id;
        $this->id_user = $id_user;
        $this->id_workshop = $id_workshop;
        $this->rating = $rating;
        $this->created_at = $created_at;
    }

    public static function fromArray(array $row): self
{
    return new self(
        (string) ($row['id'] ?? ''),
        (string) ($row['id_user'] ?? ''),
        (string) ($row['id_workshop'] ?? ''),
        (int) ($row['rating'] ?? 0),
        isset($row['created_at']) ? (string) $row['created_at'] : null,
    );
}

public function toArray(): array
{
    return [
        'id' => $this->id,
        'idUser' => $this->id_user,
        'idWorkshop' => $this->id_workshop,
        'rating' => $this->rating,
        'createdAt' => $this->created_at,
    ];
}
}
