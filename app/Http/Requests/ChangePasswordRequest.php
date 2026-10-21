<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string', Password::min(10)->letters()->numbers(), 'max:200', 'different:current_password'],
        ];
    }

    public function messages(): array
    {
        return ['password.different' => 'Choose a password different from your current one.'];
    }
}
