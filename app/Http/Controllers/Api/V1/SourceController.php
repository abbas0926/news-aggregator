<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use Illuminate\Http\JsonResponse;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        $sources = Source::where('is_active', true)->get();

        return response()->json([
            'data' => SourceResource::collection($sources),
        ]);
    }
}
