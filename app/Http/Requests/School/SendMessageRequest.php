<?php

declare(strict_types=1);

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:announcement,attendance_alert,trip_permission,quick_reply'],
            'body' => ['required', 'string', 'max:5000'],
            'recipient_id' => ['nullable', 'string', 'max:26', 'required_without:class_id'],
            'class_id' => ['nullable', 'string', 'max:26'],
            'thread_id' => ['nullable', 'string', 'max:26'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }
}
