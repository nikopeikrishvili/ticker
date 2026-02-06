<?php

namespace App\Http\Requests\Todo;

use Illuminate\Foundation\Http\FormRequest;

class StoreTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'todo_date' => 'nullable|date',
            'content' => 'required|string|max:1000',
            'status' => 'sometimes|string|in:backlog,todo,in_progress,done',
            'priority' => 'sometimes|integer|min:1|max:5',
        ];
    }
}
