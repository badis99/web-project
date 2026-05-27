<?php
declare(strict_types=1);

final class BookingService
{
    private ShowsRepository $_shows;
    private SeatsRepository $_seats;
    private TicketsRepository $_tickets;

    public function __construct(
        ShowsRepository $showsRepository,
        SeatsRepository $seatsRepository,
        TicketsRepository $ticketsRepository
    ) {
        $this->_shows = $showsRepository;
        $this->_seats = $seatsRepository;
        $this->_tickets = $ticketsRepository;
    }

    /**
     * @return array<int, array{id:int,section:string,row:string,number:int,is_occupied:bool}>
     */
    public function listSeatsWithOccupancy(string $showId): array
    {
        $seats = $this->_seats->findAll();
        $tickets = $this->_tickets->findByShowId($showId);

        $occupied = [];
        foreach ($tickets as $t) {
            $status = strtolower($t->status);
            if ($status === 'reserved' || $status === 'paid') {
                $occupied[(string)$t->seatId] = true;
            }
        }

        $out = [];
        foreach ($seats as $s) {
            $out[] = [
                'id' => $s->id,
                'section' => $s->section,
                'row' => $s->row,
                'number' => $s->number,
                'is_occupied' => isset($occupied[(string)$s->id]),
            ];
        }
        return $out;
    }

    public function ensureShow(string $name, ?string $description, ?string $startsAt): ?Show
    {
        $existing = $this->_shows->findByName($name);
        if ($existing !== null) {
            return $existing;
        }
        return $this->_shows->create($name, $description, $startsAt);
    }

    /**
     * @return array{ok:bool,conflict:bool,notFound:bool}
     */
    public function createTicket(string $showId, string $section, string $row, int $number, string $status): array
    {
        $seatId = $this->_seats->findIdByCoordinates($section, $row, $number);
        if ($seatId === null) {
            return ['ok' => false, 'conflict' => false, 'notFound' => true];
        }

        if ($this->_tickets->existsForShowAndSeat($showId, $seatId)) {
            return ['ok' => false, 'conflict' => true, 'notFound' => false];
        }

        $ok = $this->_tickets->create($showId, $seatId, $status);
        return ['ok' => $ok, 'conflict' => false, 'notFound' => false];
    }

    /**
     * Releases (deletes) a ticket to free the seat.
     *
     * @return array{ok:bool,notFound:bool}
     */
    public function releaseTicket(string $showId, string $section, string $row, int $number): array
    {
        $seatId = $this->_seats->findIdByCoordinates($section, $row, $number);
        if ($seatId === null) {
            return ['ok' => false, 'notFound' => true];
        }

        $ok = $this->_tickets->deleteByShowAndSeat($showId, $seatId);
        return ['ok' => $ok, 'notFound' => false];
    }
}

