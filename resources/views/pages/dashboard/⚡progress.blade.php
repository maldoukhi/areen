<?php

use App\Actions\Trainee\BuildProgressSeries;
use App\Models\BodyMetric;
use App\Models\WorkoutLog;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    /**
     * A string for the same reason as on the log screen: Livewire assigns URL
     * values onto matching properties before mount, so a typed int would make
     * `?exercise=x` a 500 rather than a fallback.
     */
    #[Url]
    public ?string $exercise = null;

    public function mount(): void
    {
        $this->authorize('viewAny', WorkoutLog::class);
        $this->authorize('viewAny', BodyMetric::class);
    }

    public function rendering($view): void
    {
        $view->title(__('trainee.progress.title'));
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function series(): array
    {
        $id = filter_var($this->exercise, FILTER_VALIDATE_INT);

        return app(BuildProgressSeries::class)->handle(auth()->user(), $id === false ? null : $id);
    }

    /**
     * Top load per session, ready for the SVG.
     *
     * @return list<array{label: string, value: float}>
     */
    #[Computed]
    public function weightPoints(): array
    {
        return $this->points('top_weight');
    }

    /**
     * @return list<array{label: string, value: float}>
     */
    #[Computed]
    public function volumePoints(): array
    {
        return $this->points('volume');
    }

    /**
     * @return list<array{label: string, value: float}>
     */
    #[Computed]
    public function bodyPoints(): array
    {
        $points = [];

        foreach ($this->series['body'] as $row) {
            if ($row['weight'] === null) {
                continue;
            }

            $points[] = ['label' => $row['date']->isoFormat('D MMM'), 'value' => $row['weight']];
        }

        return $points;
    }

    /**
     * @return array{weight: float|null, body_fat: float|null, date: Carbon|null}
     */
    #[Computed]
    public function latestMetric(): array
    {
        $body = $this->series['body'];
        $last = $body === [] ? null : $body[count($body) - 1];

        return [
            'weight' => $last['weight'] ?? null,
            'body_fat' => $last['body_fat'] ?? null,
            'date' => $last['date'] ?? null,
        ];
    }

    /**
     * The difference between the first weigh-in on record and the latest one.
     * Positive or negative is left to the reader — Areen has no opinion on which
     * direction is progress, because the goal is the coach's to set.
     */
    #[Computed]
    public function weightChange(): ?float
    {
        $points = $this->bodyPoints;

        if (count($points) < 2) {
            return null;
        }

        return round($points[count($points) - 1]['value'] - $points[0]['value'], 1);
    }

    /**
     * @return list<array{label: string, value: float}>
     */
    private function points(string $key): array
    {
        $points = [];

        foreach ($this->series['sessions'] as $session) {
            if ($session[$key] === null) {
                continue;
            }

            $points[] = ['label' => $session['date']->isoFormat('D MMM'), 'value' => (float) $session[$key]];
        }

        return $points;
    }

    public function exerciseUrl(int $id): string
    {
        return route('dashboard.progress', ['exercise' => $id]);
    }
};
?>

{{--
  Progress: three lines and a form.

  The charts are inline SVG drawn from figures the server computed, with no
  charting library anywhere near them — see the note in x-trainee.chart for why
  that is a feature and not a shortcut.
--}}

@php
    $series = $this->series;
    $unit = __('common.units.'.config('areen.weight_unit', 'kg'));
    $latest = $this->latestMetric;
@endphp

<div class="mx-auto w-full max-w-[1200px] px-4 pt-5 pb-12">
    <x-trainee.offline-runtime/>

    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold leading-tight text-ink-50">{{ __('trainee.progress.title') }}</h1>
    </header>

    @if (session('status'))
        <p role="status"
           class="mt-4 rounded-md border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">
            {{ session('status') }}
        </p>
    @endif

    @if ($series['exercises'] === [])
        <x-ui.empty-state class="mt-6">
            <x-slot:title>{{ __('trainee.progress.empty_title') }}</x-slot:title>
            <x-slot:body>{{ __('trainee.progress.empty_body') }}</x-slot:body>
            <x-slot:action>
                <x-ui.button :href="route('dashboard.log')" wire:navigate>
                    {{ __('trainee.progress.start_logging') }}
                </x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <section aria-labelledby="exercise-heading" class="mt-6">
            <h2 id="exercise-heading" class="text-lg font-semibold text-ink-50">
                {{ __('trainee.progress.pick_exercise') }}
            </h2>

            <ul role="list"
                class="-mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($series['exercises'] as $option)
                    <li wire:key="exercise-{{ $option['id'] }}" class="shrink-0">
                        <x-ui.chip :href="$this->exerciseUrl($option['id'])"
                                   :active="$series['exercise'] !== null && $series['exercise']['id'] === $option['id']"
                                   wire:navigate>
                            {{ $option['name'] }}
                        </x-ui.chip>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-label="{{ $series['exercise']['name'] ?? __('trainee.progress.title') }}" class="mt-6 flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-3">
                <x-ui.card>
                    <x-ui.metric :value="$series['best_weight'] === null ? '—' : rtrim(rtrim(number_format($series['best_weight'], 2, '.', ''), '0'), '.')"
                                 :unit="$series['best_weight'] === null ? null : $unit"
                                 :caption="__('trainee.progress.top_weight')"/>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.metric :value="count($series['sessions'])"
                                 :caption="__('trainee.progress.sessions')"
                                 tone="ink"/>
                </x-ui.card>
            </div>

            <x-ui.card>
                <x-trainee.chart :points="$this->weightPoints"
                                 :label="__('trainee.progress.chart_weight')"
                                 :unit="$unit"
                                 :decimals="1"
                                 tone="brand"/>
            </x-ui.card>

            <x-ui.card>
                <x-trainee.chart :points="$this->volumePoints"
                                 :label="__('trainee.progress.chart_volume')"
                                 :unit="$unit"
                                 :decimals="0"
                                 tone="success"/>
            </x-ui.card>
        </section>
    @endif

    <section aria-labelledby="metrics-heading" class="mt-10">
        <h2 id="metrics-heading" class="text-lg font-semibold text-ink-50">{{ __('trainee.metrics.title') }}</h2>

        @if ($this->bodyPoints === [])
            <x-ui.empty-state class="mt-3">
                <x-slot:title>{{ __('trainee.metrics.empty_title') }}</x-slot:title>
                <x-slot:body>{{ __('trainee.metrics.empty_body') }}</x-slot:body>
            </x-ui.empty-state>
        @else
            <div class="mt-3 grid grid-cols-2 gap-3">
                <x-ui.card>
                    <x-ui.metric :value="$latest['weight'] === null ? '—' : number_format($latest['weight'], 1, '.', '')"
                                 :unit="$latest['weight'] === null ? null : $unit"
                                 :caption="__('trainee.metrics.latest')"
                                 tone="ink"/>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.metric :value="$this->weightChange === null
                                    ? '—'
                                    : ($this->weightChange > 0 ? '+' : '').number_format($this->weightChange, 1, '.', '')"
                                 :unit="$this->weightChange === null ? null : $unit"
                                 :caption="__('trainee.metrics.change')"
                                 tone="ink"/>
                </x-ui.card>
            </div>

            <x-ui.card class="mt-3">
                <x-trainee.chart :points="$this->bodyPoints"
                                 :label="__('trainee.progress.chart_body_weight')"
                                 :unit="$unit"
                                 :decimals="1"
                                 tone="brand"/>
            </x-ui.card>
        @endif

        {{--
          A plain form post, not a Livewire action: recording a weigh-in is one
          write with one answer, and a form that works without JavaScript is one
          fewer thing to apologise for on a flaky connection.
        --}}
        <form method="POST"
              action="{{ route('dashboard.metrics.store') }}"
              class="mt-4 flex flex-col gap-4 rounded-lg border border-ink-700 bg-ink-800 p-5">
            @csrf

            <h3 class="text-base font-semibold text-ink-50">{{ __('trainee.metrics.add') }}</h3>

            <x-ui.field name="measured_on"
                        type="date"
                        :label="__('trainee.metrics.measured_on')"
                        :value="old('measured_on', \Illuminate\Support\Carbon::today()->toDateString())"
                        :error="$errors->first('measured_on')"
                        :required="true"/>

            <div class="grid grid-cols-2 gap-3">
                <x-ui.field name="weight"
                            type="number"
                            step="0.1"
                            min="20"
                            max="400"
                            inputmode="decimal"
                            :label="__('trainee.metrics.weight').' ('.$unit.')'"
                            :value="old('weight')"
                            :error="$errors->first('weight')"/>

                <x-ui.field name="body_fat"
                            type="number"
                            step="0.1"
                            min="1"
                            max="70"
                            inputmode="decimal"
                            :label="__('trainee.metrics.body_fat').' ('.__('trainee.metrics.body_fat_unit').')'"
                            :value="old('body_fat')"
                            :error="$errors->first('body_fat')"/>
            </div>

            <x-ui.field name="notes"
                        :label="__('trainee.metrics.notes')"
                        :error="$errors->first('notes')">
                <textarea id="field-notes"
                          name="notes"
                          rows="2"
                          placeholder="{{ __('trainee.metrics.notes_placeholder') }}"
                          class="block min-h-11 w-full rounded-sm border border-ink-700 bg-ink-900 px-3 py-2.5
                                 text-base text-ink-100 transition-colors duration-150 ease-out
                                 placeholder:text-ink-500 focus:border-brand-400">{{ old('notes') }}</textarea>
            </x-ui.field>

            <x-ui.button type="submit" :full="true">{{ __('trainee.metrics.save') }}</x-ui.button>
        </form>
    </section>
</div>
