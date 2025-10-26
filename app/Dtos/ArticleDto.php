<?php

namespace App\Dtos;

class ArticleDto
{
    public function __construct(
        public readonly int $sourceId,
        public readonly ?int $categoryId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $content,
        public readonly ?string $author,
        public readonly string $url,
        public readonly ?string $urlToImage,
        public readonly string $publishedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'source_id' => $this->sourceId,
            'category_id' => $this->categoryId,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'author' => $this->author,
            'url' => $this->url,
            'url_to_image' => $this->urlToImage,
            'published_at' => $this->publishedAt,
        ];
    }
}
