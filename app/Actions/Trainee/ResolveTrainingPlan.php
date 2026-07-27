<?php

declare(strict_types=1);

namespace App\Actions\Trainee;

use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Everything the trainee dashboard needs to answer one question: what do I do
 * next, and am I keeping it up.
 *
 * The "next day" is derived from history rather than stored, because a stored
 * cursor drifts the moment the trainee trains out of order, skips a week, or
 * logs a session from a printed sheet three days late. History cannot drift:
 * whatever day the last round belonged to, the next one follows it, wrapping
 * round at the end of the program. A session already begun today stays the
 * current day, so opening the app mid-workout resumes rather than skips ahead.
 */
class ResolveTrainingPlan
{
    /** How many past sessions the dashboard lists. */
    private const RECENT_SESSIONS = 5;

    /**
     * @return array{
     *     program: Program|null,
     *     days: Collection<int, ProgramDay>,
     *     day: ProgramDay|null,
     *     resuming: bool,
     *     last_session_on: Carbon|null,
     *     streak: int,
     *     sets_this_week: int,
     *     volume_this_week: float,
     *     recent: list<array{date: Carbon, sets: int, exercises: int, volume: float}>,
     * }
     */
    public function handle(User $user): array
    {
        $program = $user->activeProgram();

        /** @var Collection<int, ProgramDay> $days */
        $days = $program instanceof Program
            ? $program->days()->with('focusMuscle')->get()
            : new Collection;

        $lastRound = $program instanceof Program ? $this->lastRound($user, $program) : null;
        $lastDay = $lastRound?->programExercise?->programDay;

        $today = Carbon::today();
        $resuming = $lastRound !== null && $lastRound->performed_on?->isSameDay($today) === true;

        return [
            'program' => $program,
            'days' => $days,
            'day' => $this->nextDay($days, $lastDay, $resuming),
            'resuming' => $resuming,
            'last_session_on' => $lastRound?->performed_on,
            'streak' => $this->streak($user, $today),
            'sets_this_week' => $this->setsThisWeek($user, $today),
            'volume_this_week' => $this->volumeThisWeek($user, $today),
            'recent' => $this->recentSessions($user),
        ];
    }

    /**
     * The most recent round the trainee logged against the program they are on.
     * Restricted to that program on purpose: a round from an older plan must not
     * decide which day of the current one comes next.
     */
    private function lastRound(User $user, Program $program): ?WorkoutLog
    {
        return WorkoutLog::query()
            ->with('programExercise.programDay')
            ->forUser($user)
            ->whereHas('programExercise.programDay', static fn ($query) => $query->where('program_id', $program->getKey()))
            ->orderByDesc('performed_on')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  Collection<int, ProgramDay>  $days
     */
    private function nextDay(Collection $days, ?ProgramDay $lastDay, bool $resuming): ?ProgramDay
    {
        if ($days->isEmpty()) {
            return null;
        }

        if (! $lastDay instanceof ProgramDay) {
            return $days->first();
        }

        if ($resuming) {
            return $days->firstWhere('day_number', $lastDay->day_number) ?? $days->first();
        }

        return $days->firstWhere('day_number', '>', $lastDay->day_number) ?? $days->first();
    }

    /**
     * Consecutive days ending today or yesterday.
     *
     * Yesterday counts as still alive because a streak that dies at midnight
     * punishes somebody who trains in the evening and opens the app the next
     * morning — the number would read zero while they are, in fact, on a run.
     */
    private function streak(User $user, Carbon $today): int
    {
        /** @var list<string> $dates */
        $dates = WorkoutLog::query()
            ->forUser($user)
            ->where('is_completed', true)
            ->orderByDesc('performed_on')
            ->limit(400)
            ->pluck('performed_on')
            ->map(static fn ($date): string => Carbon::parse((string) $date)->toDateString())
            ->unique()
            ->values()
            ->all();

        if ($dates === []) {
            return 0;
        }

        $cursor = $dates[0] === $today->toDateString()
            ? $today->copy()
            : ($dates[0] === $today->copy()->subDay()->toDateString() ? $today->copy()->subDay() : null);

        if ($cursor === null) {
            return 0;
        }

        $streak = 0;

        foreach ($dates as $date) {
            if ($date !== $cursor->toDateString()) {
                break;
            }

            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    private function setsThisWeek(User $user, Carbon $today): int
    {
        return WorkoutLog::query()
            ->forUser($user)
            ->where('is_completed', true)
            ->whereBetween('performed_on', [$today->copy()->subDays(6)->toDateString(), $today->toDateString()])
            ->count();
    }

    /**
     * Load moved: reps times weight, summed. `weight` is a `decimal:2` cast and
     * therefore a *string* in PHP (CLAUDE.md §4), so the multiplication happens
     * in the database and the total is cast once on the way out.
     */
    private function volumeThisWeek(User $user, Carbon $today): float
    {
        $total = WorkoutLog::query()
            ->forUser($user)
            ->where('is_completed', true)
            ->whereNotNull('weight')
            ->whereNotNull('reps_done')
            ->whereBetween('performed_on', [$today->copy()->subDays(6)->toDateString(), $today->toDateString()])
            ->sum(DB::raw('reps_done * weight'));

        return round((float) $total, 1);
    }

    /**
     * @return list<array{date: Carbon, sets: int, exercises: int, volume: float}>
     */
    private function recentSessions(User $user): array
    {
        return WorkoutLog::query()
            ->forUser($user)
            ->where('is_completed', true)
            ->groupBy('performed_on')
            ->orderByDesc('performed_on')
            ->limit(self::RECENT_SESSIONS)
            ->get([
                'performed_on',
                DB::raw('COUNT(*) as sets_count'),
                DB::raw('COUNT(DISTINCT program_exercise_id) as exercises_count'),
                DB::raw('COALESCE(SUM(reps_done * weight), 0) as volume'),
            ])
            ->map(static fn (WorkoutLog $row): array => [
                'date' => Carbon::parse((string) $row->getRawOriginal('performed_on')),
                'sets' => (int) $row->getAttribute('sets_count'),
                'exercises' => (int) $row->getAttribute('exercises_count'),
                'volume' => round((float) $row->getAttribute('volume'), 1),
            ])
            ->all();
    }
}
