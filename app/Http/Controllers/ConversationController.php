<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Conversation $conversation, ChatOwner $chatOwner): JsonResponse
    {
        abort_unless($conversation->owner_token === $chatOwner->token($request), 404);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'model' => $conversation->model,
            ],
            'messages' => $conversation->messages()
                ->oldest()
                ->get()
                ->map->toChatArray()
                ->values(),
        ]);
    }
}
