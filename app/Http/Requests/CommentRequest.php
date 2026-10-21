<?php

namespace App\Http\Requests;

class CommentRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:10000']];
    }
}
