<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'is_active', 'role', 'allowed_models', 'token_limit', 'quota_period'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<string, bool|int|string>
     */
    protected $attributes = [
        'is_admin' => false,
        'is_active' => true,
        'role' => 'user',
        'quota_period' => 'monthly',
        'input_tokens_used' => 0,
        'output_tokens_used' => 0,
        'total_tokens_used' => 0,
    ];

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** @return HasMany<ConversationFolder, $this> */
    public function conversationFolders(): HasMany
    {
        return $this->hasMany(ConversationFolder::class);
    }

    /** @return HasMany<KnowledgeDocument, $this> */
    public function knowledgeDocuments(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class);
    }

    public function isAdministrator(): bool
    {
        return $this->is_admin || $this->role === 'admin';
    }

    public function canUseModel(string $model): bool
    {
        return $this->allowed_models === null || in_array($model, $this->allowed_models, true);
    }

    public function hasReachedTokenLimit(): bool
    {
        return $this->token_limit !== null && $this->total_tokens_used >= $this->token_limit;
    }

    public function remainingTokens(): ?int
    {
        if ($this->token_limit === null) {
            return null;
        }

        return max(0, $this->token_limit - $this->total_tokens_used);
    }

    public function resetUsagePeriod(): void
    {
        $periodEnd = match ($this->quota_period) {
            'daily' => now()->addDay(),
            'monthly' => now()->addMonth(),
            default => null,
        };

        $this->forceFill([
            'input_tokens_used' => 0,
            'output_tokens_used' => 0,
            'total_tokens_used' => 0,
            'usage_period_started_at' => now(),
            'usage_period_ends_at' => $periodEnd,
        ])->save();
    }

    public function refreshExpiredUsagePeriod(): void
    {
        if ($this->usage_period_ends_at?->isPast()) {
            $this->resetUsagePeriod();
        }
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'allowed_models' => 'array',
            'token_limit' => 'integer',
            'input_tokens_used' => 'integer',
            'output_tokens_used' => 'integer',
            'total_tokens_used' => 'integer',
            'usage_period_started_at' => 'datetime',
            'usage_period_ends_at' => 'datetime',
        ];
    }
}
