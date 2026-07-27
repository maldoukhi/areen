<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * There is no public sign-up. An account exists because an admin opened it, and
 * the admin hands the first password over in person.
 */
class TraineeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            ? (bool) $this->user()?->can('update', $user)
            : (bool) $this->user()?->can('create', User::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return self::rulesFor($user instanceof User ? $user : null);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->getKey()),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'locale' => ['required', 'string', Rule::in(array_keys((array) config('areen.locales')))],
            // Only demanded when the account is being opened; on edit an empty
            // box means "leave the password alone".
            'password' => [$user instanceof User ? 'nullable' : 'required', 'string', Password::min(8)],
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
            'name' => __('auth.fields.name'),
            'email' => __('auth.fields.email'),
            'phone' => __('auth.fields.phone'),
            'role' => __('admin.trainees.role'),
            'locale' => __('auth.account.language'),
            'password' => __('auth.fields.password'),
        ];
    }
}
