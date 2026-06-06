<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VehiculeType extends Model
{
    // On définit les champs remplissables
    protected $fillable = ['libelle', 'icon'];

    /**
     * Relation : Un type de véhicule peut être accepté par plusieurs parkings.
     */
    public function parkings(): BelongsToMany
    {
        return $this->belongsToMany(Parking::class, 'parking_vehicule_type');
    }
}