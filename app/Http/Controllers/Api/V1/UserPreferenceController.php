<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Http\Resources\UserPreferenceResource;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserPreferenceController extends Controller
{
    public function show(): JsonResponse
    {
        $preference = Auth::user()->preferences;

        if (!$preference) {
            return response()->json([
                'data' => null,
                'message' => 'No preferences set',
            ]);
        }

        return response()->json([
            'data' => new UserPreferenceResource($preference),
        ]);
    }
    public function update(UpdateUserPreferenceRequest $request): JsonResponse
    {
        $user = Auth::user();

        $preference = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return response()->json([
            'data' => new UserPreferenceResource($preference),
            'message' => 'Preferences updated successfully',
        ]);
    }
}
