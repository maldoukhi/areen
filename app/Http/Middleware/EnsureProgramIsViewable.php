<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Program;
use App\Support\ProgramAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every route that renders a program's contents.
 *
 * This lives on the route rather than inside a page because the overview and
 * the day view are separate components: gating only the overview left the full
 * schedule of a private program readable at
 * `/programs/{slug}/day/{n}` to anyone who guessed the slug, which defeats the
 * access code entirely. A route-level guard cannot be forgotten by a page added
 * later.
 *
 * A private program is reported as missing rather than forbidden, so the
 * response does not confirm that the slug exists.
 */
class EnsureProgramIsViewable
{
    public function handle(Request $request, Closure $next): Response
    {
        $program = $request->route('program');

        abort_if(
            $program instanceof Program && ! ProgramAccess::allows($request, $program),
            404,
        );

        return $next($request);
    }
}
