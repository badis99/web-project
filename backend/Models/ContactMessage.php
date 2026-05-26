<?php
/**
 * Modele simple pour un message de contact.
 */

declare(strict_types=1);

/**
 * Represente les donnees validees du formulaire de contact.
 */
class ContactMessage
{
    /** @var string Nom complet */
    private string $_fullname;

    /** @var string Email */
    private string $_email;

    /** @var string Message */
    private string $_message;

    /** @var string Date d'envoi */
    private string $_timestamp;

    /**
     * Constructeur.
     *
     * @param string $fullname  Nom complet
     * @param string $email     Email
     * @param string $message   Message
     * @param string $timestamp Date d'envoi
     */
    public function __construct(string $fullname, string $email, string $message, string $timestamp)
    {
        $this->_fullname  = $fullname;
        $this->_email     = $email;
        $this->_message   = $message;
        $this->_timestamp = $timestamp;
    }

    /**
     * Retourne le nom complet.
     *
     * @return string
     */
    public function getFullname(): string
    {
        return $this->_fullname;
    }

    /**
     * Retourne l'email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->_email;
    }

    /**
     * Retourne le message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->_message;
    }

    /**
     * Retourne la date d'envoi.
     *
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->_timestamp;
    }
}
