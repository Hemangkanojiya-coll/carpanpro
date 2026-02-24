<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * ─── Success Response ────────────────────────────────────────────────────
     */
    protected function sendResponse(string $message, mixed $data = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success'     => true,
            'message'     => $message,
            'data'        => $data,
            'status_code' => $code,
        ], $code);
    }

    /**
     * ─── Error Response ──────────────────────────────────────────────────────
     */
    protected function sendError(string $message, mixed $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success'     => false,
            'message'     => $message,
            'status_code' => $code,
        ];

        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
