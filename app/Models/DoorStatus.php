<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoorStatus extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'door_id',
        'status',
        'status_changed_at',
        'changed_by',
        'change_method',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_changed_at' => 'datetime',
    ];

    /**
     * Get the door associated with the status.
     */
    public function door()
    {
        return $this->belongsTo(Door::class);
    }

    /**
     * Get the user who changed the door status.
     */
    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Check if the door is open.
     */
    public function isOpen()
    {
        return $this->status === 'open';
    }

    /**
     * Check if the door is closed.
     */
    public function isClosed()
    {
        return $this->status === 'closed';
    }

    /**
     * Check if the door is locked.
     */
    public function isLocked()
    {
        return $this->status === 'locked';
    }

    /**
     * Check if the door is unlocked.
     */
    public function isUnlocked()
    {
        return $this->status === 'unlocked';
    }
}
