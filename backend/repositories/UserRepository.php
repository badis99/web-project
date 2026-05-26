<?php
class UserRepository
{
    public function create(array $data): void
    {
        supabase_query('POST', 'users', [
            'firstname'    => $data['firstname']    ?? null,
            'lastname'     => $data['lastname']     ?? null,
            'email'        => $data['email']        ?? null,
            'password'     => $data['password']     ?? null,
            'phone'        => $data['phone']        ?? null,
            'birthdate'    => $data['birthdate']    ?? null,
            'department'   => $data['department']   ?? null,
            'fieldofstudy' => $data['fieldofstudy'] ?? null,
            'yearofstudy'  => $data['yearofstudy']  ?? null,
            'picture'      => $data['picture']      ?? null,
        ]);
    }
}