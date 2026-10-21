<?php

namespace App\Http\Requests;

use App\Domain\Tasks\ReviewDecision;
use Illuminate\Validation\Rule;

class ResolveReviewRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::enum(ReviewDecision::class)],
            'comment' => ['nullable', 'string', 'max:10000', 'required_if:decision,request_changes'],
        ];
    }
}
