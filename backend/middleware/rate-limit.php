<?php
/**
 * rate-limit.php — IP-based rate limiting
 *
 * Uses the Supabase `ip_rate_limits` table to track
 * failed attempts per IP address.
 *
 * IMPORTANT: Run the following SQL in Supabase before using:
 *
 *   CREATE TABLE IF NOT EXISTS ip_rate_limits (
 *       ip           TEXT PRIMARY KEY,
 *       attempts     INTEGER DEFAULT 1,
 *       last_attempt TIMESTAMPTZ DEFAULT NOW()
 *   );
 *
 * HOW IT WORKS:
 *   check_rate_limit()     → call at the top of login/signup endpoints
 *   increment_rate_limit() → call only on FAILED attempts
 *   reset_rate_limit()     → call on successful login
 */

declare(strict_types=1);

const RATE_MAX_ATTEMPTS = 10;         // Max failed attempts
const RATE_WINDOW_SEC   = 15 * 60;   // 15-minute window

/**
 * Returns the real client IP, handling common proxy headers.
 */
function get_client_ip(): string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            // X-Forwarded-For can be a comma-separated list; take the first
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * check_rate_limit()
 * Call at the start of sensitive endpoints.
 * Sends 429 and exits if the IP is blocked.
 */
function check_rate_limit(): void
{
    $ip  = get_client_ip();
    $res = supabase_query('GET', 'ip_rate_limits', [], 'ip=eq.' . urlencode($ip));

    if (empty($res['data'])) {
        return; // No record → not limited
    }

    $row         = $res['data'][0];
    $lastAttempt = strtotime($row['last_attempt']);
    $elapsed     = time() - $lastAttempt;

    // Window has expired → forgive
    if ($elapsed > RATE_WINDOW_SEC) {
        supabase_query('PATCH', 'ip_rate_limits',
            ['attempts' => 0, 'last_attempt' => date('c')],
            'ip=eq.' . urlencode($ip)
        );
        return;
    }

    // Within window and over limit → block
    if ((int)$row['attempts'] >= RATE_MAX_ATTEMPTS) {
        $waitMin = (int)ceil((RATE_WINDOW_SEC - $elapsed) / 60);
        http_response_code(429);
        echo json_encode([
            'error' => "Too many attempts. Please wait {$waitMin} minute(s) before trying again."
        ]);
        exit;
    }
}

/**
 * increment_rate_limit()
 * Call on every failed login attempt.
 */
function increment_rate_limit(): void
{
    $ip  = get_client_ip();
    $res = supabase_query('GET', 'ip_rate_limits', [], 'ip=eq.' . urlencode($ip));

    if (!empty($res['data'])) {
        $current = (int)$res['data'][0]['attempts'];
        supabase_query('PATCH', 'ip_rate_limits',
            ['attempts' => $current + 1, 'last_attempt' => date('c')],
            'ip=eq.' . urlencode($ip)
        );
    } else {
        supabase_query('POST', 'ip_rate_limits', [
            'ip'           => $ip,
            'attempts'     => 1,
            'last_attempt' => date('c'),
        ]);
    }
}

/**
 * reset_rate_limit()
 * Call on successful login to clear the counter.
 */
function reset_rate_limit(): void
{
    $ip = get_client_ip();
    supabase_query('PATCH', 'ip_rate_limits',
        ['attempts' => 0, 'last_attempt' => date('c')],
        'ip=eq.' . urlencode($ip)
    );
}
