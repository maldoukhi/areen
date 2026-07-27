@props([
    'item',
    'index',
    'count',
    'handler' => 'reorder',
])

{{--
  The keyboard's way through a drag-and-drop list.

  `wire:sort` is a pointer gesture and nothing else: press, drag, drop. A
  keyboard cannot press-and-drag, and neither can a screen reader — so without
  these two buttons the ordering of a day's exercises and of the muscle groups
  was reachable by mouse and by touch and by nobody else. That is a WCAG 2.1.1
  failure, not a rough edge.

  They call the same handler the drop does, with the same contract — one moved
  row and its new index — so the two routes cannot drift apart and there is no
  second write path to keep correct.

  The button at each end of the list is disabled rather than removed, so the
  control does not jump under the finger as a row travels, and so the row count
  stays the same on every line.
--}}

<div {{ $attributes->class('flex shrink-0 flex-col') }}>
    <button type="button"
            @if ($index > 0)
                wire:click="{{ $handler }}({{ $item }}, {{ $index - 1 }})"
            @else
                disabled
            @endif
            aria-label="{{ __('common.a11y.move_up') }}"
            @class([
                'inline-flex h-6 w-11 items-center justify-center rounded-sm',
                'text-ink-300 hover:bg-ink-700' => $index > 0,
                'cursor-not-allowed text-ink-600' => $index === 0,
            ])>
        <x-admin.icon name="chevron-up" class="size-4"/>
    </button>

    <button type="button"
            @if ($index < $count - 1)
                wire:click="{{ $handler }}({{ $item }}, {{ $index + 1 }})"
            @else
                disabled
            @endif
            aria-label="{{ __('common.a11y.move_down') }}"
            @class([
                'inline-flex h-6 w-11 items-center justify-center rounded-sm',
                'text-ink-300 hover:bg-ink-700' => $index < $count - 1,
                'cursor-not-allowed text-ink-600' => $index >= $count - 1,
            ])>
        <x-admin.icon name="chevron-down" class="size-4"/>
    </button>
</div>
