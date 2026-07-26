<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\View\View;

class PublicConversationController extends Controller
{
    public function __invoke(string $shareToken): View
    {
        $conversation = Conversation::query()
            ->where('share_token', $shareToken)
            ->whereNotNull('shared_at')
            ->with(['messages' => fn ($query) => $query->oldest()])
            ->firstOrFail();

        return view('conversations.public', compact('conversation'));
    }
}
