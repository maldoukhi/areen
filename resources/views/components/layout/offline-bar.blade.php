{{--
  DESIGN.md §11: a thin ember strip at the very top while the network is down —
  ember background, #4A2A08 text, and nothing else on screen gets disabled.

  Alpine ships with Livewire, so the whole thing is three attributes: seed from
  `navigator.onLine`, then follow the window's own online/offline events. The
  server renders it with `display: none`, which is exactly the state `x-show`
  expects, so there is no flash before Alpine boots and no need for `x-cloak`.

  `peer` + `data-offline` let the header underneath drop its own `safe-pt` while
  this strip is showing, so the notch is padded once rather than twice.
--}}

<div x-data="{ offline: ! navigator.onLine }"
     x-on:online.window="offline = false"
     x-on:offline.window="offline = true"
     x-bind:data-offline="offline ? 'true' : 'false'"
     x-show="offline"
     style="display: none"
     role="status"
     aria-live="polite"
     aria-label="{{ __('common.a11y.connection_status') }}"
     {{ $attributes->class('peer safe-pt relative z-50 flex items-center justify-center gap-2 bg-ember pb-1.5 pe-4 ps-4 text-center text-xs font-medium leading-normal text-[#4A2A08] print:hidden') }}>
    <x-ui.icon name="wifi-off" class="size-4 shrink-0"/>
    <span>{{ __('pwa.offline.badge') }}</span>
</div>
