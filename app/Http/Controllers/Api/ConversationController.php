<?php

namespace App\Http\Controllers\Api;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConversationController extends ApiController
{
    /**
     * GET /api/conversations — List all conversations
     */
    public function index(): JsonResponse
    {
        $userId = auth('api')->id();

        $conversations = Conversation::where('user_one', $userId)
            ->orWhere('user_two', $userId)
            ->with(['userOne:id,name,avatar', 'userTwo:id,name,avatar', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return $this->sendResponse('Conversations.', $conversations);
    }

    /**
     * POST /api/conversations — Start new conversation
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed.', $validator->errors(), 422);
        }

        $me    = auth('api')->id();
        $other = $request->user_id;

        if ($me == $other) {
            return $this->sendError('Cannot start conversation with yourself.', null, 400);
        }

        // Check if conversation already exists
        $conversation = Conversation::where(function ($q) use ($me, $other) {
            $q->where('user_one', $me)->where('user_two', $other);
        })->orWhere(function ($q) use ($me, $other) {
            $q->where('user_one', $other)->where('user_two', $me);
        })->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'user_one'        => $me,
                'user_two'        => $other,
                'last_message_at' => now(),
            ]);
        }

        // Send first message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $me,
            'body'            => $request->message,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $this->sendResponse('Conversation started.', [
            'conversation' => $conversation->fresh(),
            'message'      => $message,
        ], 201);
    }

    /**
     * GET /api/conversations/{id} — Get messages in a conversation
     */
    public function show(int $id): JsonResponse
    {
        $userId = auth('api')->id();

        $conversation = Conversation::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('user_one', $userId)->orWhere('user_two', $userId);
            })->first();

        if (! $conversation) {
            return $this->sendError('Conversation not found.', null, 404);
        }

        // Mark unread messages as read
        Message::where('conversation_id', $id)
               ->where('sender_id', '!=', $userId)
               ->where('is_read', false)
               ->update(['is_read' => true]);

        $messages = Message::where('conversation_id', $id)
                           ->with('sender:id,name,avatar')
                           ->orderBy('created_at')
                           ->paginate(50);

        return $this->sendResponse('Messages.', [
            'conversation' => $conversation,
            'messages'     => $messages,
        ]);
    }
}
