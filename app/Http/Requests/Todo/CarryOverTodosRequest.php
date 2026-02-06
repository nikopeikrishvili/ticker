<?php

namespace App\Http\Requests\Todo;

use Illuminate\Foundation\Http\FormRequest;

class CarryOverTodosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_ids' => 'required|array',
            'task_ids.*' => 'integer|exists:todos,id',
            'target_date' => 'required|date',
        ];
    }
}
