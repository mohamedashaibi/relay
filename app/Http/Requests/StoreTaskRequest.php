<?php

namespace App\Http\Requests;

use App\Domain\Tasks\TaskPriority;
use App\Domain\Tasks\TaskStatus;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'project' => ['required', 'max:20'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:20000'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'labels' => ['nullable', 'array', 'max:8'],
            'labels.*' => ['string', 'max:40'],
            'assignee_id' => ['nullable', 'integer'],
            'reviewer_id' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
