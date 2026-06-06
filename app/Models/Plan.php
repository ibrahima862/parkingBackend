<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['nom', 'description', 'prix', 'duree_jours', 'parking_id', 'is_active'];
    
    public function parking()
    {
        return $this->belongsTo(Parking::class, 'parking_id');
    }
}
