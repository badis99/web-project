<?php
/**
 * Service d'envoi d'emails.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Envoie les emails via PHPMailer.
 */
class MailerService
{
    /**
     * Envoie un email HTML.
     *
     * @param string $to       Destinataire
     * @param string $subject  Sujet
     * @param string $htmlBody Corps HTML
     * @param string $replyTo  Adresse de reponse optionnelle
     * @return bool
     */
    public function sendEmail(string $to, string $subject, string $htmlBody, string $replyTo = ''): bool
    {
        if (empty(SMTP_HOST) || empty(SMTP_USER) || empty(SMTP_PASS)) {
            error_log('[MailerService] SMTP settings are missing.');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            if (LOCAL_DEV) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);

            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyTo);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->send();

            return true;
        } catch (Exception $exception) {
            error_log('[MailerService] Send failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Genere le corps HTML du mail de reinitialisation.
     *
     * @param string $code      Code a six chiffres
     * @param string $firstname Prenom
     * @return string
     */
    public function buildResetEmailHtml(string $code, string $firstname = ''): string
    {
        $greeting = $firstname ? "Hi {$firstname}," : 'Hello,';
        $formattedCode = substr($code, 0, 3) . ' ' . substr($code, 3, 3);

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
            Password Reset
          </p>
        </td></tr>
        <tr><td style="padding:30px 0 20px;">
          <p style="font-size:15px;line-height:1.7;color:rgba(255,255,255,0.7);margin:0 0 10px;">
            {$greeting}
          </p>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0;">
            Use the code below to reset your Theatro INSAT password.<br>
            This code expires in <strong style="color:white;">15 minutes</strong>.
          </p>
        </td></tr>
        <tr><td align="center" style="padding:10px 0 30px;">
          <div style="display:inline-block;padding:22px 48px;background:rgba(255,255,255,0.05);
                      border:1px solid rgba(255,255,255,0.25);letter-spacing:12px;
                      font-size:36px;font-weight:700;color:white;font-family:monospace;">
            {$formattedCode}
          </div>
        </td></tr>
        <tr><td>
          <p style="font-size:12px;color:rgba(255,255,255,0.3);line-height:1.6;margin:0;">
            If you didn't request this, you can safely ignore this email.<br>
            Never share this code with anyone.
          </p>
        </td></tr>
        <tr><td style="padding-top:28px;border-top:1px solid rgba(255,255,255,0.08);margin-top:28px;">
          <p style="font-size:11px;color:rgba(255,255,255,0.2);margin:0;">
            &copy; 2026 Theatro INSAT. All Rights Reserved.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
