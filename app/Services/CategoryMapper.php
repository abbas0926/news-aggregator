<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryMapper
{
    private Collection $categories;

    private array $mappings = [
        'business' => 'business',
        'technology' => 'technology',
        'tech' => 'technology',
        'entertainment' => 'entertainment',
        'health' => 'health',
        'science' => 'science',
        'sports' => 'sports',
        'sport' => 'sports',
        'politics' => 'politics',
        'world' => 'world',
        'world news' => 'world',
        'us news' => 'world',
        'uk news' => 'world',
        'international' => 'world',
        'environment' => 'environment',
        'education' => 'education',
        'general' => null,

        // Guardian specific mappings
        'uk-news' => 'world',
        'us-news' => 'world',
        'world-news' => 'world',
        'film' => 'entertainment',
        'music' => 'entertainment',
        'books' => 'entertainment',
        'stage' => 'entertainment',
        'artanddesign' => 'entertainment',
        'tv-and-radio' => 'entertainment',
        'games' => 'entertainment',
        'money' => 'business',
        'football' => 'sports',
        'lifeandstyle' => 'health',
        'society' => 'world',
        'media' => 'technology',
        'culture' => 'entertainment',

        // NY Times specific mappings
        'arts' => 'entertainment',
        'automobiles' => 'technology',
        'nyregion' => 'world',
        'opinion' => 'politics',
        'realestate' => 'business',
        'sundayreview' => 'politics',
        'magazine' => 'entertainment',
        'fashion' => 'entertainment',
        'food' => 'health',
        'travel' => 'entertainment',
        'movies' => 'entertainment',
        'theater' => 'entertainment',
        'insider' => 'business',
        't-magazine' => 'entertainment',
        'upshot' => 'politics',
        'general' => null,
    ];

    public function __construct()
    {
        $this->categories = Cache::remember('categories_by_slug', 3600, function () {
            return Category::all()->keyBy('slug');
        });
    }
    public function mapToId(?string $externalCategory): ?int
    {
        if (empty($externalCategory)) {
            return null;
        }
        $normalized = $this->normalize($externalCategory);

        if (isset($this->mappings[$normalized])) {
            $internalSlug = $this->mappings[$normalized];

            if ($internalSlug === null) {
                return null;
            }

            return $this->categories->get($internalSlug)?->id;
        }

        $categoryId = $this->fuzzyMatch($normalized);
        if ($categoryId) {
            return $categoryId;
        }

        return null;
    }

    private function normalize(string $category): string
    {
        return Str::lower(trim(str_replace(['_', '-'], ' ', $category)));
    }

    private function fuzzyMatch(string $normalized): ?int
    {
        foreach ($this->categories as $slug => $category) {
            if (str_contains($normalized, $slug)) {
                return $category->id;
            }
            if (str_contains($slug, str_replace(' ', '', $normalized))) {
                return $category->id;
            }
        }

        return null;
    }

    public static function clearCache(): void
    {
        Cache::forget('categories_by_slug');
    }
}
