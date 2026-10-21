<?php

namespace App\Http\Requests;

use App\Domain\Users\NotificationCategory;

class UpdateEmailPreferencesRequest extends StrictFormRequest
{
    public function rules(): array
    {
        $rules = ['preferences' => ['required', 'array']];
        foreach (NotificationCategory::cases() as $category) {
            $rules["preferences.{$category->value}"] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [...parent::after(), function ($validator) {
            $known = array_column(NotificationCategory::cases(), 'value');
            foreach (array_diff(array_keys((array) $this->input('preferences', [])), $known) as $unknown) {
                $validator->errors()->add("preferences.{$unknown}", "Unknown email setting “{$unknown}”.");
            }
        }];
    }
}
