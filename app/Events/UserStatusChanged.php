<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Sent on each workspace presence channel the user belongs to.
 */
class UserStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<int, string>  $workspaceIds
     * @param  array<string, mixed>|null  $customStatus
     */
    public function __construct(
        public string $userId,
        public array $workspaceIds,
        public string $presence,
        public ?array $customStatus,
    ) {}

    /** @return array<int, PresenceChannel> */
    public function broadcastOn(): array
    {
        return array_map(
            fn (string $id) => new PresenceChannel('workspace.'.$id),
            $this->workspaceIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'UserStatusChanged';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'presence' => $this->presence,
            'custom_status' => $this->customStatus,
        ];
    }
}

