<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use App\Models\ConversationFolder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof Conversation && $this->user()?->can('update', $conversation) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'folder_id' => [
                'sometimes',
                'nullable',
                Rule::exists(ConversationFolder::class, 'id')->where('user_id', $this->user()?->id),
            ],
            'system_prompt' => ['sometimes', 'nullable', 'string', 'max:8000'],
            'reasoning_effort' => ['sometimes', Rule::in(['none', 'low', 'medium', 'high'])],
            'temperature' => ['sometimes', 'nullable', 'numeric', 'between:0,2'],
            'web_search' => ['sometimes', 'boolean'],
            'use_knowledge' => ['sometimes', 'boolean'],
        ];
    }
}
