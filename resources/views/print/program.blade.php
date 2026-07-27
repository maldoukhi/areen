{{--
  The printable schedule (DESIGN.md §8): white background, #111 text, one
  table per day with every day starting a fresh physical page, a blank
  "weight used" column for the pen, and a QR code back to the online plan.

  This view is shared, byte for byte, between the browser-viewed print route
  and the PDF route — App\Support\ProgramPrintPresenter builds the same data
  for both, and App\Http\Controllers\ProgramPdfController renders this exact
  template to HTML before handing it to Chromium. Nothing here may assume a
  live app session or a Livewire round-trip: the PDF path renders it from a
  bare file on disk with no server behind it.
--}}
@extends('print.layout')

@section('title', __('program.print.heading', ['program' => $program->name]))

@section('content')
    @php
        $clubName = filled($setting->club_name) ? $setting->club_name : __('common.app_name');
        $totalDays = $days->count();
    @endphp

    {{--
      Screen-only controls. `@media print` in resources/css/print.css removes
      this whole bar, so neither the print preview nor the PDF ever shows a
      button that cannot be pressed on paper.
    --}}
    <div class="no-print" role="note">
        <p>{{ __('program.print.heading', ['program' => $program->name]) }}</p>

        <button type="button" onclick="window.print()">{{ __('common.actions.print') }}</button>
        <a href="{{ route('programs.pdf', $program) }}">{{ __('program.actions.download_pdf') }}</a>
        <a href="{{ route('programs.show', $program) }}">{{ __('program.actions.view') }}</a>
    </div>

    @if (! empty($pdfUnavailable))
        <div class="pdf-unavailable" role="alert">
            <h2>{{ __('print.pdf.unavailable_title') }}</h2>
            <p>{{ __('print.pdf.unavailable_body') }}</p>
        </div>
    @endif

    @if ($totalDays === 0)
        <section class="day-page">
            <header class="doc-header">
                @if ($clubLogoDataUri)
                    <img src="{{ $clubLogoDataUri }}" alt="">
                @endif

                <div class="identity">
                    <span class="club-name">{{ $clubName }}</span>
                    <span class="program-name">{{ $program->name }}</span>
                </div>

                <span class="printed-on">
                    {{ __('program.print.printed_on', ['date' => $printedAt->format('Y-m-d')]) }}
                </span>
            </header>

            <div class="notice-box">
                <h2>{{ __('program.days.none_title') }}</h2>
                <p>{{ __('program.days.none_body') }}</p>
            </div>
        </section>
    @else
        @foreach ($days as $day)
            <section class="day-page">
                <header class="doc-header">
                    @if ($clubLogoDataUri)
                        <img src="{{ $clubLogoDataUri }}" alt="">
                    @endif

                    <div class="identity">
                        <span class="club-name">{{ $clubName }}</span>
                        <span class="program-name">{{ $program->name }}</span>
                    </div>

                    <span class="printed-on">
                        {{ __('program.print.printed_on', ['date' => $printedAt->format('Y-m-d')]) }}
                    </span>
                </header>

                @php
                    $dayTitle = filled($day->title)
                        ? __('program.days.title', ['number' => $day->day_number, 'title' => $day->title])
                        : __('program.days.number', ['number' => $day->day_number]);
                @endphp

                <h1 class="day-title">{{ $dayTitle }}</h1>

                @if ($day->focusMuscle)
                    <p class="day-meta">{{ __('program.days.focus') }} · {{ $day->focusMuscle->name }}</p>
                @endif

                @if ($day->is_rest_day)
                    {{-- A rest day prints as a clearly marked rest day, never as an empty table. --}}
                    <div class="notice-box">
                        <h2>{{ __('program.days.rest_title') }}</h2>
                        <p>{{ __('program.days.rest_body') }}</p>

                        @if (filled($day->notes))
                            <p>{{ $day->notes }}</p>
                        @endif
                    </div>
                @elseif ($day->exercises->isEmpty())
                    <div class="notice-box">
                        <h2>{{ __('program.days.empty_title') }}</h2>
                        <p>{{ __('program.days.empty_body') }}</p>
                    </div>
                @else
                    <table class="exercise-table">
                        <thead>
                            <tr>
                                <th class="col-number">{{ __('print.table.number') }}</th>
                                <th>{{ __('print.table.exercise') }}</th>
                                <th>{{ __('exercise.prescription.sets') }}</th>
                                <th>{{ __('exercise.prescription.reps') }}</th>
                                <th>{{ __('exercise.prescription.rest') }}</th>
                                <th>{{ __('program.print.weight_used') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($day->exercises as $index => $programExercise)
                                <tr>
                                    <td class="col-number">{{ $index + 1 }}</td>
                                    <td>{{ $programExercise->exercise->name }}</td>
                                    <td class="col-sets">{{ $programExercise->sets }}</td>
                                    <td class="col-reps">{{ $programExercise->reps ?? '' }}</td>
                                    <td class="col-rest">
                                        {{ __('exercise.prescription.rest_value', ['seconds' => $programExercise->rest_seconds]) }}
                                    </td>
                                    <td class="col-weight"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <footer class="day-footer">
                    @if ($qrSvg)
                        <div class="qr" aria-hidden="true">{!! $qrSvg !!}</div>
                    @endif

                    <div class="scan-text">
                        <span>{{ __('program.print.scan_hint') }}</span>
                        <span class="verify-url">{{ __('print.verify_url_label') }}: {{ $verifyUrl }}</span>
                    </div>

                    <span class="page-position">
                        {{ __('print.page_of', ['day' => $day->day_number, 'total' => $totalDays]) }}
                    </span>
                </footer>
            </section>
        @endforeach
    @endif
@endsection
