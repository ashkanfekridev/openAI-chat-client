<?php

namespace Database\Factories;

use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeDocument>
 */
class KnowledgeDocumentFactory extends Factory
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
            'name' => fake()->word().'.txt',
            'mime_type' => 'text/plain',
            'size' => fake()->numberBetween(100, 10_000),
            'path' => 'knowledge-documents/'.fake()->uuid().'.txt',
            'status' => 'ready',
        ];
    }
}
