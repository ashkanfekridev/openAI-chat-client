@use('Illuminate\Support\Number')

<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>مدیریت کاربران — گپ</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f7f5] text-zinc-900 antialiased transition-colors dark:bg-zinc-950 dark:text-zinc-100">
        <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="grid size-10 place-items-center rounded-xl bg-zinc-950 text-white dark:bg-white dark:text-zinc-950">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 7v5c0 5 3.4 8.5 8 9 4.6-.5 8-4 8-9V7l-8-4Z"/><path d="M9 12h6M12 9v6" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold sm:text-base">پنل مدیریت گپ</h1>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">کاربران و سهمیه مصرف</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('chat') }}" class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">بازگشت به چت</a>
                    <button data-theme-toggle type="button" class="grid size-10 place-items-center rounded-xl border border-zinc-200 bg-white text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="فعال‌کردن حالت تاریک">
                        <svg class="size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.7 6.7 0 0 0 9.8 9.8Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <svg class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42" stroke-linecap="round"/></svg>
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto grid max-w-7xl gap-6 px-4 py-7 sm:px-6 sm:py-10">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">{{ $errors->first() }}</div>
            @endif

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">کاربران</p>
                    <p class="mt-2 text-2xl font-bold">{{ Number::format($stats['users']) }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">گفتگوها</p>
                    <p class="mt-2 text-2xl font-bold">{{ Number::format($stats['conversations']) }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">توکن ورودی</p>
                    <p class="mt-2 text-2xl font-bold">{{ Number::format($stats['input_tokens']) }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">توکن خروجی</p>
                    <p class="mt-2 text-2xl font-bold">{{ Number::format($stats['output_tokens']) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-950 p-4 text-white dark:bg-white dark:text-zinc-950">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">مجموع مصرف</p>
                    <p class="mt-2 text-2xl font-bold">{{ Number::format($stats['total_tokens']) }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-xs text-zinc-500">هزینه تقریبی</p><p class="mt-2 text-2xl font-bold">${{ number_format($stats['estimated_cost_micros'] / 1_000_000, 4) }}</p></div>
            </section>

            @php($chartMaximum = max(1, (int) $dailyUsage->max()))
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-sm font-bold">مصرف ۱۴ روز اخیر</h2>
                <div class="mt-5 grid gap-2" dir="ltr">
                    @foreach ($dailyUsage as $date => $total)
                        <div class="grid grid-cols-[3.5rem_1fr_5rem] items-center gap-2 text-[10px]" title="{{ $date }} — {{ Number::format($total) }} توکن"><span class="text-zinc-400">{{ substr($date, 5) }}</span><progress max="{{ $chartMaximum }}" value="{{ $total }}" class="h-2 w-full accent-zinc-900 dark:accent-white"></progress><span>{{ Number::format($total) }}</span></div>
                    @endforeach
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <h2 class="text-sm font-bold">مدیریت سهمیه کاربران</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">خالی‌گذاشتن سقف به معنی مصرف نامحدود است.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-240 text-right text-xs">
                        <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                            <tr>
                                <th class="px-5 py-3 font-semibold">کاربر</th>
                                <th class="px-4 py-3 font-semibold">گفتگو</th>
                                <th class="px-4 py-3 font-semibold">ورودی</th>
                                <th class="px-4 py-3 font-semibold">خروجی</th>
                                <th class="px-4 py-3 font-semibold">مصرف کل</th>
                                <th class="px-4 py-3 font-semibold">سقف</th>
                                <th class="px-5 py-3 font-semibold">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($users as $managedUser)
                                <tr class="align-middle hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-zinc-100 font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ mb_substr($managedUser->name, 0, 1) }}</span>
                                            <span class="min-w-0">
                                                <span class="flex items-center gap-1.5 font-semibold text-zinc-800 dark:text-zinc-100">
                                                    {{ $managedUser->name }}
                                                    @if ($managedUser->is_admin)
                                                        <span class="rounded-full bg-zinc-900 px-1.5 py-0.5 text-[9px] text-white dark:bg-white dark:text-zinc-900">ادمین</span>
                                                    @endif
                                                </span>
                                                <span class="mt-0.5 block text-[10px] text-zinc-400">{{ $managedUser->email }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">{{ Number::format($managedUser->conversations_count) }}</td>
                                    <td class="px-4 py-4">{{ Number::format($managedUser->input_tokens_used) }}</td>
                                    <td class="px-4 py-4">{{ Number::format($managedUser->output_tokens_used) }}</td>
                                    <td class="px-4 py-4 font-bold">{{ Number::format($managedUser->total_tokens_used) }}</td>
                                    <td class="px-4 py-4">
                                        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="grid min-w-96 grid-cols-2 gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input name="token_limit" type="number" min="1" max="1000000000" value="{{ $managedUser->token_limit }}" placeholder="نامحدود" class="h-9 w-28 rounded-lg border border-zinc-200 bg-white px-2.5 text-xs outline-none focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-950">
                                            <select name="quota_period" class="h-9 rounded-lg border border-zinc-200 bg-transparent px-2 text-xs dark:border-zinc-700"><option value="daily" @selected($managedUser->quota_period === 'daily')>روزانه</option><option value="monthly" @selected($managedUser->quota_period === 'monthly')>ماهانه</option><option value="unlimited" @selected($managedUser->quota_period === 'unlimited')>بدون دوره</option></select>
                                            <select name="role" class="h-9 rounded-lg border border-zinc-200 bg-transparent px-2 text-xs dark:border-zinc-700"><option value="user" @selected($managedUser->role === 'user')>کاربر</option><option value="manager" @selected($managedUser->role === 'manager')>مدیر محتوا</option><option value="admin" @selected($managedUser->isAdministrator())>ادمین</option></select>
                                            <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input name="is_active" value="1" type="checkbox" @checked($managedUser->is_active)> فعال</label>
                                            <div class="col-span-2 flex flex-wrap gap-2">@foreach(config('services.openai.models') as $modelId => $model)<label><input name="allowed_models[]" value="{{ $modelId }}" type="checkbox" @checked($managedUser->allowed_models === null || in_array($modelId, $managedUser->allowed_models, true))> {{ $model['label'] }}</label>@endforeach</div>
                                            <button type="submit" class="col-span-2 h-9 rounded-lg bg-zinc-900 px-3 font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">ذخیره تنظیمات</button>
                                        </form>
                                    </td>
                                    <td class="px-5 py-4">
                                        <form method="POST" action="{{ route('admin.users.usage.reset', $managedUser) }}">
                                            @csrf
                                            <button type="submit" class="h-9 rounded-lg border border-red-200 px-3 font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40">صفرکردن مصرف</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-12 text-center text-zinc-400">کاربری وجود ندارد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">{{ $users->links() }}</div>
                @endif
            </section>
        </main>
    </body>
</html>
