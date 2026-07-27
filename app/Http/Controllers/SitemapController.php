<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * The sitemap at `/sitemap.xml`.
 *
 * It lists the public catalogue and nothing else. The rule that matters most
 * here is the one about private programs: a program reachable only through its
 * access code must never appear, and not because its *contents* would leak —
 * the day pages are guarded separately — but because the name of a plan written
 * for one trainee is itself the disclosure. `Program::published()` is therefore
 * the only query used for programs, exactly as the catalogue uses it, so the
 * sitemap cannot drift away from what `/programs` is willing to show.
 *
 * Trashed rows are excluded by SoftDeletes on their own, and inactive exercises
 * by `Exercise::active()`.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ...$this->staticPages(),
            ...$this->programs(),
            ...$this->exercises(),
            ...$this->muscleGroups(),
        ];

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function staticPages(): array
    {
        return [
            $this->url(route('home'), null, 'daily', '1.0'),
            $this->url(route('programs.index'), null, 'weekly', '0.9'),
            $this->url(route('exercises.index'), null, 'weekly', '0.8'),
            $this->url(route('about'), null, 'monthly', '0.4'),
        ];
    }

    /**
     * Every published program, plus each of its days — a day page is where a
     * trainee actually lands from a search, so it is a destination in its own
     * right rather than a fragment of the overview.
     *
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function programs(): array
    {
        $programs = Program::query()
            ->published()
            ->with(['days' => fn ($query) => $query->orderBy('day_number')])
            ->ordered()
            ->get();

        $urls = [];

        foreach ($programs as $program) {
            $urls[] = $this->url(
                route('programs.show', $program),
                $program->updated_at,
                'weekly',
                '0.8',
            );

            foreach ($program->days as $day) {
                /** @var ProgramDay $day */
                $urls[] = $this->url(
                    route('programs.day', ['program' => $program, 'day' => $day->day_number]),
                    $day->updated_at ?? $program->updated_at,
                    'weekly',
                    '0.6',
                );
            }
        }

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function exercises(): array
    {
        return Exercise::query()
            ->active()
            ->orderBy('id')
            ->get(['id', 'slug', 'updated_at'])
            ->map(fn (Exercise $exercise): array => $this->url(
                route('exercises.show', $exercise),
                $exercise->updated_at,
                'monthly',
                '0.6',
            ))
            ->all();
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function muscleGroups(): array
    {
        return MuscleGroup::query()
            ->ordered()
            ->get(['id', 'slug', 'updated_at'])
            ->map(fn (MuscleGroup $group): array => $this->url(
                route('muscles.show', $group),
                $group->updated_at,
                'monthly',
                '0.5',
            ))
            ->all();
    }

    /**
     * @return array{loc: string, lastmod: string|null, changefreq: string, priority: string}
     */
    private function url(string $loc, ?Carbon $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod?->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
