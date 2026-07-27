<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainee;

use App\Models\WorkoutLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * The offline queue arriving all at once.
 *
 * The batch is capped rather than unbounded: a phone that spent a fortnight out
 * of signal still holds a few hundred rounds at most, and a cap keeps one hostile
 * request from asking the server to authorise ten thousand rows.
 *
 * Nothing here decides *whether* a round may be written — that is the policy's
 * job, per row, inside the action. This only decides whether the payload is
 * shaped like a set of rounds at all.
 */
class SyncWorkoutLogsRequest extends FormRequest
{
    /** The most rounds one drain may carry. */
    public const MAX_BATCH = 200;

    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkoutLog::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /*
         * The clock on the phone is the trainee's, not ours, and a gym in another
         * timezone can legitimately be a day ahead of the server. One day of slack
         * forward, a year back — anything outside that is not a mistyped session,
         * it is junk.
         */
        $earliest = Carbon::today()->subYear()->toDateString();
        $latest = Carbon::today()->addDay()->toDateString();

        return [
            'logs' => ['required', 'array', 'min:1', 'max:'.self::MAX_BATCH],

            // Minted in the browser before the round is ever sent, and the only
            // thing standing between a retried batch and a duplicated set.
            'logs.*.client_uuid' => ['required', 'uuid', 'distinct:ignore_case'],

            'logs.*.program_exercise_id' => ['required', 'integer', 'exists:program_exercises,id'],
            'logs.*.performed_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$earliest, 'before_or_equal:'.$latest],
            'logs.*.set_number' => ['required', 'integer', 'min:1', 'max:60'],

            // A skipped round is a real record: it says the trainee was there and
            // did not finish. Both figures are therefore optional.
            'logs.*.reps_done' => ['nullable', 'integer', 'min:0', 'max:999'],
            'logs.*.weight' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],

            'logs.*.is_completed' => ['nullable', 'boolean'],
            'logs.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The rounds, normalised into exactly the shape the action expects.
     *
     * @return list<array<string, mixed>>
     */
    public function rounds(): array
    {
        /** @var array<int, array<string, mixed>> $logs */
        $logs = $this->validated('logs');

        return array_values(array_map(static fn (array $log): array => [
            'client_uuid' => strtolower((string) $log['client_uuid']),
            'program_exercise_id' => (int) $log['program_exercise_id'],
            'performed_on' => (string) $log['performed_on'],
            'set_number' => (int) $log['set_number'],
            'reps_done' => isset($log['reps_done']) ? (int) $log['reps_done'] : null,
            'weight' => isset($log['weight']) ? (float) $log['weight'] : null,
            'is_completed' => (bool) ($log['is_completed'] ?? true),
            'note' => isset($log['note']) && $log['note'] !== '' ? (string) $log['note'] : null,
        ], $logs));
    }
}
