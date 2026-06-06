<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'type', 'message', 'user_name','subject_id', 'subject_type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($type, $message, $userName = 'Système') 
{
    self::create([
        'type'    => $type,
        'message' => $message,
        'user_name' => $userName ?? 'Système',
    ]);
}
}
