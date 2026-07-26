<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'role' => fake()->randomElement(['user', 'assistant']),
            'content' => fake()->paragraph(),
            'attachments' => null,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
        ];
    }
}
