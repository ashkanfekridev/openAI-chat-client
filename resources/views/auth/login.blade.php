<x-auth-layout title="ورود">
    <div class="mb-6">
        <h1 class="text-xl font-bold">ورود به حساب</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">برای دسترسی به گفتگوها وارد شوید.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="grid gap-4">
        @csrf

        <label class="grid gap-1.5">
            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">ایمیل</span>
            <input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="h-12 rounded-xl border border-zinc-200 bg-white px-3.5 text-sm outline-none transition focus:border-zinc-400 focus:ring-4 focus:ring-zinc-100 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
            @error('email') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <label class="grid gap-1.5">
            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">رمز عبور</span>
            <input name="password" type="password" required autocomplete="current-password" class="h-12 rounded-xl border border-zinc-200 bg-white px-3.5 text-sm outline-none transition focus:border-zinc-400 focus:ring-4 focus:ring-zinc-100 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
            @error('password') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <a href="{{ route('password.request') }}" class="text-xs text-zinc-500 underline underline-offset-4">رمز عبور را فراموش کرده‌اید؟</a>

        <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
            <input name="remember" type="checkbox" value="1" class="size-4 rounded border-zinc-300 text-zinc-950 focus:ring-zinc-400 dark:border-zinc-600 dark:bg-zinc-800">
            مرا به خاطر بسپار
        </label>

        <button type="submit" class="mt-1 h-12 rounded-xl bg-zinc-950 text-sm font-semibold text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200 dark:focus:ring-offset-zinc-900">ورود</button>
    </form>

    <p class="mt-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
        حساب ندارید؟
        <a href="{{ route('register') }}" class="font-semibold text-zinc-900 underline underline-offset-4 dark:text-white">ثبت‌نام کنید</a>
    </p>
</x-auth-layout>
