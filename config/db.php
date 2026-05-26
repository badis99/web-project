<?php
/**
 * db.php — Supabase connection helper
 *
 * Loads credentials from .env and exposes a
 * supabase_query() function for all REST API calls.
 *
 * WHY SERVICE ROLE KEY?
 *   The service role key bypasses Row Level Security (RLS).
 *   It lives only here, server-side, never exposed to the browser.
 */

declare(strict_types=1);

$envPath = 'C:/Users/USER/Documents/web1/web-project/.env';

if (!file_exists($envPath)) {
    $envPath = ''; // Skip loading
}

// Use a line-by-line parser instead of parse_ini_file().
// parse_ini_file() breaks on JWT values that contain '=' (base64 padding).
$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;   // skip comments
    $pos = strpos($line, '=');
    if ($pos === false) continue;                      // skip lines with no '='
    $key   = trim(substr($line, 0, $pos));
    $value = trim(substr($line, $pos + 1));
    // Strip surrounding quotes if present (single or double)
    if (strlen($value) >= 2 &&
        (($value[0] === '"' && $value[-1] === '"') ||
         ($value[0] === "'"  && $value[-1] === "'"))) {
        $value = substr($value, 1, -1);
    }
    $env[$key] = $value;
}
if (empty($env) && $envPath !== '') {
    // skip error for now so we can boot
}

// ------------------------------------------------
// Constants (set once, used everywhere)
// ------------------------------------------------
define('SUPABASE_URL', rtrim($env['SUPABASE_URL'] ?? '', '/'));
// Accept either key name: service-role key (preferred) or anon key as fallback
define('SUPABASE_KEY', $env['SUPABASE_SERVICE_ROLE_KEY'] ?? $env['SUPABASE_SERVICE_KEY'] ?? $env['SUPABASE_ANON_KEY'] ?? '');
define('LOCAL_DEV', filter_var($env['LOCAL_DEV'] ?? 'true', FILTER_VALIDATE_BOOLEAN));

// SMTP constants (used in mailer.php)
define('SMTP_HOST', $env['SMTP_HOST'] ?? '');
define('SMTP_PORT', (int) ($env['SMTP_PORT'] ?? 587));
define('SMTP_USER', $env['SMTP_USER'] ?? '');
define('SMTP_PASS', $env['SMTP_PASS'] ?? '');
define('SMTP_FROM', $env['SMTP_FROM'] ?? '');
define('SMTP_FROM_NAME', $env['SMTP_FROM_NAME'] ?? 'Theatro INSAT');

if (empty(SUPABASE_URL) || empty(SUPABASE_KEY)) {
    // allow running locally without DB for health check
}

// ------------------------------------------------
// supabase_query() — generic REST API helper
//
//   $method : GET | POST | PATCH | DELETE
//   $table  : table name in public schema
//   $body   : associative array (for POST/PATCH)
//   $query  : raw query string — e.g. "email=eq.x@y.z&select=id"
//
// Returns: ['data' => array|null, 'status' => int]
// ------------------------------------------------
function supabase_query(string $method, string $table, array $body = [], string $query = ''): array
{
    $url = SUPABASE_URL . '/rest/v1/' . $table . ($query ? '?' . $query : '');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ],
        CURLOPT_SSL_VERIFYPEER => !LOCAL_DEV,  // false on localhost (Windows has no CA bundle), true in production
    ]);

    if (in_array(strtoupper($method), ['POST', 'PATCH', 'PUT'], true) && !empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $raw = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);

    if ($curlErr) {
        return ['data' => null, 'status' => 0, 'error' => $curlErr];
    }

    $decoded = json_decode($raw, true);
    return ['data' => $decoded, 'status' => $httpCode];
}