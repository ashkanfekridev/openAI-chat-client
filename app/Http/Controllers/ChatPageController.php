<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatOwner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatPageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ChatOwner $chatOwner): View
    {
        $conversations = Conversation::query()
            ->where('owner_token', $chatOwner->token($request))
            ->latest('updated_at')
            ->get(['id', 'title', 'updated_at']);

        $models = config('services.openai.models');
        $defaultModel = config('services.openai.model');

        return view('chat', compact('conversations', 'models', 'defaultModel'));
    }
}
