<?php

namespace App\Http\Requests;

class ForgotPasswordRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:190']];
    }
}
