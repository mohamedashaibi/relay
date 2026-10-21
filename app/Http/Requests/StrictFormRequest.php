<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Rejects any input key that has no validation rule, instead of silently ignoring it. */
abstract class StrictFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authentication is enforced by route middleware; object-level checks live in the use cases.
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $allowed = array_unique(array_map(fn ($rule) => explode('.', $rule)[0], array_keys($this->rules())));

            foreach (array_diff(array_keys($this->all()), $allowed) as $unexpected) {
                $validator->errors()->add($unexpected, "The {$unexpected} field is not accepted here.");
            }
        }];
    }
}
