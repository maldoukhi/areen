<?php

declare(strict_types=1);

/*
 * Two rules the whole interface rests on, checked against the files rather than
 * against a rendered page — a rule that only holds on the pages someone
 * remembered to write a test for is not a rule.
 *
 * `ripgrep` does the scanning. `grep -P` is NOT usable here: on this platform it
 * rejects `\x{0600}` with "character code point value in \x{} is too large" and
 * exits 2, which a naive `grep -P … || echo clean` reads as a clean bill of
 * health. A test that cannot fail is worse than no test, so the tooling is
 * asserted before the rules are.
 */

/**
 * @return array{0: int, 1: string}
 */
function rg(string $pattern, string $path, string ...$flags): array
{
    $command = array_merge(['rg', '--no-config'], $flags, ['--', $pattern, base_path($path)]);

    $process = proc_open(
        $command,
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
    );

    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);

    foreach ($pipes as $pipe) {
        fclose($pipe);
    }

    return [proc_close($process), trim($output)];
}

it('has a working ripgrep, because grep -P cannot do this job here', function (): void {
    [$status] = rg('nothing-matches-this-string-anywhere', 'resources/views', '--files-with-matches');

    // 1 is ripgrep's "no matches"; 2 would mean the pattern or the tool failed.
    expect($status)->toBe(1, 'ripgrep is not available — the two rules below cannot be checked');

    [$found] = rg('[\x{0600}-\x{06FF}]', 'lang/ar', '--files-with-matches');

    expect($found)->toBe(0, 'ripgrep did not match Arabic in lang/ar — the pattern is not doing what it claims');
});

/*
 * CLAUDE.md §3: no fixed string in Blade, everything through __(). A hardcoded
 * Arabic literal is the failure mode that matters, because it renders correctly
 * to whoever wrote it and is simply wrong in English.
 */
it('has no hardcoded Arabic anywhere in a Blade file', function (): void {
    [$status, $output] = rg('[\x{0600}-\x{06FF}]', 'resources/views', '--line-number');

    expect($status)->toBe(1, "Arabic text found outside the translation files:\n".$output);
});

/*
 * CLAUDE.md §3 / DESIGN.md §12: logical properties only. A physical one looks
 * right in whichever direction it was written in and is mirrored-wrong in the
 * other, which is exactly the kind of bug nobody sees until a reader switches
 * language.
 */
it('uses no physical-direction utility in any view', function (): void {
    [$status, $output] = rg(
        '\b(ml|mr)-[0-9]|\b(left|right)-[0-9]',
        'resources/views',
        '--line-number',
    );

    expect($status)->toBe(1, "physical-direction utilities found — use ms-/me-/start-/end-:\n".$output);
});
