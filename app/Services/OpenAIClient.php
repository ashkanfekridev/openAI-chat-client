<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIClient
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $model = null,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return array{id: string, message: string, model: string, images: list<string>}
     */
    public function respond(
        string $message,
        ?string $previousResponseId = null,
        ?string $model = null,
        array $files = [],
        bool $generateImage = false,
    ): array {
        $selectedModel = $generateImage
            ? 'gpt-5.6-sol'
            : ($model ?? config('services.openai.model'));

        $payload = [
            'model' => $this->model ?? $selectedModel,
            'instructions' => 'You are a helpful assistant. Reply in the same language as the user unless they ask otherwise. Be clear and concise.',
            'input' => $this->input($message, $files),
            'reasoning' => ['effort' => 'none'],
        ];

        if ($generateImage) {
            $payload['tools'] = [[
                'type' => 'image_generation',
                'action' => 'generate',
                'quality' => 'medium',
                'size' => '1024x1024',
            ]];
            $payload['tool_choice'] = ['type' => 'image_generation'];
        }

        if ($previousResponseId !== null) {
            $payload['previous_response_id'] = $previousResponseId;
        }

        $response = $this->request()
            ->post('/responses', $payload)
            ->throw()
            ->json();

        $responseText = collect($response['output'] ?? [])
            ->where('type', 'message')
            ->flatMap(fn (array $output): array => $output['content'] ?? [])
            ->where('type', 'output_text')
            ->pluck('text')
            ->filter()
            ->implode("\n");

        $images = collect($response['output'] ?? [])
            ->where('type', 'image_generation_call')
            ->pluck('result')
            ->filter(fn (mixed $image): bool => is_string($image) && $image !== '')
            ->values()
            ->all();

        if (! is_string($response['id'] ?? null) || ($responseText === '' && $images === [])) {
            throw new RuntimeException('OpenAI returned an unexpected response.');
        }

        return [
            'id' => $response['id'],
            'message' => $responseText !== '' ? $responseText : 'تصویر آماده شد.',
            'model' => is_string($response['model'] ?? null) ? $response['model'] : (string) $payload['model'],
            'images' => $images,
        ];
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array<string, mixed>>
     */
    private function input(string $message, array $files): array
    {
        $content = [[
            'type' => 'input_text',
            'text' => $message !== '' ? $message : 'این فایل‌ها را بررسی کن.',
        ]];

        foreach ($files as $file) {
            $mimeType = $file->getMimeType() ?: 'application/octet-stream';
            $data = base64_encode($file->get());

            if (str_starts_with($mimeType, 'image/')) {
                $content[] = [
                    'type' => 'input_image',
                    'image_url' => "data:{$mimeType};base64,{$data}",
                    'detail' => 'auto',
                ];

                continue;
            }

            $inputFile = [
                'type' => 'input_file',
                'filename' => $file->getClientOriginalName(),
                'file_data' => "data:{$mimeType};base64,{$data}",
            ];

            if ($mimeType === 'application/pdf') {
                $inputFile['detail'] = 'low';
            }

            $content[] = $inputFile;
        }

        return [[
            'role' => 'user',
            'content' => $content,
        ]];
    }

    private function request(): PendingRequest
    {
        $apiKey = $this->apiKey ?? config('services.openai.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('The OpenAI API key is not configured.');
        }

        return Http::baseUrl((string) config('services.openai.base_url'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(180);
    }
}
