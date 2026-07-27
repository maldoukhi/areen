<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Program;
use Illuminate\Http\Request;

/**
 * Decides whether a visitor may read a program.
 *
 * A published program is open to anyone. An unpublished one has exactly one
 * door — its access code — and opening that door grants the session the right
 * to read the rest of the program, so the code does not have to be repeated in
 * every day link the trainee taps afterwards.
 */
final class ProgramAccess
{
    private const string SESSION_KEY = 'areen.unlocked_programs';

    /**
     * Record that this session came through the access code.
     */
    public static function grant(Request $request, Program $program): void
    {
        $unlocked = $request->session()->get(self::SESSION_KEY, []);

        if (! in_array($program->id, $unlocked, true)) {
            $unlocked[] = $program->id;
            $request->session()->put(self::SESSION_KEY, $unlocked);
        }
    }

    public static function allows(Request $request, Program $program): bool
    {
        if ($program->isPublished()) {
            return true;
        }

        return in_array(
            $program->id,
            $request->session()->get(self::SESSION_KEY, []),
            true,
        );
    }
}
