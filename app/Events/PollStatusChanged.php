<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PollModels\Poll;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a poll starts or closes. Broadcasts to the channel so clients
 * can show desktop notifications. Attach listeners for push/email as needed.
 */
//class PollStatusChanged implements ShouldBroadcast
class PollStatusChanged 
{
    use Dispatchable,SerializesModels ;
    //,InteractsWithSockets;

    public function __construct(
        public readonly Poll $poll,
        public readonly string $action,
    ) {
    }

    // public function broadcastOn(): array
    // {
    //     return [new PrivateChannel("company.{$this->poll->company_id}.channel.{$this->poll->channel_id}")];
    // }

    // public function broadcastAs(): string
    // {
    //     return $this->action === 'start' ? 'poll.started' : 'poll.closed';
    // }

    // public function broadcastWith(): array
    // {
    //     return [
    //         'poll_id'    => (string) $this->poll->getKey(),
    //         'title'      => $this->poll->title,
    //         'channel_id' => $this->poll->channel_id,
    //         'status'     => $this->poll->status,
    //     ];
    // }
} 
