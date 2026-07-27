<?php

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        return [
            'featured' => Program::query()->published()->featured()->ordered()->first(),
            'programs' => Program::query()->published()->ordered()->take(4)->get(),
            'muscles' => MuscleGroup::query()->ordered()->withCount('exercises')->get(),
            'exerciseCount' => Exercise::query()->active()->count(),
        ];
    }
};
?>

<div class="pb-16 safe-pb">
    {{--
      The featured program leads: most visitors arrive to open one plan, not to
      browse a catalogue. Everything below it is a way past it.

      On a phone this is one column and the action sits under the copy, in thumb
      reach. From `lg` it becomes two, because a full-width button spanning
      1200px reads as a banner rather than a decision.
    --}}
    @if ($featured)
        <section class="relative overflow-hidden border-b border-ink-800 hero-glow">
            <div class="relative mx-auto grid max-w-[1200px] gap-10 px-4 pb-14 pt-10
                        lg:grid-cols-[minmax(0,1fr)_360px] lg:items-center lg:gap-16 lg:pb-20 lg:pt-16">
                <div>
                    <p class="flex items-center gap-2 text-sm font-medium text-brand-400" data-reveal>
                        <span class="inline-block size-1.5 rounded-full bg-brand-400"></span>
                        {{ __('program.featured_title') }}
                    </p>

                    <h1 class="mt-3 text-[clamp(2.25rem,5vw,3rem)] font-bold leading-[1.15] text-ink-50" data-reveal>
                        {{ $featured->name }}
                    </h1>

                    @if ($featured->description)
                        <p class="mt-4 max-w-[55ch] text-lg text-ink-300" data-reveal>{{ $featured->description }}</p>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center gap-2" data-reveal>
                        <x-ui.chip>{{ __('program.level.'.$featured->level->value) }}</x-ui.chip>

                        @if ($featured->goal)
                            <x-ui.chip>{{ __('program.goal.'.$featured->goal) }}</x-ui.chip>
                        @endif

                        <x-ui.chip>{{ trans_choice('program.days.total', $featured->days_count, ['count' => $featured->days_count]) }}</x-ui.chip>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row" data-reveal>
                        <x-ui.button :href="route('programs.show', $featured)" wire:navigate
                                     class="w-full sm:w-auto">
                            {{ __('program.actions.view') }}
                        </x-ui.button>

                        <x-ui.button :href="route('programs.index')" wire:navigate variant="secondary"
                                     class="w-full sm:w-auto">
                            {{ __('program.all') }}
                        </x-ui.button>
                    </div>
                </div>

                {{-- Decorative, and the first thing to go when the screen is narrow. --}}
                <x-ui.arena class="mx-auto hidden w-full max-w-[360px] lg:block" data-reveal/>
            </div>
        </section>
    @else
        <section class="mx-auto max-w-[1200px] px-4 pt-10">
            <x-ui.empty-state :title="__('program.empty.title')" :body="__('program.empty.body')">
                <x-slot:action>
                    <x-ui.button :href="route('programs.index')" wire:navigate>
                        {{ __('program.empty.action') }}
                    </x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </section>
    @endif

    @if ($programs->isNotEmpty())
        <section class="mx-auto mt-14 max-w-[1200px] px-4">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-2xl font-semibold text-ink-50" data-reveal>{{ __('program.title') }}</h2>

                <a href="{{ route('programs.index') }}" wire:navigate
                   class="inline-flex min-h-11 items-center text-sm font-medium text-brand-400">
                    {{ __('program.all') }}
                </a>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($programs as $program)
                    <x-ui.card :href="route('programs.show', $program)" wire:key="program-{{ $program->id }}" wire:navigate data-reveal>
                        <h3 class="text-lg font-semibold text-ink-50">{{ $program->name }}</h3>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-ui.chip>{{ __('program.level.'.$program->level->value) }}</x-ui.chip>
                            <x-ui.chip>{{ trans_choice('program.days.total', $program->days_count, ['count' => $program->days_count]) }}</x-ui.chip>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </section>
    @endif

    @if ($muscles->isNotEmpty())
        <section class="mx-auto mt-14 max-w-[1200px] px-4">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-2xl font-semibold text-ink-50" data-reveal>{{ __('exercise.title') }}</h2>

                <a href="{{ route('exercises.index') }}" wire:navigate
                   class="inline-flex min-h-11 items-center gap-1 text-sm font-medium text-brand-400">
                    <span class="tabular" data-count-to="{{ $exerciseCount }}">{{ $exerciseCount }}</span>
                </a>
            </div>

            {{-- A scrollable strip, not a grid: at 360px nine chips in a grid wrap into a wall. --}}
            <div class="-mx-4 mt-4 flex gap-2 overflow-x-auto px-4 pb-1">
                @foreach ($muscles as $muscle)
                    <x-ui.chip :href="route('muscles.show', $muscle)" wire:key="muscle-{{ $muscle->id }}" wire:navigate>
                        {{ $muscle->name }}
                        <span class="tabular text-ink-400">{{ $muscle->exercises_count }}</span>
                    </x-ui.chip>
                @endforeach
            </div>
        </section>
    @endif
</div>
