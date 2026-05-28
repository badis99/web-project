<?php
/**
 * Configuration centrale de l'application.
 */

declare(strict_types=1);

/**
 * Charge le fichier .env une seule fois et expose les constantes du projet.
 *
 * @return array
 */
function loadAppConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $envPath = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '.env';

    if (!file_exists($envPath)) {
        throw new RuntimeException('Server configuration error: .env not found.');
    }

    // parse_ini_file() breaks on JWT/base64 values that contain '=' (padding).
    // Use a robust line-by-line parser compatible with common .env formats.
    $env = [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        throw new RuntimeException('Server configuration error: .env could not be read.');
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($key === '') {
            continue;
        }
        // Strip surrounding quotes if present (single or double)
        if (strlen($value) >= 2 && (
            ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
            ($value[0] === "'" && $value[strlen($value) - 1] === "'")
        )) {
            $value = substr($value, 1, -1);
        }
        $env[$key] = $value;
    }

    $config = [
        'supabaseUrl'    => rtrim($env['SUPABASE_URL'] ?? '', '/'),
        // Prefer service role key (bypasses RLS), fallback to older names if needed.
        'supabaseKey'    => $env['SUPABASE_SERVICE_ROLE_KEY'] ?? ($env['SUPABASE_SERVICE_KEY'] ?? ($env['SUPABASE_ANON_KEY'] ?? '')),
        'localDev'       => filter_var($env['LOCAL_DEV'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'smtpHost'       => $env['SMTP_HOST'] ?? '',
        'smtpPort'       => (int)($env['SMTP_PORT'] ?? 587),
        'smtpUser'       => $env['SMTP_USER'] ?? '',
        'smtpPass'       => $env['SMTP_PASS'] ?? '',
        'smtpFrom'       => $env['SMTP_FROM'] ?? '',
        'smtpFromName'   => $env['SMTP_FROM_NAME'] ?? 'Theatro INSAT',
        'contactEmail'   => $env['CONTACT_EMAIL'] ?? '',
        'storageBucket'  => $env['SUPABASE_STORAGE_BUCKET'] ?? 'profile-pictures',
    ];

    defineAppConstant('SUPABASE_URL', $config['supabaseUrl']);
    defineAppConstant('SUPABASE_KEY', $config['supabaseKey']);
    defineAppConstant('LOCAL_DEV', $config['localDev']);
    defineAppConstant('SMTP_HOST', $config['smtpHost']);
    defineAppConstant('SMTP_PORT', $config['smtpPort']);
    defineAppConstant('SMTP_USER', $config['smtpUser']);
    defineAppConstant('SMTP_PASS', $config['smtpPass']);
    defineAppConstant('SMTP_FROM', $config['smtpFrom']);
    defineAppConstant('SMTP_FROM_NAME', $config['smtpFromName']);
    defineAppConstant('CONTACT_EMAIL', $config['contactEmail']);
    defineAppConstant('SUPABASE_STORAGE_BUCKET', $config['storageBucket']);

    return $config;
}

/**
 * Definit une constante seulement si elle n'existe pas deja.
 *
 * @param string $name  Nom de la constante
 * @param mixed  $value Valeur de la constante
 * @return void
 */
function defineAppConstant(string $name, mixed $value): void
{
    if (!defined($name)) {
        define($name, $value);
    }
}
