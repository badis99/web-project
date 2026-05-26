<?php
require_once __DIR__ . '/../repositories/PendingRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/MailService.php';

class ApprovalService
{
    private PendingRepository $pending;
    private UserRepository    $users;

    public function __construct()
    {
        $this->pending = new PendingRepository();
        $this->users   = new UserRepository();
    }

    public function accept(string $id): array
    {
        $row = $this->pending->findById($id);
        if (!$row) return ['success' => false, 'message' => 'Row not found'];

        $this->users->create($row);
        $this->pending->deleteById($id);

        if (!empty($row['email'])) {
            send_email($row['email'], 'Welcome to Theatro INSAT!', accept_email_html($row['firstname'], $row['lastname']));
        }

        return ['success' => true];
    }

    public function decline(string $id): array
    {
        $row = $this->pending->findById($id);
        if (!$row) return ['success' => false, 'message' => 'Row not found'];

        $this->pending->deleteById($id);

        if (!empty($row['email'])) {
            send_email($row['email'], 'Your Theatro INSAT registration request', decline_email_html($row['firstname'], $row['lastname']));
        }

        return ['success' => true];
    }
}