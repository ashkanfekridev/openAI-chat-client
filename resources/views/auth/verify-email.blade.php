<!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>تأیید ایمیل — گپ</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="grid min-h-screen place-items-center bg-zinc-100 p-4 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100"><main class="grid w-full max-w-md gap-4 rounded-2xl bg-white p-7 text-center shadow-xl dark:bg-zinc-900">
<h1 class="text-xl font-bold">ایمیل خود را تأیید کنید</h1><p class="text-sm leading-7 text-zinc-500">لینک تأیید برای شما ارسال شد. پس از تأیید، پنل چت در دسترس خواهد بود.</p>@if(session('status'))<p class="text-sm text-emerald-600">{{ session('status') }}</p>@endif
<form method="POST" action="{{ route('verification.send') }}">@csrf<button class="w-full rounded-xl bg-zinc-950 px-4 py-3 font-semibold text-white dark:bg-white dark:text-zinc-950">ارسال دوباره لینک</button></form>
<form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-zinc-500">خروج</button></form>
</main></body></html>
