<?php
/**
 * Modele simple pour une candidature en attente.
 */

declare(strict_types=1);

/**
 * Represente une ligne a inserer dans PendingRegistration.
 */
class PendingRegistration
{
    /** @var string Prenom */
    private string $_firstname;

    /** @var string Nom */
    private string $_lastname;

    /** @var string Email */
    private string $_email;

    /** @var string Hash du mot de passe */
    private string $_password;

    /** @var string|null Telephone */
    private ?string $_phone;

    /** @var int|null Annee d'etude */
    private ?int $_yearofstudy;

    /** @var string|null Filiere */
    private ?string $_fieldofstudy;

    /** @var string Departement souhaite */
    private string $_department;

    /** @var string|null Date de naissance */
    private ?string $_birthdate;

    /** @var string|null Chemin de la photo */
    private ?string $_picture;

    /** @var string Motivation du candidat */
    private string $_whyjoin;

    /**
     * Constructeur.
     */
    public function __construct(
        string $firstname,
        string $lastname,
        string $email,
        string $password,
        ?string $phone,
        ?int $yearofstudy,
        ?string $fieldofstudy,
        string $department,
        ?string $birthdate,
        ?string $picture,
        string $whyjoin
    ) {
        $this->_firstname    = $firstname;
        $this->_lastname     = $lastname;
        $this->_email        = $email;
        $this->_password     = $password;
        $this->_phone        = $phone;
        $this->_yearofstudy  = $yearofstudy;
        $this->_fieldofstudy = $fieldofstudy;
        $this->_department   = $department;
        $this->_birthdate    = $birthdate;
        $this->_picture      = $picture;
        $this->_whyjoin      = $whyjoin;
    }

    /**
     * Convertit le modele en tableau pour Supabase.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'firstname'    => $this->_firstname,
            'lastname'     => $this->_lastname,
            'email'        => $this->_email,
            'password'     => $this->_password,
            'phone'        => $this->_phone,
            'yearofstudy'  => $this->_yearofstudy,
            'fieldofstudy' => $this->_fieldofstudy,
            'department'   => $this->_department,
            'birthdate'    => $this->_birthdate,
            'picture'      => $this->_picture,
            'whyjoin'      => $this->_whyjoin,
            'createdat'    => date('c'),
        ];
    }
}
