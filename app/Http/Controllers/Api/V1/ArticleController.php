<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\ArticleRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleSearchRequest;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository
    ) {}

    public function index(ArticleSearchRequest $request): ArticleCollection
    {
        $articles = $this->articleRepository->search($request->validated());

        return new ArticleCollection($articles);
    }

    public function show(int $id): ArticleResource
    {
        $article = $this->articleRepository->findById($id);

        if (!$article) {
            abort(404, 'Article not found');
        }

        return (new ArticleResource($article))->withContent();
    }
}
