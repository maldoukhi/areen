<?php

use App\Actions\Trainee\ResolveTrainingPlan;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\WorkoutLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    /**
     * Both arrive from the query string, and both are deliberately strings.
     * Livewire assigns a URL value straight onto a matching public property
     * before mount runs, so a typed `?int $day` would turn `/dashboard/log?day=x`
     * into a TypeError and a 500 that no guard inside the component could catch.
     * They are parsed below instead.
     */
    #[Url]
    public ?string $program = null;

    #[Url]
    public ?string $day = null;

    /** The date the rounds on this screen belong to. */
    public string $performedOn = '';

    public function mount(): void
    {
        $this->authorize('viewAny', WorkoutLog::class);

        $this->performedOn = Carbon::today()->toDateString();
    }

    public function rendering($view): void
    {
        $view->title(__('trainee.log.title'));
    }

    /**
     * Resolved through the trainee's own programs, never through Program::query().
     * That single relationship is the whole access rule for this screen: a slug
     * belonging to somebody else's plan simply does not resolve, so there is no
     * ownership check to forget further down.
     */
    #[Computed]
    public function trainingProgram(): ?Program
    {
        $user = auth()->user();

        if (filled($this->program)) {
            return $user->programs()->where('programs.slug', $this->program)->first();
        }

        return $user->activeProgram();
    }

    #[Computed]
    public function currentDay(): ?ProgramDay
    {
        $program = $this->trainingProgram;

        if (! $program instanceof Program) {
            return null;
        }

        $number = filter_var($this->day, FILTER_VALIDATE_INT);

        if ($number === false || $number < 1) {
            // No day named: open whichever one the plan says comes next, so the
            // bare URL is a useful destination rather than a chooser.
            $number = app(ResolveTrainingPlan::class)->handle(auth()->user())['day']?->day_number;
        }

        return $program->days()
            ->with(['focusMuscle', 'exercises.exercise'])
            ->firstWhere('day_number', $number ?? 1);
    }

    /**
     * Today's rounds, keyed by prescribed exercise and then by set number, so a
     * row can find its own record without a search.
     *
     * @return Collection<int, Collection<int, WorkoutLog>>
     */
    #[Computed]
    public function logs(): Collection
    {
        $day = $this->currentDay;

        if (! $day instanceof ProgramDay) {
            return new Collection;
        }

        return auth()->user()->workoutLogs()
            ->whereIn('program_exercise_id', $day->exercises->pluck('id'))
            ->onDate($this->performedOn)
            ->get()
            ->groupBy('program_exercise_id')
            ->map(static fn (Collection $rows): Collection => $rows->keyBy('set_number'));
    }

    /**
     * The top set of the last session on each exercise — "last time: 10 × 60".
     * It is the one number that turns logging into training, because it is what
     * the next set is decided against.
     *
     * Sorted by date and then by load, so the first row seen for an exercise is
     * the heaviest set of its most recent session.
     *
     * @return array<int, array{reps: int|null, weight: string|null}>
     */
    #[Computed]
    public function previousEfforts(): array
    {
        $day = $this->currentDay;

        if (! $day instanceof ProgramDay) {
            return [];
        }

        $rows = auth()->user()->workoutLogs()
            ->whereIn('program_exercise_id', $day->exercises->pluck('id'))
            ->whereDate('performed_on', '<', $this->performedOn)
            ->where('is_completed', true)
            ->orderByDesc('performed_on')
            ->orderByDesc('weight')
            ->limit(400)
            ->get(['program_exercise_id', 'performed_on', 'reps_done', 'weight']);

        $previous = [];

        foreach ($rows as $row) {
            if (array_key_exists($row->program_exercise_id, $previous)) {
                continue;
            }

            $previous[$row->program_exercise_id] = [
                'reps' => $row->reps_done,
                // `decimal:2` hands back '60.00'; the trainee reads '60'.
                'weight' => $row->weight === null ? null : rtrim(rtrim((string) $row->weight, '0'), '.'),
            ];
        }

        return $previous;
    }

    #[Computed]
    public function totalSets(): int
    {
        return (int) ($this->currentDay?->exercises->sum(static fn ($exercise): int => max(1, (int) $exercise->sets)) ?? 0);
    }

    #[Computed]
    public function loggedSets(): int
    {
        return (int) $this->logs->sum(static fn (Collection $rows): int => $rows->count());
    }

    #[Computed]
    public function pendingSets(): int
    {
        return (int) $this->logs->sum(
            static fn (Collection $rows): int => $rows->filter(static fn (WorkoutLog $log): bool => ! $log->isSynced())->count(),
        );
    }

    #[Computed]
    public function openingRest(): int
    {
        $seconds = (int) ($this->currentDay?->exercises->first()?->rest_seconds ?? 0);

        return $seconds > 0 ? $seconds : 90;
    }

    public function heading(): string
    {
        $day = $this->currentDay;

        if (! $day instanceof ProgramDay) {
            return __('trainee.log.title');
        }

        return filled($day->title)
            ? __('program.days.title', ['number' => $day->day_number, 'title' => $day->title])
            : __('program.days.number', ['number' => $day->day_number]);
    }
};
?>

{{--
  The logging screen. It is the only page in Areen that must keep working with
  the radio off, so it is rendered once by the server and then handed to the
  browser: every tap after this point is resolved in IndexedDB, and the network
  is an afterthought the trainee never waits on.

  There is no `wire:model` and no `wire:click` anywhere below on purpose. A
  Livewire round trip is a network round trip, and in a basement that is a button
  that does nothing.
--}}

@php
    $day = $this->currentDay;
    $program = $this->trainingProgram;
    $logged = $this->loggedSets;
    $total = $this->totalSets;
@endphp

<div class="mx-auto w-full max-w-[1200px] px-4 pt-5 pb-[calc(120px+env(safe-area-inset-bottom))]">
    <x-trainee.offline-runtime/>

    @if (! $day instanceof \App\Models\ProgramDay)
        <h1 class="text-2xl font-bold leading-tight text-ink-50">{{ __('trainee.log.title') }}</h1>

        @php
            // A named programme that did not resolve means one thing: it is not
            // this trainee's. Saying so, and pointing back at it, is the way out —
            // sending them to a dashboard that sends them to a catalogue that
            // sends them back here is the loop this replaces.
            $requested = filled($this->program)
                ? \App\Models\Program::query()->where('slug', $this->program)->first()
                : null;
        @endphp

        <x-ui.empty-state class="mt-6">
            @if ($requested)
                <x-slot:title>{{ __('trainee.log.not_enrolled_title') }}</x-slot:title>
                <x-slot:body>{{ __('trainee.log.not_enrolled_body', ['program' => $requested->name]) }}</x-slot:body>
                <x-slot:action>
                    <x-ui.button :href="route('programs.show', $requested)" wire:navigate>
                        {{ __('program.actions.start') }}
                    </x-ui.button>
                </x-slot:action>
            @else
                <x-slot:title>{{ __('trainee.log.no_day_title') }}</x-slot:title>
                <x-slot:body>{{ __('trainee.log.no_day_body') }}</x-slot:body>
                <x-slot:action>
                    <x-ui.button :href="route('programs.index')" wire:navigate>
                        {{ __('program.actions.browse') }}
                    </x-ui.button>
                </x-slot:action>
            @endif
        </x-ui.empty-state>
    @else
        <header class="flex flex-col gap-1">
            <a href="{{ route('programs.day', ['program' => $program, 'day' => $day->day_number]) }}"
               wire:navigate
               class="-ms-2 inline-flex min-h-11 w-fit items-center rounded-sm px-2 text-sm text-ink-300">
                {{ __('trainee.log.back_to_day') }}
            </a>

            <h1 class="text-2xl font-bold leading-tight text-ink-50">{{ $this->heading() }}</h1>

            <p class="flex flex-wrap items-center gap-x-3 text-sm text-ink-300">
                <span>{{ $program->name }}</span>

                <span class="sr-only">{{ __('trainee.log.performed_on') }}</span>

                <time datetime="{{ $this->performedOn }}" class="tabular">
                    {{ \Illuminate\Support\Carbon::parse($this->performedOn)->isoFormat('D MMMM Y') }}
                </time>
            </p>
        </header>

        {{-- DESIGN.md §11 puts this switch at the top of the workout screen. --}}
        <x-pwa.wake-lock-toggle class="mt-3"/>

        @if ($day->is_rest_day)
            <section class="mx-auto flex max-w-[45ch] flex-col items-center gap-4 px-2 py-16 text-center">
                <span class="flex size-16 items-center justify-center rounded-full border border-ink-700 bg-ink-800 text-brand-400">
                    <x-ui.icon name="moon" class="size-7"/>
                </span>

                <h2 class="text-2xl font-bold text-ink-50">{{ __('trainee.log.rest_day_title') }}</h2>
                <p class="leading-relaxed text-ink-300">{{ __('trainee.log.rest_day_body') }}</p>
            </section>
        @elseif ($day->exercises->isEmpty())
            <x-ui.empty-state class="mt-6">
                <x-slot:title>{{ __('trainee.log.empty_title') }}</x-slot:title>
                <x-slot:body>{{ __('trainee.log.empty_body') }}</x-slot:body>
            </x-ui.empty-state>
        @else
            {{--
              Every attribute the runtime reads is here or on a row inside it. The
              element is `block` because an unknown tag is inline by default.
            --}}
            <areen-set-logger data-date="{{ $this->performedOn }}" class="block">

                <div class="mt-5 flex items-center gap-3">
                    <div role="progressbar"
                         aria-label="{{ __('trainee.log.day_progress') }}"
                         aria-valuemin="0"
                         aria-valuemax="{{ $total }}"
                         aria-valuenow="{{ $logged }}"
                         class="h-1.5 flex-1 overflow-hidden rounded-full bg-ink-800">
                        <div data-progress-bar
                             class="h-full rounded-full bg-brand-400 transition-[inline-size] duration-150 ease-out"
                             style="inline-size: {{ $total > 0 ? round(($logged / $total) * 100) : 0 }}%"></div>
                    </div>

                    <p class="tabular shrink-0 text-sm font-medium text-ink-300">
                        <span data-logged-count>{{ $logged }}</span>/<span>{{ $total }}</span>
                    </p>
                </div>

                <div class="mt-4 flex flex-col gap-3">
                    @foreach ($day->exercises as $index => $programExercise)
                        <x-trainee.exercise-log wire:key="pe-{{ $programExercise->id }}"
                                                :program-exercise="$programExercise"
                                                :number="$index + 1"
                                                :logs="$this->logs[$programExercise->id] ?? []"
                                                :previous="$this->previousEfforts[$programExercise->id] ?? null"/>
                    @endforeach
                </div>

                {{--
                  The fixed bar. It carries the sync readout, and the rest timer
                  rises above it the moment a round is logged — DESIGN.md §11 asks
                  for a flash and a countdown, never a modal, so the panel appears
                  in place and leaves on its own.
                --}}
                <div class="fixed inset-x-0 bottom-0 z-40 print:hidden
                            [@media(display-mode:standalone)]:bottom-[calc(60px+env(safe-area-inset-bottom))]">
                    <div class="mx-auto w-full max-w-[520px] px-4">
                        <div data-rest-panel
                             hidden
                             role="region"
                             aria-label="{{ __('trainee.log.rest_panel') }}"
                             class="pb-3">
                            <x-pwa.rest-timer :seconds="$this->openingRest"/>
                        </div>
                    </div>

                    <div class="border-t border-ink-700 bg-ink-900/95 pt-2 backdrop-blur-md safe-pb
                                [@media(display-mode:standalone)]:pb-3">
                        <div class="mx-auto w-full max-w-[1200px] px-4">
                            <x-trainee.sync-status :pending="$this->pendingSets"/>
                        </div>
                    </div>
                </div>
            </areen-set-logger>
        @endif
    @endif
</div>
