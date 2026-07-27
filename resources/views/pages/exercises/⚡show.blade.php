<?php

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * One exercise: how it is performed, which muscles it pays, and where in the
 * club's published programs a trainee will meet it.
 */
new class extends Component
{
    public Exercise $exercise;

    /**
     * `Exercise::getRouteKeyName()` is `slug`, so the route parameter resolves
     * straight to the model and an unknown slug 404s before this runs.
     */
    public function mount(Exercise $exercise): void
    {
        $this->exercise = $exercise->load('muscleGroup');
    }

    /**
     * Secondary muscles are stored as a list of muscle slugs rather than a
     * pivot, so they are resolved here in one query. A slug with no group row
     * behind it still gets a chip — it just does not get a link.
     *
     * @return Collection<int, array{label: string, href: string|null}>
     */
    #[Computed]
    public function secondaryMuscles(): Collection
    {
        $slugs = collect($this->exercise->secondary_muscles ?? [])
            ->filter(fn ($slug): bool => is_string($slug) && $slug !== '')
            ->unique()
            ->values();

        if ($slugs->isEmpty()) {
            return collect();
        }

        $groups = MuscleGroup::query()->whereIn('slug', $slugs->all())->get()->keyBy('slug');

        return $slugs->map(fn (string $slug): array => [
            'label' => $groups->get($slug)?->name ?? __('exercise.muscle.'.$slug),
            'href' => $groups->has($slug) ? route('muscles.show', $slug) : null,
        ]);
    }

    /**
     * Only programs the club has actually published.
     *
     * `Program::published()` demands `is_public = true` AND a `published_at`
     * that is set AND already past, and the model's SoftDeletes keeps trashed
     * rows out on its own. A private program — the kind reachable only through
     * its access code on `/p/{code}` — therefore never appears here. Listing one
     * would leak the existence of a plan written for a single trainee, which is
     * worse than leaking its contents: the name alone is the tell.
     *
     * @return EloquentCollection<int, Program>
     */
    #[Computed]
    public function programs(): EloquentCollection
    {
        return Program::query()
            ->published()
            ->whereHas(
                'days.exercises',
                fn (Builder $query) => $query->where('exercise_id', $this->exercise->getKey())
            )
            ->ordered()
            ->get();
    }

    /**
     * A stored path, an absolute URL, or nothing. Every seeded exercise carries
     * a null `media_path` and a null `youtube_url`, so "nothing" is the ordinary
     * case and the page has to read as finished without any media at all.
     */
    #[Computed]
    public function mediaUrl(): ?string
    {
        $path = $this->exercise->media_path;

        if (blank($path)) {
            return null;
        }

        return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/'])
            ? $path
            : rescue(fn () => \Illuminate\Support\Facades\Storage::disk('public')->url($path), null, false);
    }
};
?>

@section('title', $exercise->name.' · '.__('common.app_name'))

<div class="flex flex-col gap-6 px-4 pt-6 pb-12">
    <nav aria-label="{{ __('exercise.library') }}">
        <a href="{{ route('exercises.index') }}"
           wire:navigate
           class="-ms-2 inline-flex min-h-11 items-center rounded-sm px-2 text-sm font-medium text-brand-400">
            {{ __('exercise.library') }}
        </a>
    </nav>

    <header class="flex flex-col gap-3">
        <h1 class="text-[2rem] font-bold leading-tight text-ink-50">{{ $exercise->name }}</h1>

        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm text-ink-400">{{ __('exercise.muscle.primary') }}</span>

            <x-ui.chip :href="route('muscles.show', $exercise->muscleGroup)" wire:navigate>
                {{ $exercise->muscleGroup->name }}
            </x-ui.chip>
        </div>
    </header>

    @if ($this->mediaUrl)
        {{-- DESIGN.md §6: 4:3 on an ink-800 bed, radius-md, lazily fetched. --}}
        <img src="{{ $this->mediaUrl }}"
             alt="{{ __('exercise.media.image') }}"
             loading="lazy"
             decoding="async"
             x-data
             x-on:error="$el.remove()"
             class="aspect-4/3 w-full rounded-md bg-ink-800 object-cover">
    @endif

    @if ($exercise->youtube_id)
        {{--
          A still and a play button, never an iframe on load: the embed is a
          third-party request that costs the trainee bandwidth in the gym and
          hands YouTube a visit it was not owed (DESIGN.md §6).
        --}}
        <section x-data="{ playing: false }" class="flex flex-col gap-2">
            <h2 class="text-sm font-medium text-ink-400">{{ __('exercise.media.video') }}</h2>

            <button type="button"
                    x-show="! playing"
                    x-on:click="playing = true"
                    class="relative block w-full overflow-hidden rounded-md border border-ink-700 bg-ink-800">
                <img src="{{ $exercise->youtube_thumbnail_url }}"
                     alt=""
                     loading="lazy"
                     decoding="async"
                     x-on:error="$el.remove()"
                     class="aspect-video w-full object-cover">

                <span class="absolute inset-0 flex items-center justify-center">
                    <span class="inline-flex min-h-11 items-center rounded-full bg-brand-400 px-5 text-sm font-medium text-brand-950">
                        {{ __('exercise.media.play') }}
                    </span>
                </span>
            </button>

            <template x-if="playing">
                <iframe class="aspect-video w-full rounded-md border border-ink-700 bg-ink-800"
                        src="https://www.youtube-nocookie.com/embed/{{ $exercise->youtube_id }}?autoplay=1&rel=0"
                        title="{{ __('exercise.media.video') }}"
                        loading="lazy"
                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            </template>
        </section>
    @endif

    <x-ui.card>
        <dl class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-1">
                <dt class="text-xs font-medium text-ink-400">{{ __('exercise.equipment.label') }}</dt>
                <dd class="text-base text-ink-100">
                    {{ filled($exercise->equipment) ? __('exercise.equipment.'.$exercise->equipment) : __('exercise.equipment.none') }}
                </dd>
            </div>

            <div class="flex flex-col gap-1">
                <dt class="text-xs font-medium text-ink-400">{{ __('exercise.difficulty.label') }}</dt>
                <dd class="text-base text-ink-100">{{ $exercise->difficulty?->label() }}</dd>
            </div>
        </dl>
    </x-ui.card>

    @if ($this->secondaryMuscles->isNotEmpty())
        <section class="flex flex-col gap-2">
            <h2 id="secondary-muscles" class="text-sm font-medium text-ink-400">{{ __('exercise.muscle.secondary') }}</h2>

            <div class="flex flex-wrap gap-2" aria-labelledby="secondary-muscles">
                @foreach ($this->secondaryMuscles as $muscle)
                    {{-- A slug with no group behind it renders as a plain span, and
                         `wire:navigate` is inert there. --}}
                    <x-ui.chip :href="$muscle['href']"
                               wire:navigate
                               wire:key="secondary-{{ $loop->index }}">{{ $muscle['label'] }}</x-ui.chip>
                @endforeach
            </div>
        </section>
    @endif

    @if (filled($exercise->description))
        <p class="max-w-[65ch] text-ink-200">{{ $exercise->description }}</p>
    @endif

    @if ($this->programs->isNotEmpty())
        <section class="flex flex-col gap-3">
            <h2 class="text-xl font-semibold text-ink-50">{{ __('program.title') }}</h2>

            <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($this->programs as $program)
                    <li wire:key="program-{{ $program->getKey() }}" class="flex">
                        <x-ui.card :href="route('programs.show', $program)" wire:navigate class="flex w-full flex-col gap-1">
                            <span class="text-base font-semibold text-ink-50">{{ $program->name }}</span>
                            <span class="tabular text-sm text-ink-400">
                                {{ __('program.days.count', ['count' => $program->days_count]) }}
                            </span>
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
