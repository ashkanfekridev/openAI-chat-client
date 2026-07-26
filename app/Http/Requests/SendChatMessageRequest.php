<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SendChatMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:4000', 'required_without:files', 'required_if:mode,image'],
            'conversation_id' => ['nullable', 'uuid'],
            'model' => ['required', 'string', Rule::in(array_keys(config('services.openai.models')))],
            'mode' => ['required', Rule::in(['chat', 'image'])],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => [
                'required',
                File::types([
                    'jpg', 'jpeg', 'png', 'webp', 'gif',
                    'pdf', 'txt', 'md', 'json', 'html', 'xml',
                    'csv', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx',
                ])->max(10 * 1024),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'لطفاً پیام خود را بنویسید.',
            'message.max' => 'پیام نمی‌تواند بیشتر از ۴۰۰۰ کاراکتر باشد.',
            'message.required_without' => 'یک پیام یا فایل برای ارسال انتخاب کنید.',
            'message.required_if' => 'برای ساخت تصویر، توضیح تصویر را بنویسید.',
            'conversation_id.uuid' => 'شناسه گفتگو معتبر نیست.',
            'model.in' => 'مدل انتخاب‌شده معتبر نیست.',
            'files.max' => 'در هر پیام حداکثر ۵ فایل قابل ارسال است.',
            'files.*.max' => 'حجم هر فایل باید حداکثر ۱۰ مگابایت باشد.',
        ];
    }
}
