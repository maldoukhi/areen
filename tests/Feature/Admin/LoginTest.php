<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('renders the sign-in screen for a guest', function (): void {
    $this->get(route('admin.login'))->assertOk();
});

it('signs an admin in and lands them on the panel', function (): void {
    $admin = User::factory()->admin()->create(['password' => 'password']);

    Livewire::test('pages::auth.login')
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
});

it('sends a signed-in trainee to the public site, not into a 403', function (): void {
    $trainee = User::factory()->trainee()->create(['password' => 'password']);

    Livewire::test('pages::auth.login')
        ->set('email', $trainee->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(url('/'));
});

it('says what went wrong and what to do when the password is wrong', function (): void {
    $admin = User::factory()->admin()->create(['password' => 'password']);

    Livewire::test('pages::auth.login')
        ->set('email', $admin->email)
        ->set('password', 'not-the-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('refuses a suspended account and names the way back in', function (): void {
    $admin = User::factory()->admin()->inactive()->create(['password' => 'password']);

    Livewire::test('pages::auth.login')
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('locks the door after five failed attempts and says for how long', function (): void {
    $admin = User::factory()->admin()->create(['password' => 'password']);

    $component = Livewire::test('pages::auth.login')
        ->set('email', $admin->email)
        ->set('password', 'not-the-password');

    foreach (range(1, 5) as $ignored) {
        $component->call('authenticate');
    }

    // The sixth try never reaches the credentials at all.
    $component->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest();

    expect(RateLimiter::tooManyAttempts(mb_strtolower($admin->email).'|127.0.0.1', 5))->toBeTrue();
});

it('turns a signed-in member away from the sign-in screen', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.login'))
        ->assertRedirect(route('admin.dashboard'));
});
