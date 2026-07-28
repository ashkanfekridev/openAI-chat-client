<?php

namespace App\Exceptions;

use Exception;

class OpenAIResponseException extends Exception
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(private readonly array $response)
    {
        parent::__construct('OpenAI returned a response that could not be processed.');
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        $output = is_array($this->response['output'] ?? null) ? $this->response['output'] : [];

        return [
            'openai_response_id' => is_string($this->response['id'] ?? null) ? $this->response['id'] : null,
            'openai_response_status' => is_string($this->response['status'] ?? null) ? $this->response['status'] : null,
            'openai_response_model' => is_string($this->response['model'] ?? null) ? $this->response['model'] : null,
            'openai_output_types' => collect($output)
                ->filter(fn (mixed $item): bool => is_array($item) && is_string($item['type'] ?? null))
                ->pluck('type')
                ->unique()
                ->values()
                ->all(),
            'openai_content_types' => collect($output)
                ->filter(fn (mixed $item): bool => is_array($item) && is_array($item['content'] ?? null))
                ->flatMap(fn (array $item): array => $item['content'])
                ->filter(fn (mixed $content): bool => is_array($content) && is_string($content['type'] ?? null))
                ->pluck('type')
                ->unique()
                ->values()
                ->all(),
            'openai_incomplete_reason' => is_string($this->response['incomplete_details']['reason'] ?? null)
                ? $this->response['incomplete_details']['reason']
                : null,
            'openai_error_type' => is_string($this->response['error']['type'] ?? null)
                ? $this->response['error']['type']
                : null,
        ];
    }
}
