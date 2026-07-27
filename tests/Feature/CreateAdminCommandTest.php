<?php

declare(strict_types=1);

use App\Models\User;

it('creates an admin that can reach the panel', function (): void {
    $this->artisan('areen:create-admin', [
        '--name' => 'مدير النادي',
        '--email' => 'owner@example.test',
        '--password' => 'a-long-enough-password',
    ])->assertSuccessful();

    $admin = User::where('email', 'owner@example.test')->firstOrFail();

    expect($admin->role->value)->toBe('admin')
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();

    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('signs in with the password it was given', function (): void {
    $this->artisan('areen:create-admin', [
        '--name' => 'مدير',
        '--email' => 'owner2@example.test',
        '--password' => 'a-long-enough-password',
    ])->assertSuccessful();

    expect(auth()->attempt([
        'email' => 'owner2@example.test',
        'password' => 'a-long-enough-password',
    ]))->toBeTrue();
});

it('refuses an address that already exists', function (): void {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->artisan('areen:create-admin', [
        '--name' => 'مدير',
        '--email' => 'taken@example.test',
        '--password' => 'a-long-enough-password',
    ])->assertFailed();
});

it('refuses a short password', function (): void {
    $this->artisan('areen:create-admin', [
        '--name' => 'مدير',
        '--email' => 'short@example.test',
        '--password' => 'abc',
    ])->assertFailed();

    expect(User::where('email', 'short@example.test')->exists())->toBeFalse();
});

it('refuses to create a trainee, who belongs in the panel', function (): void {
    $this->artisan('areen:create-admin', [
        '--name' => 'متدرب',
        '--email' => 'trainee@example.test',
        '--password' => 'a-long-enough-password',
        '--role' => 'trainee',
    ])->assertFailed();
});

it('can create a coach', function (): void {
    $this->artisan('areen:create-admin', [
        '--name' => 'مدرب',
        '--email' => 'coach2@example.test',
        '--password' => 'a-long-enough-password',
        '--role' => 'coach',
    ])->assertSuccessful();

    expect(User::where('email', 'coach2@example.test')->first()->role->value)->toBe('coach');
});
