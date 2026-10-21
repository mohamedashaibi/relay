<?php

namespace App\Http\Requests;

use App\Domain\Tasks\TaskPriority;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'labels' => ['sometimes', 'array', 'max:8'],
            'labels.*' => ['string', 'max:40'],
            'assignee_id' => ['sometimes', 'nullable', 'integer'],
            'reviewer_id' => ['sometimes', 'nullable', 'integer'],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ];
    }
}
