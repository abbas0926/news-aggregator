<?php

namespace App\Services\NewsSources;

use App\Contracts\NewsSourceInterface;
use App\Dtos\ArticleDto;
use App\Models\Source;
use App\Services\CategoryMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuardianSource implements NewsSourceInterface
{
    private Source $source;
    private string $apiKey;
    private string $baseUrl = 'https://content.guardianapis.com';
    private CategoryMapper $categoryMapper;

    public function __construct(CategoryMapper $categoryMapper)
    {
        $this->apiKey = config('services.guardian.key');
        $this->source = Source::where('slug', $this->getSlug())->firstOrFail();
        $this->categoryMapper = $categoryMapper;
    }

    public function fetchArticles(array $filters = []): Collection
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/search", [
                    'api-key' => $this->apiKey,
                    'page-size' => $filters['pageSize'] ?? 50,
                    'show-fields' => 'trailText,body,byline,thumbnail',
                    'order-by' => 'newest',
                ]);

            if (!$response->successful()) {
                throw new \Exception("Guardian API request failed: {$response->status()}");
            }

            $data = $response->json();

            if ($data['response']['status'] !== 'ok') {
                throw new \Exception("Guardian API returned error");
            }

            return collect($data['response']['results'] ?? [])
                ->filter(fn($article) => !empty($article['webUrl']) && !empty($article['webTitle']))
                ->map(fn($article) => $this->adaptArticle($article));
        } catch (\Exception $e) {
            Log::error("Guardian API fetch failed: {$e->getMessage()}");
            throw $e;
        }
    }

    private function adaptArticle(array $rawData): ArticleDto
    {
        $fields = $rawData['fields'] ?? [];

        $categoryId = null;
        if (!empty($rawData['sectionId'])) {
            $categoryId = $this->categoryMapper->mapToId($rawData['sectionId']);
        } elseif (!empty($rawData['sectionName'])) {
            $categoryId = $this->categoryMapper->mapToId($rawData['sectionName']);
        }

        return new ArticleDto(
            sourceId: $this->source->id,
            categoryId: $categoryId,
            title: $rawData['webTitle'] ?? 'Untitled',
            description: $fields['trailText'] ?? null,
            content: $fields['body'] ?? null,
            author: $fields['byline'] ?? null,
            url: $rawData['webUrl'],
            urlToImage: $fields['thumbnail'] ?? null,
            publishedAt: $rawData['webPublicationDate'] ?? now()->toISOString(),
        );
    }

    public function getName(): string
    {
        return 'The Guardian';
    }

    public function getSlug(): string
    {
        return 'guardian';
    }
}
