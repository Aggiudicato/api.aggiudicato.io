<?php

namespace App\Jobs;

use App\Models\Insertion;
use App\Services\PvpCallbackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyPvpPublicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [30, 60, 300, 900, 3600];

    public function __construct(
        public Insertion $insertion,
    ) {}

    public function handle(PvpCallbackService $callbackService): void
    {
        Log::info('NotifyPvpPublicationJob: processing', [
            'pvp_id' => $this->insertion->pvp_id,
            'attempt' => $this->attempts(),
        ]);

        $callbackService->notifyPublication($this->insertion);

        $this->insertion->update(['status' => 'published']);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('NotifyPvpPublicationJob: permanently failed', [
            'pvp_id' => $this->insertion->pvp_id,
            'error' => $exception?->getMessage(),
        ]);

        $this->insertion->update(['status' => 'error']);
    }
}
