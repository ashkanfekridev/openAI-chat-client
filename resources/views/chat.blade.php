<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>گپ — دستیار هوشمند</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-[#f7f7f5] text-zinc-900 antialiased transition-colors dark:bg-zinc-950 dark:text-zinc-100">
        <div
            data-chat
            data-chat-url="{{ route('chat') }}"
            data-speech-url="{{ route('speech.transcribe') }}"
            data-conversation-url="{{ route('conversations.show', ['conversation' => '__CONVERSATION__']) }}"
            data-conversation-update-url="{{ route('conversations.update', ['conversation' => '__CONVERSATION__']) }}"
            data-conversation-pin-url="{{ route('conversations.pin', ['conversation' => '__CONVERSATION__']) }}"
            data-conversation-archive-url="{{ route('conversations.archive', ['conversation' => '__CONVERSATION__']) }}"
            data-conversation-share-url="{{ route('conversations.share', ['conversation' => '__CONVERSATION__']) }}"
            data-conversation-delete-url="{{ route('conversations.destroy', ['conversation' => '__CONVERSATION__']) }}"
            data-conversation-export-url="{{ route('conversations.export', ['conversation' => '__CONVERSATION__', 'format' => '__FORMAT__']) }}"
            data-initial-conversation-id="{{ $initialConversationId }}"
            class="mx-auto flex h-dvh
{{--            max-w-7xl--}}
             overflow-hidden bg-white shadow-[0_0_60px_rgba(24,24,27,0.06)] transition-colors dark:bg-zinc-900 dark:shadow-[0_0_60px_rgba(0,0,0,0.25)]"
        >
            <button data-sidebar-backdrop type="button" hidden class="fixed inset-0 z-20 bg-zinc-950/30 backdrop-blur-sm md:hidden" aria-label="بستن تاریخچه"></button>

            <aside data-sidebar class="fixed inset-y-0 right-0 z-30 flex w-72 translate-x-full flex-col border-l border-zinc-200 bg-[#f5f5f3] p-3 transition duration-200 dark:border-zinc-800 dark:bg-zinc-950 md:static md:z-auto md:w-72 md:translate-x-0">
                <div class="mb-3 flex items-center justify-between px-2 py-2">
                    <div class="flex items-center gap-3">
                        <div class="grid size-9 place-items-center rounded-xl bg-zinc-950 text-white dark:bg-white dark:text-zinc-950">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M8.5 19.5 4 21l1.5-4.5A8 8 0 1 1 8.5 19.5Z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold">گپ</p>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">تاریخچه گفتگوها</p>
                        </div>
                    </div>
                    <button data-sidebar-close type="button" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-200 dark:text-zinc-400 dark:hover:bg-zinc-800 md:hidden" aria-label="بستن">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                    </button>
                </div>

                <button data-new-chat type="button" class="mb-4 flex w-full items-center justify-center gap-2 rounded-xl bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200 dark:focus:ring-offset-zinc-950">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
                    </svg>
                    گفت‌وگوی تازه
                </button>

                <livewire:conversation-sidebar :$initialConversationId />

                <div
                    data-usage
                    data-input="{{ $usage['input'] }}"
                    data-output="{{ $usage['output'] }}"
                    data-used="{{ $usage['used'] }}"
                    data-limit="{{ $usage['limit'] }}"
                    class="mt-3 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="font-semibold text-zinc-700 dark:text-zinc-200">مصرف توکن</span>
                        <span class="text-zinc-500 dark:text-zinc-400"><span data-usage-used></span> / <span data-usage-limit></span></span>
                    </div>
                    <div data-usage-track class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div data-usage-progress class="h-full rounded-full bg-zinc-900 transition-all dark:bg-white"></div>
                    </div>
                    <div class="mt-2 flex justify-between gap-2 text-[10px] text-zinc-400">
                        <span>ورودی: <span data-usage-input></span></span>
                        <span>خروجی: <span data-usage-output></span></span>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2 border-t border-zinc-200 px-2 pt-3 dark:border-zinc-800">
                    <div class="grid size-9 shrink-0 place-items-center rounded-full bg-zinc-200 text-xs font-bold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold">{{ $user->name }}</p>
                        <p class="truncate text-[10px] text-zinc-400">{{ $user->email }}</p>
                    </div>
                    @if ($user->is_admin)
                        <a href="{{ route('admin.users.index') }}" class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white" title="پنل مدیریت" aria-label="پنل مدیریت">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 7v5c0 5 3.4 8.5 8 9 4.6-.5 8-4 8-9V7l-8-4Z"/><path d="M9 12h6M12 9v6" stroke-linecap="round"/></svg>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg p-2 text-zinc-500 transition hover:bg-red-50 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-red-950/40 dark:hover:text-red-300" title="خروج" aria-label="خروج">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </form>
                </div>
            </aside>

            <main class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-18 shrink-0 items-center justify-between border-b border-zinc-200/80 px-4 transition-colors dark:border-zinc-800 sm:px-7">
                    <div class="flex min-w-0 items-center gap-3">
                        <button data-sidebar-open type="button" class="rounded-xl border border-zinc-200 p-2.5 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300 md:hidden" aria-label="نمایش تاریخچه">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                        </button>
                        <div class="min-w-0">
                            <h1 data-current-title class="truncate text-sm font-bold tracking-tight sm:text-base">{{ $initialConversation?->title ?? 'گفت‌وگوی تازه' }}</h1>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">دستیار هوشمند OpenAI</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div data-conversation-actions hidden class="flex items-center gap-1">
                            <button data-conversation-settings type="button" class="grid size-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="تنظیمات گفتگو" aria-label="تنظیمات گفتگو">⚙</button>
                            <button data-conversation-pin type="button" class="grid size-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="سنجاق" aria-label="سنجاق">⌖</button>
                            <button data-conversation-share type="button" class="grid size-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="اشتراک‌گذاری" aria-label="اشتراک‌گذاری">↗</button>
                            <details class="relative">
                                <summary class="grid size-9 cursor-pointer list-none place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">•••</summary>
                                <div class="absolute end-0 top-11 z-20 grid w-44 gap-1 rounded-xl border border-zinc-200 bg-white p-1.5 text-xs shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                    <button data-conversation-archive type="button" class="rounded-lg px-3 py-2 text-right hover:bg-zinc-100 dark:hover:bg-zinc-800">آرشیو / بازگردانی</button>
                                    <a data-export-markdown class="rounded-lg px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">خروجی Markdown</a>
                                    <a data-export-json class="rounded-lg px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">خروجی JSON</a>
                                    <a data-export-print target="_blank" class="rounded-lg px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">چاپ / PDF</a>
                                    <button data-conversation-delete type="button" class="rounded-lg px-3 py-2 text-right text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">حذف گفتگو</button>
                                </div>
                            </details>
                        </div>
                        <button data-theme-toggle type="button" class="grid size-10 place-items-center rounded-xl border border-zinc-200 bg-white text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="فعال‌کردن حالت تاریک" title="حالت تاریک">
                            <svg class="size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.7 6.7 0 0 0 9.8 9.8Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <svg class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42" stroke-linecap="round"/></svg>
                        </button>
                        <button data-new-chat type="button" class="hidden items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800 md:inline-flex">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                            گفت‌وگوی تازه
                        </button>
                    </div>
                </header>

                <section data-messages class="relative flex-1 space-y-6 overflow-y-auto px-4 py-8 sm:px-8 sm:py-10" aria-live="polite">
                    <div data-empty class="absolute inset-0 grid place-items-center px-6 text-center">
                        <div class="mb-12 max-w-lg">
                            <div class="mx-auto mb-5 grid size-14 place-items-center rounded-2xl bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100">
                                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 3 9.8 8.8 4 11l5.8 2.2L12 19l2.2-5.8L20 11l-5.8-2.2L12 3Z" stroke-linejoin="round"/></svg>
                            </div>
                            <h2 class="text-xl font-bold tracking-tight sm:text-2xl">چه کمکی از من برمیاد؟</h2>
                            <p class="mt-2 text-sm leading-7 text-zinc-500 dark:text-zinc-400">سؤال بپرس، ایده‌پردازی کن یا برای نوشتن و برنامه‌نویسی کمک بگیر.</p>
                        </div>
                    </div>
                </section>

                <div class="shrink-0 px-3 pb-3 sm:px-7 sm:pb-6">
                    <p data-error hidden role="alert" class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300"></p>

                    <form data-chat-form method="POST" action="{{ route('chat.send') }}" class="rounded-2xl border border-zinc-200 bg-white p-2 shadow-[0_8px_30px_rgba(24,24,27,0.08)] transition focus-within:border-zinc-300 focus-within:ring-4 focus-within:ring-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-[0_8px_30px_rgba(0,0,0,0.25)] dark:focus-within:border-zinc-600 dark:focus-within:ring-zinc-800">
                        @csrf
                        <div data-file-preview hidden class="flex flex-wrap gap-2 px-2 pt-2"></div>
                        <label for="message" class="sr-only">پیام شما</label>
                        <textarea id="message" name="message" rows="1" maxlength="4000" autofocus placeholder="پیامت را بنویس..." class="block max-h-40 min-h-12 w-full resize-none border-0 bg-transparent px-3 py-3 text-sm leading-6 text-zinc-900 outline-none placeholder:text-zinc-400 disabled:opacity-60 dark:text-zinc-100 dark:placeholder:text-zinc-500 sm:text-base"></textarea>
                        <div class="flex items-center justify-between gap-2 px-1 pb-1">
                            <div class="flex min-w-0 items-center gap-1.5">
                                <label class="grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" title="افزودن فایل یا تصویر">
                                    <span class="sr-only">افزودن فایل یا تصویر</span>
                                    <input data-files type="file" multiple class="hidden" accept="image/png,image/jpeg,image/webp,image/gif,.pdf,.txt,.md,.json,.html,.xml,.csv,.xls,.xlsx,.doc,.docx,.ppt,.pptx">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m21.4 11.6-8.9 8.9a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </label>
                                <button data-image-mode type="button" aria-pressed="false" class="flex h-9 shrink-0 items-center gap-1.5 rounded-lg px-2.5 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" title="ساخت تصویر">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span class="hidden sm:inline">ساخت تصویر</span>
                                </button>
                                <button data-voice-input type="button" class="grid size-9 shrink-0 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="ورودی صوتی" aria-label="شروع ضبط صدا">🎙</button>
                                <label class="relative min-w-0">
                                    <span class="sr-only">انتخاب مدل</span>
                                    <select data-model class="h-9 max-w-36 appearance-none rounded-lg border-0 bg-zinc-100 py-0 ps-2.5 pe-7 text-xs font-medium text-zinc-700 outline-none ring-0 dark:bg-zinc-800 dark:text-zinc-200 sm:max-w-48">
                                        @foreach ($models as $modelId => $model)
                                            <option value="{{ $modelId }}" @selected($modelId === $defaultModel)>{{ $model['label'] }} — {{ $model['description'] }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="pointer-events-none absolute end-2 top-1/2 size-3 -translate-y-1/2 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 10 5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </label>
                            </div>
                            <button data-submit type="submit" class="grid min-w-12 place-items-center rounded-xl bg-zinc-950 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200 dark:focus:ring-offset-zinc-900">
                                <span data-send-label class="flex items-center gap-2">
                                    <span class="hidden sm:inline">ارسال</span>
                                    <svg class="size-4 rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span data-loading-label hidden class="size-4 animate-spin rounded-full border-2 border-white/30 border-t-white" aria-label="در حال ارسال"></span>
                            </button>
                            <button data-stop hidden type="button" class="rounded-xl bg-red-600 px-3 py-2.5 text-xs font-semibold text-white">توقف</button>
                        </div>
                    </form>
                    <p class="mt-2 text-center text-[11px] text-zinc-400">پاسخ‌های هوش مصنوعی ممکن است دقیق نباشند.</p>
                </div>
            </main>
        </div>

        <dialog data-conversation-dialog class="m-auto w-[min(92vw,34rem)] rounded-2xl border border-zinc-200 bg-white p-0 text-zinc-900 shadow-2xl backdrop:bg-zinc-950/50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            <div data-conversation-settings-form class="grid gap-4 p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold">تنظیمات گفتگو</h2>
                    <button data-settings-close type="button" class="text-xl text-zinc-400" aria-label="بستن">×</button>
                </div>
                <label class="grid gap-1.5 text-xs font-semibold">عنوان<input data-settings-title class="rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 dark:border-zinc-700"></label>
                <label class="grid gap-1.5 text-xs font-semibold">پوشه<select data-settings-folder class="rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 dark:border-zinc-700"><option value="">بدون پوشه</option>@foreach ($folders as $folder)<option value="{{ $folder->id }}">{{ $folder->name }}</option>@endforeach</select></label>
                <label class="grid gap-1.5 text-xs font-semibold">System Prompt<textarea data-settings-prompt rows="4" class="rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 dark:border-zinc-700"></textarea></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="grid gap-1.5 text-xs font-semibold">Reasoning<select data-settings-reasoning class="rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 dark:border-zinc-700"><option value="none">بدون reasoning</option><option value="low">کم</option><option value="medium">متوسط</option><option value="high">زیاد</option></select></label>
                    <label class="grid gap-1.5 text-xs font-semibold">Temperature<input data-settings-temperature type="number" min="0" max="2" step="0.1" class="rounded-xl border border-zinc-200 bg-transparent px-3 py-2.5 dark:border-zinc-700"></label>
                </div>
                <label class="flex items-center gap-2 text-xs"><input data-settings-web type="checkbox" class="size-4"> جستجوی وب و منابع</label>
                <label class="flex items-center gap-2 text-xs"><input data-settings-knowledge type="checkbox" class="size-4"> استفاده از کتابخانه اسناد</label>
                <section class="grid gap-2 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="flex items-center justify-between gap-2"><h3 class="text-xs font-bold">کتابخانه اسناد</h3><span class="text-[10px] text-zinc-400">RAG / File Search</span></div>
                    <form method="POST" action="{{ route('knowledge-documents.store') }}" enctype="multipart/form-data" class="flex gap-2">
                        @csrf
                        <input name="document" type="file" required class="min-w-0 flex-1 text-xs">
                        <button class="rounded-lg bg-zinc-100 px-3 py-2 text-xs dark:bg-zinc-800">آپلود</button>
                    </form>
                    @foreach ($documents as $document)
                        <div class="flex items-center gap-2 text-[11px]">
                            <span class="min-w-0 flex-1 truncate">{{ $document->name }}</span>
                            <span class="text-zinc-400">{{ $document->status }}</span>
                            <form method="POST" action="{{ route('knowledge-documents.destroy', $document) }}">@csrf @method('DELETE')<button class="text-red-500">حذف</button></form>
                        </div>
                    @endforeach
                </section>
                <button data-settings-save type="button" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">ذخیره تنظیمات</button>
            </div>
        </dialog>

        <template data-conversation-template>
            <a data-conversation-item class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right transition hover:bg-white dark:hover:bg-zinc-900">
                <svg class="size-4 shrink-0 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8.5 18.5 4 20l1.5-4.5A7.5 7.5 0 1 1 8.5 18.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="min-w-0 flex-1">
                    <span data-history-title class="block truncate text-xs font-medium text-zinc-700 dark:text-zinc-200"></span>
                    <time class="mt-0.5 block text-[10px] text-zinc-400 dark:text-zinc-500">همین حالا</time>
                </span>
            </a>
        </template>

        <template data-user-template>
            <article data-message class="flex justify-start">
                <div class="max-w-[85%] rounded-2xl rounded-bl-md bg-zinc-950 px-3 py-3 text-white dark:bg-white dark:text-zinc-950 sm:max-w-[75%]">
                    <div data-attachments class="mb-2 flex flex-wrap gap-2 empty:hidden"></div>
                    <div class="whitespace-pre-wrap px-1 text-sm leading-7 empty:hidden sm:text-base" data-content></div>
                    <div class="mt-2 flex gap-2 text-[10px] opacity-60"><button data-copy-message type="button">کپی</button><button data-resend-message type="button">ویرایش / ارسال مجدد</button></div>
                </div>
            </article>
        </template>

        <template data-assistant-template>
            <article data-message class="flex items-start gap-3">
                <div class="mt-1 grid size-8 shrink-0 place-items-center rounded-xl bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" aria-hidden="true">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 9.8 8.8 4 11l5.8 2.2L12 19l2.2-5.8L20 11l-5.8-2.2L12 3Z" stroke-linejoin="round"/></svg>
                </div>
                <div class="max-w-[85%] sm:max-w-[78%]">
                    <div data-attachments class="mb-3 grid gap-2 empty:hidden"></div>
                    <div class="markdown-content text-sm leading-8 text-zinc-700 empty:hidden dark:text-zinc-300 sm:text-base" data-content></div>
                    <div data-citations class="mt-3 flex flex-wrap gap-1.5 empty:hidden"></div>
                    <div class="mt-2 flex gap-3 text-[10px] text-zinc-400"><button data-copy-message type="button">کپی</button><button data-speak-message type="button">خواندن</button><button data-resend-message type="button">تولید مجدد</button></div>
                </div>
            </article>
        </template>

        <template data-image-attachment-template>
            <a data-attachment-link target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-zinc-200/70 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                <img data-attachment-image class="max-h-80 w-auto max-w-full object-contain" alt="تصویر پیوست">
            </a>
        </template>

        <template data-file-attachment-template>
            <a data-attachment-link target="_blank" rel="noopener" class="flex max-w-64 items-center gap-2 rounded-xl bg-zinc-100 px-3 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span data-attachment-name class="truncate"></span>
            </a>
        </template>

        <template data-file-preview-template>
            <span data-preview-item class="flex max-w-52 items-center gap-2 rounded-lg bg-zinc-100 px-2.5 py-1.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <span data-preview-name class="truncate"></span>
                <button data-remove-file type="button" class="shrink-0 text-zinc-400 hover:text-red-500" aria-label="حذف فایل">×</button>
            </span>
        </template>
        @livewireScripts
    </body>
</html>
