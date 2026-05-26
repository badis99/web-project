<?php
/**
 * Acces aux donnees de la table users.
 */

declare(strict_types=1);

/**
 * Repository des utilisateurs.
 */
class UserRepository
{
    /** @var SupabaseClient Client Supabase partage */
    private SupabaseClient $_client;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->_client = SupabaseClient::getInstance();
    }

    /**
     * Recherche un utilisateur par email pour la connexion.
     *
     * @param string $email Adresse email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $res = $this->_client->query(
            'GET',
            'users',
            [],
            'email=eq.' . urlencode($email) . '&select=id,firstname,lastname,password'
        );

        if ($res['status'] !== 200 || !is_array($res['data'])) {
            error_log('[UserRepository] findByEmail failed: ' . json_encode($res));
            return null;
        }

        return $res['data'][0] ?? null;
    }

    /**
     * Recherche seulement l'id et le prenom d'un utilisateur.
     *
     * @param string $email Adresse email
     * @return array|null
     */
    public function findIdByEmail(string $email): ?array
    {
        $res = $this->_client->query(
            'GET',
            'users',
            [],
            'email=eq.' . urlencode($email) . '&select=id,firstname'
        );

        if ($res['status'] !== 200 || !is_array($res['data'])) {
            error_log('[UserRepository] findIdByEmail failed: ' . json_encode($res));
            return null;
        }

        return $res['data'][0] ?? null;
    }

    /**
     * Verifie si un compte existe deja pour cette adresse email.
     *
     * @param string $email Adresse email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        $res = $this->_client->query(
            'GET',
            'users',
            [],
            'email=eq.' . urlencode($email) . '&select=id'
        );

        if ($res['status'] !== 200 || !is_array($res['data'])) {
            error_log('[UserRepository] Email lookup failed: ' . json_encode($res));
            return false;
        }

        return !empty($res['data']);
    }

    /**
     * Insere un nouvel utilisateur.
     *
     * @param array $data Colonnes a inserer
     * @return bool
     */
    public function create(array $data): bool
    {
        $res = $this->_client->query('POST', 'users', $data);
        return $res['status'] === 201;
    }

    /**
     * Met a jour le mot de passe d'un utilisateur.
     *
     * @param string $userId       Identifiant utilisateur
     * @param string $passwordHash Hash bcrypt
     * @return bool
     */
    public function updatePassword(string $userId, string $passwordHash): bool
    {
        $res = $this->_client->query(
            'PATCH',
            'users',
            ['password' => $passwordHash, 'updatedat' => date('c')],
            'id=eq.' . urlencode($userId)
        );

        return $res['status'] === 200;
    }
}
