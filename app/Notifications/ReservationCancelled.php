<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ReservationCancelled extends Notification
{
    protected $reservation;

    public function __construct($reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable)
    {
        // Correction : 'database' au singulier
        return ['database']; 
    }

    /**
     * Ces données seront stockées dans la colonne 'data' de ta table 'notifications'
     * et récupérées par ton fetch() dans React.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'error',
            'title' => 'Réservation annulée',
            'message' => "Votre réservation prévue du {$this->reservation->date_debut} au {$this->reservation->date_fin} a été annulée.",
            'reservation_id' => $this->reservation->id,
            'icon' => 'XCircle'
        ];
    }
}