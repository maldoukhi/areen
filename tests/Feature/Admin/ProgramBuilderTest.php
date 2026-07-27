<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * @return array{0: ProgramDay, 1: list<ProgramExercise>}
 */
function seedDayWithRows(int $count = 3): array
{
    $program = Program::factory()->create();
    $day = ProgramDay::factory()->forProgram($program)->dayNumber(1)->create();
    $muscle = MuscleGroup::factory()->create();

    $rows = [];

    foreach (range(0, $count - 1) as $index) {
        $rows[] = ProgramExercise::factory()->create([
            'program_day_id' => $day->getKey(),
            'exercise_id' => Exercise::factory()->create(['muscle_group_id' => $muscle->getKey()])->getKey(),
            'sort' => $index,
        ]);
    }

    return [$day, $rows];
}

it('persists a new program and gives it the days it says it has', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.programs.form')
        ->set('name_ar', 'برنامج المبتدئ')
        ->set('name_en', 'Beginner Block')
        ->set('days_count', '3')
        ->set('level', 'beginner')
        ->set('goal', 'general-fitness')
        ->call('save')
        ->assertHasNoErrors();

    $program = Program::query()->where('name_ar', 'برنامج المبتدئ')->firstOrFail();

    expect($program->slug)->toBe('beginner-block')
        ->and($program->days_count)->toBe(3)
        ->and($program->days()->count())->toBe(3);
});

it('refuses a program with no Arabic name', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.programs.form')
        ->set('name_ar', '')
        ->call('save')
        ->assertHasErrors('name_ar');

    expect(Program::query()->count())->toBe(0);
});

it('lets a coach save a program', function (): void {
    $program = Program::factory()->create();

    $this->actingAs(User::factory()->coach()->create());

    Livewire::test('pages::admin.programs.form', ['program' => $program])
        ->set('name_ar', 'برنامج المدرّب')
        ->call('save')
        ->assertHasNoErrors();

    expect($program->fresh()->name_ar)->toBe('برنامج المدرّب');
});

it('persists the new order when a day\'s exercises are dragged', function (): void {
    [$day, $rows] = seedDayWithRows();

    $this->actingAs(User::factory()->admin()->create());

    // wire:sort hands the handler the row that moved and the index it landed on.
    Livewire::test('pages::admin.programs.day', ['program' => $day->program, 'day' => $day])
        ->call('reorder', (string) $rows[2]->getKey(), 0);

    expect($day->exercises()->orderBy('sort')->pluck('id')->all())
        ->toBe([$rows[2]->getKey(), $rows[0]->getKey(), $rows[1]->getKey()]);

    // And again, this time into the middle.
    Livewire::test('pages::admin.programs.day', ['program' => $day->program, 'day' => $day])
        ->call('reorder', (string) $rows[0]->getKey(), 2);

    expect($day->exercises()->orderBy('sort')->pluck('id')->all())
        ->toBe([$rows[2]->getKey(), $rows[1]->getKey(), $rows[0]->getKey()])
        ->and($day->exercises()->orderBy('sort')->pluck('sort')->all())
        ->toBe([0, 1, 2]);
});

it('writes the whole new order in a single statement, not one per row', function (): void {
    [$day, $rows] = seedDayWithRows(6);

    $this->actingAs(User::factory()->admin()->create());

    $updates = 0;

    DB::listen(function ($query) use (&$updates): void {
        if (str_starts_with(mb_strtolower(trim($query->sql)), 'update "program_exercises"')) {
            $updates++;
        }
    });

    Livewire::test('pages::admin.programs.day', ['program' => $day->program, 'day' => $day])
        ->call('reorder', (string) $rows[5]->getKey(), 0);

    expect($updates)->toBe(1)
        ->and($day->exercises()->orderBy('sort')->pluck('id')->first())->toBe($rows[5]->getKey());
});

it('ignores a dropped row that does not belong to the day', function (): void {
    [$day, $rows] = seedDayWithRows();
    [$otherDay, $otherRows] = seedDayWithRows(1);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.programs.day', ['program' => $day->program, 'day' => $day])
        ->call('reorder', (string) $otherRows[0]->getKey(), 0);

    expect($day->exercises()->orderBy('sort')->pluck('id')->all())
        ->toBe([$rows[0]->getKey(), $rows[1]->getKey(), $rows[2]->getKey()])
        ->and($otherRows[0]->fresh()->program_day_id)->toBe($otherDay->getKey());
});

it('adds an exercise from the shared library to the end of the day', function (): void {
    [$day, $rows] = seedDayWithRows(2);
    $extra = Exercise::factory()->create(['muscle_group_id' => MuscleGroup::factory()->create()->getKey()]);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.programs.day', ['program' => $day->program, 'day' => $day])
        ->call('addExercise', $extra->getKey());

    // The relation already orders by `sort`, so the last row is the newest one.
    expect($day->exercises()->count())->toBe(3)
        ->and($day->exercises()->get()->last()->exercise_id)->toBe($extra->getKey());
});

it('saves a row\'s sets, rest and superset grouping', function (): void {
    [$day, $rows] = seedDayWithRows(1);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.programs.day', ['program' => $day->program, 'day' => $day])
        ->call('editRow', $rows[0]->getKey())
        ->set('row.sets', '4')
        ->set('row.reps', '8-12')
        ->set('row.rest_seconds', '120')
        ->set('row.superset_group', 'A')
        ->call('saveRow')
        ->assertHasNoErrors();

    $row = $rows[0]->fresh();

    expect($row->sets)->toBe(4)
        ->and($row->reps)->toBe('8-12')
        ->and($row->rest_seconds)->toBe(120)
        ->and($row->superset_group)->toBe('A');
});

it('rejects a superset label that is not one of the offered groups', function (): void {
    [$day, $rows] = seedDayWithRows(1);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.programs.day', ['program' => $day->program, 'day' => $day])
        ->call('editRow', $rows[0]->getKey())
        ->set('row.superset_group', 'Z')
        ->call('saveRow')
        ->assertHasErrors('row.superset_group');
});

it('marks a day as rest and keeps its notes', function (): void {
    [$day] = seedDayWithRows(1);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.programs.day', ['program' => $day->program, 'day' => $day])
        ->set('is_rest_day', true)
        ->set('notes_ar', 'خذ راحتك اليوم.')
        ->call('saveDay')
        ->assertHasNoErrors();

    expect($day->fresh()->is_rest_day)->toBeTrue()
        ->and($day->fresh()->notes_ar)->toBe('خذ راحتك اليوم.');
});

it('generates a unique access code and shares it as a real link', function (): void {
    $program = Program::factory()->create(['access_code' => null]);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.programs.form', ['program' => $program])
        ->call('regenerateAccessCode');

    $code = $program->fresh()->access_code;

    expect($code)->not->toBeNull()->and(mb_strlen((string) $code))->toBe(8);

    $this->get('/admin/programs/'.$program->id.'/edit')
        ->assertOk()
        ->assertSee(route('programs.private', ['accessCode' => $code]), escape: false);
});
