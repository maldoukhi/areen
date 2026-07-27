<?php

declare(strict_types=1);

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainee\StoreBodyMetricRequest;
use App\Models\BodyMetric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Weigh-ins, recorded from a plain form post rather than through Livewire.
 *
 * Two reasons. The schema makes `(user_id, measured_on)` unique, so recording the
 * same day twice is a correction, not an error — and a form post makes that a
 * single obvious write. And a form that works without JavaScript is one less
 * thing to be sorry about on a phone that has just lost its connection halfway
 * through the page.
 */
class BodyMetricController extends Controller
{
    public function store(StoreBodyMetricRequest $request): RedirectResponse
    {
        $user = $request->user();
        $measuredOn = (string) $request->validated('measured_on');

        /*
         * `whereDate`, not a plain equality: Eloquent writes a `date` cast through
         * the connection's datetime format, so SQLite stores `2026-07-27 00:00:00`
         * against a DATE column while MySQL stores `2026-07-27`. Matching on the
         * bare string would find nothing on one of the two and quietly insert a
         * duplicate — which the unique index would then reject as a 500.
         */
        $metric = BodyMetric::query()
            ->where('user_id', $user->getKey())
            ->whereDate('measured_on', $measuredOn)
            ->first();

        // A row that already exists is somebody's — check whose before touching it.
        if ($metric instanceof BodyMetric) {
            Gate::forUser($user)->authorize('update', $metric);
        } else {
            Gate::forUser($user)->authorize('create', BodyMetric::class);

            $metric = new BodyMetric([
                'user_id' => $user->getKey(),
                'measured_on' => $measuredOn,
            ]);
        }

        $metric->fill([
            'weight' => $request->validated('weight'),
            'body_fat' => $request->validated('body_fat'),
            'notes' => $request->validated('notes'),
        ])->save();

        return back()->with('status', __('trainee.metrics.saved'));
    }
}
