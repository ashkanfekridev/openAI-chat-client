<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        Conversation $conversation,
        ChatMessage $chatMessage,
        int $attachment,
    ): StreamedResponse {
        abort_unless($conversation->user_id === $request->user()?->id, 404);
        abort_unless($chatMessage->conversation_id === $conversation->id, 404);

        $metadata = ($chatMessage->attachments ?? [])[$attachment] ?? null;
        abort_unless(is_array($metadata) && is_string($metadata['path'] ?? null), 404);
        abort_unless(str_starts_with($metadata['path'], "chat-attachments/{$conversation->id}/"), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($metadata['path']), 404);

        if (($metadata['kind'] ?? null) !== 'image') {
            return $disk->download($metadata['path'], $metadata['name'] ?? null, [
                'Content-Type' => $metadata['mime_type'] ?? 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return $disk->response($metadata['path'], null, [
            'Content-Type' => $metadata['mime_type'] ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
