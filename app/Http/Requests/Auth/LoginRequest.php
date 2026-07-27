<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Every write in the panel is validated by a FormRequest. The panel itself is
 * driven by Livewire rather than by form posts, so each request also publishes
 * its contract as static methods — `rulesFor()`, `messagesFor()` and
 * `attributeNames()` — which the component hands straight to `validate()`.
 * The rules live in exactly one place either way.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rulesFor(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
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
            'email' => __('auth.fields.email'),
            'password' => __('auth.fields.password'),
        ];
    }
}
