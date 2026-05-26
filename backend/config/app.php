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

    $envPath = __DIR__ . '/../../.env';

    if (!file_exists($envPath)) {
        throw new RuntimeException('Server configuration error: .env not found.');
    }

    $env = parse_ini_file($envPath);
    if ($env === false) {
        throw new RuntimeException('Server configuration error: .env could not be parsed.');
    }

    $config = [
        'supabaseUrl'    => rtrim($env['SUPABASE_URL'] ?? '', '/'),
        'supabaseKey'    => $env['SUPABASE_SERVICE_KEY'] ?? '',
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
