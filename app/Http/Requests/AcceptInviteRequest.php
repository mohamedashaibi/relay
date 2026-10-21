<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class AcceptInviteRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:200'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'password' => ['required', 'string', Password::min(10)->letters()->numbers(), 'max:200'],
        ];
    }
}
