<?php
/**
 * Service d'authentification et d'inscription.
 */

declare(strict_types=1);

/**
 * Coordonne les regles metier liees aux utilisateurs.
 */
class AuthService
{
    /** @var UserRepository Repository utilisateurs */
    private UserRepository $_userRepository;

    /** @var PendingRegistrationRepository Repository candidatures */
    private PendingRegistrationRepository $_pendingRepository;

    /** @var UploadService Service d'upload */
    private UploadService $_uploadService;

    /**
     * Constructeur.
     */
    public function __construct(
        UserRepository $userRepository,
        PendingRegistrationRepository $pendingRepository,
        UploadService $uploadService
    ) {
        $this->_userRepository    = $userRepository;
        $this->_pendingRepository = $pendingRepository;
        $this->_uploadService     = $uploadService;
    }

    /**
     * Authentifie un utilisateur depuis la table users uniquement.
     *
     * @param string $email    Email
     * @param string $password Mot de passe
     * @return array|null
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->_userRepository->findByEmail($email);
        $storedHash = $user['password'] ?? '$2y$12$invalidhashpadding000000000000000000000000000000000000000';

        if (!$user || !password_verify($password, $storedHash)) {
            return null;
        }

        return $user;
    }

    /**
     * Retourne une erreur si l'email est deja utilise.
     *
     * @param string $email Email
     * @return string|null
     */
    public function getDuplicateEmailError(string $email): ?string
    {
        if ($this->_userRepository->emailExists($email)) {
            return 'An account with this email address already exists.';
        }

        if ($this->_pendingRepository->emailExists($email)) {
            return 'An application with this email address is already pending review.';
        }

        return null;
    }

    /**
     * Cree une candidature en attente.
     *
     * @param array      $data        Donnees validees du formulaire
     * @param array|null $pictureFile Fichier optionnel
     * @return array
     */
    public function submitPendingRegistration(array $data, ?array $pictureFile): array
    {
        $picturePath = null;

        $pictureError = $pictureFile['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($pictureFile !== null && $pictureError !== UPLOAD_ERR_NO_FILE) {
            $picturePath = $this->_uploadService->saveProfilePicture($pictureFile);
            if ($picturePath === false) {
                return [
                    'success' => false,
                    'error'   => 'Invalid picture. Please upload a JPEG, PNG, GIF, or WebP image under 5 MB.',
                ];
            }
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $registration = new PendingRegistration(
            $data['firstname'],
            $data['lastname'],
            $data['email'],
            $passwordHash,
            $data['phone'] ?: null,
            $data['yearofstudy'],
            $data['fieldofstudy'] ?: null,
            $data['department'],
            $data['birthdate'],
            $picturePath,
            $data['whyjoin']
        );

        if ($this->_pendingRepository->create($registration->toArray())) {
            return [
                'success' => true,
                'message' => 'Application submitted! Your registration is pending admin approval. You will be notified by email once accepted.',
            ];
        }

        return ['success' => false, 'error' => 'Registration failed. Please try again later.'];
    }
}
