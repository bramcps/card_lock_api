<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RfidCard extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'card_number',
        'user_id',
        'card_name',
        'is_active',
        'issued_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the RFID card.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the access logs for the RFID card.
     */
    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }

    /**
     * Check if the card is expired.
     */
    public function isExpired()
    {
        return $this->expires_at !== null && now()->gt($this->expires_at);
    }

    /**
     * Check if the card is valid.
     */
    public function isValid()
    {
        return $this->is_active && !$this->isExpired();
    }
}
