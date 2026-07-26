<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('guests are redirected to the login page', function () {
    $this->get(route('chat'))->assertRedirect(route('login'));
});

test('a user can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'کاربر تازه',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'new@example.com')->firstOrFail();
    expect($user->is_admin)->toBeFalse();
});

test('a user can log in and log out', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password123',
    ]);

    $this->post(route('login.store'), [
        'email' => 'USER@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('chat'));

    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('invalid credentials do not authenticate a user', function () {
    User::factory()->create(['email' => 'user@example.com']);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a password reset link can be requested', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('unverified and inactive users cannot access chat', function () {
    $unverified = User::factory()->unverified()->create();
    $this->actingAs($unverified)->get(route('chat'))->assertRedirect(route('verification.notice'));

    $inactive = User::factory()->create(['is_active' => false]);
    $this->actingAs($inactive)->get(route('chat'))->assertRedirect(route('login'));
    $this->assertGuest();
});
