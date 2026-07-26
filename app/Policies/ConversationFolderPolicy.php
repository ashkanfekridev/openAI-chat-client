<?php

namespace App\Policies;

use App\Models\ConversationFolder;
use App\Models\User;

class ConversationFolderPolicy
{
    public function update(User $user, ConversationFolder $conversationFolder): bool
    {
        return $conversationFolder->user_id === $user->id;
    }

    public function delete(User $user, ConversationFolder $conversationFolder): bool
    {
        return $this->update($user, $conversationFolder);
    }
}
