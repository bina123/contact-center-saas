<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Call $call;

    /**
     * Create a new event instance.
     */
    public function __construct(Call $call)
    {
        $this->call = $call;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('tenant.' . $this->call->tenant_id . '.calls'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'call.started';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'call' => [
                'id' => $this->call->id,
                'call_id' => $this->call->call_id,
                'direction' => $this->call->direction,
                'from_number' => $this->call->from_number,
                'to_number' => $this->call->to_number,
                'status' => $this->call->status,
                'agent_id' => $this->call->agent_id,
                'started_at' => $this->call->started_at->toIso8601String(),
            ],
        ];
    }
}
