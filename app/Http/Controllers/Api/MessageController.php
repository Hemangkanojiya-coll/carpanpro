<?php

namespace App\Http\Controllers\Api;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends ApiController
{
    /**
     * POST /api/conversations/{id}/messages — Send a message
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $userId = auth('api')->id();

        $conversation = Conversation::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('user_one', $userId)->orWhere('user_two', $userId);
            })->first();

        if (! $conversation) {
            return $this->sendError('Conversation not found.', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        $message = Message::create([
            'conversation_id' => $id,
            'sender_id'       => $userId,
            'body'            => $request->message,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $this->sendResponse('Message sent.', ['message' => $message], 201);
    }
}
