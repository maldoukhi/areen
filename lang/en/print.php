<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Print & PDF (P6)
    |--------------------------------------------------------------------------
    |
    | Most of what the printed sheet says already exists in lang/en/program.php
    | (program.print.*) and lang/en/exercise.php (exercise.prescription.*) —
    | this file only adds what is specific to the printed table itself.
    */

    'table' => [
        'number' => 'No.',
        'exercise' => 'Exercise',
    ],

    'page_of' => 'Day :day of :total',
    'verify_url_label' => 'Or open the link',

    'pdf' => [
        'unavailable_title' => 'The PDF could not be generated',
        'unavailable_body' => 'This server cannot generate a PDF file right now. Print this page directly from your browser, or try downloading the file again later.',
    ],

];
