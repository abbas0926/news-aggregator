<?php

namespace App\Services\NewsSources;

use App\Contracts\NewsSourceInterface;
use App\Dtos\ArticleDto;
use App\Models\Source;
use App\Services\CategoryMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsApiSource implements NewsSourceInterface
{
    private Source $source;
    private string $apiKey;
    private string $baseUrl = 'https://newsapi.org/v2';
    private CategoryMapper $categoryMapper;

    public function __construct(CategoryMapper $categoryMapper)
    {
        $this->apiKey = config('services.newsapi.key');
        $this->source = Source::where('slug', $this->getSlug())->firstOrFail();
        $this->categoryMapper = $categoryMapper;
    }

    public function fetchArticles(array $filters = []): Collection
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/top-headlines", [
                    'apiKey' => $this->apiKey,
                    'language' => $filters['language'] ?? 'en',
                    'pageSize' => $filters['pageSize'] ?? 100,
                    'category' => $filters['category'] ?? null,
                ]);

            if (!$response->successful()) {
                throw new \Exception("NewsAPI request failed: {$response->status()}");
            }

            $data = $response->json();

            if ($data['status'] !== 'ok') {
                throw new \Exception("NewsAPI returned error: " . ($data['message'] ?? 'Unknown error'));
            }

            return collect($data['articles'] ?? [])
                ->filter(fn($article) => !empty($article['url']) && !empty($article['title']))
                ->map(fn($article) => $this->adaptArticle($article));
        } catch (\Exception $e) {
            Log::error("NewsAPI fetch failed: {$e->getMessage()}");
            throw $e;
        }
    }


    private function adaptArticle(array $rawData): ArticleDto
    {

        $categoryId = null;
        if (!empty($rawData['source']['category'])) {
            $categoryId = $this->categoryMapper->mapToId($rawData['source']['category']);
        }
        if (!$categoryId && !empty($rawData['category'])) {
            $categoryId = $this->categoryMapper->mapToId($rawData['category']);
        }

        return new ArticleDto(
            sourceId: $this->source->id,
            categoryId: $categoryId,
            title: $rawData['title'] ?? 'Untitled',
            description: $rawData['description'] ?? null,
            content: $rawData['content'] ?? null,
            author: $rawData['author'] ?? null,
            url: $rawData['url'],
            urlToImage: $rawData['urlToImage'] ?? null,
            publishedAt: $rawData['publishedAt'] ?? now()->toISOString(),
        );
    }

    public function getName(): string
    {
        return 'NewsAPI';
    }

    public function getSlug(): string
    {
        return 'newsapi';
    }
}
