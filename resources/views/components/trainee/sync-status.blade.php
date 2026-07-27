@props([
    'pending' => 0,
])

{{--
  How many rounds are still on the phone. That is the entire report.

  It is not an error state and must never read like one: the set is logged, the
  question is only whether it has left the device yet. So there is no red, no
  warning icon, no "failed" — an ember dot and a count, matching the dot on the
  rows themselves (DESIGN.md §11).

  "Send them now" is offered because a trainee who has just walked outside knows
  they have signal before any event does. It is an accelerator; the queue drains
  on its own from four other triggers.

  A named group (`group/sync`) rather than a bare one, so the set rows nested in
  the same subtree keep their own `data-sync` variants to themselves.
--}}

<div data-sync-status
     data-pending="{{ $pending > 0 ? 'true' : 'false' }}"
     role="status"
     aria-live="polite"
     {{ $attributes->class('group/sync flex min-h-11 flex-wrap items-center gap-x-3 gap-y-1') }}>

    <span class="hidden items-center gap-2 text-sm text-ink-200 group-data-[pending=true]/sync:inline-flex">
        <span aria-hidden="true" class="size-2 shrink-0 rounded-full bg-ember"></span>
        {{ __('trainee.log.pending') }}
        <span data-pending-count class="tabular font-bold text-ember">{{ $pending }}</span>
    </span>

    <span class="inline-flex items-center gap-2 text-sm text-ink-400 group-data-[pending=true]/sync:hidden">
        {{-- Drawn here rather than added to x-ui.icon, which P1 owns. --}}
        <svg class="size-4 shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M12 21a9 9 0 1 0-9-9"/>
            <path d="M3 21v-6h6"/>
            <path d="m9 12 2.5 2.5L16 10"/>
        </svg>
        {{ __('trainee.log.all_synced') }}
    </span>

    <button type="button"
            data-action="sync"
            class="hidden min-h-11 items-center rounded-sm px-2 text-sm font-medium text-brand-400
                   underline-offset-4 hover:underline group-data-[pending=true]/sync:inline-flex">
        {{ __('trainee.log.sync_now') }}
    </button>
</div>
