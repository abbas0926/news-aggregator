<?php

namespace App\Jobs;

use App\Models\Source;
use App\Services\NewsAggregatorService;
use App\Services\NewsSources\NewsSourceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchArticlesFromSourceJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min retry delays

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120; // 2 minutes max execution time

    public function __construct(public Source $source) {}

    public function handle(NewsAggregatorService $service): void
    {
        Log::info("Starting fetch from {$this->source->name}");

        try {
            $sourceImplementation = NewsSourceFactory::make($this->source->slug);
            $count = $service->fetchFrom($sourceImplementation);
            Log::info("Fetched {$count} new articles from {$this->source->name}");
            $this->source->update(['last_fetched_at' => now()]);
        } catch (\Exception $e) {
            Log::error("Error fetching from {$this->source->name}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to fetch from {$this->source->name} after {$this->tries} attempts", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
