<?php

namespace App\Http\Controllers\Api;

use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkerProfileController extends ApiController
{
    /**
     * GET /api/worker-profile — Get my worker profile
     */
    public function show(): JsonResponse
    {
        $user    = auth('api')->user();
        $profile = WorkerProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return $this->sendResponse('No worker profile yet.', ['profile' => null]);
        }

        return $this->sendResponse('Worker profile.', ['profile' => $profile]);
    }

    /**
     * PUT /api/worker-profile — Create or update worker profile
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'skills'           => 'sometimes|array',
            'skills.*'         => 'string|max:50',
            'bio'              => 'sometimes|string|max:500',
            'experience_years' => 'sometimes|integer|min:0',
            'hourly_rate'      => 'sometimes|numeric|min:0',
            'availability'     => 'sometimes|in:available,busy,unavailable',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        $user = auth('api')->user();

        $profile = WorkerProfile::updateOrCreate(
            ['user_id' => $user->id],
            $request->only(['skills', 'bio', 'experience_years', 'hourly_rate', 'availability'])
        );

        return $this->sendResponse('Worker profile updated.', ['profile' => $profile->fresh()]);
    }
}
