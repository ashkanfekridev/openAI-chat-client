<?php

namespace App\Services;

use App\Exceptions\OpenAIConfigurationException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
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
     * @return array{id: string, message: string, model: string, images: list<string>, citations: list<array{title: string, url: string}>, estimated_cost_micros: int, usage: array{input_tokens: int, output_tokens: int, total_tokens: int}}
     */
    public function respond(
        string $message,
        ?string $previousResponseId = null,
        ?string $model = null,
        array $files = [],
        bool $generateImage = false,
        ?string $systemPrompt = null,
        string $reasoningEffort = 'none',
        ?float $temperature = null,
        bool $webSearch = false,
        ?string $vectorStoreId = null,
    ): array {
        $selectedModel = $generateImage
            ? 'gpt-5.6-sol'
            : ($model ?? config('services.openai.model'));

        $payload = [
            'model' => $this->model ?? $selectedModel,
            'instructions' => $systemPrompt ?: 'You are a helpful assistant. Reply in the same language as the user unless they ask otherwise. Be clear and concise.',
            'input' => $this->input($message, $files),
            'reasoning' => ['effort' => $reasoningEffort],
        ];

        if ($temperature !== null) {
            $payload['temperature'] = $temperature;
        }

        if ($webSearch) {
            $payload['tools'][] = ['type' => 'web_search'];
        }

        if ($vectorStoreId !== null) {
            $payload['tools'][] = ['type' => 'file_search', 'vector_store_ids' => [$vectorStoreId]];
        }

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

        $citations = collect($response['output'] ?? [])
            ->where('type', 'message')
            ->flatMap(fn (array $output): array => $output['content'] ?? [])
            ->flatMap(fn (array $content): array => $content['annotations'] ?? [])
            ->filter(fn (mixed $annotation): bool => is_array($annotation) && ($annotation['type'] ?? null) === 'url_citation')
            ->map(fn (array $annotation): array => [
                'title' => (string) ($annotation['title'] ?? $annotation['url'] ?? 'منبع'),
                'url' => (string) ($annotation['url'] ?? ''),
            ])
            ->filter(fn (array $citation): bool => filter_var($citation['url'], FILTER_VALIDATE_URL) !== false)
            ->unique('url')
            ->values()
            ->all();

        if (! is_string($response['id'] ?? null) || ($responseText === '' && $images === [])) {
            throw new RuntimeException('OpenAI returned an unexpected response.');
        }

        $usage = is_array($response['usage'] ?? null) ? $response['usage'] : [];

        return [
            'id' => $response['id'],
            'message' => $responseText !== '' ? $responseText : 'تصویر آماده شد.',
            'model' => is_string($response['model'] ?? null) ? $response['model'] : (string) $payload['model'],
            'images' => $images,
            'citations' => $citations,
            'estimated_cost_micros' => $this->estimateCostMicros((string) ($response['model'] ?? $payload['model']), $usage),
            'usage' => [
                'input_tokens' => max(0, is_int($usage['input_tokens'] ?? null) ? $usage['input_tokens'] : 0),
                'output_tokens' => max(0, is_int($usage['output_tokens'] ?? null) ? $usage['output_tokens'] : 0),
                'total_tokens' => max(0, is_int($usage['total_tokens'] ?? null) ? $usage['total_tokens'] : 0),
            ],
        ];
    }

    /** @return array{id: string} */
    public function uploadKnowledgeFile(UploadedFile $file): array
    {
        $response = $this->request(false)
            ->attach('file', $file->get(), $file->getClientOriginalName())
            ->post('/files', ['purpose' => 'assistants'])
            ->throw()
            ->json();

        if (! is_string($response['id'] ?? null)) {
            throw new RuntimeException('OpenAI did not return a file identifier.');
        }

        return ['id' => $response['id']];
    }

    public function createVectorStore(string $name): string
    {
        $response = $this->request()->post('/vector_stores', ['name' => $name])->throw()->json();

        if (! is_string($response['id'] ?? null)) {
            throw new RuntimeException('OpenAI did not return a vector store identifier.');
        }

        return $response['id'];
    }

    public function attachFileToVectorStore(string $vectorStoreId, string $fileId): void
    {
        $this->request()->post("/vector_stores/{$vectorStoreId}/files", ['file_id' => $fileId])->throw();
    }

    public function deleteFile(string $fileId): void
    {
        $this->request()->delete("/files/{$fileId}")->throw();
    }

    public function transcribe(UploadedFile $audio): string
    {
        $response = $this->request(false)
            ->attach('file', $audio->get(), $audio->getClientOriginalName())
            ->post('/audio/transcriptions', [
                'model' => config('services.openai.transcription_model', 'gpt-4o-mini-transcribe'),
            ])
            ->throw()
            ->json();

        if (! is_string($response['text'] ?? null)) {
            throw new RuntimeException('OpenAI did not return a transcription.');
        }

        return $response['text'];
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

    /** @param array<string, mixed> $usage */
    private function estimateCostMicros(string $model, array $usage): int
    {
        $allPricing = config('services.openai.pricing', []);
        $pricing = $allPricing[$model] ?? ['input' => 0, 'output' => 0];

        return (int) round(
            ((int) Arr::get($usage, 'input_tokens', 0) * (float) Arr::get($pricing, 'input', 0))
            + ((int) Arr::get($usage, 'output_tokens', 0) * (float) Arr::get($pricing, 'output', 0)),
        );
    }

    private function request(bool $asJson = true): PendingRequest
    {
        $apiKey = $this->apiKey ?? config('services.openai.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new OpenAIConfigurationException('The OPENAI_API_KEY environment variable is not configured.');
        }

        $request = Http::baseUrl((string) config('services.openai.base_url'))
            ->withToken($apiKey)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(180)
            ->retry([200, 500], throw: false);

        return $asJson ? $request->asJson() : $request;
    }
}
