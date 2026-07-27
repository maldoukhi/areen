@props([
    'programExercise',
    'setNumber',
    'log' => null,
])

{{--
  One round: its number, the reps, the load, and the button that commits it.

  Every attribute the runtime needs is on the row itself, so the JavaScript never
  has to walk the tree or hold a map — `data-program-exercise` and
  `data-set-number` are the coordinates, `data-uuid` is the round's identity once
  it has one, and `data-state` / `data-sync` carry everything the eye needs.
  Tailwind's `group-data-*` variants read those two directly, which is why the
  runtime only ever writes attributes and never touches a class list.

  The button is NEVER disabled. DESIGN.md §11: a set logged with no signal is
  still a set, so the control behaves identically offline and the only visible
  difference is the ember dot beside it.
--}}

@php
    $rest = (int) ($programExercise->rest_seconds ?? 0);
    $logged = $log !== null;
@endphp

<div data-set-row
     data-program-exercise="{{ $programExercise->id }}"
     data-set-number="{{ $setNumber }}"
     data-rest="{{ $rest }}"
     data-uuid="{{ $log?->client_uuid }}"
     data-state="{{ $logged ? 'logged' : 'empty' }}"
     data-sync="{{ $logged ? ($log->isSynced() ? 'synced' : 'pending') : '' }}"
     {{ $attributes->class([
         'group grid grid-cols-[2.75rem_1fr_1fr_2.75rem] items-center gap-2 rounded-md border',
         'border-ink-700 bg-ink-900 p-2 transition-colors duration-150 ease-out',
         'data-[state=logged]:border-success/40',
     ]) }}>

    <div class="flex min-h-11 flex-col items-center justify-center gap-1">
        <span class="tabular text-base font-bold leading-none text-ink-300
                     group-data-[state=logged]:text-success">{{ $setNumber }}</span>

        <x-trainee.sync-dot/>
    </div>

    <label class="block">
        <span class="sr-only">{{ __('trainee.log.set', ['number' => $setNumber]) }} — {{ __('trainee.log.reps') }}</span>

        <input data-reps
               type="number"
               inputmode="numeric"
               step="1"
               min="0"
               max="999"
               autocomplete="off"
               value="{{ $log?->reps_done }}"
               class="tabular block min-h-11 w-full rounded-sm border border-ink-700 bg-ink-950 px-2 py-2
                      text-center text-base text-ink-100 transition-colors duration-150 ease-out
                      focus:border-brand-400">
    </label>

    <label class="block">
        <span class="sr-only">{{ __('trainee.log.set', ['number' => $setNumber]) }} — {{ __('trainee.log.weight') }}</span>

        <input data-weight
               type="number"
               inputmode="decimal"
               step="0.5"
               min="0"
               max="9999.99"
               autocomplete="off"
               value="{{ $log?->weight }}"
               class="tabular block min-h-11 w-full rounded-sm border border-ink-700 bg-ink-950 px-2 py-2
                      text-center text-base text-ink-100 transition-colors duration-150 ease-out
                      focus:border-brand-400">
    </label>

    {{--
      One tap, one round. Tapping again after correcting a figure reuses the same
      `client_uuid`, so the server updates the row it already has instead of
      recording the set twice.
    --}}
    <button type="button"
            data-action="log"
            aria-label="{{ __('trainee.log.save') }} — {{ __('trainee.log.set', ['number' => $setNumber]) }}"
            class="inline-flex size-11 items-center justify-center rounded-sm border border-transparent
                   bg-brand-400 text-brand-950 transition-colors duration-150 ease-out
                   hover:bg-brand-500 group-data-[state=logged]:border-success/50
                   group-data-[state=logged]:bg-transparent group-data-[state=logged]:text-success">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="m5 12.5 4.5 4.5L19 7.5"/>
        </svg>

        <span class="sr-only hidden group-data-[state=logged]:inline">{{ __('trainee.log.logged') }}</span>
    </button>

    {{--
      The server refused this round and always will — its exercise is not on this
      account, or its uuid names somebody else's row. Said once, on the row it
      belongs to, and spanning the full width beneath it.
    --}}
    <p class="col-span-4 hidden text-xs leading-normal text-danger group-data-[sync=rejected]:block">
        {{ __('trainee.log.rejected') }}
    </p>
</div>
