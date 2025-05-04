<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovementDetection extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'door_id',
        'has_recent_authorization',
        'unauthorized_duration',
        'detected_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'has_recent_authorization' => 'boolean',
        'detected_at' => 'datetime',
    ];

    /**
     * Get the door associated with the movement detection.
     */
    public function door()
    {
        return $this->belongsTo(Door::class);
    }

    /**
     * Get the alert triggered by this movement detection, if any.
     */
    public function alert()
    {
        return $this->hasOne(Alert::class);
    }

    /**
     * Scope a query to only include suspicious movements.
     */
    public function scopeSuspicious($query)
    {
        return $query->where('has_recent_authorization', false);
    }
}
