<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ProgramDay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        $day = $this->route('day');

        return $day instanceof ProgramDay
            ? (bool) $this->user()?->can('update', $day)
            : (bool) $this->user()?->can('create', ProgramDay::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $day = $this->route('day');

        return self::rulesFor($day instanceof ProgramDay ? $day : null);
    }

    /**
     * A day's title and notes are nullable in the schema — a coach is allowed to
     * leave a day unnamed, so the interface must never demand one.
     *
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(?ProgramDay $day = null): array
    {
        return [
            'title_ar' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'focus_muscle_id' => ['nullable', 'integer', Rule::exists('muscle_groups', 'id')],
            'is_rest_day' => ['boolean'],
            'notes_ar' => ['nullable', 'string', 'max:2000'],
            'notes_en' => ['nullable', 'string', 'max:2000'],
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
            'title_ar' => __('admin.fields.title_ar'),
            'title_en' => __('admin.fields.title_en'),
            'focus_muscle_id' => __('program.days.focus'),
            'is_rest_day' => __('admin.fields.is_rest_day'),
            'notes_ar' => __('admin.fields.notes'),
            'notes_en' => __('admin.fields.notes'),
        ];
    }
}
