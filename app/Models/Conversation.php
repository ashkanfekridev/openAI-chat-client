<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'folder_id',
        'owner_token',
        'title',
        'model',
        'openai_response_id',
        'is_pinned',
        'archived_at',
        'system_prompt',
        'reasoning_effort',
        'temperature',
        'web_search',
        'use_knowledge',
        'share_token',
        'shared_at',
    ];

    /** @var array<string, bool|string> */
    protected $attributes = [
        'is_pinned' => false,
        'reasoning_effort' => 'none',
        'web_search' => false,
        'use_knowledge' => false,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ConversationFolder, $this> */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(ConversationFolder::class, 'folder_id');
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * @param  Builder<Conversation>  $query
     * @return Builder<Conversation>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'archived_at' => 'datetime',
            'temperature' => 'float',
            'web_search' => 'boolean',
            'use_knowledge' => 'boolean',
            'shared_at' => 'datetime',
        ];
    }
}
