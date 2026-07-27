<?php

declare(strict_types=1);

namespace App\Actions\Trainee;

use App\Models\BodyMetric;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The numbers behind the progress charts.
 *
 * Two rules shape it. First, the series are grouped by *exercise*, not by the
 * prescribed row: the same bench press appears on day 1 of one program and day 3
 * of the next, and a trainee wants one line for the lift, not three. Second,
 * every figure leaves here as a float. `weight` is a `decimal:2` cast and comes
 * back from Eloquent as a string (CLAUDE.md §4) — multiplying those in PHP is
 * how a chart quietly starts plotting concatenated text.
 */
class BuildProgressSeries
{
    /** Sessions drawn per chart. Beyond this the line is noise on a 360px screen. */
    private const MAX_POINTS = 24;

    /**
     * @return array{
     *     exercises: list<array{id: int, name: string}>,
     *     exercise: array{id: int, name: string}|null,
     *     sessions: list<array{date: Carbon, top_weight: float|null, volume: float, sets: int}>,
     *     best_weight: float|null,
     *     best_volume: float,
     *     body: list<array{date: Carbon, weight: float|null, body_fat: float|null}>,
     * }
     */
    public function handle(User $user, ?int $exerciseId = null): array
    {
        $exercises = $this->loggedExercises($user);

        $selected = null;

        foreach ($exercises as $exercise) {
            if ($exerciseId === null || $exercise['id'] === $exerciseId) {
                $selected = $exercise;

                break;
            }
        }

        $sessions = $selected === null ? [] : $this->sessions($user, $selected['id']);

        $weights = array_values(array_filter(
            array_map(static fn (array $row): ?float => $row['top_weight'], $sessions),
            static fn (?float $value): bool => $value !== null,
        ));

        return [
            'exercises' => $exercises,
            'exercise' => $selected,
            'sessions' => $sessions,
            'best_weight' => $weights === [] ? null : max($weights),
            'best_volume' => $sessions === [] ? 0.0 : max(array_map(static fn (array $row): float => $row['volume'], $sessions)),
            'body' => $this->bodyMetrics($user),
        ];
    }

    /**
     * Every exercise this account has ever logged a round against, most recently
     * trained first — which is almost always the one they came here to look at.
     *
     * @return list<array{id: int, name: string}>
     */
    private function loggedExercises(User $user): array
    {
        return Exercise::query()
            ->join('program_exercises', 'program_exercises.exercise_id', '=', 'exercises.id')
            ->join('workout_logs', 'workout_logs.program_exercise_id', '=', 'program_exercises.id')
            ->where('workout_logs.user_id', $user->getKey())
            ->groupBy('exercises.id', 'exercises.name_ar', 'exercises.name_en')
            ->orderByRaw('MAX(workout_logs.performed_on) desc')
            ->get(['exercises.id', 'exercises.name_ar', 'exercises.name_en'])
            ->map(static fn (Exercise $exercise): array => [
                'id' => (int) $exercise->getKey(),
                'name' => (string) $exercise->name,
            ])
            ->all();
    }

    /**
     * One point per session: the heaviest load that day and the load moved.
     *
     * @return list<array{date: Carbon, top_weight: float|null, volume: float, sets: int}>
     */
    private function sessions(User $user, int $exerciseId): array
    {
        $rows = DB::table('workout_logs')
            ->join('program_exercises', 'program_exercises.id', '=', 'workout_logs.program_exercise_id')
            ->where('workout_logs.user_id', $user->getKey())
            ->where('program_exercises.exercise_id', $exerciseId)
            ->where('workout_logs.is_completed', true)
            ->groupBy('workout_logs.performed_on')
            ->orderByDesc('workout_logs.performed_on')
            ->limit(self::MAX_POINTS)
            ->get([
                'workout_logs.performed_on as performed_on',
                DB::raw('MAX(workout_logs.weight) as top_weight'),
                DB::raw('COALESCE(SUM(workout_logs.reps_done * workout_logs.weight), 0) as volume'),
                DB::raw('COUNT(*) as sets_count'),
            ]);

        return $rows
            ->reverse()
            ->values()
            ->map(static fn (object $row): array => [
                'date' => Carbon::parse((string) $row->performed_on),
                'top_weight' => $row->top_weight === null ? null : round((float) $row->top_weight, 2),
                'volume' => round((float) $row->volume, 1),
                'sets' => (int) $row->sets_count,
            ])
            ->all();
    }

    /**
     * @return list<array{date: Carbon, weight: float|null, body_fat: float|null}>
     */
    private function bodyMetrics(User $user): array
    {
        return $user->bodyMetrics()
            ->orderByDesc('measured_on')
            ->limit(self::MAX_POINTS)
            ->get()
            ->reverse()
            ->values()
            ->map(static fn (BodyMetric $metric): array => [
                'date' => Carbon::parse((string) $metric->getRawOriginal('measured_on')),
                'weight' => $metric->weight === null ? null : (float) $metric->weight,
                'body_fat' => $metric->body_fat === null ? null : (float) $metric->body_fat,
            ])
            ->all();
    }
}
