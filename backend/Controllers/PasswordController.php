<?php
/**
 * Controleur de reinitialisation du mot de passe.
 */

declare(strict_types=1);

/**
 * Gere les requetes HTTP de mot de passe oublie et reset.
 */
class PasswordController
{
    /** @var PasswordService Service mot de passe */
    private PasswordService $_passwordService;

    /**
     * Constructeur.
     *
     * @param PasswordService $passwordService Service mot de passe
     */
    public function __construct(PasswordService $passwordService)
    {
        $this->_passwordService = $passwordService;
    }

    /**
     * Envoie un code de reinitialisation.
     *
     * @return void
     */
    public function forgotPassword(): void
    {
        $this->requireMethod('POST');
        startAuthSession(false);

        $body = $this->readJsonBody();
        if (!validateCsrf($body['csrf_token'] ?? '')) {
            $this->jsonError('Invalid or expired request.', 403);
        }

        checkRateLimit();

        $email = filter_var(trim($body['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError('Please enter a valid email address.', 400);
        }

        $this->_passwordService->requestResetCode($email);
        echo json_encode(['success' => true]);
    }

    /**
     * Verifie le code et met a jour le mot de passe.
     *
     * @return void
     */
    public function resetPassword(): void
    {
        $this->requireMethod('POST');
        startAuthSession(false);
        checkRateLimit();

        $body = $this->readJsonBody();
        if (!validateCsrf($body['csrf_token'] ?? '')) {
            $this->jsonError('Invalid or expired request. Please refresh the page.', 403);
        }

        $email = filter_var(trim($body['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $code = preg_replace('/\s+/', '', $body['code'] ?? '');
        $password = $body['password'] ?? '';
        $passConfirm = $body['password_confirm'] ?? '';

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!preg_match('/^\d{6}$/', $code)) {
            $errors[] = 'Code must be exactly 6 digits.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $passConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            $this->jsonError(implode(' ', $errors), 422);
        }

        $result = $this->_passwordService->resetPassword($email, $code, $password);
        if ($result['success']) {
            echo json_encode(['success' => true]);
            return;
        }

        $status = str_starts_with($result['error'], 'Failed') ? 500 : 400;
        $this->jsonError($result['error'], $status);
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
