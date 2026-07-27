<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Models\User;
use Livewire\Livewire;

it('smoke: program form and day builder render and reorder', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->get('/admin/programs/create')->assertOk();

    $program = Program::factory()->create();
    $this->get('/admin/programs/'.$program->id.'/edit')->assertOk();

    $day = ProgramDay::factory()->for($program)->create(['day_number' => 1]);
    $muscle = MuscleGroup::factory()->create();
    $rows = collect(range(0, 2))->map(fn (int $i) => ProgramExercise::factory()->create([
        'program_day_id' => $day->id,
        'exercise_id' => Exercise::factory()->create(['muscle_group_id' => $muscle->id])->id,
        'sort' => $i,
    ]));

    $this->get('/admin/programs/'.$program->id.'/days/'.$day->id)->assertOk()->assertSee('wire:sort', escape: false);

    Livewire::test('pages::admin.programs.day', ['program' => $program, 'day' => $day])
        ->call('reorder', $rows[2]->id, 0);

    expect($day->exercises()->orderBy('sort')->pluck('id')->all())
        ->toBe([$rows[2]->id, $rows[0]->id, $rows[1]->id]);
});
