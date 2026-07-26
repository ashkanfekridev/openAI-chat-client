<?php

namespace App\Http\Requests;

use App\Models\ConversationFolder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConversationFolderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
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
                Rule::unique(ConversationFolder::class)->where('user_id', $this->user()?->id),
            ],
            'color' => ['nullable', Rule::in(['zinc', 'blue', 'emerald', 'amber', 'rose', 'violet'])],
        ];
    }
}
