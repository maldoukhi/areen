<?php

declare(strict_types=1);

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Setting;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.admin.auth-layout')]
class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Five tries a minute per email-and-address pair. Keyed on both so one
     * trainee guessing at a coach's password cannot lock the coach out from
     * somewhere else, and so a single address cannot walk the member list.
     */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function authenticate(): void
    {
        $this->validate(LoginRequest::rulesFor(), [], LoginRequest::attributeNames());

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        /*
         * A suspended account keeps its row and its history but loses its way
         * in. Checked after the credentials so a wrong password never reveals
         * that an address belongs to a suspended member.
         */
        if (Auth::user()?->is_active !== true) {
            Auth::guard('web')->logout();
            $this->reset('password');

            throw ValidationException::withMessages(['email' => __('auth.errors.inactive')]);
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        $this->redirectIntended($this->home(), navigate: false);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout(request()));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    /**
     * A trainee has no panel to land on, so they are sent to the public site.
     */
    private function home(): string
    {
        return Auth::user()?->isTrainee() === true ? url('/') : route('admin.dashboard');
    }

    public function with(): array
    {
        $settings = rescue(fn () => Setting::current(), null, false);

        return [
            'clubName' => rescue(fn () => $settings?->club_name, null, false) ?: __('common.app_name'),
        ];
    }
};
?>

{{--
  DESIGN.md §11: on a phone the block is anchored to the bottom so the primary
  action lands under the thumb; from sm up there is room to centre it.
--}}
<div class="flex flex-1 flex-col justify-end gap-8 pt-20 pb-10 safe-pb sm:justify-center">
    <header class="flex flex-col gap-4">
        <x-brand.mark class="size-12 text-brand-400"/>

        <div>
            <h1 class="text-2xl font-bold text-ink-50">{{ __('auth.login.title') }}</h1>
            <p class="mt-2 text-ink-300">{{ __('auth.login.subtitle') }}</p>
        </div>

        <p class="text-sm text-ink-400">{{ $clubName }}</p>
    </header>

    <x-admin.flash/>

    <form wire:submit="authenticate" class="flex flex-col gap-5">
        <x-ui.field :label="__('auth.fields.email')"
                    id="login-email"
                    type="email"
                    autocomplete="username"
                    inputmode="email"
                    dir="ltr"
                    required
                    wire:model="email"
                    :error="$errors->first('email')"/>

        <x-ui.field :label="__('auth.fields.password')"
                    id="login-password"
                    type="password"
                    autocomplete="current-password"
                    required
                    wire:model="password"
                    :error="$errors->first('password')"/>

        <x-admin.toggle id="login-remember"
                        :label="__('auth.login.remember')"
                        wire:model="remember"/>

        <x-ui.button type="submit" full wire:loading.attr="aria-disabled" wire:target="authenticate">
            <span wire:loading.remove wire:target="authenticate">{{ __('auth.login.action') }}</span>
            <span wire:loading wire:target="authenticate">{{ __('common.states.loading') }}</span>
        </x-ui.button>
    </form>
</div>
