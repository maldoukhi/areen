@props([
    'programExercise',
    'number' => null,
    'media' => null,
])

{{--
  One exercise, one row — DESIGN.md §5:

      square tabular number badge · name · rest with a clock · sets×reps at 20px
      in brand-400 · the video thumbnail.

  The trainee reads this standing up, one-handed, between sets. So the two things
  that answer "what do I do now" — the name and the sets×reps figure — sit on the
  same line, and everything else (equipment, difficulty, rest, tempo, coach notes)
  hangs underneath, indented to clear the badge.

  `media` is a tri-state: null asks the row to show a media block only when the
  exercise actually has a video or a still, true forces the block (and with it the
  placeholder), false suppresses it. Null is the default because every seeded
  exercise has neither, and a column of empty 4:3 boxes would push the last
  exercise of a seven-move day two screens down for no information at all.

  Reused by the private-program view, so it takes a ProgramExercise and reads
  nothing from the route.
--}}

@php
    $exercise = $programExercise->exercise;

    $hasMedia = $exercise !== null
        && (filled(rescue(fn () => $exercise->youtube_id, null, false)) || filled($exercise->media_path));

    $showMedia = $media === null ? $hasMedia : (bool) $media;

    // reps is free text: a range, a word meaning "to failure", a per-side count.
    // It is never parsed, only placed. The separator lives in the translation.
    $prescription = __('exercise.prescription.sets_reps', [
        'sets' => $programExercise->sets,
        'reps' => $programExercise->reps,
    ]);

    $coachNotes = $programExercise->coach_notes;
@endphp

<article {{ $attributes->class('flex flex-col gap-3') }}>
    <div class="flex items-start gap-3">
        {{-- Square, tabular, fixed width so every badge in the day lines up. --}}
        <span class="tabular flex size-11 shrink-0 items-center justify-center rounded-md border border-ink-700 bg-ink-900 text-base font-bold leading-none text-ink-300"
              aria-hidden="true">{{ $number }}</span>

        <div class="flex min-w-0 flex-1 items-start justify-between gap-3">
            <h3 class="min-w-0 text-base font-semibold leading-snug text-ink-50">
                @if ($exercise)
                    <a href="{{ route('exercises.show', $exercise) }}"
                       wire:navigate
                       class="rounded-sm underline-offset-4 hover:underline">{{ $exercise->name }}</a>
                @else
                    {{ $prescription }}
                @endif
            </h3>

            {{-- The hero figure: 20px, brand-400, tabular. DESIGN.md §5. --}}
            <p class="tabular shrink-0 text-end text-xl font-bold leading-none text-brand-400">
                <span class="sr-only">{{ __('exercise.prescription.sets') }} — </span>{{ $prescription }}
            </p>
        </div>
    </div>

    <div class="flex flex-col gap-3 ps-14">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-ink-300">
            @if ($programExercise->rest_seconds)
                <span class="tabular inline-flex items-center gap-1.5">
                    <x-ui.icon name="clock" class="size-4 shrink-0 text-ink-400"/>
                    <span class="sr-only">{{ __('exercise.prescription.rest') }} </span>
                    {{ __('exercise.prescription.rest_value', ['seconds' => $programExercise->rest_seconds]) }}
                </span>
            @endif

            @if (filled($programExercise->tempo))
                <span class="tabular inline-flex items-center gap-1.5">
                    <span class="text-ink-400">{{ __('exercise.prescription.tempo') }}</span>
                    {{ $programExercise->tempo }}
                </span>
            @endif

            @if (filled($programExercise->weight_note))
                <span class="inline-flex items-center gap-1.5">
                    <span class="text-ink-400">{{ __('exercise.prescription.weight') }}</span>
                    {{ $programExercise->weight_note }}
                </span>
            @endif
        </div>

        @if ($exercise)
            <div class="flex flex-wrap items-center gap-1.5">
                @if (filled($exercise->equipment))
                    <x-ui.chip>{{ __('exercise.equipment.'.$exercise->equipment) }}</x-ui.chip>
                @endif

                @if ($exercise->difficulty)
                    <x-ui.chip>{{ $exercise->difficulty->label() }}</x-ui.chip>
                @endif
            </div>
        @endif

        @if ($showMedia && $exercise)
            <x-exercise.video-thumb :exercise="$exercise" class="max-w-sm"/>
        @endif

        @if (filled($coachNotes))
            <p class="rounded-md border-s-2 border-brand-400/40 bg-ink-900/60 px-3 py-2 text-sm leading-relaxed text-ink-200">
                <span class="sr-only">{{ __('exercise.coach_notes.label') }} — </span>{{ $coachNotes }}
            </p>
        @endif
    </div>
</article>
