<?php

use App\Enums\Difficulty;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The exercise library.
 *
 * Every filter lives in the query string, so a filtered shelf is a link a coach
 * can paste into WhatsApp and a reload lands on exactly the same view.
 *
 * The results block is an `@island`: the search box carries `wire:island` and
 * the chips sit inside the island, so a keystroke re-renders the grid alone and
 * leaves the header, the shell and the input itself untouched.
 */
new class extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'muscle', except: '')]
    public string $muscle = '';

    #[Url(as: 'equipment', except: '')]
    public string $equipment = '';

    #[Url(as: 'level', except: '')]
    public string $difficulty = '';

    /**
     * A stale slug in a shared link degrades to the full library rather than to
     * an empty shelf blamed on a filter the trainee cannot see in the bar.
     */
    public function mount(): void
    {
        $this->discardUnknownFilters();
    }

    public function toggleMuscle(string $slug): void
    {
        $this->muscle = $this->muscle === $slug ? '' : $slug;
    }

    public function toggleEquipment(string $slug): void
    {
        $this->equipment = $this->equipment === $slug ? '' : $slug;
    }

    public function toggleDifficulty(string $value): void
    {
        $this->difficulty = $this->difficulty === $value ? '' : $value;
    }

    /**
     * @return EloquentCollection<int, MuscleGroup>
     */
    #[Computed]
    public function muscleGroups(): EloquentCollection
    {
        return MuscleGroup::query()->ordered()->get();
    }

    /**
     * The equipment options are read off the shelf itself, so the bar can never
     * offer a filter that has nothing behind it.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function equipmentOptions(): Collection
    {
        return Exercise::query()
            ->active()
            ->whereNotNull('equipment')
            ->distinct()
            ->orderBy('equipment')
            ->pluck('equipment');
    }

    /**
     * @return EloquentCollection<int, Exercise>
     */
    #[Computed]
    public function results(): EloquentCollection
    {
        return $this->filtered()->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function activeFilters(): array
    {
        return array_filter([
            'search' => trim($this->search),
            'muscle' => $this->muscle,
            'equipment' => $this->equipment,
            'difficulty' => $this->difficulty,
        ], fn (string $value): bool => $value !== '');
    }

    /**
     * Names the filter that emptied the shelf, so the empty state can say which
     * one to undo instead of shrugging. A filter is the culprit when lifting it
     * on its own brings results back; when no single lift does, the combination
     * is at fault and the copy says so.
     *
     * This only ever runs on an empty result set, so the extra `exists()` probes
     * never touch a page that has something to show.
     *
     * @return array{filter: string, value: string}|null
     */
    #[Computed]
    public function blockingFilter(): ?array
    {
        foreach (array_keys($this->activeFilters) as $key) {
            if ($this->filtered(without: $key)->exists()) {
                return ['filter' => $this->filterLabel($key), 'value' => $this->filterValue($key)];
            }
        }

        return null;
    }

    /**
     * `with('muscleGroup')` is not optional here: every card names its muscle
     * group, so without the eager load a full library is 57 extra queries.
     */
    protected function filtered(?string $without = null): Builder
    {
        $query = Exercise::query()
            ->active()
            ->with('muscleGroup')
            // Muscle groups are seeded in their display order, and inside a
            // group the insertion order is the order a coach reads them in.
            ->orderBy('muscle_group_id')
            ->orderBy('id');

        $term = trim($this->search);

        if ($term !== '' && $without !== 'search') {
            // A trainee reaches for whichever language comes to mind first, so
            // both name columns are searched whatever the interface is set to.
            $query->where(function (Builder $names) use ($term): void {
                $names->where('name_ar', 'like', '%'.$term.'%')
                    ->orWhere('name_en', 'like', '%'.$term.'%');
            });
        }

        if ($this->muscle !== '' && $without !== 'muscle') {
            $query->whereHas('muscleGroup', fn (Builder $group) => $group->where('slug', $this->muscle));
        }

        if ($this->equipment !== '' && $without !== 'equipment') {
            $query->where('equipment', $this->equipment);
        }

        if ($this->difficulty !== '' && $without !== 'difficulty') {
            $query->where('difficulty', $this->difficulty);
        }

        return $query;
    }

    protected function filterLabel(string $key): string
    {
        return match ($key) {
            'search' => __('common.actions.search'),
            'muscle' => __('exercise.muscle.label'),
            'equipment' => __('exercise.equipment.label'),
            default => __('exercise.difficulty.label'),
        };
    }

    protected function filterValue(string $key): string
    {
        return match ($key) {
            'search' => trim($this->search),
            'muscle' => (string) $this->muscleGroups->firstWhere('slug', $this->muscle)?->name,
            'equipment' => __('exercise.equipment.'.$this->equipment),
            default => Difficulty::from($this->difficulty)->label(),
        };
    }

    protected function discardUnknownFilters(): void
    {
        if ($this->muscle !== '' && ! $this->muscleGroups->contains('slug', $this->muscle)) {
            $this->muscle = '';
        }

        if ($this->equipment !== '' && ! $this->equipmentOptions->contains($this->equipment)) {
            $this->equipment = '';
        }

        if ($this->difficulty !== '' && Difficulty::tryFrom($this->difficulty) === null) {
            $this->difficulty = '';
        }
    }
};
?>

@section('title', __('exercise.library').' · '.__('common.app_name'))

@push('head')
    <x-seo.page :description="__('common.seo.exercises')"/>
@endpush

<div class="px-4 pt-6 pb-12">
    <h1 class="text-[2rem] font-bold leading-tight text-ink-50">{{ __('exercise.library') }}</h1>

    {{--
      The search box sits outside the island on purpose. `wire:island` scopes its
      update to the results, and keeping the input out of the morphed region
      means a slow reply can never claw back a character just typed into it.
    --}}
    <x-ui.field :label="__('common.actions.search')"
                id="exercise-search"
                type="search"
                class="mt-6"
                autocomplete="off"
                enterkeyhint="search"
                :placeholder="__('exercise.search.placeholder')"
                wire:model.live.debounce.400ms="search"
                wire:island="results"/>

    @island(name: 'results')
        <div class="mt-6 flex flex-col gap-6">
            {{--
              The chips live inside the island so their active state arrives with
              the grid they filter. Nothing here is revealed by :hover — an
              active chip is brand-400 on brand-950 whether or not it is pointed
              at (DESIGN.md §5), and every chip is a 44px target.
            --}}
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <h2 id="filter-muscle" class="text-xs font-medium text-ink-300">{{ __('exercise.muscle.label') }}</h2>

                    <div class="flex flex-wrap gap-2" role="group" aria-labelledby="filter-muscle">
                        <x-ui.chip tag="button"
                                   wire:key="muscle-any"
                                   wire:click="$set('muscle', '')"
                                   :active="$muscle === ''">{{ __('exercise.muscle.any') }}</x-ui.chip>

                        @foreach ($this->muscleGroups as $group)
                            <x-ui.chip tag="button"
                                       wire:key="muscle-{{ $group->slug }}"
                                       wire:click="toggleMuscle('{{ $group->slug }}')"
                                       :active="$muscle === $group->slug">{{ $group->name }}</x-ui.chip>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <h2 id="filter-equipment" class="text-xs font-medium text-ink-300">{{ __('exercise.equipment.label') }}</h2>

                    <div class="flex flex-wrap gap-2" role="group" aria-labelledby="filter-equipment">
                        <x-ui.chip tag="button"
                                   wire:key="equipment-any"
                                   wire:click="$set('equipment', '')"
                                   :active="$equipment === ''">{{ __('exercise.equipment.any') }}</x-ui.chip>

                        @foreach ($this->equipmentOptions as $option)
                            <x-ui.chip tag="button"
                                       wire:key="equipment-{{ $option }}"
                                       wire:click="toggleEquipment('{{ $option }}')"
                                       :active="$equipment === $option">{{ __('exercise.equipment.'.$option) }}</x-ui.chip>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <h2 id="filter-difficulty" class="text-xs font-medium text-ink-300">{{ __('exercise.difficulty.label') }}</h2>

                    <div class="flex flex-wrap gap-2" role="group" aria-labelledby="filter-difficulty">
                        <x-ui.chip tag="button"
                                   wire:key="difficulty-any"
                                   wire:click="$set('difficulty', '')"
                                   :active="$difficulty === ''">{{ __('exercise.difficulty.any') }}</x-ui.chip>

                        @foreach (App\Enums\Difficulty::cases() as $case)
                            <x-ui.chip tag="button"
                                       wire:key="difficulty-{{ $case->value }}"
                                       wire:click="toggleDifficulty('{{ $case->value }}')"
                                       :active="$difficulty === $case->value">{{ $case->label() }}</x-ui.chip>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="tabular text-sm text-ink-300" aria-live="polite">
                    {{ __('exercise.muscle.exercises_count', ['count' => $this->results->count()]) }}
                </p>

                @if ($this->activeFilters !== [])
                    {{--
                      A link rather than an action: it clears the search box that
                      sits outside the island too, and it lands on the plain,
                      shareable /exercises with an empty query string.
                    --}}
                    <a href="{{ route('exercises.index') }}"
                       wire:navigate
                       class="-me-2 inline-flex min-h-11 items-center rounded-sm px-2 text-sm font-medium text-brand-400">
                        {{ __('common.actions.clear') }}
                    </a>
                @endif
            </div>

            @if ($this->results->isEmpty())
                <x-ui.empty-state>
                    <x-slot:title>{{ __('exercise.filters.none_title') }}</x-slot:title>

                    <x-slot:body>
                        @if ($this->blockingFilter)
                            {{ __('exercise.filters.none_for', $this->blockingFilter) }}
                        @else
                            {{ __('exercise.filters.none_for_combination') }}
                        @endif
                    </x-slot:body>

                    <x-slot:action>
                        <x-ui.button :href="route('exercises.index')" wire:navigate>
                            {{ __('common.actions.clear') }}
                        </x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->results as $exercise)
                        <li wire:key="exercise-{{ $exercise->getKey() }}" class="flex">
                            <x-ui.exercise-card :exercise="$exercise" class="w-full"/>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endisland
</div>
