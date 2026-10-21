<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:200'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', Password::min(10)->letters()->numbers(), 'max:200'],
        ];
    }
}
