@props(['settings' => null])

{{--
  Club contact details, every one of them optional and every one of them read
  from the `settings` row — nothing here is written into the code. The model may
  not exist yet, so the lookup is guarded and the footer simply thins out.

  Hidden once the app is installed: someone in standalone mode has the bottom
  nav instead, and DESIGN.md §11 keeps external links out of that shell. Hidden
  in print too (DESIGN.md §8).
--}}

@php
    $settings ??= rescue(
        fn () => class_exists(\App\Models\Setting::class) && \Illuminate\Support\Facades\Schema::hasTable('settings')
            ? \App\Models\Setting::current()
            : null,
        null,
        false,
    );

    $value = fn (string $key) => rescue(fn () => $settings?->{$key}, null, false);

    $locale = app()->getLocale();

    // `club_name`, `address` and `city` are the model's locale-aware accessors;
    // the raw columns are the fallback for as long as they may not be there yet.
    $clubName = $value('club_name') ?: $value('club_name_'.$locale) ?: $value('club_name_ar') ?: __('common.app_name');
    $address = $value('address') ?: $value('address_'.$locale) ?: $value('address_ar');
    $city = $value('city') ?: $value('city_'.$locale) ?: $value('city_ar');
    // A middle dot reads the same in both directions, so no punctuation is
    // hardcoded for one script.
    $addressLine = implode(' · ', array_filter([$address, $city]));

    $phone = $value('phone');
    $whatsapp = $value('whatsapp');
    $instagram = $value('instagram');
    $mapUrl = $value('map_url');

    $whatsappUrl = $whatsapp
        ? 'https://wa.me/'.preg_replace('/\D+/', '', (string) $whatsapp)
        : null;

    $instagramUrl = $instagram
        ? (\Illuminate\Support\Str::startsWith($instagram, ['http://', 'https://'])
            ? $instagram
            : 'https://instagram.com/'.ltrim((string) $instagram, '@'))
        : null;

    $links = array_values(array_filter([
        $phone ? ['href' => 'tel:'.preg_replace('/[^\d+]+/', '', (string) $phone), 'icon' => 'phone', 'label' => __('common.contact.phone'), 'value' => $phone] : null,
        $whatsappUrl ? ['href' => $whatsappUrl, 'icon' => 'whatsapp', 'label' => __('common.contact.whatsapp'), 'value' => __('common.contact.whatsapp')] : null,
        $instagramUrl ? ['href' => $instagramUrl, 'icon' => 'instagram', 'label' => __('common.contact.instagram'), 'value' => __('common.contact.instagram')] : null,
        $mapUrl ? ['href' => $mapUrl, 'icon' => 'map', 'label' => __('common.contact.map'), 'value' => __('common.contact.map')] : null,
    ]));
@endphp

<footer {{ $attributes->class('mt-8 border-t border-ink-800 bg-ink-900 safe-pb [@media(display-mode:standalone)]:hidden print:hidden') }}>
    <div class="mx-auto flex w-full max-w-[1200px] flex-col gap-6 px-4 pt-8 text-start">
        <div class="flex flex-col gap-2">
            <p class="text-base font-semibold text-ink-100">{{ $clubName }}</p>

            @if ($addressLine !== '')
                <p class="text-sm leading-relaxed text-ink-300">{{ $addressLine }}</p>
            @endif
        </div>

        @if ($links)
            <div class="flex flex-col gap-2">
                <h2 class="text-xs font-medium text-ink-300">{{ __('common.footer.contact') }}</h2>

                <ul class="flex flex-wrap gap-x-2 gap-y-1">
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link['href'] }}"
                               @if (\Illuminate\Support\Str::startsWith($link['href'], 'http')) target="_blank" rel="noopener noreferrer" @endif
                               aria-label="{{ $link['label'] }}"
                               class="-ms-3 inline-flex min-h-11 items-center gap-2 rounded-sm px-3 text-sm text-ink-200
                                      transition-colors duration-150 ease-out hover:bg-ink-800">
                                <x-ui.icon :name="$link['icon']" class="size-5 shrink-0 text-brand-400"/>
                                <span dir="auto">{{ $link['value'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="text-xs text-ink-300">
            {{ __('common.footer.powered_by', ['name' => __('common.app_name')]) }}
        </p>
    </div>
</footer>
