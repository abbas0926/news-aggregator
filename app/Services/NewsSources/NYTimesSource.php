<?php

namespace App\Services\NewsSources;

use App\Contracts\NewsSourceInterface;
use App\Dtos\ArticleDto;
use App\Models\Source;
use App\Services\CategoryMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NYTimesSource implements NewsSourceInterface
{
    private Source $source;
    private string $apiKey;
    private ?string $apiSecret;
    private string $baseUrl = 'https://api.nytimes.com/svc';
    private CategoryMapper $categoryMapper;

    public function __construct(CategoryMapper $categoryMapper)
    {
        $this->apiKey = config('services.nytimes.key');
        $this->apiSecret = config('services.nytimes.secret');
        $this->source = Source::where('slug', $this->getSlug())->firstOrFail();
        $this->categoryMapper = $categoryMapper;

        if (empty($this->apiKey)) {
            throw new \Exception("NY Times API key is not configured. Add NYTIMES_KEY to your .env file.");
        }
    }
    public function fetchArticles(array $filters = []): Collection
    {
        try {
            $section = $filters['section'] ?? 'home';

            Log::info("NY Times API Request", [
                'endpoint' => "{$this->baseUrl}/topstories/v2/{$section}.json",
                'has_key' => !empty($this->apiKey),
                'key_length' => strlen($this->apiKey ?? ''),
            ]);

            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/topstories/v2/{$section}.json", [
                    'api-key' => $this->apiKey,
                ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error("NY Times API Error", [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'url' => "{$this->baseUrl}/topstories/v2/{$section}.json",
                ]);

                $errorData = $response->json();
                $errorMessage = $errorData['fault']['faultstring'] ?? $errorData['message'] ?? $errorBody;

                throw new \Exception("NY Times API Error ({$response->status()}): {$errorMessage}");
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception("NY Times API returned error");
            }

            return collect($data['results'] ?? [])
                ->filter(fn($article) => !empty($article['url']) && !empty($article['title']))
                ->map(fn($article) => $this->adaptArticle($article));
        } catch (\Exception $e) {
            Log::error("NY Times API fetch failed: {$e->getMessage()}");
            throw $e;
        }
    }

   
    private function adaptArticle(array $rawData): ArticleDto
    {
        $image = null;
        if (!empty($rawData['multimedia'])) {
            $largeImage = collect($rawData['multimedia'])
                ->firstWhere('format', 'superJumbo');
            $image = $largeImage['url'] ?? $rawData['multimedia'][0]['url'] ?? null;
        }

        $categoryId = null;
        if (!empty($rawData['section'])) {
            $categoryId = $this->categoryMapper->mapToId($rawData['section']);
        } elseif (!empty($rawData['subsection'])) {
            $categoryId = $this->categoryMapper->mapToId($rawData['subsection']);
        }

        return new ArticleDto(
            sourceId: $this->source->id,
            categoryId: $categoryId,
            title: $rawData['title'] ?? 'Untitled',
            description: $rawData['abstract'] ?? null,
            content: null,
            author: $rawData['byline'] ?? null,
            url: $rawData['url'],
            urlToImage: $image,
            publishedAt: $rawData['published_date'] ?? now()->toISOString(),
        );
    }

    public function getName(): string
    {
        return 'New York Times';
    }

    public function getSlug(): string
    {
        return 'nytimes';
    }
}
