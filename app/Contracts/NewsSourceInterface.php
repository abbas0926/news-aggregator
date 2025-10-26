<?php
namespace App\Contracts;
use Illuminate\Support\Collection;

interface NewsSourceInterface
{
    public function fetchArticles(array $filters = []): Collection;
    public function getName(): string;
    public function getSlug(): string;
}
