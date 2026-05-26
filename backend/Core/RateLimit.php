<?php
/**
 * Limitation de debit basee sur l'adresse IP.
 */

declare(strict_types=1);

const RATE_MAX_ATTEMPTS = 10;
const RATE_WINDOW_SEC = 15 * 60;

/**
 * Retourne l'adresse IP reelle du client.
 *
 * @return string
 */
function getClientIp(): string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0';
}

/**
 * Verifie si l'IP courante est limitee.
 *
 * @return void
 */
function checkRateLimit(): void
{
    $ip = getClientIp();
    $res = SupabaseClient::getInstance()->query(
        'GET',
        'ip_rate_limits',
        [],
        'ip=eq.' . urlencode($ip)
    );

    if ($res['status'] !== 200 || empty($res['data']) || !is_array($res['data'])) {
        return;
    }

    $row = $res['data'][0];
    $lastAttempt = strtotime($row['last_attempt']);
    $elapsed = time() - $lastAttempt;

    if ($elapsed > RATE_WINDOW_SEC) {
        SupabaseClient::getInstance()->query(
            'PATCH',
            'ip_rate_limits',
            ['attempts' => 0, 'last_attempt' => date('c')],
            'ip=eq.' . urlencode($ip)
        );
        return;
    }

    if ((int)$row['attempts'] >= RATE_MAX_ATTEMPTS) {
        $waitMin = (int)ceil((RATE_WINDOW_SEC - $elapsed) / 60);
        http_response_code(429);
        echo json_encode([
            'error' => "Too many attempts. Please wait {$waitMin} minute(s) before trying again.",
        ]);
        exit;
    }
}

/**
 * Incremente le compteur de tentatives pour l'IP courante.
 *
 * @return void
 */
function incrementRateLimit(): void
{
    $ip = getClientIp();
    $client = SupabaseClient::getInstance();
    $res = $client->query('GET', 'ip_rate_limits', [], 'ip=eq.' . urlencode($ip));

    if ($res['status'] === 200 && !empty($res['data']) && is_array($res['data'])) {
        $current = (int)$res['data'][0]['attempts'];
        $client->query(
            'PATCH',
            'ip_rate_limits',
            ['attempts' => $current + 1, 'last_attempt' => date('c')],
            'ip=eq.' . urlencode($ip)
        );
        return;
    }

    $client->query('POST', 'ip_rate_limits', [
        'ip'           => $ip,
        'attempts'     => 1,
        'last_attempt' => date('c'),
    ]);
}

/**
 * Reinitialise le compteur de tentatives pour l'IP courante.
 *
 * @return void
 */
function resetRateLimit(): void
{
    $ip = getClientIp();
    SupabaseClient::getInstance()->query(
        'PATCH',
        'ip_rate_limits',
        ['attempts' => 0, 'last_attempt' => date('c')],
        'ip=eq.' . urlencode($ip)
    );
}
