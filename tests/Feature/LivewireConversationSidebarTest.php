<?php

use App\Livewire\ConversationSidebar;
use App\Models\Conversation;
use App\Models\ConversationFolder;
use App\Models\User;
use Livewire\Livewire;

test('the livewire sidebar searches only the authenticated user conversations', function () {
    $user = User::factory()->create();
    Conversation::factory()->for($user)->create(['title' => 'گفتگوی لاراول']);
    Conversation::factory()->for($user)->create(['title' => 'گفتگوی طراحی']);
    Conversation::factory()->create(['title' => 'گفتگوی کاربر دیگر']);

    Livewire::actingAs($user)
        ->test(ConversationSidebar::class)
        ->assertSee('گفتگوی لاراول')
        ->assertSee('گفتگوی طراحی')
        ->assertDontSee('گفتگوی کاربر دیگر')
        ->set('search', 'لاراول')
        ->assertSee('گفتگوی لاراول')
        ->assertDontSee('گفتگوی طراحی');
});

test('folders can be managed from the livewire sidebar', function () {
    $user = User::factory()->create();
    $folder = ConversationFolder::factory()->for($user)->create(['name' => 'قدیمی']);

    $component = Livewire::actingAs($user)
        ->test(ConversationSidebar::class)
        ->set('newFolderName', 'پروژه جدید')
        ->call('createFolder')
        ->assertHasNoErrors()
        ->assertSee('پروژه جدید')
        ->set("folderNames.{$folder->id}", 'نام تازه')
        ->call('updateFolder', $folder->id)
        ->assertHasNoErrors()
        ->assertSee('نام تازه');

    expect($folder->refresh()->name)->toBe('نام تازه');

    $component->call('deleteFolder', $folder->id)->assertHasNoErrors();
    $this->assertModelMissing($folder);
});
