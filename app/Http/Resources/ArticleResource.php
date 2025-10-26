<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    private bool $includeContent = false;


    public function withContent(): self
    {
        $this->includeContent = true;
        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->when($this->includeContent, $this->content),
            'author' => $this->author,
            'source' => new SourceResource($this->whenLoaded('source')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'url' => $this->url,
            'url_to_image' => $this->url_to_image,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
