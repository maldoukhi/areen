<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\MuscleGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MuscleGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $muscleGroup = $this->route('muscleGroup');

        return $muscleGroup instanceof MuscleGroup
            ? (bool) $this->user()?->can('update', $muscleGroup)
            : (bool) $this->user()?->can('create', MuscleGroup::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $muscleGroup = $this->route('muscleGroup');

        return self::rulesFor($muscleGroup instanceof MuscleGroup ? $muscleGroup : null);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(?MuscleGroup $muscleGroup = null): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('muscle_groups', 'slug')->ignore($muscleGroup?->getKey()),
            ],
            'icon' => ['nullable', 'string', 'max:64'],
            'sort' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::attributeNames();
    }

    /**
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        return [
            'name_ar' => __('admin.fields.name_ar'),
            'name_en' => __('admin.fields.name_en'),
            'slug' => __('admin.fields.slug'),
            'icon' => __('admin.fields.icon'),
            'sort' => __('admin.fields.sort'),
        ];
    }
}
