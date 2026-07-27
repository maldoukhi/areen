<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;

/**
 * Builds one of everything the public surface can show, so a data set is never
 * the reason a structural rule appears to hold.
 */
function seedPublicSurface(): Program
{
    $muscle = MuscleGroup::factory()->named('chest')->create();

    $exercise = Exercise::factory()->for($muscle)->create(['slug' => 'a11y-press']);

    $program = Program::factory()->published()->featured()->create([
        'slug' => 'a11y-plan',
        'days_count' => 1,
    ]);

    $day = ProgramDay::factory()->for($program)->create(['day_number' => 1, 'is_rest_day' => false]);

    ProgramExercise::factory()->for($day, 'programDay')->for($exercise)->create(['sort' => 0]);

    return $program;
}

/**
 * @return list<string>
 */
function publicPaths(): array
{
    return [
        '/',
        '/programs',
        '/programs/a11y-plan',
        '/programs/a11y-plan/day/1',
        '/exercises',
        '/exercises/a11y-press',
        '/muscles/chest',
        '/about',
        '/offline',
    ];
}

it('gives every public page one main landmark and a skip link into it', function (string $path): void {
    seedPublicSurface();

    $body = $this->get($path)->assertOk()->getContent();

    expect(substr_count($body, '<main '))->toBe(1, "{$path} does not have exactly one <main>")
        ->and($body)->toContain('id="main"')
        ->and($body)->toContain('href="#main"')
        // The target has to be focusable, or the skip link moves the viewport
        // and leaves the keyboard behind in the header.
        ->and($body)->toContain('tabindex="-1"');
})->with(publicPaths());

it('declares the language and the direction on every public page', function (string $path): void {
    seedPublicSurface();

    $this->get($path)->assertOk()->assertSee('<html lang="ar" dir="rtl"', escape: false);
})->with(publicPaths());

it('gives every public page exactly one first-level heading', function (string $path): void {
    seedPublicSurface();

    $body = $this->get($path)->assertOk()->getContent();

    expect(preg_match_all('/<h1[\s>]/i', $body))->toBe(1, "{$path} does not have exactly one <h1>");
})->with(publicPaths());

/*
 * A heading level that jumps — h1 straight to h3 — reads to a screen reader as
 * a missing section rather than as a smaller heading.
 */
it('never skips a heading level on a public page', function (string $path): void {
    seedPublicSurface();

    $body = $this->get($path)->assertOk()->getContent();

    preg_match_all('/<h([1-6])[\s>]/i', $body, $matches);

    $levels = array_map('intval', $matches[1]);
    $previous = 0;

    foreach ($levels as $level) {
        expect($level)->toBeLessThanOrEqual(
            $previous + 1,
            "{$path} jumps from h{$previous} to h{$level}: ".implode(' ', $levels),
        );

        $previous = $level;
    }
})->with(publicPaths());

it('names every navigation landmark, so a screen reader can tell them apart', function (): void {
    seedPublicSurface();

    $body = $this->get('/programs/a11y-plan/day/1')->assertOk()->getContent();

    preg_match_all('/<nav\b[^>]*>/i', $body, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $nav) {
        expect($nav)->toMatch('/aria-label(?:ledby)?=/', "an unnamed <nav> on the day page: {$nav}");
    }
});

it('announces the connection state without stealing focus', function (): void {
    $body = $this->get('/')->assertOk()->getContent();

    // role=status is an implicit aria-live=polite; both are stated so the
    // announcement survives a browser that only honours one of them.
    expect($body)->toContain('role="status"')
        ->and($body)->toContain('aria-live="polite"')
        ->and($body)->toContain('aria-label="'.__('common.a11y.connection_status').'"');
});

it('leaves no image without an alt attribute', function (string $path): void {
    seedPublicSurface();

    $body = $this->get($path)->assertOk()->getContent();

    preg_match_all('/<img\b[^>]*>/i', $body, $matches);

    foreach ($matches[0] as $img) {
        expect($img)->toContain('alt=', "an <img> with no alt on {$path}: {$img}");
    }
})->with(publicPaths());

/*
 * WCAG 2.2 SC 2.4.11. The halo DESIGN.md specifies is brand-400 at 40%, which
 * composites to 2.27:1 on ink-950 — below the 3:1 floor on its own. The solid
 * core inside it measures 7.27:1 and is what makes the indicator visible.
 */
it('draws a focus ring that is actually visible', function (): void {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain(':focus-visible')
        ->and($css)->toMatch('/outline:\s*2px solid var\(--color-brand-400\)/');
});
