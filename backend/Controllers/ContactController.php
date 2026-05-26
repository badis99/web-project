<?php
/**
 * Controleur du formulaire de contact.
 */

declare(strict_types=1);

/**
 * Gere les requetes HTTP du formulaire de contact.
 */
class ContactController
{
    /** @var ContactService Service contact */
    private ContactService $_contactService;

    /**
     * Constructeur.
     *
     * @param ContactService $contactService Service contact
     */
    public function __construct(ContactService $contactService)
    {
        $this->_contactService = $contactService;
    }

    /**
     * Traite l'envoi d'un message.
     *
     * @return void
     */
    public function sendMessage(): void
    {
        $this->requireMethod('POST');
        startAuthSession();

        $body = $this->readJsonBody();
        if (!validateCsrf($body['csrf_token'] ?? '')) {
            $this->jsonError('Invalid or expired request. Please refresh the page.', 403);
        }

        checkRateLimit();

        $name = htmlspecialchars(strip_tags(trim($body['fullname'] ?? '')), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($body['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $message = htmlspecialchars(strip_tags(trim($body['message'] ?? '')), ENT_QUOTES, 'UTF-8');

        $errors = [];
        if (empty($name)) {
            $errors[] = 'Name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (strlen($message) < 10) {
            $errors[] = 'Message must be at least 10 characters.';
        }
        if (strlen($message) > 2000) {
            $errors[] = 'Message is too long (max 2000 characters).';
        }

        if (!empty($errors)) {
            $this->jsonError(implode(' ', $errors), 422);
        }

        $contactMessage = new ContactMessage($name, $email, $message, date('Y-m-d H:i:s T'));

        if ($this->_contactService->sendContactMessage($contactMessage)) {
            echo json_encode([
                'success' => true,
                'message' => 'Your message has been sent! We will get back to you soon.',
            ]);
            return;
        }

        $this->jsonError('Failed to send your message. Please try again later.', 500);
    }

    /**
     * Lit le corps JSON.
     *
     * @return array
     */
    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $body = json_decode((string)$raw, true);

        if (!is_array($body)) {
            $this->jsonError('Invalid request body.', 400);
        }

        return $body;
    }

    /**
     * Verifie la methode HTTP.
     *
     * @param string $method Methode attendue
     * @return void
     */
    private function requireMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            $this->jsonError('Method not allowed.', 405);
        }
    }

    /**
     * Envoie une erreur JSON et termine la requete.
     *
     * @param string $message Message
     * @param int    $status  Code HTTP
     * @return void
     */
    private function jsonError(string $message, int $status): void
    {
        http_response_code($status);
        echo json_encode(['error' => $message]);
        exit;
    }
}
