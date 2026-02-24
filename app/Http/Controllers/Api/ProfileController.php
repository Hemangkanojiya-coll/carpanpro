<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends ApiController
{
    /**
     * GET /api/profile — Current user profile
     */
    public function show(): JsonResponse
    {
        $user = auth('api')->user();
        return $this->sendResponse('Profile fetched.', ['user' => $user]);
    }

    /**
     * PUT /api/profile — Update profile
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'state' => 'sometimes|string|max:100',
            'city'  => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        $user = auth('api')->user();
        $user->update($request->only(['name', 'phone', 'state', 'city']));

        return $this->sendResponse('Profile updated.', ['user' => $user->fresh()]);
    }

    /**
     * POST /api/profile/avatar — Upload avatar image
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        $user = auth('api')->user();

        // Delete old avatar
        if ($user->avatar && file_exists(public_path('avatars/' . $user->avatar))) {
            unlink(public_path('avatars/' . $user->avatar));
        }

        $file = $request->file('avatar');
        $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('avatars'), $filename);

        $user->update(['avatar' => $filename]);

        return $this->sendResponse('Avatar uploaded.', [
            'avatar'     => $filename,
            'avatar_url' => url('avatars/' . $filename),
        ]);
    }
}
