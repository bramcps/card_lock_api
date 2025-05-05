<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnauthorizedMovementDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $alert;

    /**
     * Create a new event instance.
     */
    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('security-alerts'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'alert_id' => $this->alert->id,
            'door_name' => $this->alert->door->name,
            'door_location' => $this->alert->door->location,
            'alert_type' => $this->alert->alert_type,
            'description' => $this->alert->description,
            'triggered_at' => $this->alert->triggered_at->toIso8601String(),
        ];
    }
}
