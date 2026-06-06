<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;
    public $url;

    /**
     * On passe le token et l'email au constructeur
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
        // On construit l'URL vers ton front-end React
        $this->url = env('VITE_APP_URL') . "/reset-password?token=" . $token . "&email=" . urlencode($email);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réinitialisation de votre mot de passe',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset_password', // Le nom de la vue blade
        );
    }
}