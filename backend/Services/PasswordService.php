<?php
/**
 * Service de reinitialisation de mot de passe.
 */

declare(strict_types=1);

/**
 * Coordonne les codes de reinitialisation et l'envoi d'email.
 */
class PasswordService
{
    /** @var UserRepository Repository utilisateurs */
    private UserRepository $_userRepository;

    /** @var PasswordResetRepository Repository reset */
    private PasswordResetRepository $_passwordResetRepository;

    /** @var MailerService Service email */
    private MailerService $_mailerService;

    /**
     * Constructeur.
     */
    public function __construct(
        UserRepository $userRepository,
        PasswordResetRepository $passwordResetRepository,
        MailerService $mailerService
    ) {
        $this->_userRepository          = $userRepository;
        $this->_passwordResetRepository = $passwordResetRepository;
        $this->_mailerService           = $mailerService;
    }

    /**
     * Genere et envoie un code de reinitialisation.
     *
     * @param string $email Email
     * @return void
     */
    public function requestResetCode(string $email): void
    {
        $user = $this->_userRepository->findIdByEmail($email);

        if (!$user) {
            return;
        }

        $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = hash('sha256', $code);

        $this->_passwordResetRepository->create([
            'user_id'    => $user['id'],
            'token_hash' => $codeHash,
            'expires_at' => date('c', time() + 15 * 60),
            'used'       => false,
            'createdat'  => date('c'),
        ]);

        $subject = 'Your Theatro INSAT password reset code';
        $htmlBody = $this->_mailerService->buildResetEmailHtml($code, $user['firstname']);
        $this->_mailerService->sendEmail($email, $subject, $htmlBody);
    }

    /**
     * Met a jour le mot de passe si le code est valide.
     *
     * @param string $email    Email
     * @param string $code     Code a six chiffres
     * @param string $password Nouveau mot de passe
     * @return array
     */
    public function resetPassword(string $email, string $code, string $password): array
    {
        $user = $this->_userRepository->findIdByEmail($email);

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid code or email address.'];
        }

        $codeHash = hash('sha256', $code);
        $resetRow = $this->_passwordResetRepository->findValid($codeHash, $user['id']);

        if (!$resetRow) {
            return ['success' => false, 'error' => 'Invalid or expired code. Please request a new one.'];
        }

        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        if (!$this->_userRepository->updatePassword($user['id'], $newHash)) {
            return ['success' => false, 'error' => 'Failed to update password. Please try again.'];
        }

        $this->_passwordResetRepository->markUsed($resetRow['id']);

        return ['success' => true];
    }
}
