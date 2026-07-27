<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Difficulty;
use App\Models\Exercise;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExerciseRequest extends FormRequest
{
    /**
     * Stored as stable English slugs and translated through `exercise.equipment.*`.
     *
     * @var list<string>
     */
    public const EQUIPMENT = ['bodyweight', 'barbell', 'dumbbell', 'machine', 'cable', 'kettlebell', 'band', 'bench', 'smith'];

    public function authorize(): bool
    {
        $exercise = $this->route('exercise');

        return $exercise instanceof Exercise
            ? (bool) $this->user()?->can('update', $exercise)
            : (bool) $this->user()?->can('create', Exercise::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $exercise = $this->route('exercise');

        return self::rulesFor($exercise instanceof Exercise ? $exercise : null);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(?Exercise $exercise = null): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('exercises', 'slug')->ignore($exercise?->getKey()),
            ],
            'muscle_group_id' => ['required', 'integer', Rule::exists('muscle_groups', 'id')],
            'secondary_muscles' => ['array'],
            'secondary_muscles.*' => ['string', Rule::exists('muscle_groups', 'slug')],
            'equipment' => ['nullable', 'string', Rule::in(self::EQUIPMENT)],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'youtube_url' => ['nullable', 'string', 'max:255', self::youtubeRule()],
            'media' => ['nullable', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * The set of link shapes YouTube actually hands out lives on the Exercise
     * model, which already has to parse them to build a thumbnail. Validating
     * through the model keeps one pattern rather than two that drift.
     *
     * The failure message follows DESIGN.md §9 — what happened, then what to do.
     */
    public static function youtubeRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (blank($value)) {
                return;
            }

            if (blank((new Exercise(['youtube_url' => $value]))->youtube_id)) {
                $fail(__('exercise.media.invalid_url'));
            }
        };
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
            'muscle_group_id' => __('exercise.muscle.primary'),
            'secondary_muscles' => __('exercise.muscle.secondary'),
            'equipment' => __('exercise.equipment.label'),
            'difficulty' => __('exercise.difficulty.label'),
            'youtube_url' => __('admin.fields.youtube_url'),
            'media' => __('admin.fields.media'),
            'description_ar' => __('admin.fields.description_ar'),
            'description_en' => __('admin.fields.description_en'),
        ];
    }
}
