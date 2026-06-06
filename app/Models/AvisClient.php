<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisClient extends Model
{
    protected $fillable = ['user_id', 'parking_id', 'note', 'commentaire'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
