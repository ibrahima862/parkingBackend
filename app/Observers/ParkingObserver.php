<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Parking;
use App\Notifications\ParkingValidated; 
use App\Notifications\ParkingFull;     

class ParkingObserver
{
    /**
     * Épaulé lors de la création d'un parking
     */
    public function created(Parking $parking)
{
    // On récupère l'utilisateur pour avoir son nom
    $user = $parking->user; 

    Activity::create([
        'user_name' => $user ? $user->name : 'Inconnu', 
        'type'      => 'new_parking',
        'message'   => "Le parking '{$parking->nom}' a été soumis pour validation.",
    ]);
}

    /**
     * Épaulé lors de la mise à jour
     */
   public function updated(Parking $parking)
    {
        // 1. Détecter la validation par l'admin
        if ($parking->wasChanged('statut') && $parking->statut === 'valide') {
            
            if ($parking->user) {
                // Envoi de la notification
                $parking->user->notify(new ParkingValidated($parking));
            
                // Log de l'activité de succès
                Activity::create([
                    'user_id'   => $parking->proprietaire_id,
                    'user_name' => $parking->user->name ?? 'Propriétaire', // <--- IL MANQUAIT CETTE LIGNE
                    'type'      => 'success',
                    'message'   => "Félicitations ! Votre parking '{$parking->nom}' a été validé et est maintenant en ligne.",
                    'source'    => 'Administration'
                ]);
            }
        }
    }
}