<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends ApiController
{
    /**
     * PUT /api/settings/password — Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Current password is required.',
            'password.required'         => 'New password is required.',
            'password.min'              => 'New password must be at least 8 characters.',
            'password.confirmed'        => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        $user = auth('api')->user();

        // Check current password
        if (! Hash::check($request->current_password, $user->password)) {
            return $this->sendError('Current password is incorrect.', null, 422);
        }

        $user->password = $request->password;
        $user->save();

        return $this->sendResponse('Password changed successfully.');
    }
}
