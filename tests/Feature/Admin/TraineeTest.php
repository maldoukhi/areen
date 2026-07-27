<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('opens an account for a trainee — there is no public sign-up', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.trainees.index')
        ->set('name', 'خالد الشمري')
        ->set('email', 'khalid@example.test')
        ->set('phone', '+966500000003')
        ->set('newRole', 'trainee')
        ->set('locale', 'ar')
        ->set('password', 'first-password')
        ->call('invite')
        ->assertHasNoErrors();

    $trainee = User::query()->where('email', 'khalid@example.test')->firstOrFail();

    expect($trainee->role)->toBe(UserRole::Trainee)
        ->and($trainee->is_active)->toBeTrue()
        ->and(Hash::check('first-password', $trainee->password))->toBeTrue();
});

it('refuses a second account on the same address', function (): void {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.trainees.index')
        ->set('name', 'خالد')
        ->set('email', 'taken@example.test')
        ->set('password', 'first-password')
        ->call('invite')
        ->assertHasErrors('email');
});

it('refuses a first password shorter than eight characters', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.trainees.index')
        ->set('name', 'خالد')
        ->set('email', 'short@example.test')
        ->set('password', 'short')
        ->call('invite')
        ->assertHasErrors('password');
});

it('suspends an account instead of deleting the history behind it', function (): void {
    $trainee = User::factory()->trainee()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.trainees.index')
        ->call('toggleActive', $trainee->getKey());

    expect($trainee->fresh()->is_active)->toBeFalse()
        ->and(User::query()->whereKey($trainee->getKey())->exists())->toBeTrue();
});

it('will not let an admin lock themselves out', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.trainees.index')
        ->call('toggleActive', $admin->getKey())
        ->assertForbidden();

    expect($admin->fresh()->is_active)->toBeTrue();
});

it('assigns a program and stands the previous one down', function (): void {
    $trainee = User::factory()->trainee()->create();
    $first = Program::factory()->create();
    $second = Program::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.trainees.show', ['user' => $trainee])
        ->set('program_id', (string) $first->getKey())
        ->set('started_at', now()->toDateString())
        ->call('assignProgram')
        ->assertHasNoErrors();

    Livewire::test('pages::admin.trainees.show', ['user' => $trainee])
        ->set('program_id', (string) $second->getKey())
        ->set('started_at', now()->toDateString())
        ->call('assignProgram')
        ->assertHasNoErrors();

    expect($trainee->fresh()->activeProgram()?->getKey())->toBe($second->getKey())
        ->and($trainee->fresh()->programs()->count())->toBe(2);
});

it('shows the shareable link once a code has been generated', function (): void {
    $trainee = User::factory()->trainee()->create();
    $program = Program::factory()->create(['access_code' => null]);
    $program->users()->attach($trainee, ['started_at' => now()->toDateString(), 'is_active' => true]);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.trainees.show', ['user' => $trainee])
        ->call('regenerateAccessCode', $program->getKey());

    $code = $program->fresh()->access_code;

    expect($code)->not->toBeNull();

    $this->get('/admin/trainees/'.$trainee->id)
        ->assertOk()
        ->assertSee(route('programs.private', ['accessCode' => $code]), escape: false);
});

it('retires the old code when a new one is generated', function (): void {
    $trainee = User::factory()->trainee()->create();
    $program = Program::factory()->restricted()->create();
    $program->users()->attach($trainee, ['started_at' => now()->toDateString(), 'is_active' => true]);

    $original = $program->access_code;

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.trainees.show', ['user' => $trainee])
        ->call('regenerateAccessCode', $program->getKey());

    expect($program->fresh()->access_code)->not->toBe($original);
});

it('leaves the password alone when the box is left empty', function (): void {
    $trainee = User::factory()->trainee()->create(['password' => 'original-password']);
    $before = $trainee->password;

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.trainees.show', ['user' => $trainee])
        ->set('name', 'خالد الشمري')
        ->set('password', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($trainee->fresh()->password)->toBe($before)
        ->and($trainee->fresh()->name)->toBe('خالد الشمري');
});

it('keeps a coach out of the member screens entirely', function (): void {
    $trainee = User::factory()->trainee()->create();

    $this->actingAs(User::factory()->coach()->create());

    Livewire::test('pages::admin.trainees.index')->assertForbidden();
    Livewire::test('pages::admin.trainees.show', ['user' => $trainee])->assertForbidden();
});
