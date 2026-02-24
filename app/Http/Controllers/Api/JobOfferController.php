<?php

namespace App\Http\Controllers\Api;

use App\Models\JobOffer;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobOfferController extends ApiController
{
    /**
     * GET /api/job-offers — Incoming offers for current worker
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();

        $offers = JobOffer::with(['job:id,title,skill_required,budget,status', 'employer:id,name,phone'])
                          ->where('worker_id', $user->id)
                          ->orderByDesc('created_at')
                          ->paginate(15);

        return $this->sendResponse('Job offers.', $offers);
    }

    /**
     * POST /api/job-offers — Send offer to a worker (employer side)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_id'    => 'required|exists:jobs,id',
            'worker_id' => 'required|exists:users,id',
            'message'   => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        // Check duplicate offer
        $exists = JobOffer::where('job_id', $request->job_id)
                          ->where('worker_id', $request->worker_id)
                          ->exists();

        if ($exists) {
            return $this->sendError('Offer already sent to this worker.', null, 409);
        }

        $offer = JobOffer::create([
            'job_id'      => $request->job_id,
            'worker_id'   => $request->worker_id,
            'employer_id' => auth('api')->id(),
            'message'     => $request->message,
            'status'      => 'pending',
        ]);

        // Create notification for worker
        Notification::create([
            'user_id' => $request->worker_id,
            'type'    => 'job_offer',
            'title'   => 'New Job Offer',
            'body'    => auth('api')->user()->name . ' sent you a job offer.',
            'data'    => ['offer_id' => $offer->id, 'job_id' => $request->job_id],
        ]);

        return $this->sendResponse('Offer sent.', ['offer' => $offer], 201);
    }

    /**
     * PUT /api/job-offers/{id} — Accept/Reject offer (worker side)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $offer = JobOffer::where('worker_id', auth('api')->id())->find($id);

        if (! $offer) {
            return $this->sendError('Offer not found.', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,rejected',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        $offer->update(['status' => $request->status]);

        // Notify employer
        Notification::create([
            'user_id' => $offer->employer_id,
            'type'    => 'offer_response',
            'title'   => 'Offer ' . ucfirst($request->status),
            'body'    => auth('api')->user()->name . ' has ' . $request->status . ' your offer.',
            'data'    => ['offer_id' => $offer->id, 'job_id' => $offer->job_id],
        ]);

        return $this->sendResponse('Offer ' . $request->status . '.', ['offer' => $offer->fresh()]);
    }
}
