<?php
/**
 * Service du formulaire de contact.
 */

declare(strict_types=1);

/**
 * Coordonne la creation et l'envoi des emails de contact.
 */
class ContactService
{
    /** @var MailerService Service email */
    private MailerService $_mailerService;

    /**
     * Constructeur.
     *
     * @param MailerService $mailerService Service email
     */
    public function __construct(MailerService $mailerService)
    {
        $this->_mailerService = $mailerService;
    }

    /**
     * Envoie un message de contact.
     *
     * @param ContactMessage $message Message valide
     * @return bool
     */
    public function sendContactMessage(ContactMessage $message): bool
    {
        $subject = '[Theatro Website] Message from ' . $message->getFullname();
        $htmlBody = $this->buildEmailHtml($message);
        $recipient = CONTACT_EMAIL ? CONTACT_EMAIL : SMTP_FROM;

        return $this->_mailerService->sendEmail(
            $recipient,
            $subject,
            $htmlBody,
            $message->getEmail()
        );
    }

    /**
     * Genere le corps HTML du message de contact.
     *
     * @param ContactMessage $message Message
     * @return string
     */
    private function buildEmailHtml(ContactMessage $message): string
    {
        $name = $message->getFullname();
        $email = $message->getEmail();
        $timestamp = $message->getTimestamp();
        $formattedMessage = nl2br($message->getMessage());

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:60px 20px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#111;border:1px solid rgba(255,255,255,0.1);padding:50px 40px;color:white;">

        <tr><td style="padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.08);">
          <p style="margin:0;font-size:32px;font-weight:700;letter-spacing:2px;color:white;">Theatro</p>
          <p style="margin:4px 0 0;font-size:10px;letter-spacing:4px;color:rgba(255,255,255,0.4);text-transform:uppercase;">
            Contact Form Submission
          </p>
        </td></tr>

        <tr><td style="padding:28px 0 10px;">
          <p style="font-size:11px;letter-spacing:2px;color:rgba(255,255,255,0.4);text-transform:uppercase;margin:0 0 6px;">From</p>
          <p style="font-size:16px;color:white;margin:0;font-weight:600;">{$name}</p>
          <p style="font-size:14px;color:rgba(255,255,255,0.6);margin:4px 0 0;">{$email}</p>
        </td></tr>

        <tr><td style="padding:20px 0 28px;">
          <p style="font-size:11px;letter-spacing:2px;color:rgba(255,255,255,0.4);text-transform:uppercase;margin:0 0 12px;">Message</p>
          <p style="font-size:14px;line-height:1.8;color:rgba(255,255,255,0.85);margin:0;
                    padding:20px;background:rgba(255,255,255,0.04);border-left:2px solid rgba(255,255,255,0.15);">
            {$formattedMessage}
          </p>
        </td></tr>

        <tr><td style="padding-top:24px;border-top:1px solid rgba(255,255,255,0.08);">
          <p style="font-size:11px;color:rgba(255,255,255,0.25);margin:0;line-height:1.6;">
            Sent via Theatro INSAT website on {$timestamp}<br>
            Reply directly to this email to respond to {$name}.
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
