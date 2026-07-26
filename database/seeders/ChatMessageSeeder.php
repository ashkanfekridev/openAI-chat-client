<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatMessageSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ChatMessage::factory()->count(20)->create();
    }
}
