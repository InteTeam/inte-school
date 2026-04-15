<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'sms_fallback_enabled' => ['required', 'boolean'],
            'govuk_notify_api_key' => ['nullable', 'string', 'max:500'],
            'govuk_notify_template_id' => ['nullable', 'string', 'max:100'],
            'sms_fallback_types' => ['nullable', 'array'],
            'sms_fallback_types.*' => ['string', 'in:attendance_alert,trip_permission,announcement'],
        ];
    }
}
