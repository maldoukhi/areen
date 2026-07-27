<?php

declare(strict_types=1);

return [

    'title' => 'التمارين',
    'singular' => 'تمرين',
    'library' => 'مكتبة التمارين',

    'muscle' => [
        'label' => 'العضلة',
        'groups' => 'المجموعات العضلية',
        'primary' => 'العضلة الأساسية',
        'secondary' => 'عضلات مساندة',
        'chest' => 'صدر',
        'back' => 'ظهر',
        'shoulders' => 'أكتاف',
        'biceps' => 'باي',
        'triceps' => 'تراي',
        'forearms' => 'ساعد',
        'quads' => 'أمامية الفخذ',
        'hamstrings' => 'خلفية الفخذ',
        'glutes' => 'ألوية',
        'calves' => 'سمانة',
        'abs' => 'بطن',
        'full_body' => 'الجسم كامل',
        'cardio' => 'كارديو',
        'exercises_count' => ':count تمارين',
    ],

    'equipment' => [
        'label' => 'الأداة',
        'bodyweight' => 'وزن الجسم',
        'barbell' => 'بار',
        'dumbbell' => 'دمبل',
        'machine' => 'جهاز',
        'cable' => 'كيبل',
        'kettlebell' => 'كيتل بل',
        'band' => 'حبل مقاومة',
        'bench' => 'بنش',
        'smith' => 'سميث',
        'none' => 'بدون أداة',
        'any' => 'كل الأدوات',
    ],

    'difficulty' => [
        'label' => 'الصعوبة',
        'easy' => 'سهل',
        'medium' => 'متوسط',
        'hard' => 'صعب',
        'any' => 'كل المستويات',
    ],

    'prescription' => [
        'sets' => 'جولات',
        'reps' => 'عدات',
        'sets_reps' => ':sets × :reps',
        'sets_value' => ':count جولات',
        'reps_value' => ':count عدات',
        'rest' => 'راحة',
        'rest_value' => ':seconds ثانية',
        'tempo' => 'الإيقاع',
        'tempo_hint' => 'أربعة أرقام بالثواني: نزول · وقفة · صعود · وقفة.',
        'weight' => 'الوزن',
        'weight_note' => 'ملاحظة الوزن',
        'weight_note_hint' => 'اكتب ما يفهمه المتدرب، مثل «٧٠٪ من أقصى وزن» أو «بار فارغ».',
        'superset' => 'سوبرست',
        'superset_group' => 'مجموعة السوبرست',
        'superset_hint' => 'نفّذ التمارين المرتبطة بلا راحة بينها، ثم استرح مرة واحدة.',
        'warmup' => 'إحماء',
        'cooldown' => 'تبريد',
        'to_failure' => 'حتى الفشل',
        'each_side' => 'لكل جهة',
    ],

    'coach_notes' => [
        'label' => 'ملاحظات المدرّب',
        'placeholder' => 'اكتب ملاحظة قصيرة عن الأداء أو الوزن أو ما يجب تجنّبه.',
    ],

    'media' => [
        'video' => 'فيديو التمرين',
        'watch' => 'شاهد الأداء',
        'play' => 'شغّل الفيديو',
        'image' => 'صورة التمرين',
        'none_title' => 'أضف فيديو لهذا التمرين',
        'none_body' => 'الصق رابط يوتيوب وسيظهر هنا مع مصغّرة قابلة للتشغيل.',
        'invalid_url' => 'رابط اليوتيوب غير صالح. الصق الرابط الكامل.',
    ],

    'log' => [
        'title' => 'سجّل الجولة',
        'set_number' => 'الجولة :number',
        'reps_done' => 'العدات المنجزة',
        'weight_used' => 'الوزن المستخدم',
        'save_set' => 'سجّل الجولة',
        'undo_set' => 'تراجع عن الجولة',
        'completed' => 'تمرين مكتمل',
        'rest_timer' => 'مؤقّت الراحة',
        'start_rest' => 'ابدأ الراحة',
        'skip_rest' => 'تخطَّ الراحة',
        'rest_done' => 'انتهت الراحة. ابدأ الجولة التالية.',
        'saved' => 'حُفظت الجولة.',
        'saved_offline' => 'حُفظت على جوالك، وستُرسل عند عودة الشبكة.',
    ],

    'search' => [
        'placeholder' => 'ابحث باسم التمرين أو العضلة',
        'no_results_title' => 'جرّب كلمة أخرى',
        'no_results_body' => 'لا يوجد تمرين بهذا الاسم. ابحث باسم العضلة أو الأداة.',
    ],

    'empty' => [
        'title' => 'ابدأ بأول تمرين',
        'body' => 'أضف تمرينًا إلى المكتبة وسيصبح جاهزًا لكل برامجك.',
        'action' => 'أضف تمرين',
    ],

];
