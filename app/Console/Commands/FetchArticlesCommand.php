<?php

namespace App\Console\Commands;

use App\Jobs\FetchArticlesFromSourceJob;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FetchArticlesCommand extends Command
{

    protected $signature = 'articles:fetch 
                            {--source= : Fetch from specific source slug}
                            {--sync : Run synchronously without queue}';

    protected $description = 'Fetch articles from news sources';

    public function handle(): int
    {
        $this->info('Starting article fetch process...');

        $sourcesQuery = Source::where('is_active', true);
        if ($sourceSlug = $this->option('source')) {
            $sourcesQuery->where('slug', $sourceSlug);
        }

        $sources = $sourcesQuery->get();

        if ($sources->isEmpty()) {
            $this->error('No active sources found');
            return self::FAILURE;
        }

        $this->info("Found {$sources->count()} active source(s)");

        if ($this->option('sync')) {
            $this->fetchSynchronously($sources);
        } else {
            $this->fetchAsynchronously($sources);
        }
        return self::SUCCESS;
    }

    private function fetchAsynchronously(Collection $sources): void
    {
        foreach ($sources as $source) {
            FetchArticlesFromSourceJob::dispatch($source);
            $this->comment("Queued job for {$source->name}");
        }

        $this->newLine();
        $this->info("Dispatched {$sources->count()} fetch job(s)");
        $this->comment('Jobs queued. Run: php artisan queue:work');
    }

    private function fetchSynchronously(Collection $sources): void
    {
        $this->info('Running in synchronous mode...');
        $this->newLine();

        foreach ($sources as $source) {
            $this->info("Fetching from {$source->name}...");

            try {
                FetchArticlesFromSourceJob::dispatchSync($source);
                $this->info("Completed {$source->name}");
            } catch (\Exception $e) {
                $this->error("Failed {$source->name}: {$e->getMessage()}");
                Log::error("Failed to fetch from {$source->name}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->newLine();
        }

        $this->info('Synchronous fetch completed');
    }
}
