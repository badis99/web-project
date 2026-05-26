<?php
/**
 * Client Supabase unique pour toute l'application.
 */

declare(strict_types=1);

/**
 * Encapsule les appels REST Supabase avec le pattern Singleton.
 */
class SupabaseClient
{
    /** @var SupabaseClient|null Instance unique */
    private static ?SupabaseClient $_instance = null;

    /** @var string URL du projet Supabase */
    private string $_supabaseUrl;

    /** @var string Cle service role Supabase */
    private string $_supabaseKey;

    /** @var bool Mode developpement local */
    private bool $_localDev;

    /**
     * Constructeur prive pour empecher les instances multiples.
     */
    private function __construct()
    {
        try {
            $config = loadAppConfig();
        } catch (RuntimeException $exception) {
            http_response_code(500);
            echo json_encode(['error' => $exception->getMessage()]);
            exit;
        }

        $this->_supabaseUrl = $config['supabaseUrl'];
        $this->_supabaseKey = $config['supabaseKey'];
        $this->_localDev    = $config['localDev'];

        if (empty($this->_supabaseUrl) || empty($this->_supabaseKey)) {
            http_response_code(500);
            echo json_encode(['error' => 'Server configuration error: Supabase keys missing.']);
            exit;
        }
    }

    /**
     * Retourne l'instance unique du client Supabase.
     *
     * @return SupabaseClient
     */
    public static function getInstance(): SupabaseClient
    {
        if (self::$_instance === null) {
            self::$_instance = new SupabaseClient();
        }

        return self::$_instance;
    }

    /**
     * Execute une requete REST vers Supabase.
     *
     * @param string $method Methode HTTP
     * @param string $table  Nom exact de la table Supabase
     * @param array  $body   Corps JSON de la requete
     * @param string $query  Chaine de requete PostgREST
     * @return array
     */
    public function query(string $method, string $table, array $body = [], string $query = ''): array
    {
        $url = $this->_supabaseUrl . '/rest/v1/' . $table . ($query ? '?' . $query : '');

        $curlHandle = curl_init($url);
        curl_setopt_array($curlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => [
                'apikey: ' . $this->_supabaseKey,
                'Authorization: Bearer ' . $this->_supabaseKey,
                'Content-Type: application/json',
                'Prefer: return=representation',
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->_localDev,
        ]);

        if (in_array(strtoupper($method), ['POST', 'PATCH', 'PUT'], true) && !empty($body)) {
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $rawResponse = curl_exec($curlHandle);
        $statusCode  = (int)curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlError   = curl_error($curlHandle);

        if ($curlError) {
            curl_close($curlHandle);
            return ['data' => null, 'status' => 0, 'error' => $curlError];
        }

        $decodedResponse = json_decode((string)$rawResponse, true);
        curl_close($curlHandle);

        return ['data' => $decodedResponse, 'status' => $statusCode];
    }

    /**
     * Upload un fichier dans Supabase Storage.
     *
     * @param string $bucketName Nom du bucket
     * @param string $filePath   Chemin du fichier dans le bucket
     * @param string $localPath  Chemin temporaire local
     * @param string $mimeType   Type MIME valide
     * @return array
     */
    public function uploadFile(
        string $bucketName,
        string $filePath,
        string $localPath,
        string $mimeType
    ): array {
        $encodedPath = $this->encodeStoragePath($filePath);
        $url = $this->_supabaseUrl . '/storage/v1/object/' . rawurlencode($bucketName) . '/' . $encodedPath;
        $fileContents = file_get_contents($localPath);

        if ($fileContents === false) {
            return ['data' => null, 'status' => 0, 'error' => 'Unable to read uploaded file.'];
        }

        $curlHandle = curl_init($url);
        curl_setopt_array($curlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fileContents,
            CURLOPT_HTTPHEADER     => [
                'apikey: ' . $this->_supabaseKey,
                'Authorization: Bearer ' . $this->_supabaseKey,
                'Content-Type: ' . $mimeType,
                'cache-control: 3600',
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->_localDev,
        ]);

        $rawResponse = curl_exec($curlHandle);
        $statusCode = (int)curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curlHandle);

        if ($curlError) {
            curl_close($curlHandle);
            return ['data' => null, 'status' => 0, 'error' => $curlError];
        }

        $decodedResponse = json_decode((string)$rawResponse, true);
        curl_close($curlHandle);

        return [
            'data'      => $decodedResponse,
            'status'    => $statusCode,
            'publicUrl' => $this->getPublicFileUrl($bucketName, $filePath),
        ];
    }

    /**
     * Construit l'URL publique d'un fichier Supabase Storage.
     *
     * @param string $bucketName Nom du bucket
     * @param string $filePath   Chemin du fichier
     * @return string
     */
    public function getPublicFileUrl(string $bucketName, string $filePath): string
    {
        return $this->_supabaseUrl .
            '/storage/v1/object/public/' .
            rawurlencode($bucketName) .
            '/' .
            $this->encodeStoragePath($filePath);
    }

    /**
     * Encode chaque segment du chemin Storage sans supprimer les slashs.
     *
     * @param string $filePath Chemin du fichier
     * @return string
     */
    private function encodeStoragePath(string $filePath): string
    {
        $segments = explode('/', $filePath);
        $encodedSegments = array_map('rawurlencode', $segments);

        return implode('/', $encodedSegments);
    }

    /**
     * Interdit le clonage du Singleton.
     */
    private function __clone()
    {
    }
}
