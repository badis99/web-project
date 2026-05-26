<?php
class ApprovalController
{
    public function __construct(private ApprovalService $service) {}

    public function handle(array $data): array
    {
        $id = $data['id'] ?? null;
        $action = $data['action'] ?? null;

        if (!$id || !$action) {
            return ['success' => false, 'message' => 'Missing data'];
        }

        return match($action) {
            'accept'  => $this->service->accept($id),
            'decline' => $this->service->decline($id),
            default   => ['success' => false, 'message' => 'Invalid action']
        };
    }
}