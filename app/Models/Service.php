<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['nom', 'description', 'prix', 'parking_id'];
    public function parking()
    {
        return $this->belongsTo(Parking::class, 'parking_id');
    }
}
