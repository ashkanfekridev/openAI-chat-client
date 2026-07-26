<?php

use App\Models\Conversation;
use App\Models\ConversationFolder;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a user can organize and configure their conversation', function () {
    $conversation = Conversation::factory()->for($this->user)->create();
    $folder = ConversationFolder::factory()->for($this->user)->create();

    $this->patchJson(route('conversations.update', $conversation), [
        'title' => 'عنوان جدید',
        'folder_id' => $folder->id,
        'system_prompt' => 'مثل یک برنامه‌نویس پاسخ بده.',
        'reasoning_effort' => 'high',
        'temperature' => 0.4,
        'web_search' => true,
        'use_knowledge' => true,
    ])->assertOk()->assertJsonPath('conversation.title', 'عنوان جدید');

    $this->postJson(route('conversations.pin', $conversation))->assertOk()->assertJsonPath('conversation.is_pinned', true);
    $this->postJson(route('conversations.archive', $conversation))->assertOk();

    expect($conversation->refresh()->folder_id)->toBe($folder->id)
        ->and($conversation->system_prompt)->toBe('مثل یک برنامه‌نویس پاسخ بده.')
        ->and($conversation->archived_at)->not->toBeNull();
});

test('a conversation can be shared publicly and exported', function () {
    $conversation = Conversation::factory()->for($this->user)->create(['title' => 'گفتگوی عمومی']);
    $conversation->messages()->create(['role' => 'assistant', 'content' => 'پاسخ عمومی']);

    $url = $this->postJson(route('conversations.share', $conversation))->assertOk()->json('url');
    $this->get($url)->assertOk()->assertSee('پاسخ عمومی');
    $this->get(route('conversations.export', [$conversation, 'markdown']))
        ->assertOk()
        ->assertHeader('content-type', 'text/markdown; charset=UTF-8');
});

test('another user cannot manage a conversation', function () {
    $conversation = Conversation::factory()->create();

    $this->patchJson(route('conversations.update', $conversation), ['title' => 'غیرمجاز'])
        ->assertForbidden();
});
