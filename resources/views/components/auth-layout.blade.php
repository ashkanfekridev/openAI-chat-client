@props(['title'])

<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} — گپ</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f7f5] text-zinc-900 antialiased transition-colors dark:bg-zinc-950 dark:text-zinc-100">
        <main class="grid min-h-screen place-items-center px-4 py-10">
            <button data-theme-toggle type="button" class="fixed left-5 top-5 grid size-10 place-items-center rounded-xl border border-zinc-200 bg-white text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="فعال‌کردن حالت تاریک">
                <svg class="size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.7 6.7 0 0 0 9.8 9.8Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <svg class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42" stroke-linecap="round"/></svg>
            </button>

            <section class="w-full max-w-md">
                <div class="mb-7 text-center">
                    <div class="mx-auto mb-4 grid size-12 place-items-center rounded-2xl bg-zinc-950 text-white shadow-lg dark:bg-white dark:text-zinc-950">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8.5 19.5 4 21l1.5-4.5A8 8 0 1 1 8.5 19.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <p class="text-xl font-bold">گپ</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">دستیار هوشمند OpenAI</p>
                </div>

                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-[0_20px_60px_rgba(24,24,27,0.08)] dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black/20 sm:p-8">
                    {{ $slot }}
                </div>
            </section>
        </main>
    </body>
</html>
