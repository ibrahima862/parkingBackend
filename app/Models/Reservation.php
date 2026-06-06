<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Reservation extends Model
{
     use HasFactory;
    protected $fillable = [
        'user_id',
        'parking_id',
        'nom_conducteur',
        'prenom_conducteur',
        'matricule_vehicule',
        'telephone',
        'date_debut',
        'date_fin',
        'montant_total',
        'statut',
        'parking_status'
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parking()
    {
        return $this->belongsTo(Parking::class, 'parking_id');
    }
    // Dans App\Models\Reservation.php

    public function commission()
    {
        return $this->hasOne(Commission::class);
    }
}
