<!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>بازیابی رمز عبور — گپ</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="grid min-h-screen place-items-center bg-zinc-100 p-4 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
<form method="POST" action="{{ route('password.email') }}" class="grid w-full max-w-md gap-4 rounded-2xl bg-white p-7 shadow-xl dark:bg-zinc-900">@csrf
<h1 class="text-xl font-bold">بازیابی رمز عبور</h1><p class="text-sm text-zinc-500">لینک انتخاب رمز جدید به ایمیل شما ارسال می‌شود.</p>
@if(session('status'))<p class="text-sm text-emerald-600">{{ session('status') }}</p>@endif
@error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
<input name="email" type="email" required value="{{ old('email') }}" placeholder="ایمیل" class="rounded-xl border border-zinc-200 bg-transparent px-4 py-3 dark:border-zinc-700">
<button class="rounded-xl bg-zinc-950 px-4 py-3 font-semibold text-white dark:bg-white dark:text-zinc-950">ارسال لینک</button><a href="{{ route('login') }}" class="text-center text-sm text-zinc-500">بازگشت به ورود</a>
</form></body></html>
