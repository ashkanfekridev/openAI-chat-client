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
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'openai_response_id',
        'citations',
        'estimated_cost_micros',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'total_tokens' => 'integer',
            'citations' => 'array',
            'estimated_cost_micros' => 'integer',
        ];
    }

    /**
     * @return array{id: int, role: string, content: string, attachments: list<array<string, mixed>>, citations: list<array<string, mixed>>, estimated_cost_micros: int}
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
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'attachments' => $attachments,
            'citations' => $this->citations ?? [],
            'estimated_cost_micros' => $this->estimated_cost_micros,
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
