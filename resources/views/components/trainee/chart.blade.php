@props([
    'points' => [],
    'label' => '',
    'unit' => null,
    'tone' => 'brand',
    'decimals' => 1,
])

{{--
  A line, drawn as inline SVG from figures the server already computed.

  No charting library, on purpose. A dependency here would cost more bytes than
  the rest of the page, would need a second network round trip to be useful
  offline, and would draw its own idea of colour and type over DESIGN.md. The
  geometry below is a few lines of arithmetic; the honest trade is to write them.

  Everything is one path plus one polygon, both with `vector-effect` set, so the
  SVG can stretch to any width without the stroke fattening with it.

  `dir="ltr"` is deliberate on an Arabic-first screen: the horizontal axis is
  time, and a time series that runs right to left reads as a decline when it is a
  rise. The figures around it are Western digits for the same reason DESIGN.md §3
  gives — they are read at a glance, standing up.
--}}

@php
    $values = array_map(static fn (array $point): float => (float) $point['value'], $points);
    $count = count($values);

    $max = $count > 0 ? max($values) : 0.0;
    $min = $count > 0 ? min($values) : 0.0;

    // A perfectly flat series would divide by zero and, worse, draw along the
    // floor as though it were the worst possible result. It belongs in the middle.
    $span = ($max - $min) > 0.0001 ? $max - $min : 1.0;
    $flat = ($max - $min) <= 0.0001;

    $width = 320;
    $height = 120;
    $inset = 14;

    $coordinates = [];

    foreach ($values as $index => $value) {
        $x = $count > 1 ? ($index / ($count - 1)) * $width : $width / 2;
        $y = $flat
            ? $height / 2
            : $height - $inset - (($value - $min) / $span) * ($height - (2 * $inset));

        $coordinates[] = round($x, 2).','.round($y, 2);
    }

    $line = implode(' ', $coordinates);

    $first = $count > 0 ? explode(',', $coordinates[0])[0] : '0';
    $last = $count > 0 ? explode(',', $coordinates[$count - 1])[0] : '0';
    $area = $count > 0 ? "{$first},{$height} {$line} {$last},{$height}" : '';

    $tones = [
        'brand' => ['stroke' => 'text-brand-400', 'fill' => 'text-brand-400/15'],
        'ember' => ['stroke' => 'text-ember', 'fill' => 'text-ember/15'],
        'success' => ['stroke' => 'text-success', 'fill' => 'text-success/15'],
    ];

    $colour = $tones[$tone] ?? $tones['brand'];

    $format = static fn (float $value): string => number_format($value, $decimals, '.', '');
@endphp

<figure {{ $attributes->class('flex flex-col gap-3') }}>
    <figcaption class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <span class="text-sm font-medium text-ink-200">{{ $label }}</span>

        @if ($count > 0)
            <span class="tabular text-sm text-ink-400">
                {{ $format($min) }}@if ($unit) {{ $unit }}@endif — {{ $format($max) }}@if ($unit) {{ $unit }}@endif
            </span>
        @endif
    </figcaption>

    @if ($count > 0)
        <div dir="ltr" class="w-full">
            <svg viewBox="0 0 {{ $width }} {{ $height }}"
                 preserveAspectRatio="none"
                 role="img"
                 aria-label="{{ $label }}"
                 class="h-[132px] w-full overflow-visible">

                {{-- Three hairlines, so a value can be placed without an axis. --}}
                @foreach ([0.25, 0.5, 0.75] as $fraction)
                    <line x1="0" x2="{{ $width }}"
                          y1="{{ round($height * $fraction, 2) }}"
                          y2="{{ round($height * $fraction, 2) }}"
                          stroke="currentColor" stroke-width="1"
                          vector-effect="non-scaling-stroke"
                          class="text-ink-700/70"/>
                @endforeach

                @if ($count > 1)
                    <polygon points="{{ $area }}" fill="currentColor" class="{{ $colour['fill'] }}"/>

                    <polyline points="{{ $line }}"
                              fill="none" stroke="currentColor" stroke-width="2"
                              stroke-linecap="round" stroke-linejoin="round"
                              vector-effect="non-scaling-stroke"
                              class="{{ $colour['stroke'] }}"/>
                @endif

                @foreach ($coordinates as $index => $coordinate)
                    @php([$cx, $cy] = explode(',', $coordinate))

                    <circle cx="{{ $cx }}" cy="{{ $cy }}"
                            r="{{ $index === $count - 1 ? 4 : 2.5 }}"
                            fill="currentColor"
                            vector-effect="non-scaling-stroke"
                            class="{{ $colour['stroke'] }}"/>
                @endforeach
            </svg>
        </div>

        {{--
          The same data as text. A line drawn in SVG is unreadable to a screen
          reader whatever it is labelled, so the numbers are here too.
        --}}
        <ul class="sr-only">
            @foreach ($points as $index => $point)
                <li wire:key="point-{{ $index }}">
                    {{ __('trainee.progress.chart_point', [
                        'date' => $point['label'],
                        'value' => $format((float) $point['value']).($unit ? ' '.$unit : ''),
                    ]) }}
                </li>
            @endforeach
        </ul>

        <div class="tabular flex items-center justify-between text-xs text-ink-400">
            <span>{{ __('trainee.progress.range_from', ['date' => $points[0]['label']]) }}</span>
            <span>{{ __('trainee.progress.range_to', ['date' => $points[$count - 1]['label']]) }}</span>
        </div>

        @if ($count === 1)
            <p class="text-xs leading-normal text-ink-400">{{ __('trainee.progress.single_point') }}</p>
        @endif
    @endif
</figure>
