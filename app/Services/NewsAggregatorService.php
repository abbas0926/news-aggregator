<?php

namespace App\Services;
use App\Contracts\ArticleRepositoryInterface;
use App\Contracts\NewsSourceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NewsAggregatorService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository
    ) {}

    public function fetchFrom(NewsSourceInterface $source): int
    {
        Log::info("Starting fetch from {$source->getName()}");

        try {
            $articles = $source->fetchArticles();
            if ($articles->isEmpty()) {
                Log::info("No articles fetched from {$source->getName()}");
                return 0;
            }
            $newCount = 0;
            foreach ($articles as $articleDto) {
                    $exists = $this->articleRepository->findByUrl($articleDto->url);
                    if (!$exists) {
                        DB::transaction(function () use ($articleDto) {
                            $this->articleRepository->store($articleDto->toArray());
                        });
                        $newCount++;
                    }
            }
            Log::info("Fetched {$newCount} new articles from {$source->getName()} (Total: {$articles->count()})");
            return $newCount;
        } catch (\Exception $e) {
            Log::error("Failed to fetch from {$source->getName()}: {$e->getMessage()}", [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
