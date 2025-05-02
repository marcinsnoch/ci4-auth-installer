<?php

namespace App\Libraries;

use CodeIgniter\Email\Email;

class EmailService
{
    protected Email $email;
    protected $twig;

    public function __construct($twig = null)
    {
        $this->email = \Config\Services::email();
        $this->twig = $twig;
    }

    /**
     * Send activation email to user.
     */
    public function sendActivationEmail(object $user): bool
    {
        $message = $this->render('emails/activation', ['user' => $user]);
        $subject = lang('Email.activation.subject');

        return $this->send($user->email, $subject, $message);
    }

    /**
     * Send reset password email.
     */
    public function sendResetPasswordEmail(object $user): bool
    {
        $message = $this->render('emails/reset_password', ['user' => $user]);
        $subject = lang('Email.reset_password.subject');

        return $this->send($user->email, $subject, $message);
    }

    /**
     * Send confirmation after password change.
     */
    public function sendPasswordChangedEmail(object $user): bool
    {
        $message = $this->render('emails/password_changed', ['user' => $user]);
        $subject = lang('Email.password_changed.subject');

        return $this->send($user->email, $subject, $message);
    }

    /**
     * Send final email after account activation.
     */
    public function sendConfirmationEmail(object $user): bool
    {
        $message = $this->render('emails/confirmation', ['user' => $user]);
        $subject = lang('Email.confirmation.subject');

        return $this->send($user->email, $subject, $message);
    }

    /**
     * Core email sending logic.
     */
    protected function send(string $to, string $subject, string $message): bool
    {
        $this->email->clear();
        $this->email->setTo($to);
        $this->email->setFrom(config('Email')->fromEmail, config('Email')->fromName);
        $this->email->setSubject($subject);
        $this->email->setMessage($message);

        return $this->email->send();
    }

    /**
     * Render email body with Twig.
     */
    protected function render(string $template, array $data): string
    {
        if (!$this->twig) {
            throw new \RuntimeException("Twig renderer not available.");
        }

        return $this->twig->render($template, $data);
    }
}
