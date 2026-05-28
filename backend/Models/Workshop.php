<?php
declare(strict_types=1);

final class Workshop
{
    public string $id;
    public string $title;
    public string $date;
    public string $departement;
    public string $description;
    public ?string $videoUrl;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        string $id,
        string $title,
        string $date,
        string $departement,
        string $description,
        ?string $videoUrl = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->date = $date;
        $this->departement = $departement;
        $this->description = $description;
        $this->videoUrl = $videoUrl;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (string) ($row['id'] ?? ''),
            (string) ($row['title'] ?? ''),
            (string) ($row['date'] ?? ''),
            (string) ($row['departement'] ?? ''),
            (string) ($row['description'] ?? ''),
            // PostgREST returns the aliased name; fall back to snake_case just in case.
            isset($row['videoUrl']) ? (string) $row['videoUrl'] : (isset($row['video_url']) ? (string) $row['video_url'] : null),
            isset($row['createdAt']) ? (string) $row['createdAt'] : (isset($row['created_at']) ? (string) $row['created_at'] : null),
            isset($row['updatedAt']) ? (string) $row['updatedAt'] : (isset($row['updated_at']) ? (string) $row['updated_at'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'date' => $this->date,
            'departement' => $this->departement,
            'description' => $this->description,
            'videoUrl' => $this->videoUrl,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
