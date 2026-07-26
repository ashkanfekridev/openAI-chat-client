<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    config()->set('services.openai.api_key', 'test-key');
    config()->set('services.openai.model', 'gpt-5.6-sol');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('the chat page displays only the authenticated user history', function () {
    $visibleConversation = Conversation::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'گفتگوی قابل مشاهده',
    ]);
    Conversation::factory()->create(['title' => 'گفتگوی مرورگر دیگر']);

    $this->get(route('chat'))
        ->assertOk()
        ->assertSee('data-theme-toggle', false)
        ->assertSee('data-chat-form', false)
        ->assertSee('method="POST" action="'.route('chat.send').'"', false)
        ->assertSee('GPT-5.6 Terra')
        ->assertSee($visibleConversation->title)
        ->assertDontSee('گفتگوی مرورگر دیگر');
});

test('the legacy get chat address opens the chat page', function () {
    $this->get('/chat')->assertOk()->assertSee('data-chat-form', false);
});

test('a saved conversation can be opened', function () {
    $conversation = Conversation::factory()->create(['user_id' => $this->user->id]);
    ChatMessage::factory()->for($conversation)->create([
        'role' => 'user',
        'content' => 'پیام ذخیره‌شده',
    ]);

    $this->getJson(route('conversations.show', $conversation))
        ->assertOk()
        ->assertJsonPath('conversation.id', $conversation->id)
        ->assertJsonPath('messages.0.content', 'پیام ذخیره‌شده');
});

test('a conversation can be opened directly from its history link', function () {
    $conversation = Conversation::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'گفتگوی لینک‌شده',
    ]);

    $this->get(route('chat', ['conversation' => $conversation->id]))
        ->assertOk()
        ->assertSee('data-initial-conversation-id="'.$conversation->id.'"', false)
        ->assertSee('href="'.route('chat', ['conversation' => $conversation->id]).'"', false)
        ->assertSee('گفتگوی لینک‌شده');
});

test('another user cannot open a conversation', function () {
    $conversation = Conversation::factory()->create();

    $this->getJson(route('conversations.show', $conversation))
        ->assertNotFound();
});

test('a message creates a saved conversation', function () {
    Http::fake([
        'api.openai.com/v1/responses' => Http::response(openAIResponse()),
    ]);

    $response = $this->postJson(route('chat.send'), chatPayload(['message' => 'سلام، حالت چطوره؟']))
        ->assertOk()
        ->assertJsonPath('message', 'سلام! چطور می‌توانم کمک کنم؟')
        ->assertJsonPath('conversation.title', 'سلام، حالت چطوره؟');

    $conversationId = $response->json('conversation.id');

    $this->assertDatabaseHas('conversations', [
        'id' => $conversationId,
        'user_id' => $this->user->id,
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
        'input_tokens' => 120,
        'output_tokens' => 30,
        'total_tokens' => 150,
    ]);

    $this->user->refresh();
    expect($this->user->input_tokens_used)->toBe(120)
        ->and($this->user->output_tokens_used)->toBe(30)
        ->and($this->user->total_tokens_used)->toBe(150);
});

test('a message continues the selected conversation', function () {
    $conversation = Conversation::factory()->create([
        'user_id' => $this->user->id,
        'openai_response_id' => 'resp_previous123',
    ]);
    Http::fake([
        'api.openai.com/v1/responses' => Http::response(openAIResponse()),
    ]);

    $this->postJson(route('chat.send'), chatPayload([
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

    $this->postJson(route('chat.send'), chatPayload(['message' => 'سلام']))
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

    $response = $this->post(route('chat.send'), [
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

    $this->get($response->json('user_message.attachments.0.url'))
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

    $this->postJson(route('chat.send'), chatPayload([
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

test('a user cannot send a message after reaching the token limit', function () {
    $this->user->forceFill([
        'token_limit' => 100,
        'total_tokens_used' => 100,
    ])->save();
    Http::preventStrayRequests();

    $this->postJson(route('chat.send'), chatPayload())
        ->assertTooManyRequests()
        ->assertJsonPath('message', 'سقف مصرف شما به پایان رسیده است. برای افزایش سقف با مدیر تماس بگیرید.');

    Http::assertNothingSent();
});

test('legacy browser conversations are assigned to the authenticated user', function () {
    $ownerToken = (string) Str::uuid();
    $conversation = Conversation::factory()->create([
        'user_id' => null,
        'owner_token' => $ownerToken,
        'title' => 'گفتگوی قدیمی',
    ]);

    $this->withCookie('chat_owner_token', $ownerToken)
        ->get(route('chat'))
        ->assertOk()
        ->assertSee('گفتگوی قدیمی');

    expect($conversation->refresh()->user_id)->toBe($this->user->id);
});

test('conversation settings enable reasoning web search knowledge and citations', function () {
    $this->user->forceFill(['vector_store_id' => 'vs_123'])->save();
    $conversation = Conversation::factory()->for($this->user)->create([
        'system_prompt' => 'Only answer from reliable sources.',
        'reasoning_effort' => 'high',
        'temperature' => 0.3,
        'web_search' => true,
        'use_knowledge' => true,
    ]);
    $response = openAIResponse();
    $response['output'][0]['content'][0]['annotations'] = [[
        'type' => 'url_citation',
        'title' => 'OpenAI Docs',
        'url' => 'https://developers.openai.com/',
    ]];
    Http::fake(['api.openai.com/v1/responses' => Http::response($response)]);

    $this->postJson(route('chat.send'), chatPayload(['conversation_id' => $conversation->id]))
        ->assertOk()
        ->assertJsonPath('assistant_message.citations.0.title', 'OpenAI Docs');

    Http::assertSent(fn ($request): bool => $request['instructions'] === 'Only answer from reliable sources.'
        && $request['reasoning']['effort'] === 'high'
        && $request['temperature'] === 0.3
        && collect($request['tools'])->contains(fn ($tool) => $tool['type'] === 'web_search')
        && collect($request['tools'])->contains(fn ($tool) => $tool['type'] === 'file_search' && $tool['vector_store_ids'] === ['vs_123']));

    expect(ChatMessage::query()->where('role', 'assistant')->value('estimated_cost_micros'))->toBeGreaterThan(0);
});

test('a user cannot use a model that is not allowed for their account', function () {
    $this->user->forceFill(['allowed_models' => ['gpt-5.6-terra']])->save();
    Http::preventStrayRequests();

    $this->postJson(route('chat.send'), chatPayload(['model' => 'gpt-5.6-sol']))
        ->assertForbidden()
        ->assertJsonPath('message', 'این مدل برای حساب شما مجاز نیست.');
});

/**
 * @return array<string, mixed>
 */
function openAIResponse(): array
{
    return [
        'id' => 'resp_123',
        'model' => 'gpt-5.6-sol',
        'usage' => [
            'input_tokens' => 120,
            'output_tokens' => 30,
            'total_tokens' => 150,
        ],
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
