<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && (bool) $this->user()?->can('assignProgram', $user);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * `program_user.started_at` is a plain date column, so the value stays a
     * date string all the way down.
     *
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(): array
    {
        return [
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->whereNull('deleted_at')],
            'started_at' => ['required', 'date'],
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
            'program_id' => __('admin.entities.program'),
            'started_at' => __('admin.trainees.started_at'),
        ];
    }
}
