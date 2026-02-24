<?php

namespace App\Http\Controllers\Api;

use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobController extends ApiController
{
    /**
     * GET /api/jobs — List my posted jobs
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $jobs = Job::where('user_id', $user->id)
                   ->withCount('offers')
                   ->orderByDesc('created_at')
                   ->paginate(15);

        return $this->sendResponse('My jobs.', $jobs);
    }

    /**
     * POST /api/jobs — Post a new job
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'skill_required' => 'required|string|max:100',
            'state'          => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:100',
            'address'        => 'nullable|string|max:255',
            'budget'         => 'nullable|numeric|min:0',
            'budget_type'    => 'nullable|in:fixed,hourly',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        $job = Job::create([
            'user_id'        => auth('api')->id(),
            'title'          => $request->title,
            'description'    => $request->description,
            'skill_required' => $request->skill_required,
            'state'          => $request->state,
            'city'           => $request->city,
            'address'        => $request->address,
            'budget'         => $request->budget,
            'budget_type'    => $request->budget_type ?? 'fixed',
            'status'         => 'open',
        ]);

        return $this->sendResponse('Job posted successfully.', ['job' => $job], 201);
    }

    /**
     * GET /api/jobs/{id} — Single job detail
     */
    public function show(int $id): JsonResponse
    {
        $job = Job::with(['poster:id,name,email,phone,state,city', 'offers.worker:id,name,phone'])
                  ->find($id);

        if (! $job) {
            return $this->sendError('Job not found.', null, 404);
        }

        return $this->sendResponse('Job detail.', ['job' => $job]);
    }

    /**
     * PUT /api/jobs/{id} — Update job
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $job = Job::where('user_id', auth('api')->id())->find($id);

        if (! $job) {
            return $this->sendError('Job not found or not authorised.', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'title'          => 'sometimes|string|max:255',
            'description'    => 'sometimes|string',
            'skill_required' => 'sometimes|string|max:100',
            'state'          => 'sometimes|string|max:100',
            'city'           => 'sometimes|string|max:100',
            'address'        => 'sometimes|string|max:255',
            'budget'         => 'sometimes|numeric|min:0',
            'budget_type'    => 'sometimes|in:fixed,hourly',
            'status'         => 'sometimes|in:open,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        $job->update($request->only([
            'title', 'description', 'skill_required',
            'state', 'city', 'address', 'budget', 'budget_type', 'status',
        ]));

        return $this->sendResponse('Job updated.', ['job' => $job->fresh()]);
    }

    /**
     * DELETE /api/jobs/{id} — Delete job
     */
    public function destroy(int $id): JsonResponse
    {
        $job = Job::where('user_id', auth('api')->id())->find($id);

        if (! $job) {
            return $this->sendError('Job not found or not authorised.', null, 404);
        }

        $job->delete();

        return $this->sendResponse('Job deleted.');
    }
}
