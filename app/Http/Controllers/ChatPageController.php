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
        $user = $request->user();
        abort_if($user === null, 401);

        Conversation::query()
            ->whereNull('user_id')
            ->where('owner_token', $chatOwner->token($request))
            ->update(['user_id' => $user->id]);

        $conversations = Conversation::query()
            ->whereBelongsTo($user)
            ->with('folder:id,name,color')
            ->when(
                $request->boolean('archived'),
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->whereNull('archived_at'),
            )
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->trim()->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('messages', fn ($query) => $query->where('content', 'like', "%{$search}%"));
                });
            })
            ->when(
                $request->filled('folder'),
                fn ($query) => $query->where('folder_id', $request->integer('folder')),
            )
            ->orderByDesc('is_pinned')
            ->latest('updated_at')
            ->get(['id', 'folder_id', 'title', 'is_pinned', 'archived_at', 'updated_at']);

        $initialConversation = $conversations->firstWhere('id', $request->string('conversation')->toString());
        $initialConversationId = $initialConversation?->id;

        $models = collect(config('services.openai.models'))
            ->when($user->allowed_models !== null, fn ($models) => $models->only($user->allowed_models))
            ->all();
        $folders = $user->conversationFolders()->orderBy('name')->get(['id', 'name', 'color']);
        $documents = $user->knowledgeDocuments()->latest()->get(['id', 'name', 'size', 'status', 'error']);
        $defaultModel = array_key_exists(config('services.openai.model'), $models)
            ? config('services.openai.model')
            : array_key_first($models);

        $usage = [
            'input' => $user->input_tokens_used,
            'output' => $user->output_tokens_used,
            'used' => $user->total_tokens_used,
            'limit' => $user->token_limit,
            'remaining' => $user->remainingTokens(),
        ];

        return view('chat', compact(
            'conversations',
            'models',
            'defaultModel',
            'user',
            'usage',
            'initialConversation',
            'initialConversationId',
            'folders',
            'documents',
        ));
    }
}
