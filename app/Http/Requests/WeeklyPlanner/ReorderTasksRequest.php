<?php

namespace App\Http\Requests\WeeklyPlanner;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_key' => 'required|string|max:10',
            'day_of_week' => 'required|integer|min:1|max:5',
            'placements' => 'required|array',
            'placements.*.id' => 'required|exists:task_placements,id',
            'placements.*.order' => 'required|integer|min:0',
        ];
    }
}
