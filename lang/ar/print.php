<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Print & PDF (P6)
    |--------------------------------------------------------------------------
    |
    | Most of what the printed sheet says already exists in lang/ar/program.php
    | (program.print.*) and lang/ar/exercise.php (exercise.prescription.*) —
    | this file only adds what is specific to the printed table itself.
    */

    'table' => [
        'number' => 'الرقم',
        'exercise' => 'التمرين',
    ],

    'page_of' => 'اليوم :day من :total',
    'verify_url_label' => 'أو افتح الرابط',

    'pdf' => [
        'unavailable_title' => 'تعذّر إنشاء ملف PDF',
        'unavailable_body' => 'هذا الخادم لا يستطيع توليد ملف PDF في الوقت الحالي. اطبع هذه الصفحة من متصفحك مباشرة، أو حاول تنزيل الملف لاحقًا.',
    ],

];
