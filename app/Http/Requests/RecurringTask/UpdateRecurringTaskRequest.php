<?php

namespace App\Http\Requests\RecurringTask;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecurringTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'sometimes|string|max:1000',
            'frequency_type' => 'sometimes|in:daily,weekly',
            'weekdays' => 'nullable|array',
            'weekdays.*' => 'integer|min:1|max:7',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
