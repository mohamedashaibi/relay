<?php

namespace App\Http\Requests;

class UpdateProjectRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'archived' => ['sometimes', 'boolean'],
        ];
    }
}
