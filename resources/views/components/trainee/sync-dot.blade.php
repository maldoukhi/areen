{{--
  DESIGN.md §11: a round that has not reached the server yet carries a small
  ember dot until it does. Nothing else marks it — the set is logged either way,
  and the dot answers "has it left the phone", not "did it count".

  Driven entirely by the `data-sync` attribute the JavaScript writes on the row,
  so there is no class juggling and the state survives any repaint. Place it
  inside an element carrying `group` and `data-sync`.
--}}

<span aria-hidden="true"
      {{ $attributes->class('hidden size-2 shrink-0 rounded-full bg-ember group-data-[sync=pending]:block') }}></span>

<span class="sr-only hidden group-data-[sync=pending]:inline">{{ __('pwa.offline.unsynced') }}</span>
<span class="sr-only hidden group-data-[sync=synced]:inline">{{ __('pwa.offline.synced') }}</span>
