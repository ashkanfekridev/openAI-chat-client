<x-auth-layout title="ثبت‌نام">
    <div class="mb-6">
        <h1 class="text-xl font-bold">ساخت حساب</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">گفتگوها و مصرف شما در این حساب نگهداری می‌شود.</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="grid gap-4">
        @csrf

        <label class="grid gap-1.5">
            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">نام</span>
            <input name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="h-12 rounded-xl border border-zinc-200 bg-white px-3.5 text-sm outline-none transition focus:border-zinc-400 focus:ring-4 focus:ring-zinc-100 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
            @error('name') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <label class="grid gap-1.5">
            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">ایمیل</span>
            <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="h-12 rounded-xl border border-zinc-200 bg-white px-3.5 text-sm outline-none transition focus:border-zinc-400 focus:ring-4 focus:ring-zinc-100 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
            @error('email') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <label class="grid gap-1.5">
            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">رمز عبور</span>
            <input name="password" type="password" required autocomplete="new-password" class="h-12 rounded-xl border border-zinc-200 bg-white px-3.5 text-sm outline-none transition focus:border-zinc-400 focus:ring-4 focus:ring-zinc-100 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
            @error('password') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <label class="grid gap-1.5">
            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">تکرار رمز عبور</span>
            <input name="password_confirmation" type="password" required autocomplete="new-password" class="h-12 rounded-xl border border-zinc-200 bg-white px-3.5 text-sm outline-none transition focus:border-zinc-400 focus:ring-4 focus:ring-zinc-100 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
        </label>

        <button type="submit" class="mt-1 h-12 rounded-xl bg-zinc-950 text-sm font-semibold text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200 dark:focus:ring-offset-zinc-900">ساخت حساب</button>
    </form>

    <p class="mt-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
        قبلاً ثبت‌نام کرده‌اید؟
        <a href="{{ route('login') }}" class="font-semibold text-zinc-900 underline underline-offset-4 dark:text-white">وارد شوید</a>
    </p>
</x-auth-layout>
