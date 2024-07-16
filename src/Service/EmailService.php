<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService {
    private $mailer;
    private $router;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $router) {
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public function sendRegistrationNotification(User $user) {
        $email = (new Email())
            ->from('register@fhost.fr')
            ->to('contact@sacha-hemon.fr')
            ->subject('Nouvelle demande d\'inscription de ' . $user->getUsername())
            ->html("<p>Nouvel utilisateur : {$user->getUsername()}</p>
                <p style='padding: 2rem 0'>Description : {$user->getDescription()}</p>
                <a href='http://127.0.0.1:8000/approve/{$user->getToken()}' style='background: black; color: white; padding: 1rem; text-decoration: none; border-radius: 10px;'>Approuver l'utilisateur</a>");

        try {
            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            // Log the exception or handle it as needed
            return false;
        }
    }

    public function sendContactEmail($subject, $email, $message) {
        $email = (new Email())
            ->from($email)
            ->to('contact@sacha-hemon.fr') // Same email as registration notification
            ->subject($subject)
            ->html("<p>Message de : {$email}</p><p>Message: {$message}</p>");

        try {
            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            return false;
        }
    }
}