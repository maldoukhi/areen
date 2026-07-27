<?php

use App\Models\Setting;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        return ['club' => Setting::current()];
    }
};
?>

{{--
  Everything on this page belongs to the club, not to Areen, so every field comes
  from the settings row and each one renders only when it has been filled in. A
  club that has not entered its Instagram handle gets no empty Instagram row.
--}}
<div class="px-4 pb-10 pt-6 safe-pb">
    <h1 class="text-[2rem] font-bold leading-tight text-ink-50">
        {{ $club->club_name ?? __('common.app_name') }}
    </h1>

    @if ($club->description)
        <p class="mt-4 max-w-[65ch] text-ink-300">{{ $club->description }}</p>
    @endif

    @php
        $lines = collect([
            // The separator is translated too — the two languages do not punctuate a list alike.
            ['label' => __('common.contact.address'), 'value' => collect([$club->address, $club->city])->filter()->implode(__('common.list_separator')), 'href' => $club->map_url],
            ['label' => __('common.contact.phone'), 'value' => $club->phone, 'href' => $club->phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $club->phone) : null],
            ['label' => __('common.contact.whatsapp'), 'value' => $club->whatsapp, 'href' => $club->whatsapp ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $club->whatsapp) : null],
            ['label' => __('common.contact.instagram'), 'value' => $club->instagram, 'href' => $club->instagram ? 'https://instagram.com/'.ltrim($club->instagram, '@') : null],
        ])->filter(fn (array $line): bool => filled($line['value']));
    @endphp

    @if ($lines->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-2xl font-semibold text-ink-50">{{ __('common.footer.contact') }}</h2>

            <dl class="mt-4 divide-y divide-ink-800 border-y border-ink-800">
                @foreach ($lines as $line)
                    <div class="flex min-h-14 items-center justify-between gap-4 py-3" wire:key="line-{{ $loop->index }}">
                        <dt class="text-sm text-ink-400">{{ $line['label'] }}</dt>

                        <dd class="text-end text-ink-100">
                            @if ($line['href'])
                                <a href="{{ $line['href'] }}" rel="noopener"
                                   class="inline-flex min-h-11 items-center text-brand-400">{{ $line['value'] }}</a>
                            @else
                                {{ $line['value'] }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    <section class="mt-10">
        <x-ui.button :href="route('programs.index')" wire:navigate full>
            {{ __('program.actions.browse') }}
        </x-ui.button>
    </section>
</div>
