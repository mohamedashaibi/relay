<?php

namespace App\Http\Requests;

class UpdateProfileRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'min:2', 'max:80']];
    }
}
