<?php
/**
 * Acces aux donnees de la table PendingRegistration.
 */

declare(strict_types=1);

/**
 * Repository des candidatures en attente.
 */
class PendingRegistrationRepository
{
    private const TABLE_NAME = 'PendingRegistration';

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
     * Verifie si une candidature existe deja pour cette adresse email.
     *
     * @param string $email Adresse email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        $res = $this->_client->query(
            'GET',
            self::TABLE_NAME,
            [],
            'email=eq.' . urlencode($email) . '&select=id'
        );

        if ($res['status'] !== 200 || !is_array($res['data'])) {
            error_log('[PendingRegistrationRepository] Email lookup failed: ' . json_encode($res));
            return false;
        }

        return !empty($res['data']);
    }

    /**
     * Insere une nouvelle candidature.
     *
     * @param array $data Colonnes a inserer
     * @return bool
     */
    public function create(array $data): bool
    {
        $res = $this->_client->query('POST', self::TABLE_NAME, $data);

        if ($res['status'] !== 201) {
            error_log('[PendingRegistrationRepository] Create failed: ' . json_encode($res));
        }

        return $res['status'] === 201;
    }
}
