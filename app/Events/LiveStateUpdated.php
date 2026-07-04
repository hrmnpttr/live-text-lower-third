<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class LiveStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public array $state)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('liturgia');
    }

    public function broadcastAs(): string
    {
        return 'state.updated';
    }

    public function broadcastWith(): array
    {
        return $this->state;
    }
}
