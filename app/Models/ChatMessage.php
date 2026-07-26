<?php

namespace App\Models;

use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'content',
        'attachments',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
        ];
    }

    /**
     * @return array{role: string, content: string, attachments: list<array<string, mixed>>}
     */
    public function toChatArray(): array
    {
        $attachments = collect($this->attachments ?? [])
            ->values()
            ->map(function (array $attachment, int $index): array {
                unset($attachment['path']);

                return [
                    ...$attachment,
                    'url' => route('chat.attachments.show', [
                        'conversation' => $this->conversation_id,
                        'chatMessage' => $this->id,
                        'attachment' => $index,
                    ]),
                ];
            })
            ->all();

        return [
            'role' => $this->role,
            'content' => $this->content,
            'attachments' => $attachments,
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
