<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $conversation->title }} — گپ</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f7f5] text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <main class="mx-auto max-w-3xl px-4 py-10 sm:py-16">
            <header class="mb-8 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold text-zinc-400">گفتگوی اشتراکی گپ</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ $conversation->title }}</h1>
                </div>
                <button data-theme-toggle type="button" class="grid size-10 place-items-center rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" aria-label="تغییر پوسته">◐</button>
            </header>

            <section class="grid gap-6">
                @foreach ($conversation->messages as $message)
                    <article class="{{ $message->role === 'user' ? 'ms-auto max-w-[85%] rounded-2xl bg-zinc-950 px-4 py-3 text-white dark:bg-white dark:text-zinc-950' : 'rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900' }}">
                        <p class="mb-2 text-[10px] font-bold opacity-50">{{ $message->role === 'user' ? 'کاربر' : 'دستیار' }}</p>
                        <div class="whitespace-pre-wrap text-sm leading-8">{{ $message->content }}</div>
                    </article>
                @endforeach
            </section>
        </main>
    </body>
</html>
