<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('an administrator account can be created from the command line', function () {
    $this->artisan('app:create-admin', [
        'email' => 'admin@example.com',
        '--name' => 'مدیر اصلی',
    ])
        ->expectsQuestion('رمز عبور (حداقل ۸ کاراکتر)', 'password123')
        ->expectsOutput('حساب مدیر آماده شد.')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    expect($admin->is_admin)->toBeTrue()
        ->and(Hash::check('password123', $admin->password))->toBeTrue();
});

test('a regular user cannot open the admin panel', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('an admin can view users and aggregate usage', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'name' => 'کاربر آزمایشی',
        'input_tokens_used' => 120,
        'output_tokens_used' => 30,
        'total_tokens_used' => 150,
    ]);
    Conversation::factory()->for($user)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('پنل مدیریت گپ')
        ->assertSee('کاربر آزمایشی')
        ->assertSee('150');
});

test('an admin can set and remove a user token limit', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), ['token_limit' => 5000])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($user->refresh()->token_limit)->toBe(5000);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), ['token_limit' => ''])
        ->assertRedirect();

    expect($user->refresh()->token_limit)->toBeNull();
});

test('an admin can reset current user usage', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'input_tokens_used' => 100,
        'output_tokens_used' => 50,
        'total_tokens_used' => 150,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.usage.reset', $user))
        ->assertRedirect()
        ->assertSessionHas('status');

    $user->refresh();
    expect($user->input_tokens_used)->toBe(0)
        ->and($user->output_tokens_used)->toBe(0)
        ->and($user->total_tokens_used)->toBe(0);
});

test('a regular user cannot change another user token limit', function () {
    $user = User::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.users.update', $user), ['token_limit' => 1000])
        ->assertForbidden();

    expect($user->refresh()->token_limit)->toBeNull();
});

test('an admin can control account role period status and allowed models', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'token_limit' => 10_000,
        'quota_period' => 'daily',
        'role' => 'manager',
        'is_active' => false,
        'allowed_models' => ['gpt-5.6-terra'],
    ])->assertRedirect();

    $user->refresh();
    expect($user->quota_period)->toBe('daily')
        ->and($user->role)->toBe('manager')
        ->and($user->is_active)->toBeFalse()
        ->and($user->allowed_models)->toBe(['gpt-5.6-terra'])
        ->and($user->usage_period_ends_at)->not->toBeNull();
});
