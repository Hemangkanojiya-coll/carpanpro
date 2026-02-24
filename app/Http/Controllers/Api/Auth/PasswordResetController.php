<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends ApiController
{
    // ─── Step 1: Email bhejo OTP ke saath ────────────────────────────────────

    /**
     * POST /api/auth/forgot-password
     * Body: { "email": "user@example.com" }
     */
    public function sendOtp(Request $request): JsonResponse
    {
        if (! $request->isJson()) {
            return $this->sendError('Request must be JSON.', null, 400);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Email is required.',
            'email.email'    => 'Please provide a valid email address.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        // User exist karta hai?
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->sendError('No account found with this email.', null, 404);
        }

        // OTP generate karo (6 digit)
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Purana OTP delete karo, naya save karo
        PasswordResetOtp::where('email', $request->email)->delete();

        PasswordResetOtp::create([
            'email'      => $request->email,
            'otp'        => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Mail bhejo
        Mail::to($request->email)->send(new PasswordResetOtpMail($otp, $user->name));

        return $this->sendResponse('OTP sent to your email. Valid for 10 minutes.');
    }

    // ─── Step 2: OTP verify karo aur password reset karo ─────────────────────

    /**
     * POST /api/auth/reset-password
     * Body: { "email": "...", "otp": "123456", "password": "newpass123", "password_confirmation": "newpass123" }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        if (! $request->isJson()) {
            return $this->sendError('Request must be JSON.', null, 400);
        }

        $validator = Validator::make($request->all(), [
            'email'                 => 'required|email',
            'otp'                   => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
        ], [
            'email.required'    => 'Email is required.',
            'otp.required'      => 'OTP is required.',
            'otp.size'          => 'OTP must be 6 digits.',
            'password.required' => 'New password is required.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        // OTP record dhundho
        $record = PasswordResetOtp::where('email', $request->email)
                                   ->where('otp', $request->otp)
                                   ->first();

        if (! $record) {
            return $this->sendError('Invalid OTP. Please check and try again.', null, 422);
        }

        // Expire check
        if ($record->isExpired()) {
            $record->delete();
            return $this->sendError('OTP has expired. Please request a new one.', null, 422);
        }

        // Password update karo
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->sendError('User not found.', null, 404);
        }

        $user->password = $request->password;
        $user->save();

        // OTP delete karo (one-time use)
        $record->delete();

        return $this->sendResponse('Password has been reset successfully. You can now login.');
    }
}
