<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ];

        // Require current password if email is being changed
        if ($this->input('email') !== $user->email) {
            $rules['current_password'] = 'required|current_password';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'სახელი სავალდებულოა.',
            'email.required' => 'ელ-ფოსტა სავალდებულოა.',
            'email.email' => 'ელ-ფოსტის ფორმატი არასწორია.',
            'email.unique' => 'ეს ელ-ფოსტა უკვე რეგისტრირებულია.',
            'current_password.required' => 'მიმდინარე პაროლი სავალდებულოა ელ-ფოსტის შეცვლისთვის.',
            'current_password.current_password' => 'მიმდინარე პაროლი არასწორია.',
        ];
    }
}
