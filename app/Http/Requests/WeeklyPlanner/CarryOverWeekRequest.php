<?php

namespace App\Http\Requests\WeeklyPlanner;

use Illuminate\Foundation\Http\FormRequest;

class CarryOverWeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_key' => 'required|string|max:10',
        ];
    }
}
