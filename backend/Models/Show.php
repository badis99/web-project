<?php
declare(strict_types=1);

final class Show
{
    public string $id;
    public string $name;
    public ?string $description;
    public ?string $startsAt;

    public function __construct(string $id, string $name, ?string $description = null, ?string $startsAt = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->startsAt = $startsAt;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (string)($row['id'] ?? ''),
            (string)($row['name'] ?? ''),
            isset($row['description']) ? (string)$row['description'] : null,
            // Column comes back as `startsat` from PostgREST.
            isset($row['startsat']) ? (string)$row['startsat'] : (isset($row['startsAt']) ? (string)$row['startsAt'] : null)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'startsAt' => $this->startsAt,
        ];
    }
}

