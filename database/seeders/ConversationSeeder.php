<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Conversation::factory()
            ->count(10)
            ->has(ChatMessage::factory()->count(6), 'messages')
            ->create();
    }
}
