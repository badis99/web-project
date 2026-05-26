<?php
// Les constantes SMTP sont chargées depuis $_ENV via database.php
// On les définit ici si pas encore définies
defined('SMTP_HOST')      || define('SMTP_HOST',      $_ENV['SMTP_HOST']      ?? '');
defined('SMTP_PORT')      || define('SMTP_PORT', (int)($_ENV['SMTP_PORT']     ?? 587));
defined('SMTP_USER')      || define('SMTP_USER',      $_ENV['SMTP_USER']      ?? '');
defined('SMTP_PASS')      || define('SMTP_PASS',      $_ENV['SMTP_PASS']      ?? '');
defined('SMTP_FROM')      || define('SMTP_FROM',      $_ENV['SMTP_FROM']      ?? '');
defined('SMTP_FROM_NAME') || define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? '');
function send_email(string $to, string $subject, string $htmlBody): bool
{
    $host     = SMTP_HOST;
    $port     = SMTP_PORT;
    $username = SMTP_USER;
    $password = SMTP_PASS;
    $from     = SMTP_FROM;
    $fromName = SMTP_FROM_NAME;

    if (empty($host) || empty($username) || empty($password)) {
        error_log('[Mailer] SMTP credentials not configured in env.php');
        return false;
    }

    $context = stream_context_create([
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]
    ]);

    $errno = 0; $errstr = '';
    $socket = @stream_socket_client(
        "tcp://{$host}:{$port}",
        $errno, $errstr, 15,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        error_log("[Mailer] Connection failed: {$errstr}");
        return false;
    }

    stream_set_timeout($socket, 15);

    $cmd = function(string $command) use ($socket): string {
        if ($command !== '') fwrite($socket, $command . "\r\n");
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    };

    $cmd('');
    $cmd('EHLO localhost');

    $r = $cmd('STARTTLS');
    if (strpos($r, '220') === false) {
        fclose($socket); return false;
    }

    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        error_log('[Mailer] TLS handshake failed.');
        fclose($socket); return false;
    }

    $cmd('EHLO localhost');

    $cmd('AUTH LOGIN');
    $cmd(base64_encode($username));
    $r = $cmd(base64_encode($password));
    if (strpos($r, '235') === false) {
        error_log('[Mailer] SMTP authentication failed.');
        fclose($socket); return false;
    }

    $cmd("MAIL FROM:<{$from}>");
    $cmd("RCPT TO:<{$to}>");
    $cmd('DATA');

    $date    = date('r');
    $msgId   = '<' . bin2hex(random_bytes(8)) . '@theatro.insat>';
    $message =
        "Date: {$date}\r\n" .
        "From: {$fromName} <{$from}>\r\n" .
        "To: {$to}\r\n" .
        "Message-ID: {$msgId}\r\n" .
        "Subject: {$subject}\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/html; charset=UTF-8\r\n" .
        "Content-Transfer-Encoding: base64\r\n" .
        "\r\n" .
        chunk_split(base64_encode($htmlBody)) .
        "\r\n.\r\n";

    fwrite($socket, $message);
    $r = $cmd('');

    $cmd('QUIT');
    fclose($socket);

    return strpos($r, '250') !== false;
}

function _email_layout(string $accentColor, string $body): string
{
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:60px 20px;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#111;border:1px solid rgba(255,255,255,0.1);padding:50px 40px;color:white;">
        <tr><td style="padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.08);">
          <p style="margin:0;font-size:42px;font-weight:700;letter-spacing:2px;color:white;">Theatro</p>
          <p style="margin:4px 0 0;font-size:10px;letter-spacing:4px;color:rgba(255,255,255,0.4);text-transform:uppercase;">
            INSAT — Registration
          </p>
        </td></tr>
        {$body}
        <tr><td style="padding-top:28px;border-top:1px solid rgba(255,255,255,0.08);">
          <p style="font-size:11px;color:rgba(255,255,255,0.2);margin:0;">
            © 2026 Theatro INSAT. All Rights Reserved.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function accept_email_html(string $firstname = '', string $lastname = ''): string
{
    $name     = trim("{$firstname} {$lastname}") ?: 'there';
    $greeting = "Hi {$name},";

    $body = <<<HTML
        <tr><td style="padding:30px 0 20px;">
          <p style="font-size:15px;line-height:1.7;color:rgba(255,255,255,0.7);margin:0 0 10px;">{$greeting}</p>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0;">
            We're thrilled to inform you that your registration request for
            <strong style="color:white;">Theatro INSAT</strong> has been
            <strong style="color:white;">accepted</strong>.
          </p>
        </td></tr>
        <tr><td align="center" style="padding:10px 0 30px;">
          <div style="display:inline-block;padding:22px 48px;
                      background:rgba(255,255,255,0.05);
                      border:1px solid rgba(255,255,255,0.25);
                      font-size:28px;font-weight:700;color:white;letter-spacing:4px;">
            ✓ Welcome aboard
          </div>
        </td></tr>
        <tr><td>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0 0 16px;">
            You can now log in to your account using the credentials you provided during registration.
          </p>
          <p style="font-size:12px;color:rgba(255,255,255,0.3);line-height:1.6;margin:0;">
            If you did not submit this request, please contact us immediately.
          </p>
        </td></tr>
HTML;

    return _email_layout('#4ade80', $body);
}

function decline_email_html(string $firstname = '', string $lastname = ''): string
{
    $name     = trim("{$firstname} {$lastname}") ?: 'there';
    $greeting = "Hi {$name},";

    $body = <<<HTML
        <tr><td style="padding:30px 0 20px;">
          <p style="font-size:15px;line-height:1.7;color:rgba(255,255,255,0.7);margin:0 0 10px;">{$greeting}</p>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0;">
            After careful review, we regret to inform you that your registration request for
            <strong style="color:white;">Theatro INSAT</strong> has not been approved at this time.
          </p>
        </td></tr>
        <tr><td align="center" style="padding:10px 0 30px;">
          <div style="display:inline-block;padding:22px 48px;
                      background:rgba(255,255,255,0.05);
                      border:1px solid rgba(255,255,255,0.15);
                      font-size:28px;font-weight:700;color:rgba(255,255,255,0.5);letter-spacing:4px;">
            ✗ Not approved
          </div>
        </td></tr>
        <tr><td>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0 0 16px;">
            If you believe this is a mistake or would like more information,
            feel free to reach out to the Theatro INSAT team.
          </p>
          <p style="font-size:12px;color:rgba(255,255,255,0.3);line-height:1.6;margin:0;">
            Thank you for your interest in joining Theatro INSAT.
          </p>
        </td></tr>
HTML;

    return _email_layout('#f87171', $body);
}

function reset_email_html(string $code, string $firstname = ''): string
{
    $greeting  = $firstname ? "Hi {$firstname}," : "Hello,";
    $formatted = substr($code, 0, 3) . ' ' . substr($code, 3, 3);

    $body = <<<HTML
        <tr><td style="padding:30px 0 20px;">
          <p style="font-size:15px;line-height:1.7;color:rgba(255,255,255,0.7);margin:0 0 10px;">{$greeting}</p>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0;">
            Use the code below to reset your Theatro INSAT password.<br>
            This code expires in <strong style="color:white;">15 minutes</strong>.
          </p>
        </td></tr>
        <tr><td align="center" style="padding:10px 0 30px;">
          <div style="display:inline-block;padding:22px 48px;background:rgba(255,255,255,0.05);
                      border:1px solid rgba(255,255,255,0.25);letter-spacing:12px;
                      font-size:36px;font-weight:700;color:white;font-family:monospace;">
            {$formatted}
          </div>
        </td></tr>
        <tr><td>
          <p style="font-size:12px;color:rgba(255,255,255,0.3);line-height:1.6;margin:0;">
            If you didn't request this, you can safely ignore this email.<br>
            Never share this code with anyone.
          </p>
        </td></tr>
HTML;

    return _email_layout('#c0c0c0', $body);
}