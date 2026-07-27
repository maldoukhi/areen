<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | Arabic is the default and the fallback. Each entry carries the text
    | direction so the layout, the manifest and the print stylesheet can all
    | derive `dir` from one place.
    |
    */

    'locales' => [
        'ar' => ['name' => 'العربية', 'dir' => 'rtl'],
        'en' => ['name' => 'English', 'dir' => 'ltr'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Units
    |--------------------------------------------------------------------------
    |
    | The unit weights are recorded and displayed in.
    |
    */

    'weight_unit' => env('AREEN_WEIGHT_UNIT', 'kg'),

    /*
    |--------------------------------------------------------------------------
    | Brand Assets
    |--------------------------------------------------------------------------
    |
    | These belong to the Areen platform itself, not to any one club. Club
    | branding — name, logo, colours, contact details — comes from the
    | `settings` table and must never be written into the code.
    |
    */

    'brand' => [
        'mark' => '/brand/areen-mark.svg',
        'theme_color' => '#1A2E34',
        'background_color' => '#101F24',
    ],

];
