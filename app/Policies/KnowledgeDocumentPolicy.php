<?php

namespace App\Policies;

use App\Models\KnowledgeDocument;
use App\Models\User;

class KnowledgeDocumentPolicy
{
    public function delete(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return $knowledgeDocument->user_id === $user->id;
    }
}
