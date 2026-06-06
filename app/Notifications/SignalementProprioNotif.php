<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Report;

class SignalementProprioNotif extends Notification
{
    use Queueable;

    protected $report;
    protected $action;

    /**
     * @param Report $report Le signalement concerné
     * @param string $action L'action (warning, information_updated, parking_suspended)
     */
    public function __construct(Report $report, $action)
    {
        $this->report = $report;
        $this->action = $action;
    }

    public function via($notifiable)
    {
        // On envoie en base de données et par mail pour être sûr qu'il le voit
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $parkingName = $this->report->parking->nom ?? 'votre parking';
        
        // Personnalisation du mail selon la sévérité
        $subject = match($this->action) {
            'parking_suspended' => "⚠️ Action requise : Suspension de votre parking - SenovaPark",
            default => "📢 Notification concernant votre parking {$parkingName}",
        };

        $intro = match($this->action) {
            'warning' => "Un utilisateur a signalé une anomalie sur votre parking **{$parkingName}** (Motif : {$this->report->type_rapport}).",
            'parking_suspended' => "Suite à plusieurs signalements, nous avons pris la décision de suspendre temporairement votre parking **{$parkingName}**.",
            default => "Nous vous informons qu'un signalement concernant votre parking a été traité par notre équipe."
        };

        return (new MailMessage)
                    ->subject($subject)
                    ->greeting("Bonjour {$notifiable->name},")
                    ->line($intro)
                    ->line("Détails du signalement : " . $this->report->description)
                    ->action('Gérer mon parking', url('/partner/dashboard'))
                    ->line('Il est important de maintenir des informations à jour pour garantir la satisfaction des clients.')
                    ->salutation('L\'équipe technique SenovaPark');
    }

    public function toArray($notifiable)
    {
        $nomParking = $this->report->parking?->nom ?? 'Votre parking';

        return [
            'report_id' => $this->report->id,
            'parking_id' => $this->report->parking_id,
            'action' => $this->action,
            'type' => 'warning', // Utilisé pour le style CSS en React
            'message' => "Signalement validé pour {$nomParking} : " . $this->report->type_rapport,
            'instruction' => "Veuillez mettre à jour les informations de votre parking."
        ];
    }
}