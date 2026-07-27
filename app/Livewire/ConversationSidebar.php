<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\ConversationFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class ConversationSidebar extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'archived')]
    public bool $showArchived = false;

    #[Url(as: 'folder')]
    public ?int $folderId = null;

    public ?string $initialConversationId = null;

    public string $newFolderName = '';

    /** @var array<int, string> */
    public array $folderNames = [];

    public function mount(?string $initialConversationId = null): void
    {
        $this->initialConversationId = $initialConversationId;
        $this->syncFolderNames();
    }

    public function createFolder(): void
    {
        $user = $this->user();
        $validated = $this->validate([
            'newFolderName' => [
                'required',
                'string',
                'max:80',
                Rule::unique(ConversationFolder::class, 'name')->where('user_id', $user->id),
            ],
        ]);

        $user->conversationFolders()->create([
            'name' => $validated['newFolderName'],
            'color' => 'zinc',
        ]);
        $this->newFolderName = '';
        $this->syncFolderNames();
    }

    public function updateFolder(int $folderId): void
    {
        $folder = $this->ownedFolder($folderId);
        Gate::authorize('update', $folder);
        $validated = $this->validate([
            "folderNames.{$folderId}" => [
                'required',
                'string',
                'max:80',
                Rule::unique(ConversationFolder::class, 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($folder),
            ],
        ]);

        $folder->update(['name' => $validated['folderNames'][$folderId]]);
        $this->syncFolderNames();
    }

    public function deleteFolder(int $folderId): void
    {
        $folder = $this->ownedFolder($folderId);
        Gate::authorize('delete', $folder);
        $folder->delete();

        if ($this->folderId === $folderId) {
            $this->folderId = null;
        }

        $this->syncFolderNames();
    }

    public function selectFolder(?int $folderId = null): void
    {
        if ($folderId !== null) {
            $this->ownedFolder($folderId);
        }

        $this->folderId = $folderId;
        $this->showArchived = false;
    }

    public function toggleArchived(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->folderId = null;
    }

    public function render(): View
    {
        $user = $this->user();
        $conversations = Conversation::query()
            ->whereBelongsTo($user)
            ->with('folder:id,name,color')
            ->when(
                $this->showArchived,
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->whereNull('archived_at'),
            )
            ->when($this->search !== '', function ($query): void {
                $search = trim($this->search);
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('messages', fn ($query) => $query->where('content', 'like', "%{$search}%"));
                });
            })
            ->when($this->folderId !== null, fn ($query) => $query->where('folder_id', $this->folderId))
            ->orderByDesc('is_pinned')
            ->latest('updated_at')
            ->get(['id', 'folder_id', 'title', 'is_pinned', 'archived_at', 'updated_at']);

        $folders = $user->conversationFolders()->orderBy('name')->get(['id', 'name', 'color']);

        return view('livewire.conversation-sidebar', compact('conversations', 'folders'));
    }

    private function user(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function ownedFolder(int $folderId): ConversationFolder
    {
        return ConversationFolder::query()
            ->whereBelongsTo($this->user())
            ->findOrFail($folderId);
    }

    private function syncFolderNames(): void
    {
        /** @var Collection<int, ConversationFolder> $folders */
        $folders = $this->user()->conversationFolders()->get(['id', 'name']);
        $this->folderNames = $folders->pluck('name', 'id')->all();
    }
}
