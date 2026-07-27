<?php

declare(strict_types=1);

namespace App\Actions\Trainee;

use App\Models\ProgramExercise;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Land a batch of offline rounds, exactly once each.
 *
 * The whole design rests on one column: `workout_logs.client_uuid`. The browser
 * mints it before the round leaves the phone, so the identity of a round is
 * decided by the device that performed it and not by the database that stores
 * it. That turns "send my queue" from an insert — which duplicates on every
 * retry — into an upsert, which is safe to repeat forever. A phone that sends,
 * loses signal before the reply arrives, and sends again writes one row.
 *
 * Two refusals, and they are different questions:
 *
 *  · `owned_by_another` — the uuid already names a row on somebody else's
 *    account. Guessing or replaying another trainee's uuid must never let a
 *    stranger rewrite their history, so the row is left untouched and the round
 *    is refused. This is checked before anything is written.
 *  · `forbidden` — the account has no program attached that contains this
 *    prescribed exercise, so it has no business logging against it.
 *
 * Refusals are reported per round rather than failing the batch: one bad round
 * out of forty must not strand the other thirty-nine on the phone forever.
 */
class UpsertWorkoutLogs
{
    public const REASON_FORBIDDEN = 'forbidden';

    public const REASON_OWNED_BY_ANOTHER = 'owned_by_another';

    public const REASON_INVALID = 'invalid';

    /**
     * @param  list<array<string, mixed>>  $rounds
     * @return array{accepted: list<string>, rejected: list<array{client_uuid: string, reason: string}>, synced_at: string}
     */
    public function handle(User $user, array $rounds): array
    {
        $syncedAt = now();

        $exercises = $this->prescribedExercises($rounds);
        $existing = $this->existingRows($rounds);

        /** @var array<int, bool> $permitted */
        $permitted = [];

        $accepted = [];
        $rejected = [];

        DB::transaction(function () use ($user, $rounds, $exercises, $existing, &$permitted, &$accepted, &$rejected, $syncedAt): void {
            foreach ($rounds as $round) {
                $uuid = (string) $round['client_uuid'];
                $exerciseId = (int) $round['program_exercise_id'];

                $programExercise = $exercises[$exerciseId] ?? null;

                if (! $programExercise instanceof ProgramExercise) {
                    $rejected[] = ['client_uuid' => $uuid, 'reason' => self::REASON_INVALID];

                    continue;
                }

                /*
                 * Ownership first. A round whose uuid already belongs to another
                 * account is refused before the program check even runs, so the
                 * reply cannot be used to probe which exercises somebody else's
                 * account is attached to.
                 */
                $row = $existing[$uuid] ?? null;

                if ($row instanceof WorkoutLog && ! $user->can('update', $row)) {
                    $rejected[] = ['client_uuid' => $uuid, 'reason' => self::REASON_OWNED_BY_ANOTHER];

                    continue;
                }

                $permitted[$exerciseId] ??= $user->can('logFor', [WorkoutLog::class, $programExercise]);

                if (! $permitted[$exerciseId]) {
                    $rejected[] = ['client_uuid' => $uuid, 'reason' => self::REASON_FORBIDDEN];

                    continue;
                }

                $this->write($user, $round, $syncedAt->toDateTimeString());

                $accepted[] = $uuid;
            }
        });

        return [
            'accepted' => $accepted,
            'rejected' => $rejected,
            'synced_at' => $syncedAt->toIso8601String(),
        ];
    }

    /**
     * The upsert itself. `client_uuid` is unique, so a row either exists and is
     * corrected — the trainee retyped the weight — or it is created.
     *
     * The race worth naming: two drains firing at once (the `online` event and a
     * background sync waking together) can both find no row and both insert. The
     * database settles it with the unique index, and the loser turns its insert
     * into an update rather than surfacing a 500 for a round that did land.
     *
     * @param  array<string, mixed>  $round
     */
    private function write(User $user, array $round, string $syncedAt): void
    {
        $attributes = [
            'user_id' => $user->getKey(),
            'program_exercise_id' => (int) $round['program_exercise_id'],
            'performed_on' => (string) $round['performed_on'],
            'set_number' => (int) $round['set_number'],
            'reps_done' => $round['reps_done'],
            'weight' => $round['weight'],
            'is_completed' => (bool) $round['is_completed'],
            'note' => $round['note'],
            'synced_at' => $syncedAt,
        ];

        try {
            WorkoutLog::query()->updateOrCreate(
                ['client_uuid' => (string) $round['client_uuid']],
                $attributes,
            );
        } catch (UniqueConstraintViolationException|QueryException $exception) {
            $row = WorkoutLog::query()
                ->where('client_uuid', (string) $round['client_uuid'])
                ->first();

            if (! $row instanceof WorkoutLog || $row->user_id !== $user->getKey()) {
                throw $exception;
            }

            $row->forceFill($attributes)->save();
        }
    }

    /**
     * Every prescribed exercise the batch mentions, with the day it belongs to,
     * in one query — the policy needs the day to reach the program.
     *
     * @param  list<array<string, mixed>>  $rounds
     * @return array<int, ProgramExercise>
     */
    private function prescribedExercises(array $rounds): array
    {
        $ids = array_values(array_unique(array_map(
            static fn (array $round): int => (int) $round['program_exercise_id'],
            $rounds,
        )));

        return ProgramExercise::query()
            ->with('programDay:id,program_id')
            ->whereKey($ids)
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * Rows the batch would touch, looked up once instead of per round.
     *
     * @param  list<array<string, mixed>>  $rounds
     * @return array<string, WorkoutLog>
     */
    private function existingRows(array $rounds): array
    {
        $uuids = array_values(array_unique(array_map(
            static fn (array $round): string => (string) $round['client_uuid'],
            $rounds,
        )));

        return WorkoutLog::query()
            ->whereIn('client_uuid', $uuids)
            ->get()
            ->keyBy('client_uuid')
            ->all();
    }
}
