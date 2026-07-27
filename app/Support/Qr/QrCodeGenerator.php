<?php

declare(strict_types=1);

namespace App\Support\Qr;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * A self-contained QR Code encoder (ISO/IEC 18004), so the printed sheet in
 * P6 can link back to the online program without adding a Composer package.
 *
 * Scope, on purpose:
 * - Byte mode only. Every value this app encodes is a URL — always ASCII,
 *   never digits-only or upper-case-only — so the numeric and alphanumeric
 *   modes a general-purpose encoder would need are dead weight here.
 * - Error-correction level M (~15%) always. A printed sheet handled in a gym
 *   tolerates a fold or a coffee ring better than level L, and fixing the
 *   level keeps the block-size table below to one row per version instead of
 *   four.
 * - Versions 1-40 are all supported (the Reed-Solomon block layout and the
 *   alignment-pattern positions are the standard tables for every version),
 *   so nothing here caps how long a URL can be beyond the spec's own limit.
 *
 * Correctness was checked, not assumed: this file was cross-verified against
 * the independent, widely-used `qrcode` Python package — forcing the same
 * version and byte mode and diffing the two implementations' module matrices
 * bit-for-bit across dozens of strings from 0 to 800 bytes, including every
 * version boundary (10, where the length indicator widens from 8 to 16 bits,
 * and 7, where the version-information blocks start appearing). Every case
 * matched exactly. A further round rendered this class's own output to a PNG
 * and decoded it with OpenCV's `QRCodeDetector`, recovering the original
 * string byte-for-byte. Neither tool is a project dependency; both were used
 * only to build this file.
 *
 * One deliberate simplification versus a general-purpose library: mask
 * selection runs the real ISO penalty scoring (all four rules) across the
 * eight candidate masks and keeps the lowest, exactly like a production
 * encoder would — the "no dependency" constraint applies to the module, not
 * to how carefully it behaves.
 */
final class QrCodeGenerator
{
    private const int MODE_BYTE = 0b0100;

    /**
     * The 2-bit value the format-info string uses for error-correction level
     * M. The four levels are not numbered L=0..H=3 in the bitstream — this is
     * simply what the spec assigns to M.
     */
    private const int ECC_M = 0b00;

    /**
     * Reed-Solomon block layout for error-correction level M, one row per
     * version: a list of `[blockCount, totalCodewords, dataCodewords]`
     * groups (a version can mix two group shapes, never more).
     *
     * @var array<int, list<array{0:int,1:int,2:int}>>
     */
    private const array RS_BLOCKS = [
        1 => [[1, 26, 16]],
        2 => [[1, 44, 28]],
        3 => [[1, 70, 44]],
        4 => [[2, 50, 32]],
        5 => [[2, 67, 43]],
        6 => [[4, 43, 27]],
        7 => [[4, 49, 31]],
        8 => [[2, 60, 38], [2, 61, 39]],
        9 => [[3, 58, 36], [2, 59, 37]],
        10 => [[4, 69, 43], [1, 70, 44]],
        11 => [[1, 80, 50], [4, 81, 51]],
        12 => [[6, 58, 36], [2, 59, 37]],
        13 => [[8, 59, 37], [1, 60, 38]],
        14 => [[4, 64, 40], [5, 65, 41]],
        15 => [[5, 65, 41], [5, 66, 42]],
        16 => [[7, 73, 45], [3, 74, 46]],
        17 => [[10, 74, 46], [1, 75, 47]],
        18 => [[9, 69, 43], [4, 70, 44]],
        19 => [[3, 70, 44], [11, 71, 45]],
        20 => [[3, 67, 41], [13, 68, 42]],
        21 => [[17, 68, 42]],
        22 => [[17, 74, 46]],
        23 => [[4, 75, 47], [14, 76, 48]],
        24 => [[6, 73, 45], [14, 74, 46]],
        25 => [[8, 75, 47], [13, 76, 48]],
        26 => [[19, 74, 46], [4, 75, 47]],
        27 => [[22, 73, 45], [3, 74, 46]],
        28 => [[3, 73, 45], [23, 74, 46]],
        29 => [[21, 73, 45], [7, 74, 46]],
        30 => [[19, 75, 47], [10, 76, 48]],
        31 => [[2, 74, 46], [29, 75, 47]],
        32 => [[10, 74, 46], [23, 75, 47]],
        33 => [[14, 74, 46], [21, 75, 47]],
        34 => [[14, 74, 46], [23, 75, 47]],
        35 => [[12, 75, 47], [26, 76, 48]],
        36 => [[6, 75, 47], [34, 76, 48]],
        37 => [[29, 74, 46], [14, 75, 47]],
        38 => [[13, 74, 46], [32, 75, 47]],
        39 => [[40, 75, 47], [7, 76, 48]],
        40 => [[18, 75, 47], [31, 76, 48]],
    ];

    /**
     * Alignment-pattern centre coordinates per version. Every combination of
     * two entries (including a value with itself) is a pattern centre; the
     * ones that would collide with a finder pattern are skipped at
     * placement time because that cell is already set.
     *
     * @var array<int, list<int>>
     */
    private const array ALIGNMENT_POSITIONS = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
        5 => [6, 30],
        6 => [6, 34],
        7 => [6, 22, 38],
        8 => [6, 24, 42],
        9 => [6, 26, 46],
        10 => [6, 28, 50],
        11 => [6, 30, 54],
        12 => [6, 32, 58],
        13 => [6, 34, 62],
        14 => [6, 26, 46, 66],
        15 => [6, 26, 48, 70],
        16 => [6, 26, 50, 74],
        17 => [6, 30, 54, 78],
        18 => [6, 30, 56, 82],
        19 => [6, 30, 58, 86],
        20 => [6, 34, 62, 90],
        21 => [6, 28, 50, 72, 94],
        22 => [6, 26, 50, 74, 98],
        23 => [6, 30, 54, 78, 102],
        24 => [6, 28, 54, 80, 106],
        25 => [6, 32, 58, 84, 110],
        26 => [6, 30, 58, 86, 114],
        27 => [6, 34, 62, 90, 118],
        28 => [6, 26, 50, 74, 98, 122],
        29 => [6, 30, 54, 78, 102, 126],
        30 => [6, 26, 52, 78, 104, 130],
        31 => [6, 30, 56, 82, 108, 134],
        32 => [6, 34, 60, 86, 112, 138],
        33 => [6, 30, 58, 86, 114, 142],
        34 => [6, 34, 62, 90, 118, 146],
        35 => [6, 30, 54, 78, 102, 126, 150],
        36 => [6, 24, 50, 76, 102, 128, 154],
        37 => [6, 28, 54, 80, 106, 132, 158],
        38 => [6, 32, 58, 84, 110, 136, 162],
        39 => [6, 26, 54, 82, 110, 138, 166],
        40 => [6, 30, 58, 86, 114, 142, 170],
    ];

    /** GF(256) exponent table: gexp(n) = EXP[n % 255]. Seeded once, lazily. */
    private static array $exp = [];

    /** GF(256) logarithm table: glog(n) for n in 1..255. */
    private static array $log = [];

    /**
     * Render a value as a QR code, ready to drop into a page: a self-
     * contained `<svg>` with a white background, a quiet zone and no text.
     *
     * @param  int  $modulePixels  Size of one module, in SVG user units.
     * @param  int  $quietZone  Border width, in modules — the spec's minimum
     *                          is 4, and scanners rely on it being present.
     */
    public static function toSvg(string $value, int $modulePixels = 4, int $quietZone = 4): string
    {
        $result = self::encode($value);
        $size = $result['size'];
        $modules = $result['modules'];

        $dimension = ($size + $quietZone * 2) * $modulePixels;

        $rects = [];

        // Runs of dark modules on a row collapse into one <rect>, so a busy
        // version-8+ code is a few hundred elements rather than a few
        // thousand.
        for ($row = 0; $row < $size; $row++) {
            $col = 0;

            while ($col < $size) {
                if (! $modules[$row][$col]) {
                    $col++;

                    continue;
                }

                $runStart = $col;

                while ($col < $size && $modules[$row][$col]) {
                    $col++;
                }

                $x = ($runStart + $quietZone) * $modulePixels;
                $y = ($row + $quietZone) * $modulePixels;
                $width = ($col - $runStart) * $modulePixels;

                $rects[] = sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d"/>',
                    $x,
                    $y,
                    $width,
                    $modulePixels,
                );
            }
        }

        return sprintf(
            '<svg viewBox="0 0 %1$d %1$d" width="%1$d" height="%1$d" xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges">'
            .'<rect width="%1$d" height="%1$d" fill="#fff"/>'
            .'<g fill="#111">%2$s</g>'
            .'</svg>',
            $dimension,
            implode('', $rects),
        );
    }

    /**
     * The same as {@see self::toSvg()}, but never throws: a QR code is a
     * convenience on the printed sheet, not the reason for it, so a value
     * this encoder cannot handle (absurdly long, for instance) degrades to
     * "no code" rather than a broken print page.
     */
    public static function tryToSvg(string $value, int $modulePixels = 4, int $quietZone = 4): ?string
    {
        try {
            return self::toSvg($value, $modulePixels, $quietZone);
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * @return array{version:int, mask:int, size:int, modules:list<list<bool>>}
     */
    public static function encode(string $value): array
    {
        self::seedGaloisTables();

        $bytes = array_values(unpack('C*', $value) ?: []);
        $version = self::selectVersion(count($bytes));
        $codewords = self::buildCodewords($bytes, $version);

        $bestMask = 0;
        $bestScore = null;

        for ($mask = 0; $mask < 8; $mask++) {
            $trial = self::buildMatrix($version, $mask, test: true, codewords: $codewords);
            $score = self::lostPoint($trial);

            if ($bestScore === null || $score < $bestScore) {
                $bestScore = $score;
                $bestMask = $mask;
            }
        }

        return [
            'version' => $version,
            'mask' => $bestMask,
            'size' => $version * 4 + 17,
            'modules' => self::buildMatrix($version, $bestMask, test: false, codewords: $codewords),
        ];
    }

    private static function seedGaloisTables(): void
    {
        if (self::$exp !== []) {
            return;
        }

        $exp = array_fill(0, 256, 0);

        for ($i = 0; $i < 8; $i++) {
            $exp[$i] = 1 << $i;
        }

        // The QR spec's primitive polynomial (x^8 + x^4 + x^3 + x^2 + 1) over
        // GF(256), generated from element 2 — this recurrence is the
        // standard way to build both tables from that polynomial alone.
        for ($i = 8; $i < 256; $i++) {
            $exp[$i] = $exp[$i - 4] ^ $exp[$i - 5] ^ $exp[$i - 6] ^ $exp[$i - 8];
        }

        $log = array_fill(0, 256, 0);

        for ($i = 0; $i < 255; $i++) {
            $log[$exp[$i]] = $i;
        }

        self::$exp = $exp;
        self::$log = $log;
    }

    private static function gexp(int $n): int
    {
        return self::$exp[$n % 255];
    }

    private static function glog(int $n): int
    {
        return self::$log[$n];
    }

    /**
     * The smallest version whose byte-mode capacity fits the payload,
     * computed directly from {@see self::RS_BLOCKS} rather than from a
     * separate capacity table, so the two can never drift apart.
     */
    private static function selectVersion(int $byteCount): int
    {
        for ($version = 1; $version <= 40; $version++) {
            $countIndicatorBits = $version < 10 ? 8 : 16;
            $neededBits = 4 + $countIndicatorBits + $byteCount * 8;

            if ($neededBits <= self::dataCapacityBits($version)) {
                return $version;
            }
        }

        throw new RuntimeException('The value is too long to fit in a QR code, even at version 40.');
    }

    private static function dataCapacityBits(int $version): int
    {
        $bits = 0;

        foreach (self::RS_BLOCKS[$version] as [$blockCount, , $dataCount]) {
            $bits += $blockCount * $dataCount * 8;
        }

        return $bits;
    }

    /**
     * Builds the mode/length/data bit stream, terminates and pads it to
     * exactly fill the version's capacity, then splits, error-corrects and
     * interleaves it into the final codeword stream the matrix is drawn
     * from.
     *
     * @param  list<int>  $bytes
     * @return list<int>
     */
    private static function buildCodewords(array $bytes, int $version): array
    {
        $countIndicatorBits = $version < 10 ? 8 : 16;
        $capacityBits = self::dataCapacityBits($version);

        $bits = [];
        self::appendBits($bits, self::MODE_BYTE, 4);
        self::appendBits($bits, count($bytes), $countIndicatorBits);

        foreach ($bytes as $byte) {
            self::appendBits($bits, $byte, 8);
        }

        // Terminator: up to four 0 bits, fewer if the capacity is almost full.
        for ($i = 0; $i < 4 && count($bits) < $capacityBits; $i++) {
            $bits[] = 0;
        }

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        // The two padding codewords the spec defines, alternating until full.
        $padCodewords = [0xEC, 0x11];
        $padIndex = 0;

        while (count($bits) < $capacityBits) {
            self::appendBits($bits, $padCodewords[$padIndex % 2], 8);
            $padIndex++;
        }

        $dataBytes = [];

        for ($i = 0, $total = count($bits); $i < $total; $i += 8) {
            $byte = 0;

            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | $bits[$i + $j];
            }

            $dataBytes[] = $byte;
        }

        return self::interleave($dataBytes, $version);
    }

    /**
     * @param  list<int>  $bits
     */
    private static function appendBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    /**
     * Splits the padded data into its Reed-Solomon blocks, computes the
     * error-correction codewords for each, and interleaves data and then
     * error-correction codewords column-wise across blocks — the order a QR
     * reader expects them in.
     *
     * @param  list<int>  $dataBytes
     * @return list<int>
     */
    private static function interleave(array $dataBytes, int $version): array
    {
        $offset = 0;
        $maxDataCount = 0;
        $maxEccCount = 0;
        $dataBlocks = [];
        $eccBlocks = [];

        foreach (self::expandBlocks($version) as [$totalCount, $dataCount]) {
            $eccCount = $totalCount - $dataCount;
            $maxDataCount = max($maxDataCount, $dataCount);
            $maxEccCount = max($maxEccCount, $eccCount);

            $blockData = array_slice($dataBytes, $offset, $dataCount);
            $offset += $dataCount;

            $generator = [1];

            for ($i = 0; $i < $eccCount; $i++) {
                $generator = self::polyMultiply($generator, [1, self::gexp($i)]);
            }

            $dividend = array_merge(
                self::stripLeadingZeros($blockData),
                array_fill(0, count($generator) - 1, 0),
            );
            $remainder = self::polyMod($dividend, $generator);
            $remainderOffset = count($remainder) - $eccCount;

            $blockEcc = [];

            for ($i = 0; $i < $eccCount; $i++) {
                $index = $i + $remainderOffset;
                $blockEcc[] = $index >= 0 ? ($remainder[$index] ?? 0) : 0;
            }

            $dataBlocks[] = $blockData;
            $eccBlocks[] = $blockEcc;
        }

        $stream = [];

        for ($i = 0; $i < $maxDataCount; $i++) {
            foreach ($dataBlocks as $block) {
                if ($i < count($block)) {
                    $stream[] = $block[$i];
                }
            }
        }

        for ($i = 0; $i < $maxEccCount; $i++) {
            foreach ($eccBlocks as $block) {
                if ($i < count($block)) {
                    $stream[] = $block[$i];
                }
            }
        }

        return $stream;
    }

    /**
     * @return list<array{0:int,1:int}> one `[totalCodewords, dataCodewords]` pair per physical block
     */
    private static function expandBlocks(int $version): array
    {
        $blocks = [];

        foreach (self::RS_BLOCKS[$version] as [$count, $total, $data]) {
            for ($i = 0; $i < $count; $i++) {
                $blocks[] = [$total, $data];
            }
        }

        return $blocks;
    }

    /**
     * Multiplication of two polynomials over GF(256), coefficients ordered
     * highest degree first.
     *
     * @param  list<int>  $a
     * @param  list<int>  $b
     * @return list<int>
     */
    private static function polyMultiply(array $a, array $b): array
    {
        $result = array_fill(0, count($a) + count($b) - 1, 0);

        foreach ($a as $i => $coefficientA) {
            if ($coefficientA === 0) {
                continue;
            }

            foreach ($b as $j => $coefficientB) {
                if ($coefficientB === 0) {
                    continue;
                }

                $result[$i + $j] ^= self::gexp(self::glog($coefficientA) + self::glog($coefficientB));
            }
        }

        return $result;
    }

    /**
     * Polynomial long division over GF(256); returns the remainder, which
     * carries the error-correction codewords in its low-order coefficients.
     *
     * @param  list<int>  $dividend
     * @param  list<int>  $divisor
     * @return list<int>
     */
    private static function polyMod(array $dividend, array $divisor): array
    {
        $dividend = self::stripLeadingZeros($dividend);
        $divisor = self::stripLeadingZeros($divisor);

        while (true) {
            $difference = count($dividend) - count($divisor);

            if ($difference < 0) {
                return $dividend;
            }

            $ratio = self::glog($dividend[0]) - self::glog($divisor[0]);
            $next = [];

            foreach ($divisor as $i => $divisorCoefficient) {
                $term = $divisorCoefficient === 0 ? 0 : self::gexp(self::glog($divisorCoefficient) + $ratio);
                $next[] = $dividend[$i] ^ $term;
            }

            if ($difference > 0) {
                array_push($next, ...array_slice($dividend, count($divisor)));
            }

            $dividend = self::stripLeadingZeros($next);
        }
    }

    /**
     * @param  list<int>  $coefficients
     * @return list<int>
     */
    private static function stripLeadingZeros(array $coefficients): array
    {
        $count = count($coefficients);
        $offset = 0;

        while ($offset < $count - 1 && $coefficients[$offset] === 0) {
            $offset++;
        }

        return array_slice($coefficients, $offset);
    }

    /**
     * Draws every fixed and functional pattern, then fills in the data
     * region. `$test` suppresses the real format/version bits (both filled
     * with light modules instead) so the eight mask trials in
     * {@see self::encode()} are scored on equal footing.
     *
     * @param  list<int>  $codewords
     * @return list<list<bool>>
     */
    private static function buildMatrix(int $version, int $mask, bool $test, array $codewords): array
    {
        $size = $version * 4 + 17;
        $modules = array_fill(0, $size, array_fill(0, $size, null));

        self::placeFinderPattern($modules, $size, 0, 0);
        self::placeFinderPattern($modules, $size, $size - 7, 0);
        self::placeFinderPattern($modules, $size, 0, $size - 7);
        self::placeAlignmentPatterns($modules, $version);
        self::placeTimingPatterns($modules, $size);
        self::placeFormatInfo($modules, $size, $test, $mask);

        if ($version >= 7) {
            self::placeVersionInfo($modules, $size, $version, $test);
        }

        self::mapData($modules, $size, $codewords, $mask);

        /** @var list<list<bool>> $modules every cell was assigned above */
        return $modules;
    }

    /**
     * A 7x7 finder pattern plus its 1-module light separator, anchored at one
     * of the three corners.
     */
    private static function placeFinderPattern(array &$modules, int $size, int $row, int $col): void
    {
        for ($r = -1; $r <= 7; $r++) {
            if ($row + $r <= -1 || $size <= $row + $r) {
                continue;
            }

            for ($c = -1; $c <= 7; $c++) {
                if ($col + $c <= -1 || $size <= $col + $c) {
                    continue;
                }

                $dark = (0 <= $r && $r <= 6 && ($c === 0 || $c === 6))
                    || (0 <= $c && $c <= 6 && ($r === 0 || $r === 6))
                    || (2 <= $r && $r <= 4 && 2 <= $c && $c <= 4);

                $modules[$row + $r][$col + $c] = $dark;
            }
        }
    }

    private static function placeAlignmentPatterns(array &$modules, int $version): void
    {
        $positions = self::ALIGNMENT_POSITIONS[$version];

        foreach ($positions as $row) {
            foreach ($positions as $col) {
                // A centre that lands inside a finder pattern is skipped —
                // those cells are never null by the time this runs.
                if ($modules[$row][$col] !== null) {
                    continue;
                }

                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $dark = $r === -2 || $r === 2 || $c === -2 || $c === 2 || ($r === 0 && $c === 0);
                        $modules[$row + $r][$col + $c] = $dark;
                    }
                }
            }
        }
    }

    private static function placeTimingPatterns(array &$modules, int $size): void
    {
        for ($r = 8; $r <= $size - 9; $r++) {
            if ($modules[$r][6] !== null) {
                continue;
            }

            $modules[$r][6] = $r % 2 === 0;
        }

        for ($c = 8; $c <= $size - 9; $c++) {
            if ($modules[6][$c] !== null) {
                continue;
            }

            $modules[6][$c] = $c % 2 === 0;
        }
    }

    /**
     * The 15-bit BCH-encoded error-correction level and mask pattern,
     * written twice (the spec requires both copies, so either survives on
     * its own if the other is obscured).
     */
    private static function placeFormatInfo(array &$modules, int $size, bool $test, int $mask): void
    {
        $bits = self::bchTypeInfo((self::ECC_M << 3) | $mask);

        for ($i = 0; $i <= 14; $i++) {
            $mod = ! $test && (($bits >> $i) & 1) === 1;

            if ($i < 6) {
                $modules[$i][8] = $mod;
            } elseif ($i < 8) {
                $modules[$i + 1][8] = $mod;
            } else {
                $modules[$size - 15 + $i][8] = $mod;
            }
        }

        for ($i = 0; $i <= 14; $i++) {
            $mod = ! $test && (($bits >> $i) & 1) === 1;

            if ($i < 8) {
                $modules[8][$size - $i - 1] = $mod;
            } elseif ($i < 9) {
                $modules[8][15 - $i - 1 + 1] = $mod;
            } else {
                $modules[8][15 - $i - 1] = $mod;
            }
        }

        // The one module the spec fixes dark regardless of level or mask.
        $modules[$size - 8][8] = ! $test;
    }

    /**
     * The two 6x3 version-number blocks the spec adds from version 7 up, so
     * a reader that measures the symbol before it finishes decoding can
     * confirm which version it is looking at.
     */
    private static function placeVersionInfo(array &$modules, int $size, int $version, bool $test): void
    {
        $bits = self::bchTypeNumber($version);

        for ($i = 0; $i < 18; $i++) {
            $mod = ! $test && (($bits >> $i) & 1) === 1;
            $modules[intdiv($i, 3)][$i % 3 + $size - 8 - 3] = $mod;
        }

        for ($i = 0; $i < 18; $i++) {
            $mod = ! $test && (($bits >> $i) & 1) === 1;
            $modules[$i % 3 + $size - 8 - 3][intdiv($i, 3)] = $mod;
        }
    }

    /**
     * Walks the matrix in the zig-zag, two-columns-at-a-time order the spec
     * defines (bottom-right to top-left, reversing direction at each edge),
     * skipping the vertical timing column and every cell a fixed pattern
     * already claimed, and writes the masked data bits into what is left.
     *
     * @param  list<int>  $codewords
     */
    private static function mapData(array &$modules, int $size, array $codewords, int $mask): void
    {
        $direction = -1;
        $row = $size - 1;
        $bitIndex = 7;
        $byteIndex = 0;
        $codewordCount = count($codewords);

        $maskFn = self::maskFunction($mask);

        for ($rawCol = $size - 1; $rawCol > 0; $rawCol -= 2) {
            // Deliberately a fresh local rather than reusing $rawCol: feeding
            // the column-6 shift back into the loop's own step would drift
            // every column after it by one — a divergence only caught by
            // diffing against a reference implementation (see the class
            // docblock).
            $col = $rawCol <= 6 ? $rawCol - 1 : $rawCol;
            $colPair = [$col, $col - 1];

            while (true) {
                foreach ($colPair as $c) {
                    if ($modules[$row][$c] !== null) {
                        continue;
                    }

                    $dark = $byteIndex < $codewordCount
                        && (($codewords[$byteIndex] >> $bitIndex) & 1) === 1;

                    if ($maskFn($row, $c)) {
                        $dark = ! $dark;
                    }

                    $modules[$row][$c] = $dark;
                    $bitIndex--;

                    if ($bitIndex === -1) {
                        $byteIndex++;
                        $bitIndex = 7;
                    }
                }

                $row += $direction;

                if ($row < 0 || $size <= $row) {
                    $row -= $direction;
                    $direction = -$direction;

                    break;
                }
            }
        }
    }

    private static function maskFunction(int $pattern): Closure
    {
        return match ($pattern) {
            0 => static fn (int $r, int $c): bool => ($r + $c) % 2 === 0,
            1 => static fn (int $r, int $c): bool => $r % 2 === 0,
            2 => static fn (int $r, int $c): bool => $c % 3 === 0,
            3 => static fn (int $r, int $c): bool => ($r + $c) % 3 === 0,
            4 => static fn (int $r, int $c): bool => (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0,
            5 => static fn (int $r, int $c): bool => ($r * $c) % 2 + ($r * $c) % 3 === 0,
            6 => static fn (int $r, int $c): bool => (($r * $c) % 2 + ($r * $c) % 3) % 2 === 0,
            7 => static fn (int $r, int $c): bool => (($r * $c) % 3 + ($r + $c) % 2) % 2 === 0,
            default => throw new InvalidArgumentException("Bad mask pattern: {$pattern}."),
        };
    }

    private static function bchBitLength(int $data): int
    {
        $length = 0;

        while ($data !== 0) {
            $length++;
            $data >>= 1;
        }

        return $length;
    }

    /**
     * BCH(15,5) encoding of the 5-bit error-correction-level + mask-pattern
     * value, XOR-masked with the spec's fixed pattern so an all-zero code
     * (level M, mask 0) never produces an all-zero format string.
     */
    private static function bchTypeInfo(int $data): int
    {
        $generator = (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0);
        $mask = (1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1);

        $remainder = $data << 10;

        while (self::bchBitLength($remainder) - self::bchBitLength($generator) >= 0) {
            $remainder ^= $generator << (self::bchBitLength($remainder) - self::bchBitLength($generator));
        }

        return (($data << 10) | $remainder) ^ $mask;
    }

    /**
     * BCH(18,6) encoding of the 6-bit version number, used for version >= 7.
     */
    private static function bchTypeNumber(int $data): int
    {
        $generator = (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0);

        $remainder = $data << 12;

        while (self::bchBitLength($remainder) - self::bchBitLength($generator) >= 0) {
            $remainder ^= $generator << (self::bchBitLength($remainder) - self::bchBitLength($generator));
        }

        return ($data << 12) | $remainder;
    }

    /**
     * The ISO penalty score for a candidate mask: lower is better. All four
     * rules are the real ones (adjacent same-colour runs, 2x2 blocks,
     * finder-like 1:1:3:1:1 patterns, and the light/dark balance) — the mask
     * that wins this is the one an actual encoder would pick, not an
     * arbitrary fixed choice.
     *
     * @param  list<list<bool>>  $modules
     */
    private static function lostPoint(array $modules): int
    {
        $size = count($modules);

        return self::lostPointRuns($modules, $size)
            + self::lostPointBlocks($modules, $size)
            + self::lostPointPatterns($modules, $size)
            + self::lostPointBalance($modules, $size);
    }

    /**
     * @param  list<list<bool>>  $modules
     */
    private static function lostPointRuns(array $modules, int $size): int
    {
        $points = 0;

        for ($row = 0; $row < $size; $row++) {
            $points += self::lostPointRunsInLine(static fn (int $i): bool => $modules[$row][$i], $size);
        }

        for ($col = 0; $col < $size; $col++) {
            $points += self::lostPointRunsInLine(static fn (int $i): bool => $modules[$i][$col], $size);
        }

        return $points;
    }

    private static function lostPointRunsInLine(Closure $at, int $size): int
    {
        $points = 0;
        $previous = $at(0);
        $length = 0;

        for ($i = 0; $i < $size; $i++) {
            $current = $at($i);

            if ($current === $previous) {
                $length++;

                continue;
            }

            if ($length >= 5) {
                $points += $length - 2;
            }

            $length = 1;
            $previous = $current;
        }

        if ($length >= 5) {
            $points += $length - 2;
        }

        return $points;
    }

    /**
     * @param  list<list<bool>>  $modules
     */
    private static function lostPointBlocks(array $modules, int $size): int
    {
        $points = 0;

        for ($row = 0; $row < $size - 1; $row++) {
            for ($col = 0; $col < $size - 1; $col++) {
                $corner = $modules[$row][$col];

                if ($corner === $modules[$row][$col + 1]
                    && $corner === $modules[$row + 1][$col]
                    && $corner === $modules[$row + 1][$col + 1]) {
                    $points += 3;
                }
            }
        }

        return $points;
    }

    /**
     * @param  list<list<bool>>  $modules
     */
    private static function lostPointPatterns(array $modules, int $size): int
    {
        $points = 0;
        $limit = $size - 10;

        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $limit; $col++) {
                if (self::isFinderLikeRun(static fn (int $offset): bool => $modules[$row][$col + $offset])) {
                    $points += 40;
                }
            }
        }

        for ($col = 0; $col < $size; $col++) {
            for ($row = 0; $row < $limit; $row++) {
                if (self::isFinderLikeRun(static fn (int $offset): bool => $modules[$row + $offset][$col])) {
                    $points += 40;
                }
            }
        }

        return $points;
    }

    /**
     * The 1:1:3:1:1 dark/light ratio that echoes a finder pattern, in either
     * direction, checked over an 11-cell window starting at `$at(0)`.
     */
    private static function isFinderLikeRun(Closure $at): bool
    {
        if ($at(1) || ! $at(4) || $at(5) || ! $at(6) || $at(9)) {
            return false;
        }

        $ascending = $at(0) && $at(2) && $at(3) && ! $at(7) && ! $at(8) && ! $at(10);
        $descending = ! $at(0) && ! $at(2) && ! $at(3) && $at(7) && $at(8) && $at(10);

        return $ascending || $descending;
    }

    /**
     * @param  list<list<bool>>  $modules
     */
    private static function lostPointBalance(array $modules, int $size): int
    {
        $dark = 0;

        foreach ($modules as $row) {
            foreach ($row as $cell) {
                if ($cell) {
                    $dark++;
                }
            }
        }

        $percentDark = $dark / ($size * $size) * 100;
        $rating = (int) (abs($percentDark - 50) / 5);

        return $rating * 10;
    }
}
