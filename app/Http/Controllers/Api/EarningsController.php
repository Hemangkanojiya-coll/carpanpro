<?php

namespace App\Http\Controllers\Api;

use App\Models\Earning;
use Illuminate\Http\JsonResponse;

class EarningsController extends ApiController
{
    /**
     * GET /api/earnings — Earnings history for current worker
     */
    public function index(): JsonResponse
    {
        $userId = auth('api')->id();

        $earnings = Earning::with('job:id,title,skill_required')
                           ->where('worker_id', $userId)
                           ->orderByDesc('created_at')
                           ->paginate(20);

        $totalEarned = Earning::where('worker_id', $userId)->where('status', 'paid')->sum('amount');
        $totalPending = Earning::where('worker_id', $userId)->where('status', 'pending')->sum('amount');

        return $this->sendResponse('Earnings.', [
            'earnings'      => $earnings,
            'total_earned'  => $totalEarned,
            'total_pending' => $totalPending,
        ]);
    }
}
