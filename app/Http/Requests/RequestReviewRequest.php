<?php

namespace App\Http\Requests;

class RequestReviewRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'min:3', 'max:10000'],
            'reviewer_id' => ['nullable', 'integer'],
        ];
    }
}
