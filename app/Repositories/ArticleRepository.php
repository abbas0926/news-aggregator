<?php

namespace App\Repositories;
use App\Contracts\ArticleRepositoryInterface;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function search(array $criteria): LengthAwarePaginator
    {
        $query = Article::query()->with(['source', 'category']);
        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['source'])) {
            $query->whereHas('source', function ($q) use ($criteria) {
                $q->where('slug', $criteria['source']);
            });
        }

        if (!empty($criteria['category'])) {
            $query->whereHas('category', function ($q) use ($criteria) {
                $q->where('slug', $criteria['category']);
            });
        }

        if (!empty($criteria['author'])) {
            $query->where('author', 'like', "%{$criteria['author']}%");
        }

        if (!empty($criteria['date_from'])) {
            $query->where('published_at', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('published_at', '<=', $criteria['date_to']);
        }

        $query->orderBy('published_at', 'desc');
        $perPage = min($criteria['per_page'] ?? 15, 100);
        return $query->paginate($perPage);
    }
    
    public function store(array $data): Article
    {
        return Article::create($data);
    }

    public function findByUrl(string $url): ?Article
    {
        return Article::where('url', $url)->first();
    }

    public function findById(int $id): ?Article
    {
        return Article::with(['source', 'category'])->find($id);
    }
}
