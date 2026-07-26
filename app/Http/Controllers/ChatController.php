<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendChatMessageRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatOwner;
use App\Services\OpenAIClient;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ChatController extends Controller
{
    public function __invoke(
        SendChatMessageRequest $request,
        OpenAIClient $openAI,
        ChatOwner $chatOwner,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();
        abort_if($user === null, 401);
        $user->refreshExpiredUsagePeriod();

        if (! $user->is_active) {
            return response()->json(['message' => 'حساب کاربری شما غیرفعال است.'], 403);
        }

        if ($user->hasReachedTokenLimit()) {
            return response()->json([
                'message' => 'سقف مصرف شما به پایان رسیده است. برای افزایش سقف با مدیر تماس بگیرید.',
            ], 429);
        }

        $ownerToken = $chatOwner->token($request);
        $conversationId = $request->validated('conversation_id');
        $conversation = null;

        if (is_string($conversationId)) {
            $conversation = Conversation::query()
                ->whereBelongsTo($user)
                ->find($conversationId);

            if ($conversation === null) {
                throw (new ModelNotFoundException)->setModel(Conversation::class, [$conversationId]);
            }
        }

        $message = $request->string('message')->toString();
        $model = $request->string('model')->toString();

        if (! $user->canUseModel($model)) {
            return response()->json(['message' => 'این مدل برای حساب شما مجاز نیست.'], 403);
        }
        $generateImage = $request->string('mode')->toString() === 'image';
        /** @var list<UploadedFile> $files */
        $files = array_values($request->file('files', []));

        try {
            $result = $openAI->respond(
                $message,
                $conversation?->openai_response_id,
                $model,
                $files,
                $generateImage,
                $conversation?->system_prompt,
                $conversation?->reasoning_effort ?? 'none',
                $conversation?->temperature,
                $conversation?->web_search ?? false,
                $conversation?->use_knowledge ? $user->vector_store_id : null,
            );

            [$conversation, $userMessage, $assistantMessage] = DB::transaction(function () use (
                $conversation,
                $ownerToken,
                $user,
                $message,
                $model,
                $files,
                $result,
            ): array {
                $conversation ??= Conversation::query()->create([
                    'user_id' => $user->id,
                    'owner_token' => $ownerToken,
                    'title' => Str::limit(Str::squish($message ?: $files[0]->getClientOriginalName()), 48),
                    'model' => $model,
                ]);

                $userMessage = $conversation->messages()->create([
                    'role' => 'user',
                    'content' => $message,
                    'attachments' => $this->storeUploadedFiles($conversation, $files),
                ]);
                $assistantMessage = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $result['message'],
                    'attachments' => $this->storeGeneratedImages($conversation, $result['images']),
                    'input_tokens' => $result['usage']['input_tokens'],
                    'output_tokens' => $result['usage']['output_tokens'],
                    'total_tokens' => $result['usage']['total_tokens'],
                    'openai_response_id' => $result['id'],
                    'citations' => $result['citations'],
                    'estimated_cost_micros' => $result['estimated_cost_micros'],
                ]);

                $conversation->update([
                    'model' => $model,
                    'openai_response_id' => $result['id'],
                ]);

                User::query()
                    ->whereKey($user->id)
                    ->incrementEach([
                        'input_tokens_used' => $result['usage']['input_tokens'],
                        'output_tokens_used' => $result['usage']['output_tokens'],
                        'total_tokens_used' => $result['usage']['total_tokens'],
                    ]);

                return [$conversation, $userMessage, $assistantMessage];
            });

            $user->refresh();
            unset($result['images'], $result['usage'], $result['citations'], $result['estimated_cost_micros']);

            return response()->json([
                ...$result,
                'user_message' => $userMessage->toChatArray(),
                'assistant_message' => $assistantMessage->toChatArray(),
                'conversation' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'model' => $conversation->model,
                    'updated_at' => $conversation->updated_at?->toISOString(),
                ],
                'usage' => [
                    'input' => $user->input_tokens_used,
                    'output' => $user->output_tokens_used,
                    'used' => $user->total_tokens_used,
                    'limit' => $user->token_limit,
                    'remaining' => $user->remainingTokens(),
                ],
            ]);
        } catch (RequestException $exception) {
            report($exception);

            $status = $exception->response->status();

            return response()->json([
                'message' => $status === 429
                    ? 'تعداد درخواست‌ها زیاد است؛ کمی بعد دوباره تلاش کنید.'
                    : 'ارتباط با OpenAI ناموفق بود. تنظیمات کلید و مدل را بررسی کنید.',
            ], $status === 429 ? 429 : 502);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'اتصال به OpenAI برقرار نشد.'], 502);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'سرویس چت به‌درستی پیکربندی نشده است.'], 500);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'خطای پیش‌بینی‌نشده‌ای رخ داد.'], 500);
        }
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{name: string, mime_type: string, kind: string, path: string}>
     */
    private function storeUploadedFiles(Conversation $conversation, array $files): array
    {
        return collect($files)
            ->map(function (UploadedFile $file) use ($conversation): array {
                $mimeType = $file->getMimeType() ?: 'application/octet-stream';
                $extension = $file->guessExtension() ?: 'bin';
                $path = "chat-attachments/{$conversation->id}/".Str::uuid().".{$extension}";

                if (! Storage::disk('local')->put($path, $file->get())) {
                    throw new RuntimeException('The uploaded file could not be stored.');
                }

                return [
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $mimeType,
                    'kind' => str_starts_with($mimeType, 'image/') ? 'image' : 'file',
                    'path' => $path,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $images
     * @return list<array{name: string, mime_type: string, kind: string, path: string}>
     */
    private function storeGeneratedImages(Conversation $conversation, array $images): array
    {
        return collect($images)
            ->map(function (string $image, int $index) use ($conversation): array {
                $decodedImage = base64_decode($image, true);

                if ($decodedImage === false) {
                    throw new RuntimeException('OpenAI returned invalid image data.');
                }

                $path = "chat-attachments/{$conversation->id}/".Str::uuid().'.png';

                if (! Storage::disk('local')->put($path, $decodedImage)) {
                    throw new RuntimeException('The generated image could not be stored.');
                }

                return [
                    'name' => 'generated-image-'.($index + 1).'.png',
                    'mime_type' => 'image/png',
                    'kind' => 'image',
                    'path' => $path,
                ];
            })
            ->values()
            ->all();
    }
}
