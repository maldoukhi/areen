<?php

declare(strict_types=1);

use App\Http\Requests\Admin\MuscleGroupRequest;
use App\Http\Requests\Admin\ReorderRequest;
use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.admin.layout')]
class extends Component
{
    public ?int $editingId = null;

    public string $name_ar = '';

    public string $name_en = '';

    public string $slug = '';

    public string $icon = '';

    public string $sort = '0';

    public function mount(): void
    {
        $this->authorize('viewAny', MuscleGroup::class);
    }

    public function edit(int $id): void
    {
        $group = MuscleGroup::query()->findOrFail($id);

        $this->authorize('update', $group);

        $this->editingId = $id;

        $this->fill([
            'name_ar' => (string) $group->name_ar,
            'name_en' => (string) $group->name_en,
            'slug' => (string) $group->slug,
            'icon' => (string) $group->icon,
            'sort' => (string) $group->sort,
        ]);
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name_ar', 'name_en', 'slug', 'icon', 'sort');
        $this->resetValidation();
    }

    public function save(): void
    {
        $group = $this->editingId === null ? null : MuscleGroup::query()->findOrFail($this->editingId);

        $group === null
            ? $this->authorize('create', MuscleGroup::class)
            : $this->authorize('update', $group);

        $validated = $this->validate(
            MuscleGroupRequest::rulesFor($group),
            [],
            MuscleGroupRequest::attributeNames(),
        );

        $group ??= new MuscleGroup;

        $group->fill([
            'name_ar' => $validated['name_ar'],
            'name_en' => blank($validated['name_en'] ?? null) ? null : $validated['name_en'],
            'slug' => $this->resolveSlug($group),
            'icon' => blank($validated['icon'] ?? null) ? null : $validated['icon'],
            'sort' => (int) $validated['sort'],
        ])->save();

        $created = $this->editingId === null;

        $this->cancel();

        unset($this->groups);

        session()->flash('status', $created
            ? __('admin.messages.created', ['name' => $group->name])
            : __('admin.messages.updated'));
    }

    public function delete(int $id): void
    {
        $group = MuscleGroup::query()->findOrFail($id);

        $this->authorize('delete', $group);

        $name = $group->name;

        try {
            $group->delete();
        } catch (QueryException) {
            // The exercises table restricts the delete, which is the right
            // answer — the group is still classifying somebody's library.
            session()->flash('danger', __('admin.messages.in_use'));

            return;
        }

        unset($this->groups);

        session()->flash('status', __('admin.messages.deleted', ['name' => $name]));
    }

    /**
     * One drop, one request, one statement — the same contract the day builder
     * uses for its exercise rows.
     */
    public function reorder(mixed $item, mixed $position): void
    {
        $this->authorize('create', MuscleGroup::class);

        $validated = validator(
            ['item' => $item, 'position' => $position],
            ReorderRequest::rulesFor(),
            [],
            ReorderRequest::attributeNames(),
        )->validate();

        $moved = (int) $validated['item'];

        /** @var list<int> $ids */
        $ids = MuscleGroup::query()->ordered()->pluck('id')->all();

        $from = array_search($moved, $ids, true);

        if ($from === false) {
            return;
        }

        array_splice($ids, $from, 1);
        array_splice($ids, max(0, min((int) $validated['position'], count($ids))), 0, [$moved]);

        $cases = '';

        foreach ($ids as $index => $id) {
            $cases .= ' when '.(int) $id.' then '.$index;
        }

        MuscleGroup::query()
            ->whereIn('id', $ids)
            ->update(['sort' => DB::raw('case id'.$cases.' end')]);

        unset($this->groups);
    }

    private function resolveSlug(MuscleGroup $group): string
    {
        $base = Str::slug($this->slug ?: ($this->name_en ?: $this->name_ar));

        if (blank($base)) {
            $base = 'muscle-'.Str::lower(Str::random(6));
        }

        $candidate = $base;
        $suffix = 1;

        while (MuscleGroup::query()
            ->where('slug', $candidate)
            ->when($group->exists, fn ($query) => $query->whereKeyNot($group->getKey()))
            ->exists()) {
            $candidate = $base.'-'.(++$suffix);
        }

        return $candidate;
    }

    /**
     * @return Collection<int, MuscleGroup>
     */
    #[Computed]
    public function groups(): Collection
    {
        return MuscleGroup::query()->ordered()->withCount('exercises')->get();
    }
};
?>

<div>
    <x-admin.page-header :title="__('admin.entities.muscle_groups')" :description="__('admin.day.reorder_hint')"/>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        @island(name: 'groups')
            <div>
                @if ($this->groups->isEmpty())
                    <x-ui.empty-state>
                        <x-slot:title>{{ __('admin.empty.muscle_groups_title') }}</x-slot:title>
                        <x-slot:body>{{ __('admin.empty.muscle_groups_body') }}</x-slot:body>
                    </x-ui.empty-state>
                @else
                    <ul wire:sort="reorder" class="flex flex-col gap-2">
                        @foreach ($this->groups as $index => $group)
                            <li wire:sort:item="{{ $group->id }}"
                                wire:key="muscle-{{ $group->id }}"
                                class="flex items-center gap-2 rounded-md border border-ink-700 bg-ink-800 p-2">

                                <button type="button"
                                        wire:sort:handle
                                        aria-hidden="true"
                                        tabindex="-1"
                                        class="inline-flex size-11 shrink-0 cursor-grab items-center justify-center rounded-sm text-ink-300 hover:bg-ink-700">
                                    <x-admin.icon name="grip" class="size-5"/>
                                </button>

                                {{-- The keyboard's route through the same reorder. --}}
                                <x-admin.reorder-keys :item="$group->id"
                                                      :index="$index"
                                                      :count="$this->groups->count()"/>

                                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-sm bg-ink-900 tabular text-sm font-bold text-ink-300">
                                    {{ $index + 1 }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-ink-100">{{ $group->name }}</p>
                                    <p class="truncate text-xs text-ink-300" dir="ltr">{{ $group->slug }}</p>
                                </div>

                                <span class="shrink-0 tabular text-xs text-ink-300">
                                    {{ __('exercise.muscle.exercises_count', ['count' => $group->exercises_count]) }}
                                </span>

                                <button type="button"
                                        wire:click="edit({{ $group->id }})"
                                        aria-label="{{ __('admin.actions.edit') }}"
                                        class="inline-flex size-11 shrink-0 items-center justify-center rounded-sm text-ink-200 hover:bg-ink-700">
                                    <x-admin.icon name="pencil" class="size-5"/>
                                </button>

                                <button type="button"
                                        wire:click="delete({{ $group->id }})"
                                        wire:confirm="{{ __('admin.confirm.delete_body', ['name' => $group->name]) }}"
                                        aria-label="{{ __('admin.actions.delete') }}"
                                        class="inline-flex size-11 shrink-0 items-center justify-center rounded-sm text-danger hover:bg-ink-700">
                                    <x-admin.icon name="trash" class="size-5"/>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endisland

        <form wire:submit="save" class="flex flex-col gap-4">
            <x-ui.card class="flex flex-col gap-5">
                <h3 class="text-lg font-semibold text-ink-50">
                    {{ $editingId === null ? __('admin.actions.create') : __('admin.actions.edit') }}
                </h3>

                <x-ui.field :label="__('admin.fields.name_ar')" id="muscle-name-ar" required
                            wire:model="name_ar" :error="$errors->first('name_ar')"/>

                <x-ui.field :label="__('admin.fields.name_en')" id="muscle-name-en" dir="ltr"
                            wire:model="name_en" :error="$errors->first('name_en')"/>

                <x-ui.field :label="__('admin.fields.slug')" id="muscle-slug" dir="ltr"
                            :hint="__('admin.fields.slug_hint')"
                            wire:model="slug" :error="$errors->first('slug')"/>

                <x-ui.field :label="__('admin.fields.icon')" id="muscle-icon" dir="ltr"
                            wire:model="icon" :error="$errors->first('icon')"/>

                <x-ui.field :label="__('admin.fields.sort')" id="muscle-sort" type="number" min="0" max="999"
                            wire:model="sort" :error="$errors->first('sort')"/>

                <div class="flex flex-wrap gap-2">
                    <x-ui.button type="submit">
                        {{ $editingId === null ? __('admin.actions.store') : __('admin.actions.update') }}
                    </x-ui.button>

                    @if ($editingId !== null)
                        <x-ui.button variant="ghost" wire:click="cancel">{{ __('common.actions.cancel') }}</x-ui.button>
                    @endif
                </div>
            </x-ui.card>
        </form>
    </div>
</div>
