<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = ['montant','abonnement_id', 'transaction_id', 'methode_paiement', 'date_paiement', 'statut', 'reservation_id'];
    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }
}
