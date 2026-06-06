<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
//use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Report;

class SignalementAuteurNotif extends Notification
{
    /*use Queueable;*/

    protected $report;
    protected $action;

    /**
     * @param Report $report Le signalement concerné
     * @param string $action L'action effectuée par l'admin (traite, rejete, delete_parking)
     */
    public function __construct(Report $report, $action)
    {
        $this->report = $report;
        $this->action = $action;
    }

    /**
     * Détermine les canaux d'envoi (Mail + Base de données)
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Configuration de l'E-mail
     */
    public function toMail($notifiable)
    {
        $parkingName = $this->report->parking->nom ?? 'le parking';
        
        $message = match($this->action) {
            'traite' => "Bonne nouvelle ! Nous avons traité votre signalement concernant **{$parkingName}**. Les mesures nécessaires ont été prises pour corriger les informations.",
            'rejete' => "Nous avons examiné votre signalement concernant **{$parkingName}**, mais nous n'avons pas pu confirmer l'anomalie pour le moment.",
            'delete_parking' => "Suite à votre signalement, nous avons décidé de suspendre le parking **{$parkingName}** car il ne respectait plus nos standards de qualité.",
            default => "Votre signalement a été examiné par notre équipe de modération."
        };

        return (new MailMessage)
                    ->subject('Mise à jour de votre signalement - SenovaPark')
                    ->greeting("Bonjour {$notifiable->name},")
                    ->line($message)
                    ->action('Voir mon compte', url('/profile'))
                    ->line('Merci de nous aider à rendre SenovaPark plus fiable !');
    }

    /**
     * Données enregistrées en base de données pour l'interface client
     */
    public function toArray($notifiable)
{
    // On s'assure que même si le parking a été supprimé, ça ne crash pas
    $nomParking = $this->report->parking?->nom ?? 'un parking';

    return [
        'report_id' => $this->report->id,
        'parking_name' => $nomParking,
        'action' => $this->action,
        'message' => "Votre signalement pour {$nomParking} a été traité.",
    ];
}
}