<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Les attributs qui peuvent être assignés massivement.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telephone',
        'role',
        'is_approved',
        'versoCIN',
        'rectoCIN'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean'
        ];
    }

    /**
     * Relation : Un utilisateur peut posséder plusieurs pa rkings
     */
    public function parkings(): HasMany
    {
        return $this->hasMany(Parking::class, 'proprietaire_id');
    }

    public function soldeRetirable()
    {
        // 1. Calcul des GAINS BRUTS (Somme des réservations confirmées/terminées)
        $gainsBruts = \DB::table('reservations')
            ->join('parkings', 'reservations.parking_id', '=', 'parkings.id')
            ->where('parkings.proprietaire_id', $this->id)
            ->whereIn('reservations.statut', ['confirme', 'termine'])
            ->sum('montant_total');


        // On cherche les commissions liées aux réservations de ce propriétaire
        $totalCommissions = \DB::table('commissions')
            ->join('reservations', 'commissions.reservation_id', '=', 'reservations.id')
            ->join('parkings', 'reservations.parking_id', '=', 'parkings.id')
            ->where('parkings.proprietaire_id', $this->id)
            ->where('commissions.statut', 'valide')
            ->sum('commissions.montant');

        // 3. Calcul des RETRAITS (déjà validés ou en cours)
        $retraitsBloques = $this->retraits()
            ->whereIn('statut', ['valide', 'en_attente'])
            ->sum('montant');

        // SOLDE NET = BRUT - COMMISSIONS - RETRAITS
        $soldeNet = ($gainsBruts - $totalCommissions) - $retraitsBloques;

        return max(0, $soldeNet);
    }

    public function retraits(): HasMany
    {
        return $this->hasMany(Retrait::class, 'user_id');
    }


    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    public function vehicules()
    {
        return $this->hasMany(Vehicule::class);
    }
}