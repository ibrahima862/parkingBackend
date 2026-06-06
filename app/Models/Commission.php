<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    protected $fillable = [
        'reservation_id',
        'abonnement_id',
        'taux',
        'montant',
        'statut'
    ];

    /**
     * Une commission appartient à une réservation.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Une commission peut aussi appartenir à un abonnement.
     */
    public function abonnement(): BelongsTo
    {
        return $this->belongsTo(Abonnement::class);
    }
}