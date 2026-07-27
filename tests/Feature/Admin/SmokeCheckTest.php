<?php

declare(strict_types=1);

use App\Models\User;

it('smoke: guest redirected, admin ok, login ok', function (): void {
    $this->get('/admin')->assertRedirect(route('admin.login'));
    $this->get('/admin/login')->assertOk()->assertSee('id="login-email"', escape: false);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/admin')->assertOk();

    $trainee = User::factory()->trainee()->create();
    $this->actingAs($trainee)->get('/admin')->assertForbidden();
});
