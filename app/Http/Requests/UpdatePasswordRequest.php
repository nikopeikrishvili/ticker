<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|current_password',
            'password' => 'required|confirmed|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'მიმდინარე პაროლი სავალდებულოა.',
            'current_password.current_password' => 'მიმდინარე პაროლი არასწორია.',
            'password.required' => 'ახალი პაროლი სავალდებულოა.',
            'password.confirmed' => 'პაროლები არ ემთხვევა.',
            'password.min' => 'პაროლი უნდა იყოს მინიმუმ 8 სიმბოლო.',
        ];
    }
}
