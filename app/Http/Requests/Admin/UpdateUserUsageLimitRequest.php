<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserUsageLimitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token_limit' => ['nullable', 'integer', 'min:1', 'max:1000000000'],
            'quota_period' => ['sometimes', Rule::in(['daily', 'monthly', 'unlimited'])],
            'role' => ['sometimes', Rule::in(['user', 'manager', 'admin'])],
            'is_active' => ['sometimes', 'boolean'],
            'allowed_models' => ['nullable', 'array'],
            'allowed_models.*' => ['string', Rule::in(array_keys(config('services.openai.models')))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token_limit.integer' => 'سقف مصرف باید یک عدد صحیح باشد.',
            'token_limit.min' => 'سقف مصرف باید حداقل یک توکن باشد.',
            'token_limit.max' => 'سقف مصرف نمی‌تواند بیشتر از یک میلیارد توکن باشد.',
        ];
    }
}
