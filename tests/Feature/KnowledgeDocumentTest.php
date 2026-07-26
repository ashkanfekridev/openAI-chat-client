<?php

use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('a user can add and delete a knowledge document', function () {
    Storage::fake('local');
    Http::fake([
        'api.openai.com/v1/files*' => Http::sequence()
            ->push(['id' => 'file_123'])
            ->push([], 200),
        'api.openai.com/v1/vector_stores' => Http::response(['id' => 'vs_123']),
        'api.openai.com/v1/vector_stores/vs_123/files' => Http::response(['id' => 'vs_file_123']),
    ]);
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('knowledge-documents.store'), [
        'document' => UploadedFile::fake()->createWithContent('guide.txt', 'knowledge content'),
    ], ['Accept' => 'application/json'])->assertCreated();

    $document = KnowledgeDocument::query()->firstOrFail();
    expect($document->status)->toBe('ready')
        ->and($user->refresh()->vector_store_id)->toBe('vs_123');
    Storage::disk('local')->assertExists($document->path);

    $this->deleteJson(route('knowledge-documents.destroy', $document))->assertNoContent();
    $this->assertModelMissing($document);
    Storage::disk('local')->assertMissing($document->path);
});

test('a user cannot delete another users document', function () {
    $document = KnowledgeDocument::factory()->create();

    $this->actingAs(User::factory()->create())
        ->deleteJson(route('knowledge-documents.destroy', $document))
        ->assertForbidden();
});
