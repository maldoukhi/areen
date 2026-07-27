<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ProgramLevel;
use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramRequest extends FormRequest
{
    /**
     * The goals the interface offers. Stored as stable English slugs and
     * translated through `program.goal.*`, never stored in Arabic.
     *
     * @var list<string>
     */
    public const GOALS = ['general-fitness', 'hypertrophy', 'strength', 'fat-loss', 'endurance'];

    public function authorize(): bool
    {
        $program = $this->route('program');

        return $program instanceof Program
            ? (bool) $this->user()?->can('update', $program)
            : (bool) $this->user()?->can('create', Program::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $program = $this->route('program');

        return self::rulesFor($program instanceof Program ? $program : null);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(?Program $program = null): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('programs', 'slug')->ignore($program?->getKey()),
            ],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'days_count' => ['required', 'integer', 'min:1', 'max:7'],
            'level' => ['required', Rule::enum(ProgramLevel::class)],
            'goal' => ['nullable', 'string', Rule::in(self::GOALS)],
            'is_public' => ['boolean'],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
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
            'description_ar' => __('admin.fields.description_ar'),
            'description_en' => __('admin.fields.description_en'),
            'days_count' => __('admin.fields.days_count'),
            'level' => __('program.level.label'),
            'goal' => __('program.goal.label'),
            'published_at' => __('admin.fields.published_at'),
            'cover' => __('admin.fields.cover'),
        ];
    }
}
