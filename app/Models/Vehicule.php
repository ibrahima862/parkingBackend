<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicule extends Model
{
    protected $fillable = [
        'user_id', 
        'vehicule_type_id', 
        'plaque_immatriculation', 
        'marque', 
        'modele', 
        'couleur', 
        'is_main'
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'client_id');
    }

    public function type()
    {
        return $this->belongsTo(VehiculeType::class, 'vehicule_type_id');
    }
}
