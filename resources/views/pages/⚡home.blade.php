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

<div class="px-4 pb-10 safe-pb">
    {{--
      The featured program leads: most visitors are here to open one plan, not to
      browse a catalogue. Everything below it is a way past it.
    --}}
    @if ($featured)
        <section class="pt-6">
            <p class="text-sm font-medium text-brand-400">{{ __('program.featured_title') }}</p>

            <h1 class="mt-2 text-[2rem] font-bold leading-tight text-ink-50">
                {{ $featured->name }}
            </h1>

            @if ($featured->description)
                <p class="mt-3 max-w-[65ch] text-ink-300">{{ $featured->description }}</p>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-ui.chip>{{ __('program.level.'.$featured->level->value) }}</x-ui.chip>

                @if ($featured->goal)
                    <x-ui.chip>{{ __('program.goal.'.$featured->goal) }}</x-ui.chip>
                @endif

                <x-ui.chip>{{ trans_choice('program.days.total', $featured->days_count, ['count' => $featured->days_count]) }}</x-ui.chip>
            </div>

            {{-- Thumb reach: the action that matters sits below the copy, not above it. --}}
            <div class="mt-6">
                <x-ui.button :href="route('programs.show', $featured)" wire:navigate full>
                    {{ __('program.actions.view') }}
                </x-ui.button>
            </div>
        </section>
    @else
        <section class="pt-6">
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
        <section class="mt-10">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-2xl font-semibold text-ink-50">{{ __('program.title') }}</h2>

                <a href="{{ route('programs.index') }}" wire:navigate
                   class="inline-flex min-h-11 items-center text-sm font-medium text-brand-400">
                    {{ __('program.all') }}
                </a>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($programs as $program)
                    <x-ui.card :href="route('programs.show', $program)" wire:key="program-{{ $program->id }}" wire:navigate>
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
        <section class="mt-10">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-2xl font-semibold text-ink-50">{{ __('exercise.title') }}</h2>

                <a href="{{ route('exercises.index') }}" wire:navigate
                   class="inline-flex min-h-11 items-center gap-1 text-sm font-medium text-brand-400">
                    <span class="tabular">{{ $exerciseCount }}</span>
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
