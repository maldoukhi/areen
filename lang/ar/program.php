<?php

declare(strict_types=1);

return [

    'title' => 'البرامج',
    'singular' => 'برنامج',
    'all' => 'كل البرامج',
    'featured_title' => 'برامج مختارة',
    'featured' => 'مميّز',
    'latest' => 'أحدث البرامج',

    'visibility' => [
        'label' => 'الظهور',
        'public' => 'عام',
        'private' => 'خاص برمز',
        'draft' => 'مسودة',
        'published' => 'منشور',
    ],

    'level' => [
        'label' => 'المستوى',
        'beginner' => 'مبتدئ',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدّم',
        'any' => 'كل المستويات',
    ],

    'goal' => [
        'label' => 'الهدف',
        'strength' => 'قوة',
        'hypertrophy' => 'تضخيم',
        'fat_loss' => 'خسارة دهون',
        'endurance' => 'تحمّل',
        'general_fitness' => 'لياقة عامة',
        'any' => 'كل الأهداف',
    ],

    'days' => [
        'label' => 'الأيام',
        'count' => ':count أيام في الأسبوع',
        'number' => 'اليوم :number',
        'title' => 'يوم :number · :title',
        'focus' => 'التركيز',
        'notes' => 'ملاحظات اليوم',
        'exercises_count' => ':count تمارين',
        'rest' => 'يوم راحة',
        'rest_title' => 'اليوم راحة',
        'rest_body' => 'خذ راحتك اليوم. نم جيدًا، اشرب ماء كافيًا، وعُد غدًا أقوى.',
        'empty_title' => 'ابنِ هذا اليوم',
        'empty_body' => 'أضف أول تمرين وسيظهر في جدول اليوم مباشرة.',
        'next' => 'اليوم التالي',
        'previous' => 'اليوم السابق',
        'today' => 'اليوم',
    ],

    'access' => [
        'badge' => 'برنامج خاص',
        'code' => 'رمز الدخول',
        'code_hint' => 'أدخل الرمز الذي أعطاك إياه مدرّبك.',
        'code_placeholder' => 'الرمز',
        'open' => 'افتح البرنامج',
        'invalid' => 'الرمز غير صحيح. راجعه مع مدرّبك ثم أعد المحاولة.',
        'locked_title' => 'هذا البرنامج بحاجة إلى رمز',
        'locked_body' => 'أدخل رمز الدخول ليفتح الجدول كاملًا.',
    ],

    'actions' => [
        'view' => 'افتح البرنامج',
        'start' => 'ابدأ البرنامج',
        'continue' => 'أكمل البرنامج',
        'start_day' => 'ابدأ اليوم',
        'browse' => 'تصفّح البرامج',
        'print' => 'اطبع الجدول',
        'download_pdf' => 'نزّل الجدول PDF',
        'share' => 'شارك الرابط',
        'copy_link' => 'انسخ الرابط',
        'link_copied' => 'نُسخ الرابط',
    ],

    'meta' => [
        'about' => 'عن البرنامج',
        'duration' => 'المدة',
        'weeks' => ':count أسابيع',
        'coach' => 'المدرّب',
        'published_on' => 'نُشر في :date',
        'updated_on' => 'آخر تحديث :date',
        'started_on' => 'بدأته في :date',
    ],

    'progress' => [
        'label' => 'تقدّمك',
        'completed_days' => ':done من :total أيام',
        'streak' => 'أيام متتالية',
        'last_session' => 'آخر تمرين',
    ],

    'print' => [
        'heading' => 'جدول :program',
        'printed_on' => 'تاريخ الطباعة :date',
        'weight_used' => 'الوزن المستخدم',
        'scan_hint' => 'امسح الرمز لفتح الجدول على جوالك.',
    ],

    'filters' => [
        'title' => 'صفِّ البرامج',
        'reset' => 'أعد الضبط',
        'no_results_title' => 'وسّع بحثك قليلًا',
        'no_results_body' => 'لا يوجد برنامج بهذه المواصفات. جرّب مستوى آخر أو هدفًا آخر.',
    ],

    'empty' => [
        'title' => 'ابدأ ببرنامجك الأول',
        'body' => 'اختر برنامجًا يناسب مستواك وهدفك، وسيظهر جدولك هنا يومًا بيوم.',
        'action' => 'تصفّح البرامج',
    ],

];
