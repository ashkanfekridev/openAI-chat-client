<div class="contents">
    <p class="mb-2 px-2 text-[11px] font-semibold text-zinc-400 dark:text-zinc-500">گفتگوهای شما</p>

    <div class="mb-2 flex gap-1.5">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="جستجو در گفتگوها..." class="min-w-0 flex-1 rounded-lg border border-zinc-200 bg-white px-2.5 py-2 text-xs outline-none focus:border-zinc-400 dark:border-zinc-800 dark:bg-zinc-900">
        <span wire:loading wire:target="search" class="grid size-8.5 place-items-center text-xs text-zinc-400" aria-label="در حال جستجو">…</span>
    </div>

    <div class="mb-2 flex gap-1 overflow-x-auto pb-1 text-[10px]">
        <button wire:click="selectFolder" type="button" class="shrink-0 rounded-full px-2.5 py-1 {{ $folderId === null && ! $showArchived ? 'bg-zinc-200 dark:bg-zinc-800' : 'border border-zinc-200 dark:border-zinc-800' }}">همه</button>
        @foreach ($folders as $folder)
            <button wire:key="folder-filter-{{ $folder->id }}" wire:click="selectFolder({{ $folder->id }})" type="button" class="shrink-0 rounded-full px-2.5 py-1 {{ $folderId === $folder->id ? 'bg-zinc-200 dark:bg-zinc-800' : 'border border-zinc-200 dark:border-zinc-800' }}">{{ $folder->name }}</button>
        @endforeach
        <button wire:click="toggleArchived" type="button" class="shrink-0 rounded-full px-2.5 py-1 {{ $showArchived ? 'bg-zinc-200 dark:bg-zinc-800' : 'border border-zinc-200 dark:border-zinc-800' }}">آرشیو</button>
    </div>

    <form wire:submit="createFolder" class="mb-2 flex gap-1.5">
        <input wire:model="newFolderName" maxlength="80" placeholder="پوشه جدید..." class="min-w-0 flex-1 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-[11px] outline-none dark:border-zinc-800 dark:bg-zinc-900">
        <button class="rounded-lg border border-zinc-200 px-2.5 text-xs dark:border-zinc-800" aria-label="ساخت پوشه">+</button>
    </form>
    @error('newFolderName') <p class="mb-2 px-2 text-[10px] text-red-500">{{ $message }}</p> @enderror

    @if ($folders->isNotEmpty())
        <details class="mb-2 rounded-lg border border-zinc-200 p-2 text-[11px] dark:border-zinc-800">
            <summary class="cursor-pointer text-zinc-500">مدیریت پوشه‌ها</summary>
            <div class="mt-2 grid gap-2">
                @foreach ($folders as $folder)
                    <div wire:key="folder-editor-{{ $folder->id }}" class="flex gap-1">
                        <form wire:submit="updateFolder({{ $folder->id }})" class="flex min-w-0 flex-1 gap-1">
                            <input wire:model="folderNames.{{ $folder->id }}" class="min-w-0 flex-1 rounded border border-zinc-200 bg-transparent px-2 dark:border-zinc-700">
                            <button class="px-1">ذخیره</button>
                        </form>
                        <button wire:click="deleteFolder({{ $folder->id }})" wire:confirm="این پوشه حذف شود؟" type="button" class="px-1 text-red-500">حذف</button>
                    </div>
                    @error('folderNames.'.$folder->id) <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror
                @endforeach
            </div>
        </details>
    @endif

    <nav data-history class="flex-1 space-y-1 overflow-y-auto" aria-label="تاریخچه گفتگوها">
        @foreach ($conversations as $conversation)
            <a
                wire:key="conversation-{{ $conversation->id }}"
                data-conversation-item
                data-conversation-id="{{ $conversation->id }}"
                href="{{ route('chat', ['conversation' => $conversation->id]) }}"
                @if ($initialConversationId === $conversation->id) aria-current="true" @endif
                class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right transition hover:bg-white dark:hover:bg-zinc-900"
            >
                <svg class="size-4 shrink-0 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8.5 18.5 4 20l1.5-4.5A7.5 7.5 0 1 1 8.5 18.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="min-w-0 flex-1">
                    <span data-history-title class="block truncate text-xs font-medium text-zinc-700 dark:text-zinc-200">{{ $conversation->title }}</span>
                    <span class="mt-0.5 flex items-center gap-1 text-[10px] text-zinc-400 dark:text-zinc-500">
                        @if ($conversation->is_pinned)<span title="سنجاق‌شده">●</span>@endif
                        @if ($conversation->folder)<span>{{ $conversation->folder->name }}</span>@endif
                        <time>{{ $conversation->updated_at->format('Y/m/d') }}</time>
                    </span>
                </span>
            </a>
        @endforeach
    </nav>

    <div data-history-empty @if ($conversations->isNotEmpty()) hidden @endif class="px-3 py-8 text-center text-xs leading-6 text-zinc-400 dark:text-zinc-500">
        گفتگویی با این فیلتر پیدا نشد.
    </div>
</div>
