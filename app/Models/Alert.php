<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'door_id',
        'movement_detection_id',
        'alert_type',
        'description',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'triggered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'triggered_at' => 'datetime',
    ];

    /**
     * Get the door associated with the alert.
     */
    public function door()
    {
        return $this->belongsTo(Door::class);
    }

    /**
     * Get the movement detection that triggered this alert.
     */
    public function movementDetection()
    {
        return $this->belongsTo(MovementDetection::class);
    }

    /**
     * Get the user who acknowledged the alert.
     */
    public function acknowledgedByUser()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope a query to only include unacknowledged alerts.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    /**
     * Acknowledge the alert.
     */
    public function acknowledge($userId)
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
    }
}
