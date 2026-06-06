<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingHoraire extends Model
{
    protected $fillable = [
        'parking_id',
        'jour',
        'heure_ouverture',
        'heure_fermeture',
        'est_ferme'
    ];

    public function parking()
    {
        return $this->belongsTo(Parking::class);
    }   
}
