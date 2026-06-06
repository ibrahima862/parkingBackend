<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parking_id',
        'category',
        'description',
        'evidence_url',
        'status',
        'admin_notes'
    ];

    /**
     * Le client qui a fait le signalement
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * Le parking concerné
     */
    public function parking(): BelongsTo {
        return $this->belongsTo(Parking::class);
    }

    /**
     * Scope pour filtrer facilement les signalements en attente (Dashboard Admin)
     */
    public function scopePending($query) {
        return $query->where('status', 'en_attente');
    }
}