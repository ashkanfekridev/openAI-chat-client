<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === $request->user()?->id, 404);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'model' => $conversation->model,
                'folder_id' => $conversation->folder_id,
                'is_pinned' => $conversation->is_pinned,
                'archived_at' => $conversation->archived_at?->toISOString(),
                'system_prompt' => $conversation->system_prompt,
                'reasoning_effort' => $conversation->reasoning_effort,
                'temperature' => $conversation->temperature,
                'web_search' => $conversation->web_search,
                'use_knowledge' => $conversation->use_knowledge,
                'shared_url' => $conversation->share_token === null ? null : route('conversations.public', $conversation->share_token),
            ],
            'messages' => $conversation->messages()
                ->oldest()
                ->get()
                ->map->toChatArray()
                ->values(),
        ]);
    }
}
