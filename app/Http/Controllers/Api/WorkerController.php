<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerController extends ApiController
{
    /**
     * GET /api/workers?skill=&state=&city= — Search workers
     */
    public function search(Request $request): JsonResponse
    {
        $query = WorkerProfile::with('user:id,name,email,phone,state,city,role,avatar');

        // Filter by skill
        if ($request->filled('skill')) {
            $skill = $request->skill;
            $query->whereJsonContains('skills', $skill);
        }

        // Filter by state (from user table)
        if ($request->filled('state')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('state', $request->state);
            });
        }

        // Filter by city (from user table)
        if ($request->filled('city')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('city', $request->city);
            });
        }

        // Filter by availability
        if ($request->filled('availability')) {
            $query->where('availability', $request->availability);
        }

        $workers = $query->orderByDesc('rating')->paginate(20);

        return $this->sendResponse('Workers found.', $workers);
    }

    /**
     * GET /api/workers/{id} — Worker detail
     */
    public function show(int $id): JsonResponse
    {
        $profile = WorkerProfile::with('user:id,name,email,phone,state,city,role,avatar')
                                ->where('user_id', $id)
                                ->first();

        if (! $profile) {
            return $this->sendError('Worker profile not found.', null, 404);
        }

        return $this->sendResponse('Worker detail.', ['worker' => $profile]);
    }
}
