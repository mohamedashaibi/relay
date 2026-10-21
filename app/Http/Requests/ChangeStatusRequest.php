<?php

namespace App\Http\Requests;

use App\Domain\Tasks\TaskStatus;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'position' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
        ];
    }
}
