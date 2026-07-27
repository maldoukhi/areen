<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('rejects a pasted link that is not a YouTube video, and says what to do', function (): void {
    $muscle = MuscleGroup::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.exercises.form')
        ->set('name_ar', 'ضغط البار')
        ->set('muscle_group_id', (string) $muscle->getKey())
        ->set('difficulty', 'beginner')
        ->set('youtube_url', 'https://example.com/not-a-video')
        ->call('save')
        ->assertHasErrors('youtube_url')
        ->assertSee(__('exercise.media.invalid_url'));

    expect(Exercise::query()->count())->toBe(0);
});

it('rejects a YouTube host with no video id on it', function (): void {
    $muscle = MuscleGroup::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.exercises.form')
        ->set('name_ar', 'ضغط البار')
        ->set('muscle_group_id', (string) $muscle->getKey())
        ->set('youtube_url', 'https://www.youtube.com/')
        ->call('save')
        ->assertHasErrors('youtube_url');
});

it('accepts every link shape YouTube actually hands out', function (string $url): void {
    $muscle = MuscleGroup::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.exercises.form')
        ->set('name_ar', 'ضغط البار')
        ->set('name_en', 'Bench Press '.md5($url))
        ->set('muscle_group_id', (string) $muscle->getKey())
        ->set('difficulty', 'beginner')
        ->set('youtube_url', $url)
        ->call('save')
        ->assertHasNoErrors();
})->with([
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://youtu.be/dQw4w9WgXcQ',
    'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'https://www.youtube.com/embed/dQw4w9WgXcQ',
]);

it('stores exercise media on the local public disk, never on S3', function (): void {
    Storage::fake('public');

    $muscle = MuscleGroup::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.exercises.form')
        ->set('name_ar', 'سكوات')
        ->set('name_en', 'Back Squat')
        ->set('muscle_group_id', (string) $muscle->getKey())
        ->set('media', UploadedFile::fake()->image('squat.gif'))
        ->call('save')
        ->assertHasNoErrors();

    $exercise = Exercise::query()->where('name_ar', 'سكوات')->firstOrFail();

    expect($exercise->media_path)->toStartWith('exercises/');

    Storage::disk('public')->assertExists($exercise->media_path);
});

it('refuses a file that is not an image', function (): void {
    Storage::fake('public');

    $muscle = MuscleGroup::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.exercises.form')
        ->set('name_ar', 'سكوات')
        ->set('muscle_group_id', (string) $muscle->getKey())
        ->set('media', UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors('media');
});

it('keeps secondary muscles as slugs, never as Arabic text', function (): void {
    $primary = MuscleGroup::factory()->create();
    $secondary = MuscleGroup::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.exercises.form')
        ->set('name_ar', 'ضغط مائل')
        ->set('name_en', 'Incline Press')
        ->set('muscle_group_id', (string) $primary->getKey())
        ->set('secondary_muscles', [$secondary->slug])
        ->call('save')
        ->assertHasNoErrors();

    expect(Exercise::query()->where('slug', 'incline-press')->firstOrFail()->secondary_muscles)
        ->toBe([$secondary->slug]);
});

it('lets a coach add to the shared library', function (): void {
    $muscle = MuscleGroup::factory()->create();

    $this->actingAs(User::factory()->coach()->create());

    Livewire::test('pages::admin.exercises.form')
        ->set('name_ar', 'سحب أمامي')
        ->set('name_en', 'Lat Pulldown')
        ->set('muscle_group_id', (string) $muscle->getKey())
        ->call('save')
        ->assertHasNoErrors();

    expect(Exercise::query()->where('slug', 'lat-pulldown')->exists())->toBeTrue();
});
