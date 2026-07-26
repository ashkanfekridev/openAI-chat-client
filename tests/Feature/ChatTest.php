<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    config()->set('services.openai.api_key', 'test-key');
    config()->set('services.openai.model', 'gpt-5.6-sol');
    $this->ownerToken = (string) Str::uuid();
    $this->withCredentials();
});

test('the chat page displays only the current browser history', function () {
    $visibleConversation = Conversation::factory()->create([
        'owner_token' => $this->ownerToken,
        'title' => 'گفتگوی قابل مشاهده',
    ]);
    Conversation::factory()->create(['title' => 'گفتگوی مرورگر دیگر']);

    $this->withCookie('chat_owner_token', $this->ownerToken)
        ->get(route('chat'))
        ->assertOk()
        ->assertSee('data-theme-toggle', false)
        ->assertSee('GPT-5.6 Terra')
        ->assertSee($visibleConversation->title)
        ->assertDontSee('گفتگوی مرورگر دیگر');
});

test('a saved conversation can be opened', function () {
    $conversation = Conversation::factory()->create(['owner_token' => $this->ownerToken]);
    ChatMessage::factory()->for($conversation)->create([
        'role' => 'user',
        'content' => 'پیام ذخیره‌شده',
    ]);

    $this->withCookie('chat_owner_token', $this->ownerToken)
        ->getJson(route('conversations.show', $conversation))
        ->assertOk()
        ->assertJsonPath('conversation.id', $conversation->id)
        ->assertJsonPath('messages.0.content', 'پیام ذخیره‌شده');
});

test('another browser cannot open a conversation', function () {
    $conversation = Conversation::factory()->create();

    $this->withCookie('chat_owner_token', $this->ownerToken)
        ->getJson(route('conversations.show', $conversation))
        ->assertNotFound();
});

test('a message creates a saved conversation', function () {
    Http::fake([
        'api.openai.com/v1/responses' => Http::response(openAIResponse()),
    ]);

    $response = $this->withCookie('chat_owner_token', $this->ownerToken)
        ->postJson(route('chat.send'), chatPayload(['message' => 'سلام، حالت چطوره؟']))
        ->assertOk()
        ->assertJsonPath('message', 'سلام! چطور می‌توانم کمک کنم؟')
        ->assertJsonPath('conversation.title', 'سلام، حالت چطوره؟');

    $conversationId = $response->json('conversation.id');

    $this->assertDatabaseHas('conversations', [
        'id' => $conversationId,
        'owner_token' => $this->ownerToken,
        'model' => 'gpt-5.6-sol',
        'openai_response_id' => 'resp_123',
    ]);
    $this->assertDatabaseHas('chat_messages', [
        'conversation_id' => $conversationId,
        'role' => 'user',
        'content' => 'سلام، حالت چطوره؟',
    ]);
    $this->assertDatabaseHas('chat_messages', [
        'conversation_id' => $conversationId,
        'role' => 'assistant',
        'content' => 'سلام! چطور می‌توانم کمک کنم؟',
    ]);
});

test('a message continues the selected conversation', function () {
    $conversation = Conversation::factory()->create([
        'owner_token' => $this->ownerToken,
        'openai_response_id' => 'resp_previous123',
    ]);
    Http::fake([
        'api.openai.com/v1/responses' => Http::response(openAIResponse()),
    ]);

    $this->withCookie('chat_owner_token', $this->ownerToken)
        ->postJson(route('chat.send'), chatPayload([
            'message' => 'ادامه بده',
            'conversation_id' => $conversation->id,
            'model' => 'gpt-5.6-terra',
        ]))
        ->assertOk()
        ->assertJsonPath('conversation.id', $conversation->id);

    Http::assertSent(fn ($request): bool => $request['model'] === 'gpt-5.6-terra'
        && $request['input'][0]['content'][0]['text'] === 'ادامه بده'
        && $request['previous_response_id'] === 'resp_previous123'
        && $request['reasoning']['effort'] === 'none');
});

test('a message is required', function () {
    $this->postJson(route('chat.send'), chatPayload(['message' => '']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.message.0', 'یک پیام یا فایل برای ارسال انتخاب کنید.');
});

test('an openai failure returns a safe error without saving messages', function () {
    Http::fake([
        'api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'Invalid key']], 401),
    ]);

    $this->withCookie('chat_owner_token', $this->ownerToken)
        ->postJson(route('chat.send'), chatPayload(['message' => 'سلام']))
        ->assertStatus(502)
        ->assertJsonPath('message', 'ارتباط با OpenAI ناموفق بود. تنظیمات کلید و مدل را بررسی کنید.');

    expect(ChatMessage::query()->count())->toBe(0);
});

test('an uploaded image is sent to the model and saved with the message', function () {
    Storage::fake('local');
    Http::fake([
        'api.openai.com/v1/responses' => Http::response(openAIResponse()),
    ]);
    $image = UploadedFile::fake()->create('photo.png', 100, 'image/png');

    $response = $this->withCookie('chat_owner_token', $this->ownerToken)
        ->post(route('chat.send'), [
            ...chatPayload(['message' => 'این تصویر چیست؟']),
            'files' => [$image],
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('user_message.attachments.0.kind', 'image');

    $message = ChatMessage::query()->where('role', 'user')->firstOrFail();
    Storage::disk('local')->assertExists($message->attachments[0]['path']);

    Http::assertSent(fn ($request): bool => str_starts_with(
        $request['input'][0]['content'][1]['image_url'],
        'data:image/png;base64,',
    ));

    $this->withCookie('chat_owner_token', $this->ownerToken)
        ->get($response->json('user_message.attachments.0.url'))
        ->assertOk();
});

test('image mode generates and saves an image', function () {
    Storage::fake('local');
    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'id' => 'resp_image123',
            'model' => 'gpt-5.6-sol',
            'output' => [[
                'type' => 'image_generation_call',
                'result' => base64_encode('fake-image-bytes'),
            ]],
        ]),
    ]);

    $this->withCookie('chat_owner_token', $this->ownerToken)
        ->postJson(route('chat.send'), chatPayload([
            'message' => 'یک روباه در جنگل بساز',
            'mode' => 'image',
            'model' => 'gpt-5.6-luna',
        ]))
        ->assertOk()
        ->assertJsonPath('assistant_message.attachments.0.kind', 'image');

    $message = ChatMessage::query()->where('role', 'assistant')->firstOrFail();
    Storage::disk('local')->assertExists($message->attachments[0]['path']);

    Http::assertSent(fn ($request): bool => $request['model'] === 'gpt-5.6-sol'
        && $request['tools'][0]['type'] === 'image_generation'
        && $request['tool_choice']['type'] === 'image_generation');
});

/**
 * @return array<string, mixed>
 */
function openAIResponse(): array
{
    return [
        'id' => 'resp_123',
        'model' => 'gpt-5.6-sol',
        'output' => [[
            'type' => 'message',
            'content' => [[
                'type' => 'output_text',
                'text' => 'سلام! چطور می‌توانم کمک کنم؟',
            ]],
        ]],
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function chatPayload(array $overrides = []): array
{
    return [
        'message' => 'سلام',
        'model' => 'gpt-5.6-sol',
        'mode' => 'chat',
        ...$overrides,
    ];
}
