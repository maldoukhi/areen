<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ProgramExercise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramExerciseRequest extends FormRequest
{
    /**
     * Superset labels are identifiers, not copy — a row marked `A` sits with
     * every other `A` in the same day and is performed back to back with them.
     *
     * @var list<string>
     */
    public const SUPERSET_GROUPS = ['A', 'B', 'C', 'D'];

    public function authorize(): bool
    {
        $row = $this->route('programExercise');

        return $row instanceof ProgramExercise
            ? (bool) $this->user()?->can('update', $row)
            : (bool) $this->user()?->can('create', ProgramExercise::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * `reps` is free text on purpose: coaches write "8-12", "حتى الفشل" or
     * "٣٠ ثانية" and the schema stores all three.
     *
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(): array
    {
        return [
            'sets' => ['required', 'integer', 'min:1', 'max:20'],
            'reps' => ['nullable', 'string', 'max:255'],
            'rest_seconds' => ['required', 'integer', 'min:0', 'max:900'],
            'tempo' => ['nullable', 'string', 'max:16'],
            'weight_note' => ['nullable', 'string', 'max:255'],
            'coach_notes_ar' => ['nullable', 'string', 'max:1000'],
            'coach_notes_en' => ['nullable', 'string', 'max:1000'],
            'superset_group' => ['nullable', 'string', Rule::in(self::SUPERSET_GROUPS)],
        ];
    }

    /**
     * The library picker writes a single row, so it carries its own contract.
     *
     * @return array<string, list<mixed>>
     */
    public static function attachRules(): array
    {
        return [
            'exercise_id' => ['required', 'integer', Rule::exists('exercises', 'id')],
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
            'exercise_id' => __('admin.entities.exercise'),
            'sets' => __('exercise.prescription.sets'),
            'reps' => __('exercise.prescription.reps'),
            'rest_seconds' => __('exercise.prescription.rest'),
            'tempo' => __('exercise.prescription.tempo'),
            'weight_note' => __('exercise.prescription.weight_note'),
            'coach_notes_ar' => __('exercise.coach_notes.label'),
            'coach_notes_en' => __('exercise.coach_notes.label'),
            'superset_group' => __('exercise.prescription.superset_group'),
        ];
    }
}
