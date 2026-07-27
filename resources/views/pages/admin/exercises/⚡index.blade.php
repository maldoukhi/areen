<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.admin.layout')]
class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $muscle = '';

    #[Url(except: '')]
    public string $difficulty = '';

    #[Url(except: false)]
    public bool $trashed = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Exercise::class);
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'muscle', 'difficulty', 'trashed');
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $exercise = Exercise::query()->findOrFail($id);

        $this->authorize('update', $exercise);

        $exercise->forceFill(['is_active' => ! $exercise->is_active])->save();

        session()->flash('status', __('admin.messages.updated'));
    }

    public function delete(int $id): void
    {
        $exercise = Exercise::query()->findOrFail($id);

        $this->authorize('delete', $exercise);

        $name = $exercise->name;
        $exercise->delete();

        session()->flash('status', __('admin.messages.deleted', ['name' => $name]));
    }

    public function restore(int $id): void
    {
        $exercise = Exercise::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $exercise);

        $exercise->restore();

        session()->flash('status', __('admin.messages.restored', ['name' => $exercise->name]));
    }

    /**
     * @return LengthAwarePaginator<int, Exercise>
     */
    #[Computed]
    public function exercises(): LengthAwarePaginator
    {
        return Exercise::query()
            ->when($this->trashed, fn (Builder $query) => $query->onlyTrashed())
            ->with('muscleGroup')
            ->when(filled($this->search), function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(fn (Builder $inner) => $inner
                    ->where('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('slug', 'like', $term));
            })
            ->when(filled($this->muscle), fn (Builder $query) => $query->where('muscle_group_id', (int) $this->muscle))
            ->when(filled($this->difficulty), fn (Builder $query) => $query->where('difficulty', $this->difficulty))
            ->orderBy('name_ar')
            ->paginate(20);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'muscles' => MuscleGroup::query()->ordered()->get(),
            'difficulties' => Difficulty::cases(),
        ];
    }
};
?>

<div>
    <x-admin.page-header :title="__('admin.entities.exercises')">
        @can('create', App\Models\Exercise::class)
            <x-slot:actions>
                <x-ui.button :href="route('admin.exercises.create')" wire:navigate>
                    <x-admin.icon name="plus" class="size-5"/>
                    {{ __('exercise.empty.action') }}
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-admin.page-header>

    @island(name: 'exercises')
        <div>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                <x-ui.field class="flex-1 sm:min-w-56" id="exercises-search"
                            :label="__('admin.table.search')" type="search"
                            wire:model.live.debounce.300ms="search"/>

                <x-ui.field class="sm:w-44" id="exercises-muscle" :label="__('exercise.muscle.label')">
                    <x-admin.select id="exercises-muscle" wire:model.live="muscle">
                        <option value="">{{ __('admin.filters.all_muscles') }}</option>
                        @foreach ($muscles as $muscleGroup)
                            <option wire:key="muscle-{{ $muscleGroup->id }}" value="{{ $muscleGroup->id }}">{{ $muscleGroup->name }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field class="sm:w-44" id="exercises-difficulty" :label="__('exercise.difficulty.label')">
                    <x-admin.select id="exercises-difficulty" wire:model.live="difficulty">
                        <option value="">{{ __('exercise.difficulty.any') }}</option>
                        @foreach ($difficulties as $case)
                            <option wire:key="difficulty-{{ $case->value }}" value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <div class="flex items-center gap-2">
                    <x-admin.toggle id="exercises-trashed" :label="__('admin.table.trashed')" wire:model.live="trashed"/>

                    <button type="button"
                            wire:click="resetFilters"
                            class="inline-flex min-h-11 items-center rounded-sm px-3 text-sm font-medium text-ink-300 hover:bg-ink-800">
                        {{ __('admin.filters.reset') }}
                    </button>
                </div>
            </div>

            @if ($this->exercises->isEmpty())
                <x-ui.empty-state>
                    <x-slot:title>{{ filled($search) || filled($muscle) || filled($difficulty) ? __('exercise.search.no_results_title') : __('admin.empty.exercises_title') }}</x-slot:title>
                    <x-slot:body>{{ filled($search) || filled($muscle) || filled($difficulty) ? __('exercise.search.no_results_body') : __('admin.empty.exercises_body') }}</x-slot:body>
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead>
                            <tr class="border-b border-ink-700 text-xs font-medium text-ink-300">
                                <th scope="col" class="py-3 pe-3 text-start">{{ __('admin.fields.name_ar') }}</th>
                                <th scope="col" class="hidden py-3 pe-3 text-start sm:table-cell">{{ __('exercise.muscle.label') }}</th>
                                <th scope="col" class="hidden py-3 pe-3 text-start md:table-cell">{{ __('exercise.equipment.label') }}</th>
                                <th scope="col" class="hidden py-3 pe-3 text-start md:table-cell">{{ __('exercise.difficulty.label') }}</th>
                                <th scope="col" class="py-3 text-end">{{ __('admin.actions.edit') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($this->exercises as $exercise)
                                <tr wire:key="exercise-{{ $exercise->id }}" class="border-b border-ink-800 transition-colors duration-150 ease-out hover:bg-ink-800/50">
                                    <td class="py-3 pe-3">
                                        <div class="flex items-center gap-3">
                                            @if ($exercise->media_path)
                                                <img src="{{ Storage::disk('public')->url($exercise->media_path) }}"
                                                     alt=""
                                                     loading="lazy"
                                                     class="aspect-[4/3] w-16 shrink-0 rounded-md border border-ink-700 bg-ink-800 object-cover">
                                            @endif

                                            <div class="min-w-0">
                                                <a href="{{ route('admin.exercises.edit', $exercise) }}"
                                                   wire:navigate
                                                   class="block font-medium text-ink-100">{{ $exercise->name }}</a>

                                                @unless ($exercise->is_active)
                                                    <span class="block text-xs text-ink-300">{{ __('admin.trainees.inactive') }}</span>
                                                @endunless
                                            </div>
                                        </div>
                                    </td>

                                    <td class="hidden py-3 pe-3 text-ink-300 sm:table-cell">{{ $exercise->muscleGroup?->name }}</td>

                                    <td class="hidden py-3 pe-3 text-ink-300 md:table-cell">
                                        {{ $exercise->equipment ? __('exercise.equipment.'.$exercise->equipment) : __('exercise.equipment.none') }}
                                    </td>

                                    <td class="hidden py-3 pe-3 text-ink-300 md:table-cell">{{ $exercise->difficulty?->label() }}</td>

                                    <td class="py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            @if ($trashed)
                                                <button type="button"
                                                        wire:click="restore({{ $exercise->id }})"
                                                        class="inline-flex min-h-11 items-center rounded-sm px-3 text-sm font-medium text-brand-400 hover:bg-ink-800">
                                                    {{ __('admin.actions.restore') }}
                                                </button>
                                            @else
                                                <button type="button"
                                                        wire:click="toggleActive({{ $exercise->id }})"
                                                        class="inline-flex min-h-11 items-center rounded-sm px-3 text-sm font-medium text-ink-200 hover:bg-ink-800">
                                                    {{ $exercise->is_active ? __('admin.trainees.deactivate') : __('admin.trainees.activate') }}
                                                </button>

                                                <a href="{{ route('admin.exercises.edit', $exercise) }}"
                                                   wire:navigate
                                                   aria-label="{{ __('admin.actions.edit') }}"
                                                   class="inline-flex size-11 items-center justify-center rounded-sm text-ink-200 hover:bg-ink-800">
                                                    <x-admin.icon name="pencil" class="size-5"/>
                                                </a>

                                                <button type="button"
                                                        wire:click="delete({{ $exercise->id }})"
                                                        wire:confirm="{{ __('admin.confirm.delete_body', ['name' => $exercise->name]) }}"
                                                        aria-label="{{ __('admin.actions.delete') }}"
                                                        class="inline-flex size-11 items-center justify-center rounded-sm text-danger hover:bg-ink-800">
                                                    <x-admin.icon name="trash" class="size-5"/>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $this->exercises->links() }}</div>
            @endif
        </div>
    @endisland
</div>
