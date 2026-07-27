<?php

declare(strict_types=1);

use App\Enums\ProgramLevel;
use App\Models\Program;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
    public string $level = '';

    #[Url(except: '')]
    public string $visibility = '';

    #[Url(except: false)]
    public bool $trashed = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Program::class);
    }

    /**
     * Any filter change starts the list again from page one — page four of a
     * search that no longer matches is an empty screen with no explanation.
     */
    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'level', 'visibility', 'trashed');
        $this->resetPage();
    }

    public function togglePublished(int $id): void
    {
        $program = Program::query()->findOrFail($id);

        $this->authorize('update', $program);

        $publishing = ! ($program->is_public && $program->published_at !== null);

        $program->forceFill([
            'is_public' => $publishing,
            'published_at' => $publishing ? ($program->published_at ?? now()) : null,
        ])->save();

        session()->flash('status', __(
            $publishing ? 'admin.messages.published' : 'admin.messages.unpublished',
            ['name' => $program->name],
        ));
    }

    public function delete(int $id): void
    {
        $program = Program::query()->findOrFail($id);

        $this->authorize('delete', $program);

        $name = $program->name;
        $program->delete();

        session()->flash('status', __('admin.messages.deleted', ['name' => $name]));
    }

    public function restore(int $id): void
    {
        $program = Program::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $program);

        $program->restore();

        session()->flash('status', __('admin.messages.restored', ['name' => $program->name]));
    }

    /**
     * A computed property rather than data passed through `with()`: an island
     * only ever sees the component's own state, so anything the table renders
     * has to hang off `$this`.
     *
     * @return LengthAwarePaginator<int, Program>
     */
    #[Computed]
    public function programs(): LengthAwarePaginator
    {
        return Program::query()
            ->when($this->trashed, fn (Builder $query) => $query->onlyTrashed())
            ->when(filled($this->search), function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(fn (Builder $inner) => $inner
                    ->where('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('slug', 'like', $term));
            })
            ->when(filled($this->level), fn (Builder $query) => $query->where('level', $this->level))
            ->when($this->visibility === 'public', fn (Builder $query) => $query->where('is_public', true)->whereNotNull('published_at'))
            ->when($this->visibility === 'draft', fn (Builder $query) => $query->where('is_public', false)->whereNull('access_code'))
            ->when($this->visibility === 'private', fn (Builder $query) => $query->whereNotNull('access_code'))
            ->withCount('days')
            ->ordered()
            ->paginate(15);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'levels' => ProgramLevel::cases(),
        ];
    }
};
?>

<div>
    <x-admin.page-header :title="__('admin.entities.programs')">
        @can('create', App\Models\Program::class)
            <x-slot:actions>
                <x-ui.button :href="route('admin.programs.create')" wire:navigate>
                    <x-admin.icon name="plus" class="size-5"/>
                    {{ __('admin.actions.create') }}
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-admin.page-header>

    {{--
      Filtering and paging stay inside the island, so neither one re-renders the
      shell, the header or the primary action above it.
    --}}
    @island(name: 'programs')
        <div>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                <x-ui.field class="flex-1 sm:min-w-56"
                            id="programs-search"
                            :label="__('admin.table.search')"
                            type="search"
                            wire:model.live.debounce.300ms="search"/>

                <x-ui.field class="sm:w-44" id="programs-level" :label="__('program.level.label')">
                    <x-admin.select id="programs-level" wire:model.live="level">
                        <option value="">{{ __('admin.filters.all_levels') }}</option>
                        @foreach ($levels as $case)
                            <option wire:key="level-{{ $case->value }}" value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field class="sm:w-44" id="programs-visibility" :label="__('program.visibility.label')">
                    <x-admin.select id="programs-visibility" wire:model.live="visibility">
                        <option value="">{{ __('admin.filters.all_visibility') }}</option>
                        <option value="public">{{ __('program.visibility.public') }}</option>
                        <option value="draft">{{ __('program.visibility.draft') }}</option>
                        <option value="private">{{ __('program.visibility.private') }}</option>
                    </x-admin.select>
                </x-ui.field>

                <div class="flex items-center gap-2">
                    <x-admin.toggle id="programs-trashed" :label="__('admin.table.trashed')" wire:model.live="trashed"/>

                    <button type="button"
                            wire:click="resetFilters"
                            class="inline-flex min-h-11 items-center rounded-sm px-3 text-sm font-medium text-ink-300 hover:bg-ink-800">
                        {{ __('admin.filters.reset') }}
                    </button>
                </div>
            </div>

            @if ($this->programs->isEmpty())
                <x-ui.empty-state>
                    <x-slot:title>{{ filled($search) || filled($level) || filled($visibility) ? __('admin.empty.results_title') : __('admin.empty.programs_title') }}</x-slot:title>
                    <x-slot:body>{{ filled($search) || filled($level) || filled($visibility) ? __('admin.empty.results_body') : __('admin.empty.programs_body') }}</x-slot:body>
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead>
                            <tr class="border-b border-ink-700 text-xs font-medium text-ink-300">
                                <th scope="col" class="py-3 pe-3 text-start">{{ __('admin.fields.name_ar') }}</th>
                                <th scope="col" class="hidden py-3 pe-3 text-start md:table-cell">{{ __('program.level.label') }}</th>
                                <th scope="col" class="hidden py-3 pe-3 text-start md:table-cell">{{ __('admin.entities.days') }}</th>
                                <th scope="col" class="hidden py-3 pe-3 text-start sm:table-cell">{{ __('program.visibility.label') }}</th>
                                <th scope="col" class="py-3 text-end">{{ __('admin.actions.edit') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($this->programs as $program)
                                <tr wire:key="program-{{ $program->id }}" class="border-b border-ink-800 transition-colors duration-150 ease-out hover:bg-ink-800/50">
                                    <td class="py-3 pe-3">
                                        <a href="{{ route('admin.programs.edit', $program) }}"
                                           wire:navigate
                                           class="block font-medium text-ink-100">{{ $program->name }}</a>
                                        <span class="block text-xs text-ink-300" dir="ltr">{{ $program->slug }}</span>
                                    </td>

                                    <td class="hidden py-3 pe-3 text-ink-300 md:table-cell">{{ $program->level?->label() }}</td>

                                    <td class="hidden py-3 pe-3 tabular text-ink-300 md:table-cell">{{ $program->days_count }}</td>

                                    <td class="hidden py-3 pe-3 sm:table-cell">
                                        <x-ui.chip :active="$program->is_public && $program->published_at !== null">
                                            @if ($program->access_code)
                                                {{ __('program.visibility.private') }}
                                            @elseif ($program->is_public && $program->published_at !== null)
                                                {{ __('program.visibility.published') }}
                                            @else
                                                {{ __('program.visibility.draft') }}
                                            @endif
                                        </x-ui.chip>
                                    </td>

                                    <td class="py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            @if ($trashed)
                                                <button type="button"
                                                        wire:click="restore({{ $program->id }})"
                                                        class="inline-flex min-h-11 items-center rounded-sm px-3 text-sm font-medium text-brand-400 hover:bg-ink-800">
                                                    {{ __('admin.actions.restore') }}
                                                </button>
                                            @else
                                                <button type="button"
                                                        wire:click="togglePublished({{ $program->id }})"
                                                        class="inline-flex min-h-11 items-center rounded-sm px-3 text-sm font-medium text-ink-200 hover:bg-ink-800">
                                                    {{ $program->is_public && $program->published_at !== null ? __('admin.actions.unpublish') : __('admin.actions.publish') }}
                                                </button>

                                                <a href="{{ route('admin.programs.edit', $program) }}"
                                                   wire:navigate
                                                   aria-label="{{ __('admin.actions.edit') }}"
                                                   class="inline-flex size-11 items-center justify-center rounded-sm text-ink-200 hover:bg-ink-800">
                                                    <x-admin.icon name="pencil" class="size-5"/>
                                                </a>

                                                <button type="button"
                                                        wire:click="delete({{ $program->id }})"
                                                        wire:confirm="{{ __('admin.confirm.delete_body', ['name' => $program->name]) }}"
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

                <div class="mt-4">{{ $this->programs->links() }}</div>
            @endif
        </div>
    @endisland
</div>
