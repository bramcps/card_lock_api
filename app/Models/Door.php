<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Door extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'location',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the access logs for the door.
     */
    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }

    /**
     * Get the movement detections for the door.
     */
    public function movementDetections()
    {
        return $this->hasMany(MovementDetection::class);
    }

    /**
     * Get the alerts for the door.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get the current status of the door.
     */
    public function statuses()
    {
        return $this->hasMany(DoorStatus::class);
    }

    /**
     * Get the current status of the door.
     */
    public function currentStatus()
    {
        return $this->hasOne(DoorStatus::class)->latest();
    }

    /**
     * Get users with permission to access this door.
     */
    public function authorizedUsers()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->wherePivot('is_active', true);
    }
}
