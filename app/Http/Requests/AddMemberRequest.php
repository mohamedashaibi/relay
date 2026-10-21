<?php

namespace App\Http\Requests;

class AddMemberRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
            'role' => ['required', 'in:admin,member'],
            'name' => ['nullable', 'string', 'min:2', 'max:80'],
        ];
    }
}
