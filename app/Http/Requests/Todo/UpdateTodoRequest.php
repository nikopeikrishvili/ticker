<?php

namespace App\Http\Requests\Todo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'sometimes|string|max:1000',
            'is_completed' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:backlog,todo,in_progress,done',
            'priority' => 'sometimes|integer|min:1|max:5',
        ];
    }
}
