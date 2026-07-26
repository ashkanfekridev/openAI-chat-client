<!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>رمز عبور جدید — گپ</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="grid min-h-screen place-items-center bg-zinc-100 p-4 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
<form method="POST" action="{{ route('password.store') }}" class="grid w-full max-w-md gap-4 rounded-2xl bg-white p-7 shadow-xl dark:bg-zinc-900">@csrf
<input type="hidden" name="token" value="{{ $token }}"><h1 class="text-xl font-bold">انتخاب رمز عبور جدید</h1>
@if($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
<input name="email" type="email" required value="{{ old('email', $email) }}" placeholder="ایمیل" class="rounded-xl border border-zinc-200 bg-transparent px-4 py-3 dark:border-zinc-700">
<input name="password" type="password" required placeholder="رمز عبور جدید" class="rounded-xl border border-zinc-200 bg-transparent px-4 py-3 dark:border-zinc-700"><input name="password_confirmation" type="password" required placeholder="تکرار رمز عبور" class="rounded-xl border border-zinc-200 bg-transparent px-4 py-3 dark:border-zinc-700">
<button class="rounded-xl bg-zinc-950 px-4 py-3 font-semibold text-white dark:bg-white dark:text-zinc-950">ذخیره رمز عبور</button>
</form></body></html>
