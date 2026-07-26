<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserUsageRequest;
use App\Http\Requests\Admin\UpdateUserUsageLimitRequest;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->is_admin === true, 403);

        $users = User::query()
            ->withCount('conversations')
            ->latest()
            ->paginate(20);

        $stats = [
            'users' => User::query()->count(),
            'conversations' => Conversation::query()->count(),
            'input_tokens' => (int) User::query()->sum('input_tokens_used'),
            'output_tokens' => (int) User::query()->sum('output_tokens_used'),
            'total_tokens' => (int) User::query()->sum('total_tokens_used'),
            'estimated_cost_micros' => (int) ChatMessage::query()->sum('estimated_cost_micros'),
        ];

        $dailyUsage = ChatMessage::query()
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as usage_date, SUM(total_tokens) as total')
            ->groupBy('usage_date')
            ->orderBy('usage_date')
            ->pluck('total', 'usage_date');

        return view('admin.users.index', compact('users', 'stats', 'dailyUsage'));
    }

    public function update(UpdateUserUsageLimitRequest $request, User $user): RedirectResponse
    {
        abort_if($request->has('is_active') && $request->user()->is($user) && ! $request->boolean('is_active'), 422, 'نمی‌توانید حساب خودتان را غیرفعال کنید.');
        $data = $request->validated();
        if (array_key_exists('role', $data)) {
            $data['is_admin'] = $data['role'] === 'admin';
        }
        $user->update($data);

        if (array_key_exists('quota_period', $data) && $user->wasChanged('quota_period')) {
            $user->resetUsagePeriod();
        }

        return back()->with('status', 'سقف مصرف کاربر به‌روزرسانی شد.');
    }

    public function resetUsage(ResetUserUsageRequest $request, User $user): RedirectResponse
    {
        $user->resetUsagePeriod();

        return back()->with('status', 'مصرف دوره فعلی کاربر صفر شد.');
    }
}
