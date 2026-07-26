<?php

use App\Models\User;

test('the application returns a successful response', function () {
    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertSuccessful();
});
