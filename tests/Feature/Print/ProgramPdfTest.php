<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Support\PdfRenderer;

function pdfReadyProgram(): Program
{
    $program = Program::factory()->create([
        'is_public' => true,
        'published_at' => now()->subDay(),
        'access_code' => null,
    ]);

    $muscle = MuscleGroup::factory()->create();
    $exercise = Exercise::factory()->create(['muscle_group_id' => $muscle->getKey()]);

    $dayOne = ProgramDay::factory()->forProgram($program)->dayNumber(1)->create();
    ProgramExercise::factory()->forDay($dayOne)->forExercise($exercise)->create();

    $dayTwo = ProgramDay::factory()->forProgram($program)->dayNumber(2)->create();
    ProgramExercise::factory()->forDay($dayTwo)->forExercise($exercise)->create();

    return $program;
}

// `CHROMIUM_PATH` is process-global state (see App\Support\PdfRenderer), so
// a test that overrides it must always put it back — otherwise a later test
// in the same run inherits a broken override it never asked for.
afterEach(function (): void {
    putenv('CHROMIUM_PATH');
});

it('downloads a real, multi-page PDF for a published program', function (): void {
    // This environment ships a real Chromium; skip rather than fail on a box
    // that genuinely has none, so the suite still reports the reason honestly.
    if (! PdfRenderer::isAvailable()) {
        $this->markTestSkipped('No Chromium binary available in this environment.');
    }

    $program = pdfReadyProgram();

    $response = $this->get('/programs/'.$program->slug.'/pdf');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');

    $bytes = $response->getContent();

    expect($bytes)->not->toBeFalse();
    expect(substr((string) $bytes, 0, 4))->toBe('%PDF');
    // Comfortably above "a fake stub file" — a real page of embedded, shaped
    // Arabic text runs to tens of kilobytes on its own.
    expect(strlen((string) $bytes))->toBeGreaterThan(5000);

    // Content-based, not just byte-count: a `/Type /Page` object exists per
    // physical page, so this confirms Chromium actually paginated the two
    // days into two pages rather than emitting one page or an error page.
    // (`/Type /Pages` — the singular tree node — is deliberately excluded.)
    expect(preg_match_all('#/Type\s*/Page(?!s)#', (string) $bytes))->toBe(2);
});

it('degrades to the print page with a plain notice when no Chromium binary is available', function (): void {
    putenv('CHROMIUM_PATH=/definitely/not/a/real/binary');

    expect(PdfRenderer::isAvailable())->toBeFalse();

    $program = pdfReadyProgram();

    $response = $this->get('/programs/'.$program->slug.'/pdf');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/html');
    $response->assertSee(__('print.pdf.unavailable_title'));
    $response->assertSee(__('print.pdf.unavailable_body'));
});
