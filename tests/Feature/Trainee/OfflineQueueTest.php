<?php

declare(strict_types=1);

use App\Actions\Trainee\OfflineRuntimeScript;
use App\Models\Exercise;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Models\User;

/**
 * @return array{user: User, program: Program}
 */
function offlineFixture(): array
{
    $user = User::factory()->trainee()->create();
    $program = Program::factory()->create();

    $day = ProgramDay::factory()->forProgram($program)->dayNumber(1)->create();

    ProgramExercise::factory()
        ->forDay($day)
        ->forExercise(Exercise::factory()->create())
        ->create(['sets' => 2, 'rest_seconds' => 90]);

    $program->users()->attach($user, ['started_at' => now()->toDateString(), 'is_active' => true]);

    return ['user' => $user, 'program' => $program];
}

/*
 | The runtime is inlined rather than served from /build on purpose: Workbox
 | precaches build assets and caches HTML navigations, but a `<script src>`
 | fetched at runtime matches neither rule and would 404 in exactly the basement
 | this feature exists for. These assertions pin that decision down.
 */
it('inlines the offline runtime into the logging page', function (): void {
    ['user' => $user, 'program' => $program] = offlineFixture();

    $html = $this->actingAs($user)
        ->get('/dashboard/log?program='.$program->slug.'&day=1')
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('areen-set-logger')
        ->toContain('data-areen-sync')
        ->toContain('data-endpoint="'.route('dashboard.logs.sync').'"')
        // The uuid is minted on the phone before the round is ever sent.
        ->toContain('randomUUID')
        // Background Sync where it exists…
        ->toContain('areen-workout-logs')
        // …and the fallbacks that carry iOS, which has never shipped it.
        ->toContain("addEventListener('online'")
        ->toContain('visibilitychange')
        ->toContain('pagehide');
});

it('hands the page a CSRF token so a drain can post while signed in', function (): void {
    ['user' => $user, 'program' => $program] = offlineFixture();

    $html = $this->actingAs($user)
        ->get('/dashboard/log?program='.$program->slug.'&day=1')
        ->getContent();

    expect($html)->toMatch('/data-csrf="[A-Za-z0-9]{20,}"/');
});

it('never disables the log button because the network is down', function (): void {
    ['user' => $user, 'program' => $program] = offlineFixture();

    $html = $this->actingAs($user)
        ->get('/dashboard/log?program='.$program->slug.'&day=1')
        ->getContent();

    // DESIGN.md §11: accept the input and store it, whatever the radio is doing.
    expect($html)->not->toContain('data-action="log" disabled')
        ->and(OfflineRuntimeScript::contents())->not->toContain('.disabled = true');
});

it('concatenates the runtime modules in dependency order', function (): void {
    OfflineRuntimeScript::flush();

    $script = OfflineRuntimeScript::contents();

    $store = strpos($script, 'areen.offlineStore = {');
    $sync = strpos($script, 'areen.offlineSync = {');
    $logger = strpos($script, "customElements.define('areen-set-logger'");

    expect($store)->toBeInt()
        ->and($sync)->toBeGreaterThan($store)
        ->and($logger)->toBeGreaterThan($sync);
});

it('carries the rest seconds each row should start the timer with', function (): void {
    ['user' => $user, 'program' => $program] = offlineFixture();

    $html = $this->actingAs($user)
        ->get('/dashboard/log?program='.$program->slug.'&day=1')
        ->getContent();

    expect($html)->toContain('data-rest="90"')
        ->toContain('areen:rest-start');
});

it('opens the logging screen from the day view for a signed-in trainee', function (): void {
    ['user' => $user, 'program' => $program] = offlineFixture();

    $expected = route('dashboard.log', ['program' => $program->slug, 'day' => 1]);

    $this->actingAs($user)
        ->get(route('programs.day', ['program' => $program, 'day' => 1]))
        ->assertOk()
        // Escaped, because the query separator is written `&amp;` in an href.
        ->assertSee(e($expected), escape: false)
        ->assertDontSee(__('program.days.log_soon'));
});

it('still explains the button to a visitor with no account', function (): void {
    ['program' => $program] = offlineFixture();

    $this->get(route('programs.day', ['program' => $program, 'day' => 1]))
        ->assertOk()
        ->assertSee(__('program.days.log_soon'));
});
