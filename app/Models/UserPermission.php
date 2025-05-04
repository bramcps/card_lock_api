<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'door_id',
        'access_start_time',
        'access_end_time',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'access_start_time' => 'datetime',
        'access_end_time' => 'datetime',
    ];

    /**
     * Get the user associated with the permission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the door associated with the permission.
     */
    public function door()
    {
        return $this->belongsTo(Door::class);
    }

    /**
     * Check if the user has permission at the current time.
     */
    public function hasPermissionNow()
    {
        if (!$this->is_active) {
            return false;
        }

        // If no time restrictions, always has permission
        if ($this->access_start_time === null && $this->access_end_time === null) {
            return true;
        }

        $now = now()->format('H:i:s');
        $startTime = $this->access_start_time ? $this->access_start_time->format('H:i:s') : '00:00:00';
        $endTime = $this->access_end_time ? $this->access_end_time->format('H:i:s') : '23:59:59';

        return $now >= $startTime && $now <= $endTime;
    }
}
