<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Trainee\UpsertWorkoutLogs;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainee\SyncWorkoutLogsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * The one door the offline queue knocks on.
 *
 * It answers with the two lists the phone needs to empty its queue correctly:
 * `accepted`, the rounds that are now on the server, and `rejected`, the rounds
 * that never will be. Anything in neither list is still the phone's problem and
 * stays queued — which is what makes a half-delivered batch safe.
 *
 * Returning the uuids rather than a bare "ok" is the point. A count would leave
 * the client guessing which rounds to clear after a partial acceptance, and a
 * client that guesses either loses a set or sends it forever.
 */
class WorkoutLogSyncController extends Controller
{
    public function __invoke(SyncWorkoutLogsRequest $request, UpsertWorkoutLogs $upsert): JsonResponse
    {
        $result = $upsert->handle($request->user(), $request->rounds());

        return response()->json($result, Response::HTTP_OK);
    }
}
