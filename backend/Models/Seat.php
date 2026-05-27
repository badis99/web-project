<?php
declare(strict_types=1);

final class Seat
{
    public int $id;
    public string $section; // left|center|right
    public string $row;     // A-O
    public int $number;

    public function __construct(int $id, string $section, string $row, int $number)
    {
        $this->id = $id;
        $this->section = $section;
        $this->row = $row;
        $this->number = $number;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int)($row['id'] ?? 0),
            (string)($row['section'] ?? ''),
            (string)($row['row'] ?? ''),
            (int)($row['number'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'section' => $this->section,
            'row' => $this->row,
            'number' => $this->number,
        ];
    }
}

