<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Notifications\ParkingFull;
use App\Notifications\ReservationCancelled; // Import corrigé
use Illuminate\Support\Facades\Log;

class ReservationObserver
{
    /**
     * Écoute la mise à jour d'une réservation
     */
    public function updated(Reservation $reservation)
    {
        // 1. Logique pour le parking plein
        // On vérifie si le statut de stationnement vient de passer à 'gare'
        if ($reservation->wasChanged('parking_status') && $reservation->parking_status === 'gare') {
            
            $parking = $reservation->parking;
            
            if ($parking) {
                // Info : Assure-toi que ton modèle Parking a un attribut 'is_full' 
                // ou une méthode qui calcule (places_occupees >= capacite)
                if ($parking->is_full) {
                    if ($parking->user) {
                        $parking->user->notify(new ParkingFull($parking));
                        Log::info("Notification envoyée : Le parking {$parking->nom} est plein !");
                    } else {
                        Log::warning("Avertissement : Le parking {$parking->nom} n'a pas de gérant associé.");
                    }
                }
            }
        }

        // 2. Logique pour l'annulation
        // On vérifie si le statut de la réservation vient de passer à 'annule'
        if ($reservation->wasChanged('statut') && $reservation->statut === 'annule') {
            if ($reservation->user) {
                $reservation->user->notify(new ReservationCancelled($reservation));
                Log::info("Notification d'annulation envoyée à l'utilisateur : {$reservation->user->email}");
            }
        }
    }
}