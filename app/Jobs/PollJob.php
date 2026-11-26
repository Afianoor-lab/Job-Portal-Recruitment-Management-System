<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\PollStatusChanged;
use App\Models\PollModels\Poll;
use App\Services\Poll\PollService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Starts or closes a poll at its scheduled time.
 * Idempotent: guarded by the poll's schedule_token, so stale jobs
 * (poll rescheduled / cancelled / already processed) are silently skipped.
 */
class PollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const ACTION_START = 'start';
    public const ACTION_CLOSE = 'close';

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly string $pollId,
        public readonly string $token,
        public readonly string $action,
    ) {
        $this->onQueue(config('poll.queue', 'polls'));
    }

    public static function start(string $pollId, string $token): self
    {
        return new self($pollId, $token, self::ACTION_START);
    }

    public static function close(string $pollId, string $token): self
    {
        return new self($pollId, $token, self::ACTION_CLOSE);
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(PollService $service): void
    {
        $changed = match ($this->action) {
            self::ACTION_START => $service->startPoll($this->pollId, $this->token),
            self::ACTION_CLOSE => $service->closePoll($this->pollId, $this->token),
            default            => false,
        };

        if (! $changed) {
            Log::info('PollJob skipped (stale or already processed)', [
                'poll_id' => $this->pollId,
                'action'  => $this->action,
            ]);
            return;
        }

        $poll = Poll::find($this->pollId);

        if ($poll) {
            event(new PollStatusChanged($poll, $this->action));
        }

        Log::info('PollJob processed', ['poll_id' => $this->pollId, 'action' => $this->action]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('PollJob failed', [
            'poll_id' => $this->pollId,
            'action'  => $this->action,
            'error'   => $e->getMessage(),
        ]);
    }
}
