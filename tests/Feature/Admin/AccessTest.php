<?php

declare(strict_types=1);

use App\Models\Program;
use App\Models\User;

it('sends a signed-out visitor to the sign-in screen rather than a blank 403', function (): void {
    $this->get('/admin')
        ->assertRedirect(route('admin.login'));
});

it('refuses a trainee outright — the panel is not theirs to see', function (): void {
    $this->actingAs(User::factory()->trainee()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('opens the panel for an admin', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk();
});

it('opens the panel for a coach', function (): void {
    $this->actingAs(User::factory()->coach()->create())
        ->get('/admin')
        ->assertOk();
});

it('lets a coach edit a program', function (): void {
    $program = Program::factory()->create();

    $this->actingAs(User::factory()->coach()->create())
        ->get('/admin/programs/'.$program->id.'/edit')
        ->assertOk();
});

it('keeps a coach out of the member list and the club settings', function (): void {
    $coach = User::factory()->coach()->create();
    $trainee = User::factory()->trainee()->create();

    $this->actingAs($coach)->get('/admin/trainees')->assertForbidden();
    $this->actingAs($coach)->get('/admin/trainees/'.$trainee->id)->assertForbidden();
    $this->actingAs($coach)->get('/admin/settings')->assertForbidden();
});

it('shuts a suspended admin out even though the role is still there', function (): void {
    $this->actingAs(User::factory()->admin()->inactive()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('hides the sections a coach cannot reach from the panel navigation', function (): void {
    $coach = User::factory()->coach()->create();

    $this->actingAs($coach)
        ->get('/admin')
        ->assertOk()
        ->assertDontSee(route('admin.settings.edit'), escape: false)
        ->assertDontSee(route('admin.trainees.index'), escape: false)
        ->assertSee(route('admin.programs.index'), escape: false);
});

it('signs a member out on a POST and returns them to the door', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/logout')
        ->assertRedirect(route('admin.login'));

    $this->assertGuest();
});
