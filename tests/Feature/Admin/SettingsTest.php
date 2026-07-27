<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('saves the club identity and clears the cached copy', function (): void {
    Setting::factory()->create(['club_name_ar' => 'قسورة الأزرق']);

    // Warm the cache so the invalidation is actually being tested.
    expect(Setting::current()->club_name_ar)->toBe('قسورة الأزرق');

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.settings')
        ->set('form.club_name_ar', 'نادي عرين')
        ->set('form.phone', '+966500000000')
        ->call('save')
        ->assertHasNoErrors();

    Setting::forget();

    expect(Setting::current()->club_name_ar)->toBe('نادي عرين')
        ->and(Setting::current()->phone)->toBe('+966500000000');
});

it('forgets the cached settings on its own when a row is saved', function (): void {
    $setting = Setting::factory()->create(['club_name_ar' => 'قسورة الأزرق']);

    expect(Setting::current()->club_name_ar)->toBe('قسورة الأزرق');

    // No Setting::forget() here: App\Models\Setting::booted() already hooks
    // `saved`, which is why the settings screen does not call it a second time.
    $setting->forceFill(['club_name_ar' => 'نادي عرين'])->save();

    expect(Setting::current()->club_name_ar)->toBe('نادي عرين');
});

it('demands an Arabic club name — the fallback locale depends on it', function (): void {
    Setting::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.settings')
        ->set('form.club_name_ar', '')
        ->call('save')
        ->assertHasErrors('form.club_name_ar');
});

it('refuses a map link that is not a link', function (): void {
    Setting::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.settings')
        ->set('form.map_url', 'not a url')
        ->call('save')
        ->assertHasErrors('form.map_url');
});

it('stores the club logo on the local public disk', function (): void {
    Storage::fake('public');
    Setting::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.settings')
        ->set('logo', UploadedFile::fake()->image('logo.png', 400, 400))
        ->call('save')
        ->assertHasNoErrors();

    $path = Setting::query()->first()->logo_path;

    expect($path)->toStartWith('club/');

    Storage::disk('public')->assertExists($path);
});

it('offers no colour field: the palette is a design token, not club data', function (): void {
    Setting::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/settings')
        ->assertOk()
        ->assertDontSee('type="color"', escape: false);
});

it('keeps a coach out of the club settings', function (): void {
    Setting::factory()->create();

    $this->actingAs(User::factory()->coach()->create());

    Livewire::test('pages::admin.settings')->assertForbidden();
});

it('renders on a fresh install that has no settings row yet', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/settings')
        ->assertOk();
});
