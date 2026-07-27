<?php

declare(strict_types=1);

return [

    'app_name' => 'عرين',
    'tagline' => 'جدول تمرينك في جيبك',
    'list_separator' => '، ',

    'nav' => [
        'home' => 'الرئيسية',
        'programs' => 'البرامج',
        'exercises' => 'التمارين',
        'my_workout' => 'تمريني',
        'account' => 'حسابي',
        'about' => 'عن النادي',
        'admin' => 'الإدارة',
    ],

    'actions' => [
        'back' => 'ارجع',
        'save' => 'احفظ',
        'cancel' => 'ألغِ',
        'delete' => 'احذف',
        'edit' => 'عدّل',
        'search' => 'ابحث',
        'filter' => 'صفِّ',
        'clear' => 'امسح',
        'print' => 'اطبع',
        'download_pdf' => 'نزّل PDF',
        'retry' => 'أعد المحاولة',
        'add' => 'أضف',
        'open' => 'افتح',
        'close' => 'أغلق',
        'copy' => 'انسخ',
        'copied' => 'نُسخ',
        'share' => 'شارك',
        'show_more' => 'اعرض المزيد',
        'show_less' => 'اعرض أقل',
        'skip_to_content' => 'تخطَّ إلى المحتوى',
    ],

    'locale' => [
        'switch' => 'غيّر اللغة',
        'current' => 'اللغة الحالية',
    ],

    'units' => [
        'kg' => 'كجم',
        'lb' => 'رطل',
        'second' => 'ثانية|ثانيتان|ثوانٍ',
        'minute' => 'دقيقة|دقيقتان|دقائق',
    ],

    'states' => [
        'loading' => 'جارٍ التحميل',
        'error_title' => 'تعذّر إكمال الطلب',
        'error_body' => 'حدث خطأ غير متوقع. أعد المحاولة بعد قليل.',
        'not_found_title' => 'الصفحة غير موجودة',
        'not_found_body' => 'الرابط الذي فتحته لم يعد موجودًا. ارجع للرئيسية وابدأ من هناك.',
        'empty_invite_title' => 'ابدأ من هنا',
        'empty_invite_body' => 'أضف أول عنصر وسيظهر في هذه الصفحة مباشرة.',
    ],

    'footer' => [
        'powered_by' => 'مبني على :name',
        'contact' => 'تواصل معنا',
    ],

    'contact' => [
        'phone' => 'اتصل بنا',
        'whatsapp' => 'واتساب',
        'instagram' => 'إنستقرام',
        'map' => 'الموقع على الخريطة',
        'address' => 'العنوان',
    ],

    'fields' => [
        'required' => 'حقل مطلوب',
        'optional' => 'اختياري',
    ],

    'a11y' => [
        'primary_nav' => 'التنقّل الرئيسي',
        'club_home' => 'الرئيسية',
        'connection_status' => 'حالة الاتصال',
        'move_up' => 'حرّك لأعلى',
        'move_down' => 'حرّك لأسفل',
        'reorder_keyboard_hint' => 'اسحب المقبض، أو استخدم زرّي التحريك لأعلى وأسفل.',
    ],

    /*
     | Meta descriptions. Written for a search result and a shared link, not for
     | the page itself — one sentence, no exclamation marks (DESIGN.md §9).
     */
    'seo' => [
        'home' => 'جداول تمارين جاهزة ومكتبة تمارين مشروحة، تفتحها من جوالك داخل النادي بدون إنترنت.',
        'programs' => 'كل برامج التمرين المنشورة: المستوى، الهدف، وعدد الأيام في كل برنامج.',
        'exercises' => 'مكتبة التمارين كاملة، مصنّفة حسب العضلة والأداة ومستوى الصعوبة.',
        'about' => 'العنوان وطرق التواصل ومواعيد الوصول إلى النادي.',
        'muscle' => 'تمارين :muscle المتاحة في النادي، مع الأداة ومستوى الصعوبة لكل تمرين.',
        'program_day' => ':day من :program. التمارين والجولات والعدات وأوقات الراحة.',
        'exercise' => ':exercise — العضلة المستهدفة والأداة ومستوى الصعوبة وطريقة الأداء.',
    ],

];
