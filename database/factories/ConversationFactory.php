<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'owner_token' => fake()->uuid(),
            'title' => fake()->sentence(4),
            'model' => fake()->randomElement(array_keys(config('services.openai.models'))),
            'openai_response_id' => 'resp_'.Str::random(24),
        ];
    }
}
