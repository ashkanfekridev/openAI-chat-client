<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $conversation->title }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white text-zinc-950">
        <main class="mx-auto max-w-3xl px-8 py-12">
            <header class="mb-10 border-b border-zinc-200 pb-6">
                <h1 class="text-3xl font-bold">{{ $conversation->title }}</h1>
                <p class="mt-2 text-sm text-zinc-500">مدل: {{ $conversation->model }}</p>
                <button data-print-page type="button" class="mt-4 rounded-lg bg-zinc-950 px-4 py-2 text-sm font-semibold text-white print:hidden">چاپ یا ذخیره PDF</button>
            </header>

            <section class="grid gap-8">
                @foreach ($conversation->messages as $message)
                    <article>
                        <h2 class="mb-2 text-xs font-bold text-zinc-500">{{ $message->role === 'user' ? 'کاربر' : 'دستیار' }}</h2>
                        <div class="whitespace-pre-wrap text-sm leading-8">{{ $message->content }}</div>
                    </article>
                @endforeach
            </section>
        </main>
    </body>
</html>
