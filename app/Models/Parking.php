<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parking extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom','pays', 'departement', 'quartier', 
        'description', 'capacite', 'image', 'latitude', 
        'longitude', 'proprietaire_id', 'statut'
    ];

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class, 'parking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }
    public function services()
    {
        return $this->hasMany(Service::class, 'parking_id');
    }
   
    public function vehiculeTypes(): BelongsToMany
    {
        // On précise le nom de la table pivot si elle ne suit pas l'ordre alphabétique
        return $this->belongsToMany(VehiculeType::class, 'parking_vehicule_type');
    }
    public function reservations():HasMany{
        return $this->hasMany(Reservation::class);
    }
    
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class, 'parking_id');
    }   
    public function horaires(): HasMany
    {
        return $this->hasMany(ParkingHoraire::class);
    }

    public function avis_clients(): HasMany
    {
        return $this->hasMany(AvisClient::class, 'parking_id');
    }
    
/**
 * Calculer le nombre de places occupées
 */
public function getPlacesOccupeesAttribute()
{
    // On compte les réservations dont le statut du parking 
    return $this->reservations()->where('parking_status', 'gare')->count();
}


/**
 * Vérifier si le parking est plein
 */
public function getIsFullAttribute()
{
    return $this->places_occupees >= $this->capacite;
}
}