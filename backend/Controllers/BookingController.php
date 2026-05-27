<?php
declare(strict_types=1);

final class BookingController
{
    private BookingService $_bookingService;
    private ShowsRepository $_showsRepository;

    public function __construct(BookingService $bookingService, ShowsRepository $showsRepository)
    {
        $this->_bookingService = $bookingService;
        $this->_showsRepository = $showsRepository;
    }

    public function getShows(): void
    {
        $this->requireMethod('GET');
        $shows = $this->_showsRepository->findAll();
        echo json_encode(array_map(fn(Show $s) => $s->toArray(), $shows));
    }

    public function getShowByName(string $name): void
    {
        $this->requireMethod('GET');
        $name = $this->safeShowName($name);
        $show = $this->_showsRepository->findByName($name);
        if ($show === null) {
            $this->jsonError('Show not found.', 404);
        }
        echo json_encode($show->toArray());
    }

    public function ensureShow(): void
    {
        $this->requireMethod('POST');
        $body = $this->readJsonBody();
        $name = $this->safeShowName((string)($body['name'] ?? ''));
        $description = isset($body['description']) ? (string)$body['description'] : null;
        $startsAt = isset($body['startsAt']) ? (string)$body['startsAt'] : null;

        $show = $this->_bookingService->ensureShow($name, $description, $startsAt);
        if ($show === null || $show->id === '') {
            $this->jsonError('Failed to create show.', 502);
        }
        echo json_encode(['id' => $show->id]);
    }

    public function getShowSeats(string $showId): void
    {
        $this->requireMethod('GET');
        $showId = $this->safeUuid($showId);
        echo json_encode($this->_bookingService->listSeatsWithOccupancy($showId));
    }

    public function createTicket(): void
    {
        $this->requireMethod('POST');
        $body = $this->readJsonBody();

        $showId = $this->safeUuid((string)($body['show_id'] ?? ''));
        $section = $this->safeSection((string)($body['section'] ?? ''));
        $row = $this->safeRow((string)($body['row'] ?? ''));
        $number = $this->safeSeatNumber($body['number'] ?? null);
        $status = $this->safeTicketStatus((string)($body['status'] ?? 'reserved'));

        $res = $this->_bookingService->createTicket($showId, $section, $row, $number, $status);
        if ($res['notFound']) {
            $this->jsonError('Seat not found.', 404);
        }
        if ($res['conflict']) {
            $this->jsonError('Seat already booked.', 409);
        }
        if (!$res['ok']) {
            $this->jsonError('Failed to create ticket.', 502);
        }

        echo json_encode(['success' => true]);
    }

    public function releaseTicket(): void
    {
        $this->requireMethod('POST');
        $body = $this->readJsonBody();

        $showId = $this->safeUuid((string)($body['show_id'] ?? ''));
        $section = $this->safeSection((string)($body['section'] ?? ''));
        $row = $this->safeRow((string)($body['row'] ?? ''));
        $number = $this->safeSeatNumber($body['number'] ?? null);

        $res = $this->_bookingService->releaseTicket($showId, $section, $row, $number);
        if ($res['notFound']) {
            $this->jsonError('Seat not found.', 404);
        }
        if (!$res['ok']) {
            $this->jsonError('Failed to release ticket.', 502);
        }

        echo json_encode(['success' => true]);
    }

    // ---------------------
    // helpers
    // ---------------------

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

    private function jsonError(string $message, int $status): void
    {
        http_response_code($status);
        echo json_encode(['error' => $message]);
        exit;
    }

    private function safeUuid(string $value): string
    {
        $value = trim($value);
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $value)) {
            $this->jsonError('Invalid id format.', 400);
        }
        return $value;
    }

    private function safeShowName(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = mb_substr((string)$value, 0, 120);
        if ($value === '') {
            $this->jsonError('Show name is required.', 400);
        }
        return $value;
    }

    private function safeSection(string $value): string
    {
        $value = strtolower(trim($value));
        $allowed = ['left', 'center', 'right'];
        if (!in_array($value, $allowed, true)) {
            $this->jsonError('Invalid section.', 422);
        }
        return $value;
    }

    private function safeRow(string $value): string
    {
        $value = strtoupper(trim($value));
        if (!preg_match('/^[A-O]$/', $value)) {
            $this->jsonError('Invalid row.', 422);
        }
        return $value;
    }

    private function safeSeatNumber(mixed $value): int
    {
        $n = (int)$value;
        if ($n < 1 || $n > 99) {
            $this->jsonError('Invalid seat number.', 422);
        }
        return $n;
    }

    private function safeTicketStatus(string $value): string
    {
        $value = strtolower(trim($value));
        $allowed = ['reserved', 'paid'];
        if (!in_array($value, $allowed, true)) {
            $this->jsonError('Invalid status.', 422);
        }
        return $value;
    }
}

