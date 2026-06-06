<?php

// app/Notifications/ParkingValidated.php

namespace App\Notifications;

use Illuminate\Notifications\Notification;


class ParkingValidated extends Notification
{
    protected $parking;

    public function __construct($parking) {
        $this->parking = $parking;
    }

    // On définit les canaux : 'database' pour ton Dashboard React
    public function via($notifiable) {
        return ['database']; 
    }

    // Ce qui sera stocké en base de données et lu par ton API
    public function toArray($notifiable) {
        return [
            'type' => 'success',
            'title' => 'Espace Validé !',
            'message' => "Votre parking '{$this->parking->nom}' est désormais en ligne.",
            'parking_id' => $this->parking->id,
            'icon' => 'CheckCircle2'
        ];
    }
}
