{{--
  DESIGN.md §5 and §9: the Areen mark at 6% behind an invitation and one button.
  The copy invites, it never apologises — "start with your first program", not
  "there is nothing here". Callers pass their own wording through the slots.

    <x-ui.empty-state>
        <x-slot:title>{{ __('program.empty.title') }}</x-slot:title>
        <x-slot:body>{{ __('program.empty.body') }}</x-slot:body>
        <x-slot:action><x-ui.button :href="..." >…</x-ui.button></x-slot:action>
    </x-ui.empty-state>
--}}

<div {{ $attributes->class('relative isolate overflow-hidden rounded-lg border border-ink-700 bg-ink-800 px-5 py-12 text-center') }}>
    <x-brand.mark class="pointer-events-none absolute inset-0 -z-10 m-auto size-40 text-brand-400 opacity-[0.06]"/>

    <div class="mx-auto flex max-w-[45ch] flex-col items-center gap-3">
        <h2 class="text-xl font-semibold text-ink-50">
            {{ $title ?? __('common.states.empty_invite_title') }}
        </h2>

        <p class="text-ink-300">
            {{ $body ?? ($slot->isNotEmpty() ? $slot : __('common.states.empty_invite_body')) }}
        </p>

        @isset($action)
            <div class="mt-3">{{ $action }}</div>
        @endisset
    </div>
</div>
