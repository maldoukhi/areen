<?php

use App\Actions\Trainee\ResolveTrainingPlan;
use App\Models\WorkoutLog;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function mount(): void
    {
        // The screen shows nobody's rows but the viewer's, and the policy is what
        // says so — a suspended account is turned away here rather than shown an
        // empty dashboard it cannot explain.
        $this->authorize('viewAny', WorkoutLog::class);
    }

    public function rendering($view): void
    {
        $view->title(__('trainee.title'));
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function plan(): array
    {
        return app(ResolveTrainingPlan::class)->handle(auth()->user());
    }

    /**
     * The one destination this screen exists to point at: the day the trainee is
     * about to do, ready to log.
     */
    #[Computed]
    public function sessionUrl(): ?string
    {
        $program = $this->plan['program'];
        $day = $this->plan['day'];

        if ($program === null || $day === null) {
            return null;
        }

        return route('dashboard.log', ['program' => $program->slug, 'day' => $day->day_number]);
    }

    public function dayHeading(): string
    {
        $day = $this->plan['day'];

        if ($day === null) {
            return '';
        }

        return filled($day->title)
            ? __('program.days.title', ['number' => $day->day_number, 'title' => $day->title])
            : __('program.days.number', ['number' => $day->day_number]);
    }
};
?>

{{--
  The trainee's home screen. One question — what do I do next — and one button
  that answers it, parked in the lower half where the thumb is (DESIGN.md §11).

  Everything above the button is context for that decision and nothing else: the
  program, the day, and just enough history to show the habit is alive.
--}}

@php
    $plan = $this->plan;
    $program = $plan['program'];
    $unit = __('common.units.'.config('areen.weight_unit', 'kg'));
@endphp

<div @class([
    'mx-auto w-full max-w-[1200px] px-4 pt-5',
    'pb-[calc(104px+env(safe-area-inset-bottom))]' => $this->sessionUrl !== null,
    'pb-12' => $this->sessionUrl === null,
])>
    {{-- Drains anything left on the phone from last night, before anything else. --}}
    <x-trainee.offline-runtime/>

    <header class="flex flex-col gap-1">
        <p class="text-sm text-ink-400">{{ __('trainee.greeting', ['name' => auth()->user()->name]) }}</p>
        <h1 class="text-2xl font-bold leading-tight text-ink-50">{{ __('trainee.title') }}</h1>
    </header>

    @if ($program === null)
        <x-ui.empty-state class="mt-6">
            <x-slot:title>{{ __('trainee.dashboard.empty_title') }}</x-slot:title>
            <x-slot:body>{{ __('trainee.dashboard.empty_body') }}</x-slot:body>
            <x-slot:action>
                <x-ui.button :href="route('programs.index')" wire:navigate>
                    {{ __('trainee.dashboard.browse') }}
                </x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <x-ui.card class="mt-6">
            <p class="text-xs font-medium text-ink-400">{{ __('trainee.dashboard.active_program') }}</p>

            <a href="{{ route('programs.show', $program) }}"
               wire:navigate
               class="-my-1 mt-1 block rounded-sm py-1 text-lg font-semibold leading-snug text-ink-50 underline-offset-4 hover:underline">
                {{ $program->name }}
            </a>

            @if ($plan['day'] !== null)
                <div class="mt-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t border-ink-700 pt-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium text-ink-400">
                            {{ $plan['resuming'] ? __('trainee.dashboard.today') : __('trainee.dashboard.next_up') }}
                        </span>

                        <span class="text-base font-semibold text-brand-400">{{ $this->dayHeading() }}</span>
                    </div>

                    @if ($plan['day']->focusMuscle)
                        <x-ui.chip :active="true">{{ $plan['day']->focusMuscle->name }}</x-ui.chip>
                    @endif
                </div>

                @if ($plan['day']->is_rest_day)
                    <p class="mt-3 text-sm leading-relaxed text-ink-300">{{ __('trainee.dashboard.rest_day_note') }}</p>
                @endif
            @endif

            <p class="mt-3 flex flex-wrap items-baseline gap-x-2 text-sm text-ink-400">
                <span>{{ __('trainee.dashboard.last_session') }}</span>

                @if ($plan['last_session_on'] === null)
                    <span class="text-ink-300">{{ __('trainee.dashboard.never_logged') }}</span>
                @else
                    <time datetime="{{ $plan['last_session_on']->toDateString() }}" class="tabular text-ink-200">
                        {{ $plan['last_session_on']->isoFormat('D MMMM') }}
                    </time>
                @endif
            </p>
        </x-ui.card>

        {{--
          The habit, in three figures. `ember` appears exactly once on this screen
          and this is it (DESIGN.md §2): the streak is the only number here that is
          about persistence rather than volume.
        --}}
        {{--
          Two tiles across at 360px, three from 640px (DESIGN.md §4). The load
          figure runs to four digits plus a unit, so on the narrow layout it takes
          the full width rather than being squeezed into a third of it.
        --}}
        <section aria-label="{{ __('program.progress.label') }}"
                 class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="flex flex-col items-start rounded-lg border border-ink-700 bg-ink-800 p-4">
                <x-ui.metric :value="$plan['streak']"
                             :caption="__('trainee.dashboard.streak')"
                             tone="ember"/>
            </div>

            <div class="flex flex-col items-start rounded-lg border border-ink-700 bg-ink-800 p-4">
                <x-ui.metric :value="$plan['sets_this_week']"
                             :caption="__('trainee.dashboard.sets_this_week')"
                             tone="ink"/>
            </div>

            <div class="col-span-2 flex flex-col items-start rounded-lg border border-ink-700 bg-ink-800 p-4 sm:col-span-1">
                <x-ui.metric :value="number_format($plan['volume_this_week'], 0)"
                             :unit="$unit"
                             :caption="__('trainee.dashboard.volume_this_week')"
                             tone="ink"/>
            </div>
        </section>

        <section aria-labelledby="recent-heading" class="mt-8">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="recent-heading" class="text-lg font-semibold text-ink-50">
                    {{ __('trainee.dashboard.recent') }}
                </h2>

                <a href="{{ route('dashboard.progress') }}"
                   wire:navigate
                   class="-my-2 inline-flex min-h-11 items-center rounded-sm px-1 text-sm font-medium text-brand-400 underline-offset-4 hover:underline">
                    {{ __('trainee.dashboard.see_progress') }}
                </a>
            </div>

            @if ($plan['recent'] === [])
                <p class="mt-3 rounded-lg border border-ink-700 bg-ink-900 p-4 text-sm leading-relaxed text-ink-300">
                    {{ __('trainee.dashboard.recent_empty') }}
                </p>
            @else
                <ul role="list" class="mt-3 flex flex-col gap-2">
                    @foreach ($plan['recent'] as $session)
                        <li wire:key="session-{{ $session['date']->toDateString() }}"
                            class="flex items-center justify-between gap-4 rounded-md border border-ink-700 bg-ink-900 px-4 py-3">
                            <time datetime="{{ $session['date']->toDateString() }}"
                                  class="tabular text-sm font-medium text-ink-100">
                                {{ $session['date']->isoFormat('D MMM') }}
                            </time>

                            <span class="tabular text-sm text-ink-300">
                                {{ __('trainee.dashboard.recent_entry', [
                                    'sets' => $session['sets'],
                                    'exercises' => $session['exercises'],
                                ]) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    @if ($this->sessionUrl !== null)
        {{--
          The primary action, fixed in the lower half and clearing the gesture bar
          through `safe-pb`. In standalone mode it stacks above the app's own
          bottom nav, which already owns the safe area.
        --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-700 bg-ink-900/95 pt-3 backdrop-blur-md safe-pb print:hidden
                    [@media(display-mode:standalone)]:bottom-[calc(60px+env(safe-area-inset-bottom))]
                    [@media(display-mode:standalone)]:pb-4">
            <div class="mx-auto w-full max-w-[1200px] px-4">
                <x-ui.button :href="$this->sessionUrl" wire:navigate :full="true">
                    {{ $plan['day']?->is_rest_day
                        ? __('trainee.dashboard.open_rest_day')
                        : __('trainee.dashboard.start_session') }}
                </x-ui.button>
            </div>
        </div>
    @endif
</div>
