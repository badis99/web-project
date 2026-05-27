<?php
declare(strict_types=1);

final class Ticket
{
    public string $id;
    public string $showId;
    public int $seatId;
    public string $status; // reserved|paid

    public function __construct(string $id, string $showId, int $seatId, string $status)
    {
        $this->id = $id;
        $this->showId = $showId;
        $this->seatId = $seatId;
        $this->status = $status;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (string)($row['id'] ?? ''),
            (string)($row['show_id'] ?? ''),
            (int)($row['seat_id'] ?? 0),
            (string)($row['status'] ?? '')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'show_id' => $this->showId,
            'seat_id' => $this->seatId,
            'status' => $this->status,
        ];
    }
}

