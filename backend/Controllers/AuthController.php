<?php
/**
 * Controleur d'authentification.
 */

declare(strict_types=1);

/**
 * Gere les requetes HTTP de connexion, inscription et session.
 */
class AuthController
{
    /** @var AuthService Service d'authentification */
    private AuthService $_authService;

    /**
     * Constructeur.
     *
     * @param AuthService $authService Service d'authentification
     */
    public function __construct(AuthService $authService)
    {
        $this->_authService = $authService;
    }

    /**
     * Traite la connexion d'un utilisateur.
     *
     * @return void
     */
    public function login(): void
    {
        $this->requireMethod('POST');
        startAuthSession();

        $body = $this->readJsonBody();
        if (!validateCsrf($body['csrf_token'] ?? '')) {
            $this->jsonError('Invalid or expired request. Please refresh the page.', 403);
        }

        checkRateLimit();

        $email = filter_var(trim($body['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $body['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            $this->jsonError('Invalid email or password.', 400);
        }

        $user = $this->_authService->authenticate($email, $password);

        if (!$user) {
            incrementRateLimit();
            $this->jsonError('Invalid email or password.', 401);
        }

        session_regenerate_id(true);
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['firstname']    = $user['firstname'];
        $_SESSION['lastname']     = $user['lastname'];
        $_SESSION['logged_in_at'] = time();

        resetRateLimit();

        echo json_encode([
            'success'   => true,
            'firstname' => $user['firstname'],
        ]);
    }

    /**
     * Traite l'inscription d'un nouvel utilisateur.
     *
     * @return void
     */
    public function signup(): void
    {
        $this->requireMethod('POST');
        startAuthSession();

        if (!validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->jsonError('Invalid or expired request. Please refresh the page.', 403);
        }

        checkRateLimit();

        $data = $this->readSignupData();
        $errors = $this->validateSignupData($data);

        if (!empty($errors)) {
            $this->jsonError(implode(' ', $errors), 422);
        }

        $duplicateError = $this->_authService->getDuplicateEmailError($data['email']);
        if ($duplicateError !== null) {
            $this->jsonError($duplicateError, 409);
        }

        $pictureFile = $_FILES['picture'] ?? null;
        $result = $this->_authService->submitPendingRegistration($data, $pictureFile);

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
            ]);
            return;
        }

        $statusCode = str_starts_with($result['error'], 'Invalid picture') ? 422 : 500;
        $this->jsonError($result['error'], $statusCode);
    }

    /**
     * Traite la deconnexion.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->requireMethod('POST');
        startAuthSession();

        $_SESSION = [];

        setcookie('theatro_session', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => !LOCAL_DEV,
        ]);

        session_destroy();
        echo json_encode(['success' => true]);
    }

    /**
     * Verifie la session courante.
     *
     * @return void
     */
    public function checkSession(): void
    {
        startAuthSession();

        if (!empty($_SESSION['user_id'])) {
            echo json_encode([
                'logged_in' => true,
                'firstname' => $_SESSION['firstname'] ?? '',
            ]);
            return;
        }

        echo json_encode(['logged_in' => false]);
    }

    /**
     * Lit et valide le corps JSON.
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
     * Lit les donnees du formulaire d'inscription.
     *
     * @return array
     */
    private function readSignupData(): array
    {
        $allowedFields = ['MPI', 'CBA', 'GL', 'RT', 'IIA', 'IMI'];

        $fieldRaw = $_POST['fieldofstudy'] ?? '';
        if (in_array($fieldRaw, $allowedFields, true)) {
            $fieldofstudy = $fieldRaw;
        } else {
            $fieldofstudy = mb_substr(
                htmlspecialchars(strip_tags(trim($fieldRaw)), ENT_QUOTES, 'UTF-8'),
                0,
                100
            );
        }

        $yearRaw = $_POST['yearofstudy'] ?? null;
        $yearofstudy = ($yearRaw !== null && $yearRaw !== '') ? (int)$yearRaw : null;

        $birthdate = trim($_POST['birthdate'] ?? '');
        $birthdateValue = null;
        if (!empty($birthdate)) {
            $date = DateTime::createFromFormat('Y-m-d', $birthdate);
            if ($date && $date->format('Y-m-d') === $birthdate) {
                $birthdateValue = $birthdate;
            }
        }

        return [
            'firstname'        => htmlspecialchars(strip_tags(trim($_POST['firstname'] ?? '')), ENT_QUOTES, 'UTF-8'),
            'lastname'         => htmlspecialchars(strip_tags(trim($_POST['lastname'] ?? '')), ENT_QUOTES, 'UTF-8'),
            'email'            => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'phone'            => htmlspecialchars(strip_tags(trim($_POST['phone'] ?? '')), ENT_QUOTES, 'UTF-8'),
            'yearofstudy'      => $yearofstudy,
            'fieldofstudy'     => $fieldofstudy,
            'department'       => $_POST['department'] ?? '',
            'birthdate'        => $birthdateValue,
            'whyjoin'          => htmlspecialchars(strip_tags(trim($_POST['whyjoin'] ?? '')), ENT_QUOTES, 'UTF-8'),
            'password'         => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
        ];
    }

    /**
     * Valide les donnees d'inscription.
     *
     * @param array $data Donnees du formulaire
     * @return array
     */
    private function validateSignupData(array $data): array
    {
        $allowedDepts = ['Acting', 'Music', 'Dancing', 'Writing', 'Media'];
        $errors = [];

        if (empty($data['firstname'])) {
            $errors[] = 'First name is required.';
        }
        if (empty($data['lastname'])) {
            $errors[] = 'Last name is required.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($data['password'] !== $data['password_confirm']) {
            $errors[] = 'Passwords do not match.';
        }
        if (!in_array($data['department'], $allowedDepts, true)) {
            $errors[] = 'Please select a valid department.';
        }
        if (empty($data['fieldofstudy'])) {
            $errors[] = 'Please select or specify your field of study.';
        }
        if (empty($data['whyjoin']) || strlen($data['whyjoin']) < 10) {
            $errors[] = 'Please tell us why you want to join.';
        }
        if (strlen($data['whyjoin']) > 1000) {
            $errors[] = 'Motivation must be 1000 characters or less.';
        }
        if ($data['yearofstudy'] !== null && ($data['yearofstudy'] < 1 || $data['yearofstudy'] > 6)) {
            $errors[] = 'Year of study must be between 1 and 6.';
        }

        return $errors;
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
     * @param string $message Message d'erreur
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
