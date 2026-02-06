<?php

namespace App\Http\Requests\TimeLog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:categories,id',
        ];
    }
}
