<?php

namespace App\Http\Requests;

class StoreProjectRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'key' => ['nullable', 'string', 'regex:/^[A-Za-z][A-Za-z0-9]{1,5}$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return ['key.regex' => 'Keys are 2–6 letters or digits and start with a letter, like WEB or OPS2.'];
    }
}
