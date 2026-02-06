<?php

namespace App\Http\Requests\WeeklyPlanner;

use Illuminate\Foundation\Http\FormRequest;

class MoveToBacklogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'todo_id' => 'required|exists:todos,id',
        ];
    }
}
