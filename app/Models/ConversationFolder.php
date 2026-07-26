<?php

namespace App\Models;

use Database\Factories\ConversationFolderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversationFolder extends Model
{
    /** @use HasFactory<ConversationFolderFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['user_id', 'name', 'color'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'folder_id');
    }
}
