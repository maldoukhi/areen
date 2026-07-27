<?php

use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Program $program;

    /**
     * The day NUMBER inside the program, never a primary key. Day 1 of one
     * program and day 1 of another are different rows with the same number.
     *
     * Deliberately NOT named `day`: Livewire assigns any route parameter whose
     * name matches a public property straight onto that property, before mount
     * ever runs. A `public int $day` would therefore take the raw URL segment
     * and turn /day/abc into a TypeError and a 500 that no guard here can catch.
     */
    public int $dayNumber;

    public function mount(Program $program, int|string $day): void
    {
        // `{day}` arrives as a raw URL segment with no route constraint, so it is
        // validated here rather than typed into the signature — a bare `int $day`
        // would be a TypeError rather than a 404. Anything that is not a positive
        // whole number — 0, abc, 2.5, -1 — is a 404.
        $number = filter_var($day, FILTER_VALIDATE_INT);

        abort_if($number === false || $number < 1, 404);

        $this->program = $program;
        $this->dayNumber = $number;

        // A day number this program does not have — 99, or a day that belongs to
        // a different program — is a 404 too, not an empty screen.
        abort_unless($this->currentDay instanceof ProgramDay, 404);
    }

    /**
     * "Day 3 · Push · Areen". The day number alone is what the tab said before,
     * and out of context — in a browser history, a bookmark bar, a search
     * result — it does not say which program it belongs to.
     */
    public function rendering($view): void
    {
        $view->title($this->heading.' · '.$this->program->name.' · '.__('common.app_name'));
    }

    /**
     * Every day of the program in order. The tab strip, the swipe and the
     * previous/next links all read this one query.
     *
     * @return Collection<int, ProgramDay>
     */
    #[Computed]
    public function days(): Collection
    {
        return $this->program->days()->get([
            'id', 'program_id', 'day_number', 'title_ar', 'title_en', 'is_rest_day',
        ]);
    }

    #[Computed]
    public function currentDay(): ?ProgramDay
    {
        return $this->program->days()
            ->with(['focusMuscle', 'exercises.exercise'])
            ->firstWhere('day_number', $this->dayNumber);
    }

    #[Computed]
    public function heading(): string
    {
        return filled($this->currentDay->title)
            ? __('program.days.title', ['number' => $this->dayNumber, 'title' => $this->currentDay->title])
            : __('program.days.number', ['number' => $this->dayNumber]);
    }

    #[Computed]
    public function previousDay(): ?int
    {
        return $this->days->where('day_number', '<', $this->dayNumber)->max('day_number');
    }

    #[Computed]
    public function nextDay(): ?int
    {
        return $this->days->where('day_number', '>', $this->dayNumber)->min('day_number');
    }

    /**
     * The day's rows grouped into blocks. Rows sharing a `superset_group` value
     * are performed back to back, so they travel together inside one block and
     * are drawn as one unit; every other row is a block of one. The badge number
     * keeps counting across blocks, because the trainee counts exercises, not
     * blocks.
     *
     * @return list<array{group: string|null, rows: list<array{number: int, exercise: ProgramExercise}>}>
     */
    #[Computed]
    public function blocks(): array
    {
        $blocks = [];
        $number = 0;

        foreach ($this->currentDay->exercises as $exercise) {
            $number++;

            $group = filled($exercise->superset_group) ? (string) $exercise->superset_group : null;
            $last = array_key_last($blocks);

            // Only *adjacent* rows join: a group letter reused later in the day is
            // a second superset, not a continuation of the first.
            if ($group !== null && $last !== null && $blocks[$last]['group'] === $group) {
                $blocks[$last]['rows'][] = ['number' => $number, 'exercise' => $exercise];

                continue;
            }

            $blocks[] = ['group' => $group, 'rows' => [['number' => $number, 'exercise' => $exercise]]];
        }

        return $blocks;
    }

    #[Computed]
    public function totalSets(): int
    {
        return (int) $this->currentDay->exercises->sum('sets');
    }

    /**
     * The rest the day opens with, used as the idle readout on the timer. P5
     * takes it over and counts down from the rest of the set just logged.
     */
    #[Computed]
    public function openingRest(): ?int
    {
        $seconds = (int) ($this->currentDay->exercises->first()?->rest_seconds ?? 0);

        return $seconds > 0 ? $seconds : null;
    }

    /**
     * Whether this viewer can actually log against this plan.
     *
     * The logging screen resolves a programme through the trainee's own
     * enrolment, so offering the button to anyone signed in promised something
     * the destination could not deliver: the visitor landed on an empty screen,
     * was sent to their dashboard, was sent on to the catalogue, and arrived
     * back here — a loop with no way out.
     */
    #[Computed]
    public function isEnrolled(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->programs()->whereKey($this->program->getKey())->exists();
    }

    /**
     * Join a published plan and go straight to logging.
     *
     * Only a plan this visitor could already read: `EnsureProgramIsViewable`
     * guards the route, so reaching this method at all means either the plan is
     * published or an access code opened it for this session.
     */
    public function startProgram(): void
    {
        $user = auth()->user();

        abort_if($user === null, 403);

        $user->programs()->syncWithoutDetaching([
            $this->program->getKey() => ['started_at' => now(), 'is_active' => true],
        ]);

        unset($this->isEnrolled);

        $this->redirectRoute('dashboard.log', [
            'program' => $this->program->slug,
            'day' => $this->dayNumber,
        ], navigate: true);
    }

    public function dayUrl(int $number): string
    {
        return route('programs.day', ['program' => $this->program, 'day' => $number]);
    }
};
?>

@push('head')
    <x-seo.page type="article"
                :description="__('common.seo.program_day', ['day' => $this->heading, 'program' => $this->program->name])"/>
@endpush

{{--
  DESIGN.md §11 — the day screen. The trainee is standing up, one-handed, out of
  breath, on a basement connection. One goal, large numbers, generous spacing,
  and nothing on the screen that is not doing work.

  Everything that answers "which day am I on" is a real URL, so the back button,
  a shared link, the print sheet and the service worker's precache all agree.
  Day switching is therefore navigation, not component state.
--}}

<div @class([
    'mx-auto w-full max-w-[1200px] px-4 pt-5',
    // Clearance for the fixed bar, so the last exercise is never trapped under it.
    'pb-[calc(136px+env(safe-area-inset-bottom))]' => ! $this->currentDay->is_rest_day && $this->blocks !== [],
    'pb-12' => $this->currentDay->is_rest_day || $this->blocks === [],
])>

    <div class="flex flex-col gap-1">
        <a href="{{ route('programs.show', $this->program) }}"
           wire:navigate
           aria-label="{{ __('common.actions.back') }}"
           class="-ms-2 inline-flex min-h-11 w-fit items-center rounded-sm px-2 text-sm text-ink-300 print:hidden">
            {{ $this->program->name }}
        </a>

        <h1 class="text-2xl font-bold leading-tight text-ink-50">{{ $this->heading }}</h1>
    </div>

    {{--
      DESIGN.md §5: a horizontally scrollable segmented strip, the active tab
      underlined 2px brand-400. It is also the keyboard and screen-reader route
      between days — the swipe below is an accelerator, never the only way.
    --}}
    <nav aria-label="{{ __('program.days.label') }}"
         class="-mx-4 mt-4 border-b border-ink-800 print:hidden">
        <ul role="list" class="flex gap-1 overflow-x-auto px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ($this->days as $tab)
                @php($isCurrent = $tab->day_number === $this->dayNumber)

                <li wire:key="day-tab-{{ $tab->id }}" class="shrink-0">
                    <a href="{{ $this->dayUrl($tab->day_number) }}"
                       wire:navigate
                       @if ($isCurrent) aria-current="page" x-data x-init="$el.scrollIntoView({ inline: 'center', block: 'nearest' })" @endif
                       @class([
                           'tabular flex min-h-11 items-center gap-1.5 border-b-2 px-3 text-sm font-medium',
                           'transition-colors duration-150 ease-out',
                           'border-brand-400 text-brand-400' => $isCurrent,
                           'border-transparent text-ink-300' => ! $isCurrent,
                       ])>
                        {{ __('program.days.number', ['number' => $tab->day_number]) }}

                        @if ($tab->is_rest_day)
                            <x-ui.icon name="moon" class="size-4 shrink-0 opacity-70"/>
                            <span class="sr-only">{{ __('program.days.rest') }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{--
      Swipe between days. Alpine ships inside Livewire 4, so this is the whole of
      it — no script tag, no library.

      Direction is resolved against the document's writing direction rather than
      the screen: the content follows the finger, so dragging the page to the
      right uncovers whatever sits to its left. In RTL that is the NEXT day; in
      LTR it is the previous one.

      Only touch and pen gestures swipe — a mouse drag is a text selection. A
      mostly-vertical drag is a scroll and is handed straight back to the browser.
      The drag-follow nudge is skipped entirely under prefers-reduced-motion; the
      navigation itself still works, because it is a gesture, not an animation.
    --}}
    <div x-data="{
             startX: 0,
             startY: 0,
             dx: 0,
             tracking: false,
             threshold: 56,
             reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,

             begin(event) {
                 if (event.pointerType === 'mouse' || ! event.isPrimary) return

                 this.startX = event.clientX
                 this.startY = event.clientY
                 this.dx = 0
                 this.tracking = true
             },

             move(event) {
                 if (! this.tracking) return

                 const dx = event.clientX - this.startX
                 const dy = event.clientY - this.startY

                 // Let the gesture declare itself before locking an axis. The first
                 // pixel of a horizontal swipe is nearly always vertical noise, and
                 // deciding on it would cancel almost every real swipe.
                 if (Math.abs(dx) < 12 && Math.abs(dy) < 12) return

                 if (Math.abs(dy) > Math.abs(dx)) return this.cancel()

                 this.dx = dx
             },

             finish(event) {
                 if (! this.tracking) return

                 const dx = event.clientX - this.startX
                 const dy = event.clientY - this.startY

                 this.cancel()

                 if (Math.abs(dx) < this.threshold || Math.abs(dx) <= Math.abs(dy)) return

                 const rtl = getComputedStyle(this.$el).direction === 'rtl'
                 const forward = rtl ? dx > 0 : dx < 0
                 const target = this.$refs[forward ? 'nextDay' : 'previousDay']

                 if (target) target.click()
             },

             cancel() {
                 this.tracking = false
                 this.dx = 0
             },
         }"
         x-on:pointerdown="begin($event)"
         x-on:pointermove="move($event)"
         x-on:pointerup="finish($event)"
         x-on:pointercancel="cancel()"
         x-bind:style="tracking && ! reduced
             ? 'transform: translateX(' + Math.max(-24, Math.min(24, dx * 0.2)) + 'px)'
             : ''"
         class="motion-safe:transition-transform motion-safe:duration-150 motion-safe:ease-out">

        {{--
          The swipe's accessible twin. Hidden until focused, so a keyboard or a
          screen reader gets the same two moves the thumb does — and the swipe
          handler has something real to click instead of a JS navigation API.
        --}}
        <div class="print:hidden">
            @if ($this->previousDay)
                <a href="{{ $this->dayUrl($this->previousDay) }}"
                   wire:navigate
                   x-ref="previousDay"
                   class="sr-only focus:not-sr-only focus:mt-3 focus:inline-flex focus:min-h-11 focus:items-center focus:rounded-sm focus:bg-ink-800 focus:px-4 focus:text-sm focus:text-ink-100">
                    {{ __('program.days.previous') }}
                </a>
            @endif

            @if ($this->nextDay)
                <a href="{{ $this->dayUrl($this->nextDay) }}"
                   wire:navigate
                   x-ref="nextDay"
                   class="sr-only focus:not-sr-only focus:mt-3 focus:inline-flex focus:min-h-11 focus:items-center focus:rounded-sm focus:bg-ink-800 focus:px-4 focus:text-sm focus:text-ink-100">
                    {{ __('program.days.next') }}
                </a>
            @endif
        </div>

        @if ($this->currentDay->is_rest_day)
            {{--
              A rest day says so and stops. This is not an empty state and must not
              read like one: nothing is missing, the plan is to do nothing.
            --}}
            <section class="mx-auto flex max-w-[45ch] flex-col items-center gap-4 px-2 py-16 text-center">
                <span class="flex size-16 items-center justify-center rounded-full border border-ink-700 bg-ink-800 text-brand-400">
                    <x-ui.icon name="moon" class="size-7"/>
                </span>

                <h2 class="text-2xl font-bold text-ink-50">{{ __('program.days.rest_title') }}</h2>

                <p class="leading-relaxed text-ink-300">{{ __('program.days.rest_body') }}</p>

                @if (filled($this->currentDay->notes))
                    <p class="w-full rounded-lg border border-ink-700 bg-ink-900 p-4 text-start text-sm leading-relaxed text-ink-200">
                        {{ $this->currentDay->notes }}
                    </p>
                @endif

                @if ($this->nextDay)
                    <x-ui.button variant="secondary"
                                 :href="$this->dayUrl($this->nextDay)"
                                 wire:navigate
                                 class="mt-2 print:hidden">
                        {{ __('program.days.next') }}
                    </x-ui.button>
                @endif
            </section>
        @else
            <div class="mt-6 flex flex-wrap items-center gap-x-8 gap-y-4">
                <x-ui.metric :value="$this->currentDay->exercises->count()"
                             :caption="__('exercise.title')"
                             tone="ink"/>

                <x-ui.metric :value="$this->totalSets"
                             :caption="__('exercise.prescription.sets')"
                             tone="ink"/>

                @if ($this->currentDay->focusMuscle)
                    <div class="flex flex-col items-start gap-1">
                        <x-ui.chip :active="true">{{ $this->currentDay->focusMuscle->name }}</x-ui.chip>
                        <span class="text-xs font-medium text-ink-300">{{ __('program.days.focus') }}</span>
                    </div>
                @endif
            </div>

            @if (filled($this->currentDay->notes))
                <x-ui.card class="mt-6">
                    <h2 class="text-xs font-medium text-ink-300">{{ __('program.days.notes') }}</h2>
                    <p class="mt-1 leading-relaxed text-ink-200">{{ $this->currentDay->notes }}</p>
                </x-ui.card>
            @endif

            @if ($this->blocks === [])
                <x-ui.empty-state class="mt-6">
                    <x-slot:title>{{ __('program.days.empty_title') }}</x-slot:title>
                    <x-slot:body>{{ __('program.days.empty_body') }}</x-slot:body>
                </x-ui.empty-state>
            @else
                <ul role="list" class="mt-6 flex flex-col gap-3">
                    @foreach ($this->blocks as $index => $block)
                        <li wire:key="block-{{ $this->currentDay->id }}-{{ $index }}">
                            @if ($block['group'] !== null && count($block['rows']) > 1)
                                {{--
                                  Superset. The members run back to back with no rest in
                                  between, so they are drawn as one bordered unit with a
                                  brand hairline threading the rows together — the border
                                  is the statement, the chip only names it.
                                --}}
                                <section aria-label="{{ __('exercise.prescription.superset') }}"
                                         class="rounded-lg border border-brand-400/40 bg-ink-900/40 p-3">
                                    <div class="flex flex-wrap items-center gap-2 pb-3">
                                        <x-ui.chip :active="true" class="gap-1.5">
                                            <x-ui.icon name="link" class="size-4 shrink-0"/>
                                            {{ __('exercise.prescription.superset') }}
                                        </x-ui.chip>

                                        <span class="text-xs leading-normal text-ink-300">
                                            {{ __('exercise.prescription.superset_hint') }}
                                        </span>
                                    </div>

                                    <div class="flex flex-col divide-y divide-brand-400/25 border-t border-brand-400/25">
                                        @foreach ($block['rows'] as $row)
                                            <x-exercise.row wire:key="pe-{{ $row['exercise']->id }}"
                                                            :program-exercise="$row['exercise']"
                                                            :number="$row['number']"
                                                            :heading-level="2"
                                                            class="py-4"/>
                                        @endforeach
                                    </div>
                                </section>
                            @else
                                @foreach ($block['rows'] as $row)
                                    <x-ui.card wire:key="pe-{{ $row['exercise']->id }}">
                                        <x-exercise.row :program-exercise="$row['exercise']"
                                                        :number="$row['number']"
                                                        :heading-level="2"/>
                                    </x-ui.card>
                                @endforeach
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>

    @if (! $this->currentDay->is_rest_day && $this->blocks !== [])
        {{--
          DESIGN.md §11: the fixed bottom bar — the log-set action plus the timer.
          It sits in the lower half because that is where the thumb reaches, and it
          clears the gesture bar through `safe-pb`. In standalone mode it stacks
          above the app's own bottom nav, which already owns the safe area, so the
          padding is handed over rather than counted twice.

          SCOPE: this bar is layout only in this phase.
            · P4 owns set logging and has taken the seam: signed in, the button
              opens /dashboard/log for this program and day. A visitor with no
              account still sees it, announced as unavailable rather than hidden,
              so the affordance and its explanation ship together.
            · P5 owns the rest timer. The ring and the readout are drawn in their
              idle state; P5 counts the dial down, promotes the figure to the 48px
              ember display DESIGN.md §11 specifies while resting, and adds the
              vibration, the tone and the single flash at zero.
          Both seams sit inside this island so a tick or a saved set repaints the
          bar alone and never the exercise list underneath it.
        --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-700 bg-ink-900/95 pt-3 backdrop-blur-md safe-pb print:hidden
                    [@media(display-mode:standalone)]:bottom-[calc(60px+env(safe-area-inset-bottom))]
                    [@media(display-mode:standalone)]:pb-4">
            <div class="mx-auto w-full max-w-[1200px] px-4">
                @island(name: 'action-bar')
                    <div class="flex items-center gap-3">
                        <div class="relative flex size-14 shrink-0 items-center justify-center"
                             role="timer"
                             aria-label="{{ __('exercise.log.rest_timer') }}">
                            <svg class="absolute inset-0 size-full -rotate-90" viewBox="0 0 56 56" fill="none" aria-hidden="true">
                                <circle cx="28" cy="28" r="25" stroke="currentColor" stroke-width="3" class="text-ink-700"/>
                                {{-- Idle: the dial is empty. P5 walks stroke-dashoffset from 157 down to 0. --}}
                                <circle cx="28" cy="28" r="25" stroke="currentColor" stroke-width="3"
                                        stroke-linecap="round" class="text-ember"
                                        stroke-dasharray="157" stroke-dashoffset="157"/>
                            </svg>

                            <span class="tabular relative text-sm font-bold leading-none text-ink-300">
                                {{ sprintf('%02d:%02d', intdiv((int) $this->openingRest, 60), (int) $this->openingRest % 60) }}
                            </span>
                        </div>

                        {{--
                          The button offers only what the next screen can honour:
                          logging when this plan is already theirs, joining it when
                          it is not, and an explanation when nobody is signed in.
                        --}}
                        @auth
                            @if ($this->isEnrolled)
                                <x-ui.button :href="route('dashboard.log', ['program' => $this->program->slug, 'day' => $this->dayNumber])"
                                             wire:navigate
                                             class="flex-1">
                                    {{ __('program.days.log_set') }}
                                </x-ui.button>
                            @else
                                <x-ui.button wire:click="startProgram"
                                             wire:loading.attr="disabled"
                                             class="flex-1">
                                    {{ __('program.actions.start') }}
                                </x-ui.button>
                            @endif
                        @else
                            <x-ui.button aria-disabled="true"
                                         aria-describedby="log-set-note"
                                         class="flex-1">
                                {{ __('program.days.log_set') }}
                            </x-ui.button>
                        @endauth
                    </div>

                    @guest
                        <p id="log-set-note" class="pt-2 text-xs leading-normal text-ink-300">
                            {{ __('program.days.log_soon') }}
                        </p>
                    @endguest
                @endisland
            </div>
        </div>
    @endif
</div>
