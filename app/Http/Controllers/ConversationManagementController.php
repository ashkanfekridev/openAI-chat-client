<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConversationRequest;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ConversationManagementController extends Controller
{
    public function update(UpdateConversationRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $conversation->update($request->validated());

        return $request->expectsJson()
            ? response()->json(['conversation' => $this->summary($conversation->refresh())])
            : back()->with('status', 'گفتگو به‌روزرسانی شد.');
    }

    public function togglePin(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('update', $conversation);
        $conversation->update(['is_pinned' => ! $conversation->is_pinned]);

        return response()->json(['conversation' => $this->summary($conversation->refresh())]);
    }

    public function toggleArchive(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('update', $conversation);
        $conversation->update(['archived_at' => $conversation->archived_at === null ? now() : null]);

        return response()->json(['conversation' => $this->summary($conversation->refresh())]);
    }

    public function share(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('update', $conversation);
        $conversation->update([
            'share_token' => $conversation->share_token ?? Str::random(48),
            'shared_at' => now(),
        ]);

        return response()->json(['url' => route('conversations.public', $conversation->share_token)]);
    }

    public function unshare(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('update', $conversation);
        $conversation->update(['share_token' => null, 'shared_at' => null]);

        return response()->json(['message' => 'اشتراک گفتگو غیرفعال شد.']);
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('delete', $conversation);
        Storage::disk('local')->deleteDirectory("chat-attachments/{$conversation->id}");
        $conversation->delete();

        return response()->json(status: 204);
    }

    public function export(Request $request, Conversation $conversation, string $format): Response|View
    {
        Gate::authorize('view', $conversation);
        abort_unless(in_array($format, ['json', 'markdown', 'print'], true), 404);
        $conversation->load(['messages' => fn ($query) => $query->oldest()]);

        if ($format === 'print') {
            return view('conversations.print', compact('conversation'));
        }

        $content = $format === 'json'
            ? json_encode([
                'title' => $conversation->title,
                'model' => $conversation->model,
                'messages' => $conversation->messages->map->toChatArray(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : $conversation->messages->map(
                fn ($message): string => '## '.($message->role === 'user' ? 'کاربر' : 'دستیار')."\n\n{$message->content}",
            )->prepend("# {$conversation->title}")->implode("\n\n---\n\n");

        return response($content ?: '')
            ->header('Content-Type', $format === 'json' ? 'application/json; charset=UTF-8' : 'text/markdown; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="conversation-'.$conversation->id.'.'.($format === 'json' ? 'json' : 'md').'"');
    }

    /** @return array<string, mixed> */
    private function summary(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'folder_id' => $conversation->folder_id,
            'is_pinned' => $conversation->is_pinned,
            'archived_at' => $conversation->archived_at?->toISOString(),
            'system_prompt' => $conversation->system_prompt,
            'reasoning_effort' => $conversation->reasoning_effort,
            'temperature' => $conversation->temperature,
            'web_search' => $conversation->web_search,
            'use_knowledge' => $conversation->use_knowledge,
            'shared_url' => $conversation->share_token === null ? null : route('conversations.public', $conversation->share_token),
        ];
    }
}
