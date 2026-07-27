<?php

declare(strict_types=1);

use App\Enums\ProgramLevel;
use App\Http\Requests\Admin\ProgramRequest;
use App\Models\Program;
use App\Models\ProgramDay;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new
#[Layout('components.admin.layout')]
class extends Component
{
    use WithFileUploads;

    public ?Program $program = null;

    public string $name_ar = '';

    public string $name_en = '';

    public string $slug = '';

    public string $description_ar = '';

    public string $description_en = '';

    public string $days_count = '3';

    public string $level = 'beginner';

    public string $goal = '';

    public bool $is_public = false;

    public bool $is_featured = false;

    public string $published_at = '';

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    public function mount(?Program $program = null): void
    {
        $this->program = $program?->exists === true ? $program : null;

        if ($this->program === null) {
            $this->authorize('create', Program::class);

            return;
        }

        $this->authorize('update', $this->program);

        $this->fill([
            'name_ar' => (string) $this->program->name_ar,
            'name_en' => (string) $this->program->name_en,
            'slug' => (string) $this->program->slug,
            'description_ar' => (string) $this->program->description_ar,
            'description_en' => (string) $this->program->description_en,
            'days_count' => (string) $this->program->days_count,
            'level' => $this->program->level?->value ?? 'beginner',
            'goal' => (string) $this->program->goal,
            'is_public' => (bool) $this->program->is_public,
            'is_featured' => (bool) $this->program->is_featured,
            'published_at' => $this->program->published_at?->format('Y-m-d\TH:i') ?? '',
        ]);
    }

    public function save(): void
    {
        $this->program === null
            ? $this->authorize('create', Program::class)
            : $this->authorize('update', $this->program);

        $validated = $this->validate(
            ProgramRequest::rulesFor($this->program),
            [],
            ProgramRequest::attributeNames(),
        );

        $creating = $this->program === null;
        $program = $this->program ?? new Program;

        $program->fill([
            'name_ar' => $validated['name_ar'],
            'name_en' => blank($validated['name_en'] ?? null) ? null : $validated['name_en'],
            'slug' => $this->resolveSlug(),
            'description_ar' => blank($validated['description_ar'] ?? null) ? null : $validated['description_ar'],
            'description_en' => blank($validated['description_en'] ?? null) ? null : $validated['description_en'],
            'days_count' => (int) $validated['days_count'],
            'level' => $validated['level'],
            'goal' => blank($validated['goal'] ?? null) ? null : $validated['goal'],
            'is_public' => $this->is_public,
            'is_featured' => $this->is_featured,
            // Ticking "visible to everyone" without naming a date means now;
            // a date left in place while unpublished would silently re-publish.
            'published_at' => $this->is_public
                ? (blank($this->published_at) ? now() : $this->published_at)
                : null,
        ]);

        if ($this->cover instanceof TemporaryUploadedFile) {
            $program->cover_path = $this->cover->store('programs', 'public');
        }

        $program->save();

        if ($creating) {
            $this->seedDays($program);

            $this->redirectRoute('admin.programs.edit', ['program' => $program], navigate: false);
            session()->flash('status', __('admin.messages.created', ['name' => $program->name]));

            return;
        }

        $this->program = $program->fresh();
        $this->reset('cover');

        session()->flash('status', __('admin.messages.updated'));
    }

    public function addDay(): void
    {
        $program = $this->requireProgram();

        $this->authorize('create', ProgramDay::class);

        $number = (int) $program->days()->max('day_number') + 1;

        $program->days()->create(['day_number' => $number]);

        if ($number > $program->days_count) {
            $program->forceFill(['days_count' => $number])->save();
            $this->days_count = (string) $number;
        }

        $this->program = $program->fresh();

        session()->flash('status', __('admin.messages.day_added', ['number' => $number]));
    }

    public function deleteDay(int $dayId): void
    {
        $program = $this->requireProgram();

        $day = $program->days()->findOrFail($dayId);

        $this->authorize('delete', $day);

        $day->delete();

        $this->program = $program->fresh();

        session()->flash('status', __('admin.messages.deleted', ['name' => __('program.days.number', ['number' => $day->day_number])]));
    }

    /**
     * The access code is the only credential a private program has, so
     * regenerating it locks out every link already shared. The interface warns
     * before the click, not after.
     */
    public function regenerateAccessCode(): void
    {
        $program = $this->requireProgram();

        $this->authorize('update', $program);

        do {
            $code = Str::upper(Str::random(8));
        } while (Program::withTrashed()->where('access_code', $code)->exists());

        $program->forceFill(['access_code' => $code])->save();

        $this->program = $program->fresh();

        session()->flash('status', __('admin.messages.code_generated'));
    }

    public function clearAccessCode(): void
    {
        $program = $this->requireProgram();

        $this->authorize('update', $program);

        $program->forceFill(['access_code' => null])->save();

        $this->program = $program->fresh();

        session()->flash('status', __('admin.messages.updated'));
    }

    /**
     * A new program starts with the days it says it has, so the builder is
     * never a blank page waiting for a second decision.
     */
    private function seedDays(Program $program): void
    {
        foreach (range(1, max(1, $program->days_count)) as $number) {
            $program->days()->create(['day_number' => $number]);
        }
    }

    private function requireProgram(): Program
    {
        abort_if($this->program === null, 404);

        return $this->program;
    }

    /**
     * Arabic does not transliterate into a usable slug, so an English name is
     * preferred and a random suffix is the last resort. Collisions are walked
     * past rather than reported: the slug is machinery, not the coach's problem.
     */
    private function resolveSlug(): string
    {
        $base = Str::slug($this->slug ?: ($this->name_en ?: $this->name_ar));

        if (blank($base)) {
            $base = 'program-'.Str::lower(Str::random(6));
        }

        $candidate = $base;
        $suffix = 1;

        while (Program::withTrashed()
            ->where('slug', $candidate)
            ->when($this->program !== null, fn ($query) => $query->whereKeyNot($this->program->getKey()))
            ->exists()) {
            $candidate = $base.'-'.(++$suffix);
        }

        return $candidate;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'levels' => ProgramLevel::cases(),
            'goals' => ProgramRequest::GOALS,
            'days' => $this->program?->days()->withCount('exercises')->get() ?? collect(),
        ];
    }
};
?>

<div>
    <x-admin.page-header :back="route('admin.programs.index')"
                         :title="$program?->exists ? $program->name : __('admin.entities.program')"/>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form wire:submit="save" class="flex flex-col gap-5">
            <x-ui.card class="flex flex-col gap-5">
                <x-ui.field :label="__('admin.fields.name_ar')" id="program-name-ar" required
                            wire:model="name_ar" :error="$errors->first('name_ar')"/>

                <x-ui.field :label="__('admin.fields.name_en')" id="program-name-en" dir="ltr"
                            wire:model="name_en" :error="$errors->first('name_en')"/>

                <x-ui.field :label="__('admin.fields.slug')" id="program-slug" dir="ltr"
                            :hint="__('admin.fields.slug_hint')"
                            wire:model="slug" :error="$errors->first('slug')"/>

                <x-ui.field :label="__('admin.fields.description_ar')" id="program-description-ar" :error="$errors->first('description_ar')">
                    <x-admin.textarea id="program-description-ar" rows="4" wire:model="description_ar" :error="filled($errors->first('description_ar'))"/>
                </x-ui.field>

                <x-ui.field :label="__('admin.fields.description_en')" id="program-description-en" :error="$errors->first('description_en')">
                    <x-admin.textarea id="program-description-en" rows="4" dir="ltr" wire:model="description_en" :error="filled($errors->first('description_en'))"/>
                </x-ui.field>
            </x-ui.card>

            <x-ui.card class="grid gap-5 sm:grid-cols-2">
                <x-ui.field :label="__('admin.fields.days_count')" id="program-days-count" type="number" min="1" max="7"
                            wire:model="days_count" :error="$errors->first('days_count')"/>

                <x-ui.field :label="__('program.level.label')" id="program-level" :error="$errors->first('level')">
                    <x-admin.select id="program-level" wire:model="level" :error="filled($errors->first('level'))">
                        @foreach ($levels as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field :label="__('program.goal.label')" id="program-goal" :error="$errors->first('goal')">
                    <x-admin.select id="program-goal" wire:model="goal" :error="filled($errors->first('goal'))">
                        <option value="">{{ __('admin.fields.none') }}</option>
                        @foreach ($goals as $goal)
                            <option value="{{ $goal }}">{{ __('program.goal.'.$goal) }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field :label="__('admin.fields.published_at')" id="program-published-at" type="datetime-local"
                            wire:model="published_at" :error="$errors->first('published_at')"/>

                <x-admin.toggle id="program-is-public" :label="__('admin.fields.is_public')" wire:model="is_public"/>

                <x-admin.toggle id="program-is-featured" :label="__('admin.fields.is_featured')" wire:model="is_featured"/>
            </x-ui.card>

            <x-ui.card class="flex flex-col gap-4">
                <x-ui.field :label="__('admin.fields.cover')" id="program-cover" type="file"
                            accept="image/jpeg,image/png,image/webp"
                            :hint="__('admin.fields.cover_hint')"
                            wire:model="cover" :error="$errors->first('cover')"/>

                @if ($program?->cover_path)
                    <img src="{{ Storage::disk('public')->url($program->cover_path) }}"
                         alt="{{ __('admin.fields.current_media') }}"
                         loading="lazy"
                         class="aspect-[4/3] w-40 rounded-md border border-ink-700 bg-ink-800 object-cover">
                @endif
            </x-ui.card>

            <x-admin.form-actions>
                <x-ui.button type="submit" class="flex-1 sm:flex-none">
                    {{ $program?->exists ? __('admin.actions.update') : __('admin.actions.store') }}
                </x-ui.button>

                <x-ui.button variant="ghost" :href="route('admin.programs.index')" wire:navigate>
                    {{ __('common.actions.cancel') }}
                </x-ui.button>
            </x-admin.form-actions>
        </form>

        <aside class="flex flex-col gap-4">
            @if ($program?->exists)
                <x-ui.card class="flex flex-col gap-3">
                    <h3 class="text-lg font-semibold text-ink-50">{{ __('admin.entities.days') }}</h3>

                    @forelse ($days as $day)
                        <div wire:key="day-{{ $day->id }}"
                             class="flex items-center gap-2 border-b border-ink-800 pb-2 last:border-0 last:pb-0">
                            <a href="{{ route('admin.programs.day', ['program' => $program, 'day' => $day]) }}"
                               wire:navigate
                               class="flex min-h-11 flex-1 flex-col justify-center rounded-sm px-1 text-start hover:bg-ink-800">
                                <span class="text-sm font-medium text-ink-100">
                                    {{ __('program.days.number', ['number' => $day->day_number]) }}{{ $day->title ? ' · '.$day->title : '' }}
                                </span>
                                <span class="text-xs text-ink-400">
                                    {{ $day->is_rest_day
                                        ? __('program.days.rest')
                                        : __('program.days.exercises_count', ['count' => $day->exercises_count]) }}
                                </span>
                            </a>

                            <button type="button"
                                    wire:click="deleteDay({{ $day->id }})"
                                    wire:confirm="{{ __('admin.confirm.delete_body', ['name' => __('program.days.number', ['number' => $day->day_number])]) }}"
                                    aria-label="{{ __('admin.actions.remove_day') }}"
                                    class="inline-flex size-11 shrink-0 items-center justify-center rounded-sm text-danger hover:bg-ink-800">
                                <x-admin.icon name="trash" class="size-5"/>
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-ink-300">{{ __('admin.empty.day_body') }}</p>
                    @endforelse

                    <x-ui.button variant="secondary" wire:click="addDay" full>
                        <x-admin.icon name="plus" class="size-5"/>
                        {{ __('admin.actions.add_day') }}
                    </x-ui.button>
                </x-ui.card>

                <x-ui.card class="flex flex-col gap-3">
                    <h3 class="text-lg font-semibold text-ink-50">{{ __('admin.access_code.label') }}</h3>

                    @if ($program->access_code)
                        <x-admin.copy :value="route('programs.private', ['accessCode' => $program->access_code])"
                                      :label="__('admin.access_code.link')"
                                      :hint="__('admin.access_code.hint')"/>

                        <p class="text-xs leading-normal text-warning">{{ __('admin.access_code.regenerate_warning') }}</p>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.button variant="secondary"
                                         wire:click="regenerateAccessCode"
                                         wire:confirm="{{ __('admin.access_code.regenerate_warning') }}">
                                <x-admin.icon name="refresh" class="size-5"/>
                                {{ __('admin.access_code.regenerate') }}
                            </x-ui.button>

                            <x-ui.button variant="ghost" wire:click="clearAccessCode">
                                {{ __('admin.access_code.none') }}
                            </x-ui.button>
                        </div>
                    @else
                        <p class="text-sm text-ink-300">{{ __('admin.access_code.hint') }}</p>

                        <x-ui.button variant="secondary" wire:click="regenerateAccessCode" full>
                            {{ __('admin.access_code.generate') }}
                        </x-ui.button>
                    @endif
                </x-ui.card>
            @endif
        </aside>
    </div>
</div>
