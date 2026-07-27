@props(['program'])

{{--
  The two facts a trainee sorts on before anything else: how hard it is and what
  it is for. Both are chips (DESIGN.md §5) so they read the same on the catalogue
  card and on the overview, and neither is ever written as literal text.

  `goal` is stored as a stable English slug, so the label is looked up rather
  than stored. An unrecognised slug drops the chip instead of printing the raw
  translation key on the screen.

  Anything passed in the slot joins the same wrapping row, which is how the
  overview adds its "private program" badge without nesting a second flex row
  inside this one.
--}}

@php
    $goalKey = filled($program->goal) ? 'program.goal.'.$program->goal : null;
    $goalLabel = $goalKey && \Illuminate\Support\Facades\Lang::has($goalKey) ? __($goalKey) : null;
@endphp

<div {{ $attributes->class('flex flex-wrap items-center gap-2') }}>
    {{ $slot }}

    @if ($program->level)
        <x-ui.chip>{{ $program->level->label() }}</x-ui.chip>
    @endif

    @if ($goalLabel)
        <x-ui.chip>{{ $goalLabel }}</x-ui.chip>
    @endif
</div>
