<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Http\Requests\Admin\ExerciseRequest;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Support\Facades\Storage;
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

    public ?Exercise $exercise = null;

    public string $name_ar = '';

    public string $name_en = '';

    public string $slug = '';

    public string $muscle_group_id = '';

    /** @var list<string> */
    public array $secondary_muscles = [];

    public string $equipment = '';

    public string $difficulty = 'beginner';

    public string $youtube_url = '';

    public string $description_ar = '';

    public string $description_en = '';

    public bool $is_active = true;

    /** @var TemporaryUploadedFile|null */
    public $media = null;

    public function mount(?Exercise $exercise = null): void
    {
        $this->exercise = $exercise?->exists === true ? $exercise : null;

        if ($this->exercise === null) {
            $this->authorize('create', Exercise::class);

            $this->muscle_group_id = (string) (MuscleGroup::query()->ordered()->value('id') ?? '');

            return;
        }

        $this->authorize('update', $this->exercise);

        $this->fill([
            'name_ar' => (string) $this->exercise->name_ar,
            'name_en' => (string) $this->exercise->name_en,
            'slug' => (string) $this->exercise->slug,
            'muscle_group_id' => (string) $this->exercise->muscle_group_id,
            'secondary_muscles' => array_values(array_map('strval', (array) $this->exercise->secondary_muscles)),
            'equipment' => (string) $this->exercise->equipment,
            'difficulty' => $this->exercise->difficulty?->value ?? 'beginner',
            'youtube_url' => (string) $this->exercise->youtube_url,
            'description_ar' => (string) $this->exercise->description_ar,
            'description_en' => (string) $this->exercise->description_en,
            'is_active' => (bool) $this->exercise->is_active,
        ]);
    }

    public function save(): void
    {
        $this->exercise === null
            ? $this->authorize('create', Exercise::class)
            : $this->authorize('update', $this->exercise);

        $validated = $this->validate(
            ExerciseRequest::rulesFor($this->exercise),
            [],
            ExerciseRequest::attributeNames(),
        );

        $creating = $this->exercise === null;
        $exercise = $this->exercise ?? new Exercise;

        $exercise->fill([
            'name_ar' => $validated['name_ar'],
            'name_en' => blank($validated['name_en'] ?? null) ? null : $validated['name_en'],
            'slug' => $this->resolveSlug(),
            'muscle_group_id' => (int) $validated['muscle_group_id'],
            'secondary_muscles' => array_values($validated['secondary_muscles'] ?? []),
            'equipment' => blank($validated['equipment'] ?? null) ? null : $validated['equipment'],
            'difficulty' => $validated['difficulty'],
            'youtube_url' => blank($validated['youtube_url'] ?? null) ? null : $validated['youtube_url'],
            'description_ar' => blank($validated['description_ar'] ?? null) ? null : $validated['description_ar'],
            'description_en' => blank($validated['description_en'] ?? null) ? null : $validated['description_en'],
            'is_active' => $this->is_active,
        ]);

        if ($this->media instanceof TemporaryUploadedFile) {
            $previous = $exercise->media_path;

            // Local `public` disk, not S3 — the club's own server holds the media.
            $exercise->media_path = $this->media->store('exercises', 'public');

            if (filled($previous)) {
                Storage::disk('public')->delete($previous);
            }
        }

        $exercise->save();

        $this->exercise = $exercise->fresh();
        $this->reset('media');

        if ($creating) {
            $this->redirectRoute('admin.exercises.edit', ['exercise' => $exercise], navigate: false);
            session()->flash('status', __('admin.messages.created', ['name' => $exercise->name]));

            return;
        }

        session()->flash('status', __('admin.messages.updated'));
    }

    public function removeMedia(): void
    {
        abort_if($this->exercise === null, 404);

        $this->authorize('update', $this->exercise);

        if (filled($this->exercise->media_path)) {
            Storage::disk('public')->delete($this->exercise->media_path);
        }

        $this->exercise->forceFill(['media_path' => null])->save();
        $this->exercise = $this->exercise->fresh();

        session()->flash('status', __('admin.messages.updated'));
    }

    private function resolveSlug(): string
    {
        $base = Str::slug($this->slug ?: ($this->name_en ?: $this->name_ar));

        if (blank($base)) {
            $base = 'exercise-'.Str::lower(Str::random(6));
        }

        $candidate = $base;
        $suffix = 1;

        while (Exercise::withTrashed()
            ->where('slug', $candidate)
            ->when($this->exercise !== null, fn ($query) => $query->whereKeyNot($this->exercise->getKey()))
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
            'muscles' => MuscleGroup::query()->ordered()->get(),
            'difficulties' => Difficulty::cases(),
            'equipmentOptions' => ExerciseRequest::EQUIPMENT,
        ];
    }
};
?>

<div>
    <x-admin.page-header :back="route('admin.exercises.index')"
                         :title="$exercise?->exists ? $exercise->name : __('admin.entities.exercise')"/>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="flex flex-col gap-5">
            <x-ui.card class="flex flex-col gap-5">
                <x-ui.field :label="__('admin.fields.name_ar')" id="exercise-name-ar" required
                            wire:model="name_ar" :error="$errors->first('name_ar')"/>

                <x-ui.field :label="__('admin.fields.name_en')" id="exercise-name-en" dir="ltr"
                            wire:model="name_en" :error="$errors->first('name_en')"/>

                <x-ui.field :label="__('admin.fields.slug')" id="exercise-slug" dir="ltr"
                            :hint="__('admin.fields.slug_hint')"
                            wire:model="slug" :error="$errors->first('slug')"/>

                <x-ui.field :label="__('admin.fields.description_ar')" id="exercise-description-ar" :error="$errors->first('description_ar')">
                    <x-admin.textarea id="exercise-description-ar" rows="4" wire:model="description_ar" :error="filled($errors->first('description_ar'))"/>
                </x-ui.field>

                <x-ui.field :label="__('admin.fields.description_en')" id="exercise-description-en" :error="$errors->first('description_en')">
                    <x-admin.textarea id="exercise-description-en" rows="4" dir="ltr" wire:model="description_en" :error="filled($errors->first('description_en'))"/>
                </x-ui.field>
            </x-ui.card>

            <x-ui.card class="flex flex-col gap-5">
                <h3 class="text-lg font-semibold text-ink-50">{{ __('exercise.media.video') }}</h3>

                <x-ui.field :label="__('admin.fields.youtube_url')" id="exercise-youtube" dir="ltr" type="url"
                            :hint="__('admin.fields.youtube_hint')"
                            wire:model="youtube_url" :error="$errors->first('youtube_url')"/>

                @if ($exercise?->youtube_thumbnail_url)
                    <img src="{{ $exercise->youtube_thumbnail_url }}"
                         alt="{{ __('exercise.media.video') }}"
                         loading="lazy"
                         class="aspect-video w-56 rounded-md border border-ink-700 bg-ink-800 object-cover">
                @endif

                <x-ui.field :label="__('admin.fields.media')" id="exercise-media" type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            :hint="__('admin.fields.media_hint')"
                            wire:model="media" :error="$errors->first('media')"/>

                @if ($exercise?->media_path)
                    <div class="flex items-center gap-3">
                        <img src="{{ Storage::disk('public')->url($exercise->media_path) }}"
                             alt="{{ __('admin.fields.current_media') }}"
                             loading="lazy"
                             class="aspect-[4/3] w-32 rounded-md border border-ink-700 bg-ink-800 object-cover">

                        <x-ui.button variant="ghost" wire:click="removeMedia">{{ __('admin.actions.remove_media') }}</x-ui.button>
                    </div>
                @endif
            </x-ui.card>
        </div>

        <aside class="flex flex-col gap-5">
            <x-ui.card class="flex flex-col gap-5">
                <x-ui.field :label="__('exercise.muscle.primary')" id="exercise-muscle" required :error="$errors->first('muscle_group_id')">
                    <x-admin.select id="exercise-muscle" wire:model="muscle_group_id" :error="filled($errors->first('muscle_group_id'))">
                        @foreach ($muscles as $muscleGroup)
                            <option wire:key="muscle-{{ $muscleGroup->id }}" value="{{ $muscleGroup->id }}">{{ $muscleGroup->name }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <fieldset class="flex flex-col gap-2">
                    <legend class="mb-1 block text-sm font-medium text-ink-200">{{ __('exercise.muscle.secondary') }}</legend>

                    <div class="grid grid-cols-2 gap-x-3">
                        @foreach ($muscles as $muscleGroup)
                            <x-admin.toggle wire:key="secondary-{{ $muscleGroup->id }}"
                                            id="secondary-{{ $muscleGroup->slug }}"
                                            :label="$muscleGroup->name"
                                            value="{{ $muscleGroup->slug }}"
                                            wire:model="secondary_muscles"/>
                        @endforeach
                    </div>

                    @error('secondary_muscles.*')
                        <p class="text-sm leading-normal text-danger">{{ $message }}</p>
                    @enderror
                </fieldset>

                <x-ui.field :label="__('exercise.equipment.label')" id="exercise-equipment" :error="$errors->first('equipment')">
                    <x-admin.select id="exercise-equipment" wire:model="equipment" :error="filled($errors->first('equipment'))">
                        <option value="">{{ __('exercise.equipment.none') }}</option>
                        @foreach ($equipmentOptions as $option)
                            <option wire:key="equipment-{{ $option }}" value="{{ $option }}">{{ __('exercise.equipment.'.$option) }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field :label="__('exercise.difficulty.label')" id="exercise-difficulty" :error="$errors->first('difficulty')">
                    <x-admin.select id="exercise-difficulty" wire:model="difficulty" :error="filled($errors->first('difficulty'))">
                        @foreach ($difficulties as $case)
                            <option wire:key="difficulty-{{ $case->value }}" value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-admin.toggle id="exercise-is-active" :label="__('admin.fields.is_active')" wire:model="is_active"/>
            </x-ui.card>
        </aside>

        <x-admin.form-actions class="lg:col-span-2">
            <x-ui.button type="submit" class="flex-1 sm:flex-none">
                {{ $exercise?->exists ? __('admin.actions.update') : __('admin.actions.store') }}
            </x-ui.button>

            <x-ui.button variant="ghost" :href="route('admin.exercises.index')" wire:navigate>
                {{ __('common.actions.cancel') }}
            </x-ui.button>
        </x-admin.form-actions>
    </form>
</div>
