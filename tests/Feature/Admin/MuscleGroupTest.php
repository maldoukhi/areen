<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Livewire\Livewire;

it('adds a muscle group with its place in the order', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.muscle-groups')
        ->set('name_ar', 'صدر')
        ->set('name_en', 'Chest')
        ->set('sort', '2')
        ->call('save')
        ->assertHasNoErrors();

    $group = MuscleGroup::query()->where('slug', 'chest')->firstOrFail();

    expect($group->name_ar)->toBe('صدر')->and($group->sort)->toBe(2);
});

it('persists the new order when groups are dragged', function (): void {
    $groups = MuscleGroup::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.muscle-groups')
        ->call('reorder', (string) $groups[2]->getKey(), 0);

    expect(MuscleGroup::query()->ordered()->pluck('id')->all())
        ->toBe([$groups[2]->getKey(), $groups[0]->getKey(), $groups[1]->getKey()]);
});

it('refuses to delete a group that is still classifying exercises', function (): void {
    $group = MuscleGroup::factory()->create();
    Exercise::factory()->create(['muscle_group_id' => $group->getKey()]);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.muscle-groups')
        ->call('delete', $group->getKey());

    expect(MuscleGroup::query()->whereKey($group->getKey())->exists())->toBeTrue();
});

it('deletes a group nothing points at', function (): void {
    $group = MuscleGroup::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.muscle-groups')
        ->call('delete', $group->getKey());

    expect(MuscleGroup::query()->whereKey($group->getKey())->exists())->toBeFalse();
});

it('lets a coach manage the muscle library', function (): void {
    $this->actingAs(User::factory()->coach()->create());

    Livewire::test('pages::admin.muscle-groups')
        ->set('name_ar', 'ظهر')
        ->set('name_en', 'Back')
        ->call('save')
        ->assertHasNoErrors();

    expect(MuscleGroup::query()->where('slug', 'back')->exists())->toBeTrue();
});
