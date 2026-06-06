<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abonnement extends Model
{
    protected $fillable = ['type', 'prix', 'date_debut', 'date_fin', 'statut', 'user_id', 'parking_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parking()
    {
        return $this->belongsTo(Parking::class, 'parking_id');
    }

    // Dans App\Models\Abonnement.php

    public function commission()
    {
        return $this->hasOne(Commission::class);
    }
}
