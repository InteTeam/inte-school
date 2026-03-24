<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'require_2fa' => ['required', 'boolean'],
            'session_timeout_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
        ];
    }
}
