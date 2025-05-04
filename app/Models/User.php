<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUlids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get the RFID cards associated with the user.
     */
    public function rfidCards()
    {
        return $this->hasMany(RfidCard::class);
    }

    /**
     * Get the access logs associated with the user.
     */
    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }

    /**
     * Get the acknowledged alerts.
     */
    public function acknowledgedAlerts()
    {
        return $this->hasMany(Alert::class, 'acknowledged_by');
    }

    /**
     * Get the door status changes made by the user.
     */
    public function doorStatusChanges()
    {
        return $this->hasMany(DoorStatus::class, 'changed_by');
    }

    /**
     * Get the user's door permissions.
     */
    public function permissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Get all doors the user has access to.
     */
    public function accessibleDoors()
    {
        return $this->belongsToMany(Door::class, 'user_permissions')
            ->wherePivot('is_active', true);
    }
}
