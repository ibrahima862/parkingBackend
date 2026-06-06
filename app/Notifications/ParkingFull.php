<?php


namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ParkingFull extends Notification
{
    protected $parking;

    public function __construct($parking) {
        $this->parking = $parking;
    }

    public function via($notifiable) {
      
        return ['database']; 
    }

    public function toArray($notifiable) {
        return [
            'type' => 'danger',
            'title' => 'Parking Complet !',
            'message' => "Attention : '{$this->parking->nom}' a atteint sa capacité maximale.",
            'parking_id' => $this->parking->id,
            'icon' => 'AlertTriangle'
        ];
    }
}