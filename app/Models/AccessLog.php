<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'rfid_card_id',
        'door_id',
        'access_type',
        'status',
        'accessed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    /**
     * Get the user associated with the access log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the RFID card associated with the access log.
     */
    public function rfidCard()
    {
        return $this->belongsTo(RfidCard::class);
    }

    /**
     * Get the door associated with the access log.
     */
    public function door()
    {
        return $this->belongsTo(Door::class);
    }

    /**
     * Scope a query to only include successful accesses.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed accesses.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include authorized accesses.
     */
    public function scopeAuthorized($query)
    {
        return $query->where('access_type', 'authorized');
    }

    /**
     * Scope a query to only include unauthorized accesses.
     */
    public function scopeUnauthorized($query)
    {
        return $query->where('access_type', 'unauthorized');
    }
}
