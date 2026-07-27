<?php

use App\Enums\ProgramLevel;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    /**
     * An empty string means "every level". It rides in the query string so a
     * filtered catalogue can be shared and reopened exactly as it was left.
     */
    #[Url(as: 'level', except: '')]
    public string $level = '';

    /**
     * Tapping the chip that is already on clears the filter, so the control
     * needs no second reset target competing for the thumb.
     */
    public function filterByLevel(string $level = ''): void
    {
        $this->level = $this->level === $level ? '' : $level;
    }

    /**
     * @return list<ProgramLevel>
     */
    #[Computed]
    public function levels(): array
    {
        return ProgramLevel::cases();
    }

    /**
     * `published()` requires both `is_public` and a `published_at` that has
     * already arrived, so a private program can never reach the catalogue —
     * its only door is `programs.private`.
     *
     * @return Collection<int, Program>
     */
    #[Computed]
    public function programs(): Collection
    {
        $level = ProgramLevel::tryFrom($this->level);

        return Program::query()
            ->published()
            ->when($level, fn (Builder $query) => $query->where('level', $level->value))
            ->ordered()
            ->get();
    }

    /**
     * Tells an empty grid apart from a catalogue that simply has nothing in it
     * yet, so the page can invite for the right reason.
     */
    #[Computed]
    public function hasAnyProgram(): bool
    {
        return Program::query()->published()->exists();
    }
};
?>

@section('title', __('program.title').' · '.__('common.app_name'))

@push('head')
    <x-seo.page :description="__('common.seo.programs')"/>
@endpush

<div class="flex flex-col gap-6 px-4 pt-6 pb-8">
    <header class="flex flex-col gap-2">
        <h1 class="text-[2rem] font-bold leading-tight text-ink-50">{{ __('program.title') }}</h1>
        <p class="max-w-[65ch] text-ink-300">{{ __('common.tagline') }}</p>
    </header>

    {{--
      The filter and the grid it drives sit in one island, so choosing a level
      swaps the cards and leaves the rest of the document — header, shell and
      scroll position — untouched.
    --}}
    @island(name: 'catalogue')
        <div class="flex flex-col gap-6">
            <div role="group"
                 aria-label="{{ __('program.level.label') }}"
                 class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1">
                <x-ui.chip tag="button"
                           wire:click="filterByLevel"
                           :active="$level === ''">{{ __('program.level.any') }}</x-ui.chip>

                @foreach ($this->levels as $case)
                    <x-ui.chip tag="button"
                               wire:key="level-{{ $case->value }}"
                               wire:click="filterByLevel('{{ $case->value }}')"
                               :active="$level === $case->value">{{ $case->label() }}</x-ui.chip>
                @endforeach
            </div>

            <div aria-live="polite">
                @if ($this->programs->isNotEmpty())
                    <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->programs as $program)
                            <li wire:key="program-{{ $program->getKey() }}" class="flex">
                                <x-program.card :program="$program" :heading-level="2" class="w-full"/>
                            </li>
                        @endforeach
                    </ul>
                @elseif ($this->hasAnyProgram)
                    <x-ui.empty-state>
                        <x-slot:title>{{ __('program.filters.no_results_title') }}</x-slot:title>
                        <x-slot:body>{{ __('program.filters.no_results_body') }}</x-slot:body>
                        <x-slot:action>
                            <x-ui.button variant="secondary" wire:click="filterByLevel">
                                {{ __('program.filters.reset') }}
                            </x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state>
                        <x-slot:title>{{ __('program.empty.title') }}</x-slot:title>
                        <x-slot:body>{{ __('program.empty.body') }}</x-slot:body>
                        <x-slot:action>
                            <x-ui.button :href="route('exercises.index')" wire:navigate>
                                {{ __('exercise.library') }}
                            </x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @endif
            </div>
        </div>
    @endisland
</div>
