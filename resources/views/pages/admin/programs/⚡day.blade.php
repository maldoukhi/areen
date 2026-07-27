<?php

declare(strict_types=1);

use App\Http\Requests\Admin\ProgramDayRequest;
use App\Http\Requests\Admin\ProgramExerciseRequest;
use App\Http\Requests\Admin\ReorderRequest;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.admin.layout')]
class extends Component
{
    public Program $program;

    public ProgramDay $day;

    // Day details
    public string $title_ar = '';

    public string $title_en = '';

    public string $focus_muscle_id = '';

    public bool $is_rest_day = false;

    public string $notes_ar = '';

    public string $notes_en = '';

    // Library picker
    public string $search = '';

    public string $muscle = '';

    // Inline row editor
    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $row = [];

    public function mount(Program $program, ProgramDay $day): void
    {
        abort_unless($day->program_id === $program->getKey(), 404);

        $this->authorize('update', $day);

        $this->program = $program;
        $this->day = $day;

        $this->fill([
            'title_ar' => (string) $day->title_ar,
            'title_en' => (string) $day->title_en,
            'focus_muscle_id' => (string) $day->focus_muscle_id,
            'is_rest_day' => (bool) $day->is_rest_day,
            'notes_ar' => (string) $day->notes_ar,
            'notes_en' => (string) $day->notes_en,
        ]);
    }

    public function saveDay(): void
    {
        $this->authorize('update', $this->day);

        $validated = $this->validate(
            ProgramDayRequest::rulesFor($this->day),
            [],
            ProgramDayRequest::attributeNames(),
        );

        $this->day->fill([
            'title_ar' => blank($validated['title_ar'] ?? null) ? null : $validated['title_ar'],
            'title_en' => blank($validated['title_en'] ?? null) ? null : $validated['title_en'],
            'focus_muscle_id' => blank($this->focus_muscle_id) ? null : (int) $this->focus_muscle_id,
            'is_rest_day' => $this->is_rest_day,
            'notes_ar' => blank($validated['notes_ar'] ?? null) ? null : $validated['notes_ar'],
            'notes_en' => blank($validated['notes_en'] ?? null) ? null : $validated['notes_en'],
        ])->save();

        session()->flash('status', __('admin.messages.updated'));
    }

    /**
     * The `wire:sort` handler. One drop is one request carrying the row that
     * moved and where it landed — never one request per row — and the whole new
     * order is written in a single UPDATE.
     */
    public function reorder(mixed $item, mixed $position): void
    {
        $this->authorize('update', $this->day);

        $validated = validator(
            ['item' => $item, 'position' => $position],
            ReorderRequest::rulesFor(),
            [],
            ReorderRequest::attributeNames(),
        )->validate();

        $moved = (int) $validated['item'];

        /** @var list<int> $ids */
        $ids = $this->day->exercises()->orderBy('sort')->orderBy('id')->pluck('id')->all();

        $from = array_search($moved, $ids, true);

        if ($from === false) {
            return;
        }

        array_splice($ids, $from, 1);
        array_splice($ids, max(0, min((int) $validated['position'], count($ids))), 0, [$moved]);

        /*
         * A CASE expression, so a nine-exercise day is nine positions in one
         * statement rather than nine statements. Every value interpolated here
         * is an integer that has already been through the validator.
         */
        $cases = '';

        foreach ($ids as $index => $id) {
            $cases .= ' when '.(int) $id.' then '.$index;
        }

        ProgramExercise::query()
            ->whereIn('id', $ids)
            ->update(['sort' => DB::raw('case id'.$cases.' end')]);

        unset($this->rows);
    }

    public function addExercise(int $exerciseId): void
    {
        $this->authorize('create', ProgramExercise::class);

        validator(
            ['exercise_id' => $exerciseId],
            ProgramExerciseRequest::attachRules(),
            [],
            ProgramExerciseRequest::attributeNames(),
        )->validate();

        $exercise = Exercise::query()->findOrFail($exerciseId);

        $count = $this->day->exercises()->count();

        $this->day->exercises()->create([
            'exercise_id' => $exercise->getKey(),
            'sort' => $count,
            'sets' => 3,
            'reps' => null,
            'rest_seconds' => 90,
        ]);

        unset($this->rows);

        session()->flash('status', __('admin.messages.exercise_added', ['name' => $exercise->name]));
    }

    public function removeExercise(int $rowId): void
    {
        $row = $this->day->exercises()->with('exercise')->findOrFail($rowId);

        $this->authorize('delete', $row);

        $name = $row->exercise?->name ?? '';
        $row->delete();

        if ($this->editingId === $rowId) {
            $this->cancelRow();
        }

        unset($this->rows);

        session()->flash('status', __('admin.messages.exercise_removed', ['name' => $name]));
    }

    public function editRow(int $rowId): void
    {
        $row = $this->day->exercises()->findOrFail($rowId);

        $this->authorize('update', $row);

        $this->editingId = $rowId;

        $this->row = [
            'sets' => (string) $row->sets,
            'reps' => (string) $row->reps,
            'rest_seconds' => (string) $row->rest_seconds,
            'tempo' => (string) $row->tempo,
            'weight_note' => (string) $row->weight_note,
            'coach_notes_ar' => (string) $row->coach_notes_ar,
            'coach_notes_en' => (string) $row->coach_notes_en,
            'superset_group' => (string) $row->superset_group,
        ];
    }

    public function cancelRow(): void
    {
        $this->reset('editingId', 'row');
        $this->resetValidation();
    }

    public function saveRow(): void
    {
        $row = $this->day->exercises()->findOrFail((int) $this->editingId);

        $this->authorize('update', $row);

        $rules = [];

        foreach (ProgramExerciseRequest::rulesFor() as $field => $rule) {
            $rules['row.'.$field] = $rule;
        }

        $names = [];

        foreach (ProgramExerciseRequest::attributeNames() as $field => $name) {
            $names['row.'.$field] = $name;
        }

        $this->validate($rules, [], $names);

        $row->fill([
            'sets' => (int) $this->row['sets'],
            'reps' => blank($this->row['reps']) ? null : $this->row['reps'],
            'rest_seconds' => (int) $this->row['rest_seconds'],
            'tempo' => blank($this->row['tempo']) ? null : $this->row['tempo'],
            'weight_note' => blank($this->row['weight_note']) ? null : $this->row['weight_note'],
            'coach_notes_ar' => blank($this->row['coach_notes_ar']) ? null : $this->row['coach_notes_ar'],
            'coach_notes_en' => blank($this->row['coach_notes_en']) ? null : $this->row['coach_notes_en'],
            'superset_group' => blank($this->row['superset_group']) ? null : $this->row['superset_group'],
        ])->save();

        $this->cancelRow();

        unset($this->rows);

        session()->flash('status', __('admin.messages.updated'));
    }

    /**
     * @return Collection<int, ProgramExercise>
     */
    #[Computed]
    public function rows(): Collection
    {
        return $this->day->exercises()
            ->with('exercise.muscleGroup')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    /**
     * The shared library, minus whatever is already on this day.
     *
     * @return Collection<int, Exercise>
     */
    #[Computed]
    public function library(): Collection
    {
        return Exercise::query()
            ->active()
            ->with('muscleGroup')
            ->whereNotIn('id', $this->rows->pluck('exercise_id')->all())
            ->when(filled($this->search), function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(fn (Builder $inner) => $inner
                    ->where('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term));
            })
            ->when(filled($this->muscle), fn (Builder $query) => $query->where('muscle_group_id', (int) $this->muscle))
            ->orderBy('name_ar')
            ->limit(20)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'muscles' => MuscleGroup::query()->ordered()->get(),
            'days' => $this->program->days()->get(),
            'supersets' => ProgramExerciseRequest::SUPERSET_GROUPS,
        ];
    }
};
?>

<div>
    <x-admin.page-header :back="route('admin.programs.edit', $program)"
                         :back-label="$program->name"
                         :title="__('program.days.number', ['number' => $day->day_number])"
                         :description="__('admin.day.reorder_hint')"/>

    {{-- Day tabs: DESIGN.md §5 — a scrollable segmented strip, active underlined. --}}
    <div class="-mx-4 mb-6 overflow-x-auto px-4">
        <div class="flex gap-2">
            @foreach ($days as $other)
                <a href="{{ route('admin.programs.day', ['program' => $program, 'day' => $other]) }}"
                   wire:navigate
                   wire:key="day-tab-{{ $other->id }}"
                   @if ($other->is($day)) aria-current="page" @endif
                   @class([
                       'inline-flex min-h-11 shrink-0 items-center rounded-sm px-4 text-sm font-medium',
                       'border-b-2 border-brand-400 text-brand-400' => $other->is($day),
                       'text-ink-300 hover:bg-ink-800' => ! $other->is($day),
                   ])>
                    {{ __('program.days.number', ['number' => $other->day_number]) }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <form wire:submit="saveDay" class="flex flex-col gap-4 lg:order-2 lg:col-start-1 lg:row-start-1">
            <x-ui.card class="flex flex-col gap-5">
                <h3 class="text-lg font-semibold text-ink-50">{{ __('admin.day.settings') }}</h3>

                <x-ui.field :label="__('admin.fields.title_ar')" id="day-title-ar"
                            wire:model="title_ar" :error="$errors->first('title_ar')"/>

                <x-ui.field :label="__('admin.fields.title_en')" id="day-title-en" dir="ltr"
                            wire:model="title_en" :error="$errors->first('title_en')"/>

                <x-ui.field :label="__('program.days.focus')" id="day-focus" :error="$errors->first('focus_muscle_id')">
                    <x-admin.select id="day-focus" wire:model="focus_muscle_id" :error="filled($errors->first('focus_muscle_id'))">
                        <option value="">{{ __('admin.fields.none') }}</option>
                        @foreach ($muscles as $muscleGroup)
                            <option value="{{ $muscleGroup->id }}">{{ $muscleGroup->name }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-admin.toggle id="day-rest" :label="__('admin.fields.is_rest_day')" wire:model.live="is_rest_day"/>

                <x-ui.field :label="__('program.days.notes')" id="day-notes-ar" :error="$errors->first('notes_ar')">
                    <x-admin.textarea id="day-notes-ar" rows="3" wire:model="notes_ar" :error="filled($errors->first('notes_ar'))"/>
                </x-ui.field>

                <x-ui.field :label="__('admin.fields.notes')" id="day-notes-en" :error="$errors->first('notes_en')">
                    <x-admin.textarea id="day-notes-en" rows="3" dir="ltr" wire:model="notes_en" :error="filled($errors->first('notes_en'))"/>
                </x-ui.field>

                <x-ui.button type="submit" full>{{ __('admin.actions.save_day') }}</x-ui.button>
            </x-ui.card>
        </form>

        <div class="flex flex-col gap-4 lg:col-start-2 lg:row-start-1">
            @if ($is_rest_day)
                <p class="rounded-md border border-warning/40 bg-warning/10 px-4 py-3 text-sm leading-normal text-warning">
                    {{ __('admin.day.rest_notice') }}
                </p>
            @endif

            {{--
              The rows and the library picker share one island: adding, removing
              and reordering all touch the same list, so isolating them together
              keeps the page — and the day form beside it — untouched.
            --}}
            @island(name: 'builder')
                <div class="flex flex-col gap-4">
                    <h3 class="text-lg font-semibold text-ink-50">{{ __('admin.day.exercises') }}</h3>

                    @if ($this->rows->isEmpty())
                        <x-ui.empty-state>
                            <x-slot:title>{{ __('admin.empty.day_title') }}</x-slot:title>
                            <x-slot:body>{{ __('admin.empty.day_body') }}</x-slot:body>
                        </x-ui.empty-state>
                    @else
                        {{--
                          Livewire 4's `wire:sort`: the container names the handler,
                          each child carries `wire:sort:item` with its key, and the
                          handle limits the drag to the grip so the row's own
                          controls stay clickable.
                        --}}
                        <ul wire:sort="reorder" class="flex flex-col gap-2">
                            @foreach ($this->rows as $index => $programExercise)
                                <li wire:sort:item="{{ $programExercise->id }}"
                                    wire:key="row-{{ $programExercise->id }}"
                                    class="rounded-md border border-ink-700 bg-ink-800 p-3">

                                    <div class="flex items-start gap-2">
                                        <button type="button"
                                                wire:sort:handle
                                                aria-label="{{ __('admin.day.drag_handle') }}"
                                                class="inline-flex size-11 shrink-0 cursor-grab items-center justify-center rounded-sm text-ink-400 hover:bg-ink-700">
                                            <x-admin.icon name="grip" class="size-5"/>
                                        </button>

                                        <span class="mt-2 inline-flex size-8 shrink-0 items-center justify-center rounded-sm bg-ink-900 tabular text-sm font-bold text-ink-300">
                                            {{ $index + 1 }}
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-medium text-ink-100">{{ $programExercise->exercise?->name }}</p>

                                            <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-400">
                                                <span class="tabular text-xl font-bold text-brand-400">
                                                    {{ __('exercise.prescription.sets_reps', ['sets' => $programExercise->sets, 'reps' => $programExercise->reps ?: '—']) }}
                                                </span>

                                                <span class="tabular">{{ __('exercise.prescription.rest_value', ['seconds' => $programExercise->rest_seconds]) }}</span>

                                                @if ($programExercise->superset_group)
                                                    <span class="text-ember">{{ __('exercise.prescription.superset') }} {{ $programExercise->superset_group }}</span>
                                                @endif
                                            </p>
                                        </div>

                                        <div class="flex shrink-0 items-center">
                                            <button type="button"
                                                    wire:click="editRow({{ $programExercise->id }})"
                                                    aria-label="{{ __('admin.actions.edit') }}"
                                                    class="inline-flex size-11 items-center justify-center rounded-sm text-ink-200 hover:bg-ink-700">
                                                <x-admin.icon name="pencil" class="size-5"/>
                                            </button>

                                            <button type="button"
                                                    wire:click="removeExercise({{ $programExercise->id }})"
                                                    wire:confirm="{{ __('admin.confirm.delete_body', ['name' => $programExercise->exercise?->name]) }}"
                                                    aria-label="{{ __('admin.actions.remove_exercise') }}"
                                                    class="inline-flex size-11 items-center justify-center rounded-sm text-danger hover:bg-ink-700">
                                                <x-admin.icon name="trash" class="size-5"/>
                                            </button>
                                        </div>
                                    </div>

                                    @if ($editingId === $programExercise->id)
                                        <form wire:submit="saveRow" class="mt-4 grid gap-4 border-t border-ink-700 pt-4 sm:grid-cols-2">
                                            <x-ui.field :label="__('exercise.prescription.sets')" id="row-sets" type="number" min="1" max="20"
                                                        wire:model="row.sets" :error="$errors->first('row.sets')"/>

                                            <x-ui.field :label="__('exercise.prescription.reps')" id="row-reps"
                                                        wire:model="row.reps" :error="$errors->first('row.reps')"/>

                                            <x-ui.field :label="__('exercise.prescription.rest')" id="row-rest" type="number" min="0" max="900"
                                                        wire:model="row.rest_seconds" :error="$errors->first('row.rest_seconds')"/>

                                            <x-ui.field :label="__('exercise.prescription.tempo')" id="row-tempo" dir="ltr"
                                                        :hint="__('exercise.prescription.tempo_hint')"
                                                        wire:model="row.tempo" :error="$errors->first('row.tempo')"/>

                                            <x-ui.field :label="__('exercise.prescription.weight_note')" id="row-weight"
                                                        :hint="__('exercise.prescription.weight_note_hint')"
                                                        wire:model="row.weight_note" :error="$errors->first('row.weight_note')"/>

                                            <x-ui.field :label="__('exercise.prescription.superset_group')" id="row-superset"
                                                        :hint="__('exercise.prescription.superset_hint')"
                                                        :error="$errors->first('row.superset_group')">
                                                <x-admin.select id="row-superset" wire:model="row.superset_group" :error="filled($errors->first('row.superset_group'))">
                                                    <option value="">{{ __('admin.fields.none') }}</option>
                                                    @foreach ($supersets as $group)
                                                        <option value="{{ $group }}">{{ $group }}</option>
                                                    @endforeach
                                                </x-admin.select>
                                            </x-ui.field>

                                            <x-ui.field class="sm:col-span-2" :label="__('exercise.coach_notes.label')" id="row-notes-ar" :error="$errors->first('row.coach_notes_ar')">
                                                <x-admin.textarea id="row-notes-ar" rows="2"
                                                                  :placeholder="__('exercise.coach_notes.placeholder')"
                                                                  wire:model="row.coach_notes_ar"
                                                                  :error="filled($errors->first('row.coach_notes_ar'))"/>
                                            </x-ui.field>

                                            <div class="flex flex-wrap gap-2 sm:col-span-2">
                                                <x-ui.button type="submit">{{ __('admin.actions.update') }}</x-ui.button>
                                                <x-ui.button variant="ghost" wire:click="cancelRow">{{ __('common.actions.cancel') }}</x-ui.button>
                                            </div>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <x-ui.card class="flex flex-col gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-ink-50">{{ __('admin.day.pick_title') }}</h3>
                            <p class="mt-1 text-sm text-ink-300">{{ __('admin.day.pick_hint') }}</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <x-ui.field class="flex-1" id="library-search" :label="__('exercise.search.placeholder')" type="search"
                                        wire:model.live.debounce.300ms="search"/>

                            <x-ui.field class="sm:w-48" id="library-muscle" :label="__('exercise.muscle.label')">
                                <x-admin.select id="library-muscle" wire:model.live="muscle">
                                    <option value="">{{ __('admin.filters.all_muscles') }}</option>
                                    @foreach ($muscles as $muscleGroup)
                                        <option value="{{ $muscleGroup->id }}">{{ $muscleGroup->name }}</option>
                                    @endforeach
                                </x-admin.select>
                            </x-ui.field>
                        </div>

                        @if ($this->library->isEmpty())
                            <p class="text-sm text-ink-300">{{ __('exercise.search.no_results_body') }}</p>
                        @else
                            <ul class="flex flex-col">
                                @foreach ($this->library as $exercise)
                                    <li wire:key="library-{{ $exercise->id }}" class="border-b border-ink-800 last:border-0">
                                        <button type="button"
                                                wire:click="addExercise({{ $exercise->id }})"
                                                class="flex min-h-11 w-full items-center gap-3 rounded-sm px-1 py-2 text-start hover:bg-ink-800/50">
                                            <x-admin.icon name="plus" class="size-5 shrink-0 text-brand-400"/>

                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-medium text-ink-100">{{ $exercise->name }}</span>
                                                <span class="block truncate text-xs text-ink-400">{{ $exercise->muscleGroup?->name }}</span>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-ui.card>
                </div>
            @endisland
        </div>
    </div>
</div>
