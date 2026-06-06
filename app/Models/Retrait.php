<?php

// app/Models/Retrait.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Retrait extends Model
{
    protected $fillable = [
        'user_id',
        'montant',
        'methode',
        'numero_compte',
        'statut',
        'motif_rejet',
        'reference_transaction',
        'date_paiement'
    ];

    /**
     * Le propriétaire qui a demandé le retrait
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper pour savoir si le retrait est encore modifiable
    public function isPending()
    {
        return $this->statut === 'en_attente';
    }
}
