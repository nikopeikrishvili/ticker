<?php

namespace App\Http\Requests\WeeklyPlanner;

use Illuminate\Foundation\Http\FormRequest;

class AssignToDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'todo_id' => 'required|exists:todos,id',
            'week_key' => 'required|string|max:10',
            'day_of_week' => 'required|integer|min:1|max:5',
        ];
    }
}
