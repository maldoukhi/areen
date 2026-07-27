<?php

use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Everything the club has for one muscle.
 *
 * This is the library filtered down to a single group, so it borrows the same
 * `<x-ui.exercise-card>` and the same grid rather than growing a second design
 * that would drift out of step with `/exercises`.
 */
new class extends Component
{
    public MuscleGroup $muscleGroup;

    /**
     * `MuscleGroup::getRouteKeyName()` is `slug`, so `/muscles/chest` binds
     * straight to the model and an unknown slug 404s before this runs.
     */
    public function mount(MuscleGroup $muscleGroup): void
    {
        $this->muscleGroup = $muscleGroup;
    }

    /**
     * `with('muscleGroup')` looks redundant on a single-group page, but the card
     * reads `$exercise->muscleGroup->name` for every row and a lazy load would
     * fire one query per card.
     *
     * @return EloquentCollection<int, Exercise>
     */
    #[Computed]
    public function exercises(): EloquentCollection
    {
        return Exercise::query()
            ->active()
            ->forMuscle($this->muscleGroup)
            ->with('muscleGroup')
            // The seeded order inside a group is the order a coach reads them in.
            ->orderBy('id')
            ->get();
    }
};
?>

@section('title', $muscleGroup->name.' · '.__('common.app_name'))

@push('head')
    <x-seo.page :description="__('common.seo.muscle', ['muscle' => $muscleGroup->name])"/>
@endpush

<div class="flex flex-col gap-6 px-4 pt-6 pb-12">
    <nav aria-label="{{ __('exercise.library') }}">
        <a href="{{ route('exercises.index') }}"
           wire:navigate
           class="-ms-2 inline-flex min-h-11 items-center rounded-sm px-2 text-sm font-medium text-brand-400">
            {{ __('exercise.library') }}
        </a>
    </nav>

    <header class="flex flex-col gap-2">
        <h1 class="text-[2rem] font-bold leading-tight text-ink-50">{{ $muscleGroup->name }}</h1>

        <p class="tabular text-sm text-ink-300">
            {{ __('exercise.muscle.exercises_count', ['count' => $this->exercises->count()]) }}
        </p>
    </header>

    @if ($this->exercises->isEmpty())
        <x-ui.empty-state>
            <x-slot:title>{{ __('exercise.empty.title') }}</x-slot:title>
            <x-slot:body>{{ __('exercise.empty.body') }}</x-slot:body>
            <x-slot:action>
                <x-ui.button :href="route('exercises.index')" wire:navigate>
                    {{ __('exercise.library') }}
                </x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        {{-- The same grid as /exercises: one column, two at 640px, three at 1024px. --}}
        <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->exercises as $exercise)
                <li wire:key="exercise-{{ $exercise->getKey() }}" class="flex">
                    <x-ui.exercise-card :exercise="$exercise" :show-muscle="false" :heading-level="2" class="w-full"/>
                </li>
            @endforeach
        </ul>
    @endif

    {{--
      Ten exercises is a long scroll on a 360px screen, so the way onward is
      repeated at the bottom where the thumb already is (DESIGN.md §11) rather
      than only at the top where it started.
    --}}
    <div class="flex">
        <x-ui.button variant="secondary" :href="route('exercises.index')" wire:navigate full>
            {{ __('exercise.library') }}
        </x-ui.button>
    </div>
</div>
