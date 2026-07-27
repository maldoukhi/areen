<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Enums\ProgramLevel;
use App\Enums\UserRole;
use App\Models\Exercise;
use App\Models\Program;

/*
 * Raw translation keys reached real screens three separate times — difficulty
 * chips, programme goals and the signed-in user's role — because `label()`
 * happily returns the key it could not find. These tests fail instead.
 */

it('resolves every enum label in both languages', function (): void {
    $enums = [Difficulty::class, ProgramLevel::class, UserRole::class];

    foreach (['ar', 'en'] as $locale) {
        app()->setLocale($locale);

        foreach ($enums as $enum) {
            foreach ($enum::cases() as $case) {
                expect($case->label())
                    ->not->toContain('.', "{$enum}::{$case->name} has no {$locale} label");
            }
        }
    }
});

it('has a label for every value the seed actually stores', function (): void {
    $this->seed();

    $lookups = [
        'program.goal.' => Program::query()->distinct()->pluck('goal')->filter(),
        'exercise.equipment.' => Exercise::query()->distinct()->pluck('equipment')->filter(),
    ];

    foreach (['ar', 'en'] as $locale) {
        app()->setLocale($locale);

        foreach ($lookups as $prefix => $values) {
            foreach ($values as $value) {
                expect(__($prefix.$value))
                    ->not->toBe($prefix.$value, "missing {$locale} label for {$prefix}{$value}");
            }
        }
    }
});

it('keeps the Arabic and English files key for key identical', function (): void {
    $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($items as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            $keys = is_array($value)
                ? array_merge($keys, $flatten($value, $path))
                : [...$keys, $path];
        }

        return $keys;
    };

    foreach (glob(lang_path('ar/*.php')) as $arabic) {
        $group = basename($arabic, '.php');
        $english = lang_path("en/{$group}.php");

        expect(file_exists($english))->toBeTrue("lang/en/{$group}.php is missing");

        $ar = $flatten(require $arabic);
        $en = $flatten(require $english);

        expect(array_diff($ar, $en))->toBe([], "keys only in ar/{$group}: ".implode(', ', array_diff($ar, $en)))
            ->and(array_diff($en, $ar))->toBe([], "keys only in en/{$group}: ".implode(', ', array_diff($en, $ar)));
    }
});
