<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends ApiController
{
    // Access token TTL — 15 minutes
    private int $accessTtl = 15;

    // Refresh token TTL — 7 days
    private int $refreshTtlDays = 7;

    // ─── Register ────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        // 1. Request must be JSON
        if (! $request->isJson()) {
            return $this->sendError('Request must be JSON. Set Content-Type: application/json', null, 400);
        }

        // 2. Validate fields
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|string|max:20',
            'state'    => 'nullable|string|max:100',
            'city'     => 'nullable|string|max:100',
            'role'     => 'required|in:client,carpenter,majdur,plumber,electrician,painter',
        ], [
            // Custom messages
            'name.required'     => 'Name is required.',
            'email.required'    => 'Email is required.',
            'email.email'       => 'Please provide a valid email address.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 8 characters.',
            'role.required'     => 'Role is required.',
            'role.in'           => 'Role must be one of: client, carpenter, majdur, plumber, electrician, painter.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        // 3. Duplicate email check
        if (User::where('email', $request->email)->exists()) {
            return $this->sendError('Email already registered. Please use a different email.', null, 409);
        }

        // 4. Create user
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'phone'    => $request->phone,
            'state'    => $request->state,
            'city'     => $request->city,
            'role'     => $request->role,
        ]);

        return $this->sendResponse('User registered successfully.', [
            'userId' => $user->id,
            'role'   => $user->role,
        ], 201);
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        // 1. JSON check
        if (! $request->isJson()) {
            return $this->sendError('Request must be JSON. Set Content-Type: application/json', null, 400);
        }

        // 2. Validate
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Email is required.',
            'email.email'       => 'Please provide a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        // 3. Attempt login
        config(['jwt.ttl' => $this->accessTtl]);

        $token = auth('api')->attempt([
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        if (! $token) {
            return $this->sendError('Invalid email or password.', null, 401);
        }

        $user = auth('api')->user();
        $refreshToken = $this->createRefreshToken($user->id);

        // 4. Session mein user store karo (4 din tak)
        session([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);

        return $this->sendResponse('Login successful.', [
            'access_token'  => $token,
            'token_type'    => 'Bearer',
            'expires_in'    => $this->accessTtl * 60, // seconds
            'refresh_token' => $refreshToken,
            'role'          => $user->role,
            'user_name'     => $user->name,
        ]);
    }

    // ─── Me ──────────────────────────────────────────────────────────────────

    /**
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->sendError('Unauthorized. Please login first.', null, 401);
        }

        return $this->sendResponse('Authenticated user details.', [
            'user' => $user,
        ]);
    }

    // ─── Logout ──────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        $user = auth('api')->user();

        if ($user) {
            RefreshToken::where('user_id', $user->id)->delete();
        }

        auth('api')->logout();

        // Session bhi clear karo
        session()->forget('user');
        session()->invalidate();
        session()->regenerateToken();

        return $this->sendResponse('Logged out successfully.');
    }

    // ─── Refresh ─────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        // 1. JSON check
        if (! $request->isJson()) {
            return $this->sendError('Request must be JSON. Set Content-Type: application/json', null, 400);
        }

        // 2. Validate
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ], [
            'refresh_token.required' => 'refresh_token is required.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        // 3. Find token
        $storedToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (! $storedToken) {
            return $this->sendError('Invalid refresh token.', null, 401);
        }

        // 4. Expiry check
        if ($storedToken->isExpired()) {
            $storedToken->delete();
            return $this->sendError('Refresh token expired. Please login again.', null, 401);
        }

        $user = $storedToken->user;

        // 5. Rotate tokens
        $storedToken->delete();

        config(['jwt.ttl' => $this->accessTtl]);
        $newAccessToken  = JWTAuth::fromUser($user);
        $newRefreshToken = $this->createRefreshToken($user->id);

        return $this->sendResponse('Token refreshed successfully.', [
            'access_token'  => $newAccessToken,
            'token_type'    => 'Bearer',
            'expires_in'    => $this->accessTtl * 60,
            'refresh_token' => $newRefreshToken,
        ]);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function createRefreshToken(int $userId): string
    {
        RefreshToken::where('user_id', $userId)->delete();

        $plain = Str::random(80);

        RefreshToken::create([
            'user_id'    => $userId,
            'token'      => $plain,
            'expires_at' => now()->addDays($this->refreshTtlDays),
        ]);

        return $plain;
    }
}
