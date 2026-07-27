@props([
    'programExercise',
    'number' => null,
    'logs' => [],
    'previous' => null,
])

{{--
  One prescribed exercise on the logging screen: what the coach asked for, what
  was done last time, and a row per round.

  Deliberately not `<x-exercise.row>`. That component is the reading view — media,
  chips, coach notes — and the trainee is not reading here, they are between sets
  and out of breath. Everything that is not a number the thumb is about to change
  has been stripped out.

  The set count comes from the prescription, widened to cover any round already
  logged beyond it: a trainee who squeezed a fourth set out of a three-set
  prescription must still see it when the page reloads.
--}}

@php
    $exercise = $programExercise->exercise;

    $prescribed = max(1, (int) ($programExercise->sets ?? 1));
    $highestLogged = collect($logs)->keys()->map(static fn ($key): int => (int) $key)->max() ?? 0;
    $setNumbers = range(1, max($prescribed, $highestLogged));

    $prescription = __('exercise.prescription.sets_reps', [
        'sets' => $programExercise->sets,
        'reps' => $programExercise->reps,
    ]);

    $unit = __('common.units.'.config('areen.weight_unit', 'kg'));
@endphp

<section {{ $attributes->class('rounded-lg border border-ink-700 bg-ink-800 p-4') }}>
    <div class="flex items-start gap-3">
        @if ($number !== null)
            <span aria-hidden="true"
                  class="tabular flex size-9 shrink-0 items-center justify-center rounded-md border
                         border-ink-700 bg-ink-900 text-sm font-bold leading-none text-ink-300">{{ $number }}</span>
        @endif

        <div class="flex min-w-0 flex-1 items-start justify-between gap-3">
            <h2 class="min-w-0 text-base font-semibold leading-snug text-ink-50">
                @if ($exercise)
                    <a href="{{ route('exercises.show', $exercise) }}"
                       wire:navigate
                       class="-my-2 block rounded-sm py-2 underline-offset-4 hover:underline">{{ $exercise->name }}</a>
                @else
                    {{ __('exercise.singular') }}
                @endif
            </h2>

            {{-- DESIGN.md §5: the sets×reps figure at 20px in brand-400. --}}
            <p class="tabular shrink-0 text-end text-xl font-bold leading-none text-brand-400">
                <span class="sr-only">{{ __('exercise.prescription.sets') }} — </span>{{ $prescription }}
            </p>
        </div>
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-300">
        @if ($programExercise->rest_seconds)
            <span class="tabular inline-flex items-center gap-1.5">
                <x-ui.icon name="clock" class="size-4 shrink-0"/>
                <span class="sr-only">{{ __('exercise.prescription.rest') }} </span>
                {{ __('exercise.prescription.rest_value', ['seconds' => $programExercise->rest_seconds]) }}
            </span>
        @endif

        @if ($previous !== null)
            <span class="tabular">
                @if ($previous['weight'] !== null)
                    {{ __('trainee.log.previous', [
                        'reps' => $previous['reps'] ?? '—',
                        'weight' => $previous['weight'].' '.$unit,
                    ]) }}
                @else
                    {{ __('trainee.log.previous_reps_only', ['reps' => $previous['reps'] ?? '—']) }}
                @endif
            </span>
        @endif
    </div>

    {{--
      Column captions once for the whole block instead of a label on every input.
      At 360px a per-row label is the difference between four columns and two.
    --}}
    <div aria-hidden="true"
         class="mt-4 grid grid-cols-[2.75rem_1fr_1fr_2.75rem] gap-2 px-2 text-center text-xs font-medium text-ink-300">
        <span>{{ __('exercise.prescription.sets') }}</span>
        <span>{{ __('trainee.log.reps') }}</span>
        <span>{{ __('trainee.log.weight_with_unit', ['unit' => $unit]) }}</span>
        <span></span>
    </div>

    <div class="mt-1 flex flex-col gap-2">
        @foreach ($setNumbers as $setNumber)
            <x-trainee.set-row wire:key="set-{{ $programExercise->id }}-{{ $setNumber }}"
                               :program-exercise="$programExercise"
                               :set-number="$setNumber"
                               :log="$logs[$setNumber] ?? null"/>
        @endforeach
    </div>
</section>
