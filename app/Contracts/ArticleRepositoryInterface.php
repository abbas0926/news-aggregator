<?php

namespace App\Contracts;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    public function search(array $criteria): LengthAwarePaginator;

    public function store(array $data): Article;

    public function findByUrl(string $url): ?Article;

    public function findById(int $id): ?Article;
}
