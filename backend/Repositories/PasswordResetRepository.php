<?php
/**
 * Acces aux donnees de la table password_resets.
 */

declare(strict_types=1);

/**
 * Repository des codes de reinitialisation de mot de passe.
 */
class PasswordResetRepository
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
     * Enregistre un nouveau token de reinitialisation.
     *
     * @param array $data Colonnes a inserer
     * @return bool
     */
    public function create(array $data): bool
    {
        $res = $this->_client->query('POST', 'password_resets', $data);
        return $res['status'] === 201;
    }

    /**
     * Recherche un token valide.
     *
     * @param string $tokenHash Hash SHA-256 du code
     * @param string $userId    Identifiant utilisateur
     * @return array|null
     */
    public function findValid(string $tokenHash, string $userId): ?array
    {
        $now = date('c');
        $res = $this->_client->query(
            'GET',
            'password_resets',
            [],
            'token_hash=eq.' . urlencode($tokenHash) .
            '&user_id=eq.' . urlencode($userId) .
            '&used=eq.false' .
            '&expires_at=gt.' . urlencode($now) .
            '&select=id&limit=1'
        );

        if ($res['status'] !== 200 || !is_array($res['data'])) {
            error_log('[PasswordResetRepository] findValid failed: ' . json_encode($res));
            return null;
        }

        return $res['data'][0] ?? null;
    }

    /**
     * Marque un token comme utilise.
     *
     * @param string $resetId Identifiant du token
     * @return bool
     */
    public function markUsed(string $resetId): bool
    {
        $res = $this->_client->query(
            'PATCH',
            'password_resets',
            ['used' => true],
            'id=eq.' . urlencode($resetId)
        );

        return $res['status'] === 200;
    }
}
