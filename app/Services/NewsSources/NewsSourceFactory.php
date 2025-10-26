<?php

namespace App\Services\NewsSources;

use App\Contracts\NewsSourceInterface;

class NewsSourceFactory
{

    public static function make(string $slug): NewsSourceInterface
    {
        return match ($slug) {
            'newsapi' => app(NewsApiSource::class),
            'guardian' => app(GuardianSource::class),
            'nytimes' => app(NYTimesSource::class),
            default => throw new \InvalidArgumentException("Unsupported news source: {$slug}"),
        };
    }

    public static function getAvailableSources(): array
    {
        return ['newsapi', 'guardian', 'nytimes'];
    }
}
