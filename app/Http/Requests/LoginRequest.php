<?php

namespace App\Http\Requests;

class LoginRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['nullable', 'boolean'],
        ];
    }
}
