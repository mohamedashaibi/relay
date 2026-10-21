<?php

namespace App\Http\Requests;

use App\Domain\Tasks\TaskPriority;
use App\Domain\Tasks\TaskStatus;
use Illuminate\Validation\Rule;

class SearchTasksRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'project' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'regex:/^[a-z_]+(,[a-z_]+)*$/', function ($attr, $value, $fail) {
                if (array_diff(explode(',', $value), TaskStatus::values())) {
                    $fail('Unknown status.');
                }
            }],
            'assignee' => ['nullable', 'string', 'regex:/^(me|unassigned|\d+)$/'],
            'awaiting_my_review' => ['nullable', 'boolean'],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'q' => ['nullable', 'string', 'max:200'],
            'label' => ['nullable', 'string', 'max:40'],
            'include_done' => ['nullable', 'boolean'],
            'overdue' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
