<?php

namespace App\Http\Requests;

use App\Models\ConversationFolder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConversationFolderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $folder = $this->route('conversationFolder');

        return $folder instanceof ConversationFolder && $this->user()?->can('update', $folder) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique(ConversationFolder::class)
                    ->where('user_id', $this->user()?->id)
                    ->ignore($this->route('conversationFolder')),
            ],
            'color' => ['nullable', Rule::in(['zinc', 'blue', 'emerald', 'amber', 'rose', 'violet'])],
        ];
    }
}
