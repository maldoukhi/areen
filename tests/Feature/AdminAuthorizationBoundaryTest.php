<?php

declare(strict_types=1);

use App\Models\User;

/*
 * An independent check of the authorisation boundary, written without reading
 * the panel's own tests, so a mistake shared between the code and its tests
 * still shows up here.
 */
$paths = ['/admin', '/admin/programs', '/admin/exercises', '/admin/muscle-groups', '/admin/trainees', '/admin/settings'];

it('turns a guest away from every admin path', function () use ($paths): void {
    foreach ($paths as $path) {
        $this->get($path)->assertRedirect('/admin/login');
    }
});

it('refuses a trainee every admin path', function () use ($paths): void {
    $trainee = User::factory()->create(['role' => 'trainee']);

    foreach ($paths as $path) {
        $this->actingAs($trainee)->get($path)->assertForbidden();
    }
});

it('lets an admin into every admin path', function () use ($paths): void {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach ($paths as $path) {
        $this->actingAs($admin)->get($path)->assertOk();
    }
});

it('lets a coach manage training but not people or the club', function (): void {
    $coach = User::factory()->create(['role' => 'coach']);

    $this->actingAs($coach)->get('/admin/programs')->assertOk();
    $this->actingAs($coach)->get('/admin/exercises')->assertOk();

    $this->actingAs($coach)->get('/admin/trainees')->assertForbidden();
    $this->actingAs($coach)->get('/admin/settings')->assertForbidden();
});

it('strips every permission from a deactivated admin', function (): void {
    $suspended = User::factory()->create(['role' => 'admin', 'is_active' => false]);

    $this->actingAs($suspended)->get('/admin')->assertForbidden();
});
