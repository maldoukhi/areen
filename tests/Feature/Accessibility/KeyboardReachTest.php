<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Models\User;
use Livewire\Livewire;

/*
 * Everything the thumb can reach has to be reachable from a keyboard too. The
 * two places that were not are the ones tested hardest here: the day-tab strip,
 * which the finger swipes between, and the admin's drag-and-drop reordering,
 * which a keyboard cannot press-and-drag at all.
 */

it('makes every day of a program reachable as a real link', function (): void {
    $program = Program::factory()->published()->create(['slug' => 'tabs-plan', 'days_count' => 3]);

    foreach ([1, 2, 3] as $number) {
        ProgramDay::factory()->for($program)->create(['day_number' => $number]);
    }

    $body = $this->get('/programs/tabs-plan/day/2')->assertOk()->getContent();

    // The swipe is an accelerator. Each tab is an <a href>, so it is tabbable,
    // it is announced, and the back button agrees with it.
    foreach ([1, 2, 3] as $number) {
        expect($body)->toContain(route('programs.day', ['program' => $program, 'day' => $number]));
    }

    expect($body)->toContain('aria-current="page"');
});

it('offers the previous and next day to a keyboard, not only to a swipe', function (): void {
    $program = Program::factory()->published()->create(['slug' => 'swipe-plan', 'days_count' => 3]);

    foreach ([1, 2, 3] as $number) {
        ProgramDay::factory()->for($program)->create(['day_number' => $number]);
    }

    $body = $this->get('/programs/swipe-plan/day/2')->assertOk()->getContent();

    // Hidden until focused, then visible — the same two moves the thumb has.
    expect($body)->toContain('x-ref="previousDay"')
        ->and($body)->toContain('x-ref="nextDay"')
        ->and($body)->toContain('focus:not-sr-only');
});

it('gives the day builder a keyboard path through the drag-and-drop order', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

    $program = Program::factory()->create(['days_count' => 1]);
    $day = ProgramDay::factory()->for($program)->create(['day_number' => 1, 'is_rest_day' => false]);
    $muscle = MuscleGroup::factory()->named('chest')->create();

    $first = ProgramExercise::factory()
        ->for($day, 'programDay')
        ->for(Exercise::factory()->for($muscle))
        ->create(['sort' => 0]);

    $second = ProgramExercise::factory()
        ->for($day, 'programDay')
        ->for(Exercise::factory()->for($muscle))
        ->create(['sort' => 1]);

    $body = $this->actingAs($admin)
        ->get(route('admin.programs.day', ['program' => $program, 'day' => $day]))
        ->assertOk()
        ->getContent();

    expect($body)->toContain(__('common.a11y.move_up'))
        ->and($body)->toContain(__('common.a11y.move_down'))
        // The grip is a pointer gesture only, so it must not sit in the tab
        // order pretending to be a control.
        ->and($body)->toContain('wire:sort:handle');

    expect(preg_match('/wire:sort:handle[^>]*aria-hidden="true"/s', $body))
        ->toBe(1, 'the drag grip is still exposed as a control it cannot be');

    // And the buttons drive the same handler a drop does.
    Livewire::actingAs($admin)
        ->test('pages::admin.programs.day', ['program' => $program, 'day' => $day])
        ->call('reorder', $second->id, 0)
        ->assertHasNoErrors();

    expect($second->fresh()->sort)->toBe(0)
        ->and($first->fresh()->sort)->toBe(1);
});

it('gives the muscle-group list the same keyboard path', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

    $first = MuscleGroup::factory()->create(['sort' => 0]);
    $second = MuscleGroup::factory()->create(['sort' => 1]);

    $body = $this->actingAs($admin)
        ->get(route('admin.muscle-groups.index'))
        ->assertOk()
        ->getContent();

    expect($body)->toContain(__('common.a11y.move_up'))
        ->and($body)->toContain(__('common.a11y.move_down'));

    Livewire::actingAs($admin)
        ->test('pages::admin.muscle-groups')
        ->call('reorder', $second->id, 0)
        ->assertHasNoErrors();

    expect($second->fresh()->sort)->toBe(0)
        ->and($first->fresh()->sort)->toBe(1);
});

it('labels every form control on the public surface', function (string $path): void {
    MuscleGroup::factory()->named('chest')->create();
    Exercise::factory()->create(['slug' => 'labelled-press']);

    $body = $this->get($path)->assertOk()->getContent();

    preg_match_all('/<(input|select|textarea)\b[^>]*>/i', $body, $matches);

    foreach ($matches[0] as $control) {
        if (preg_match('/type="(hidden|submit|button)"/i', $control)) {
            continue;
        }

        $labelled = str_contains($control, 'aria-label')
            || str_contains($control, 'aria-labelledby')
            || (preg_match('/\bid="([^"]+)"/', $control, $id) && str_contains($body, 'for="'.$id[1].'"'));

        expect($labelled)->toBeTrue("an unlabelled control on {$path}: {$control}");
    }
})->with(['/', '/exercises', '/programs', '/about']);

it('labels the sign-in form', function (): void {
    $body = $this->get(route('admin.login'))->assertOk()->getContent();

    preg_match_all('/<input\b[^>]*>/i', $body, $matches);

    foreach ($matches[0] as $control) {
        if (preg_match('/type="(hidden|submit)"/i', $control)) {
            continue;
        }

        $labelled = str_contains($control, 'aria-label')
            || (preg_match('/\bid="([^"]+)"/', $control, $id) && str_contains($body, 'for="'.$id[1].'"'));

        expect($labelled)->toBeTrue("an unlabelled control on the sign-in form: {$control}");
    }
});
