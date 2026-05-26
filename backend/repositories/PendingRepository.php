<?php
class PendingRepository
{
    public function findAll(): array
    {
        $res = supabase_query('GET', 'PendingRegistration');
        return $res['data'] ?? [];
    }

    public function findById(string $id): array|null
    {
        $res = supabase_query('GET', 'PendingRegistration', [], "id=eq.{$id}");
        return $res['data'][0] ?? null;
    }

    public function deleteById(string $id): void
    {
        supabase_query('DELETE', 'PendingRegistration', [], "id=eq.{$id}");
    }
}