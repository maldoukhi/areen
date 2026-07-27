# CLAUDE.md — عرين | Areen

ملف ذاكرة المشروع لـ Claude Code. اقرأه في بداية كل جلسة، وحدّثه في نهاية كل مرحلة.

---

## 1. المشروع

**عرين (Areen)** — منصة **جوال أولًا** تُثبَّت كتطبيق (**PWA**) لعرض وإدارة جداول التمارين: واجهة عامة، مكتبة تمارين، برامج خاصة برابط سري، لوحة أدمن، وحسابات متدربين مع تتبّع التقدّم. ثنائي اللغة **عربي (افتراضي، RTL) + إنجليزي**. **تعمل بدون إنترنت داخل النادي.**

العميل الأول: نادي **قسورة الأزرق**. المنصة منتج مستقل قد يُباع لأندية أخرى — لذلك **كل بيانات النادي تأتي من جدول `settings`، وممنوع كتابتها في الكود**.

- repo `areen` · package `qaswarah/areen` · DB `areen` · `APP_NAME="عرين"`
- الدومين: `areen.on-forge.com` (Laravel Forge)

---

## 2. الستاك — الإصدارات المثبَّتة فعليًا

| المكوّن | الإصدار |
|---|---|
| PHP | 8.4.19 |
| Laravel | 13.22 |
| Livewire | 4.3.3 (Volt مدمج — لا تثبّت Volt منفصلًا) |
| Tailwind CSS | 4.3.3 — CSS-first، **لا يوجد `tailwind.config.js`** |
| Build | Vite 8.1.5 + `@tailwindcss/vite` |
| PWA | `vite-plugin-pwa` 1.3.0 + Workbox |
| الاختبارات | Pest 4.7.5 |
| التنسيق | Laravel Pint 1.29.3 |
| توثيق مطابق للإصدار | Laravel Boost 2.4.13 |

> **قاعدة:** أي API في Livewire 4 / Laravel 13 لست متأكدًا منه ١٠٠٪ → راجع Boost أو التوثيق الرسمي قبل الكتابة. لا تخمّن.
> إرشادات Livewire 4 المرفقة مع Boost موجودة في `vendor/laravel/boost/.ai/livewire/4/`.

---

## 3. اتفاقيات الكود

### Livewire 4

- الافتراضي **Single File Components** في `resources/views/components/` (أسماء الملفات تبدأ بـ ⚡؛ الإعداد في `config/livewire.php` تحت `make_command.emoji`).
- صفحات كاملة: `php artisan make:livewire pages::name` → `resources/views/pages/⚡name.blade.php`.
- استخدم `@island` لعزل الأجزاء الثقيلة (جداول الأدمن، القوائم المفلترة، الرسوم).
- التوجيه: `Route::livewire('/path', 'pages::name')`.
- `component_layout` في `config/livewire.php` = `layouts::app`.
- كمبوننت من ~150 سطر فأكثر أو يحتاج حقن تبعيات → انقله لصيغة class-based (`--class`).
- `wire:key` إلزامي في كل `@foreach`.

### الجوال و PWA

- **جوال أولًا فعليًا**: صمّم شاشة 360px أولًا ثم وسّع.
- منطقة لمس ≥ 44px، والإجراءات الأساسية في النصف السفلي (متناول الإبهام).
- كل عنصر ثابت يستخدم `env(safe-area-inset-*)` مع `viewport-fit=cover` — الأداتان `safe-pt` و `safe-pb` جاهزتان في `app.css`.
- لا وظيفة تعتمد على `:hover`.
- شريط تنقّل سفلي في وضع `display-mode: standalone` فقط.
- Screen Wake Lock أثناء التمرين + Vibration API عند انتهاء مؤقّت الراحة.
- **⚠️ الـ service worker يتجاوز كل طلبات Livewire وكل POST.**
  Livewire 4 يشتقّ مساره من `APP_KEY` (`/livewire-<8 hex>/update` وليس `/livewire/update`)، و `APP_KEY` يختلف بين جهاز البناء والخادم — لذلك المطابقة في `vite.config.js` بالنمط `/^\/livewire[-/]/` لا بمسار حرفي. **لا تحوّلها إلى نص ثابت.**
- الأوفلاين: precache لأيام برنامج المتدرب وصور تمارينه + صفحة `/offline` بهوية النادي.
- تسجيل الجولات أوفلاين في IndexedDB بـ `client_uuid`، ومزامنة بـ Background Sync، والخادم يعمل upsert على `client_uuid` لا insert.
- بانر تثبيت مخصص عبر `beforeinstallprompt`؛ iOS لا يطلقه → تعليمات «شارك ← أضف إلى الشاشة الرئيسية».
- عند إصدار جديد: شريط «يتوفر تحديث» لا تحديث صامت (`registerType: 'prompt'`، والحدث `areen:update-available`).

### بنية الـ service worker (لا تكسرها)

- Vite يبني إلى `public/build`، لكن الـ worker **يجب** أن يكون على `/sw.js` وإلا كان نطاقه `/build/` فقط ولم يتحكم في أي صفحة.
- لذلك: `inlineWorkboxRuntime: true` (ملف واحد مكتفٍ بذاته) + `scripts/lift-service-worker.mjs` ينقله بعد البناء.
- و `modifyURLPrefix: { '': '/build/' }` لأن مسارات الـ precache تُكتب نسبيةً للـ worker.
- `npm run build` = `vite build && node scripts/lift-service-worker.mjs`. لا تشغّل `vite build` وحده.

### Tailwind 4

- كل الإعدادات داخل `resources/css/app.css` عبر `@import "tailwindcss";` و `@theme { … }`.
- **استخدم الخصائص المنطقية فقط**: `ms-` `me-` `ps-` `pe-` `start-` `end-` `text-start` `text-end`.
  ممنوع `ml-` `mr-` `left-` `right-` (تكسر RTL).

### Design tokens

المصدر الوحيد `DESIGN.md`، ومطبَّق في `resources/css/app.css`. لا تخترع لونًا أو نصف قطر خارجه.

- علامة المنصة: `public/brand/areen-mark.svg` + مكوّن `<x-brand.mark/>` (يرث `currentColor`).
- شعار النادي: من `settings.logo_path` — **لا يُكتب في الكود**.
- الخط محلي في `public/fonts/` (IBM Plex Sans Arabic، ٨ ملفات woff2، بدون CDN) و `@font-face` في `resources/css/fonts.css`.
- الوضع الداكن هو الأساس.

### الترجمة

- **ممنوع أي نص ثابت في Blade** — كل شيء عبر `__()`.
- `lang/ar/*.php` و `lang/en/*.php` مقسّمة: `common`, `program`, `exercise`, `admin`, `auth`, `pwa`.
- تعدد اللغة **بالـ session** (لا بادئة `/{locale}/`) — `App\Http\Middleware\SetLocale` مضاف لمجموعة `web`، والتبديل عبر `POST /locale/{locale}`.
- اللغات المدعومة واتجاهها في `config/areen.php`.
- حقول المحتوى في DB مزدوجة `_ar` / `_en` مع accessor يرجع حسب `app()->getLocale()` و fallback للعربي.
- `<html dir>` و `lang` مشتقّان من اللغة الحالية.
- `manifest` ديناميكي عبر `ManifestController` على `/manifest.json` ليتبع اللغة — **لا نولّد manifest ثابتًا من Vite** (مصدر واحد للحقيقة).

### عام

- `declare(strict_types=1)` في كل ملف PHP (يفرضه Pint).
- `FormRequest` لكل إدخال، `Policy` لكل صلاحية، لا منطق أعمال داخل الـ Controller.
- كل نموذج له Factory + Seeder ببيانات **عربية واقعية**.
- `softDeletes` للبرامج والتمارين.

---

## 4. مخطط قاعدة البيانات

```
settings         (Singleton) هوية النادي: الاسم ar/en، الوصف، العنوان، المدينة،
                 رابط الخريطة، الهاتف، واتساب، إنستقرام، الشعار، الألوان
users            role: admin | coach | trainee، locale، phone، is_active
muscle_groups    name_ar/en، slug، icon، sort
exercises        name_ar/en، slug، muscle_group_id، secondary_muscles(json)،
                 equipment، difficulty، youtube_url، media_path، description_ar/en
programs         name_ar/en، slug، description_ar/en، days_count، level، goal،
                 cover_path، is_public، is_featured، access_code(unique)، published_at
program_days     program_id، day_number، title_ar/en، focus_muscle_id، is_rest_day، notes
program_exercises program_day_id، exercise_id، sort، sets، reps، rest_seconds، tempo،
                 weight_note، coach_notes_ar/en، superset_group
program_user     program_id، user_id، started_at، is_active
workout_logs     user_id، program_exercise_id، performed_on، set_number،
                 reps_done، weight، is_completed، note،
                 client_uuid (unique) ← لمنع تكرار مزامنة الأوفلاين، synced_at
body_metrics     user_id، measured_on، weight، body_fat، notes
```

فهارس على: `programs.slug`, `programs.access_code`, `exercises.slug`, `workout_logs(user_id, performed_on)`.

---

## 5. خريطة المسارات

**عامة:** `/` · `/programs` · `/programs/{slug}` · `/programs/{slug}/day/{n}` · `/exercises` · `/exercises/{slug}` · `/muscles/{slug}` · `/p/{access_code}` · `/programs/{slug}/print` · `/programs/{slug}/pdf` · `/about` · `/offline`
**متدرب:** `/dashboard` · `/dashboard/log` · `/dashboard/progress`
**أدمن (`/admin`):** لوحة · البرامج + بانِي الأيام · التمارين · العضلات · المتدربون · الإعدادات
**نظام:** `/manifest.json` · `/locale/{locale}` (POST) · `/up`

---

## 6. أسلوب العمل مع المستخدم

1. **اسأل عبر `AskUserQuestion`** — لا أسئلة كنص عادي. دفعات ٢–٤ أسئلة، ٢–٤ خيارات.
2. **بعد كل خطوة: سطر واحد** `✅ <ما تم>`. لا شرح، لا سرد ملفات، لا إعادة لصق كود.
3. **الملخص النهائي فقط** في نهاية المرحلة، ٦ أسطر كحد أقصى: ما تم / كيف أشغّله / قرارات مطلوبة.
4. لا تنتقل لمرحلة جديدة قبل موافقة صريحة.
5. لا تثبّت أي حزمة غير مذكورة هنا بدون سؤال.

---

## 7. الأوامر

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
npm run dev            # التطوير
npm run build          # الإنتاج (يبني + ينقل الـ service worker)
php artisan test       # Pest
vendor/bin/pint        # التنسيق
```

---

## 8. Definition of Done (قبل تسليم أي مرحلة)

- [ ] `php artisan test` أخضر
- [ ] `vendor/bin/pint --test` نظيف
- [ ] `npm run build` بدون أخطاء
- [ ] لا نص ثابت خارج ملفات الترجمة
- [ ] الصفحة سليمة في RTL و LTR، وعلى شاشة 360px عرضًا
- [ ] لا شيء ثابت بدون `safe-area-inset`، ولا وظيفة تعتمد على hover
- [ ] الـ service worker لا يزال يتجاوز طلبات Livewire (`/^\/livewire[-/]/`)
- [ ] هذا الملف محدَّث (قسم ٩)

---

## 9. الحالة الحالية

- **المرحلة الحالية:** P0 — الإعداد ✅ مكتملة. التالي: P1 (الهوية والبيانات).
- **آخر تحديث:** 2026-07-27

### قرارات معمارية

- الاسم «عرين» معتمد · جوال أولًا + PWA متطلّب أساسي · بيانات النادي من `settings` لا من الكود.
- قاعدة البيانات **MySQL** (اختيار المستخدم) — `.env.example` مضبوط عليها.
- الدومين `areen.on-forge.com` — الاستضافة Laravel Forge.
- لوحة الأدمن **تُبنى يدويًا بـ Livewire 4** (لا Filament).
- تعدد اللغة **بالـ session** لا ببادئة مسار.
- علامة عرين **منفصلة عن شعار النادي** — العلامة في الكود، شعار النادي في `settings`.
- `manifest` ديناميكي من Laravel (مصدر واحد)، و `manifest: false` في إعداد Vite PWA.
- الـ service worker يُنقل إلى `public/sw.js` بعد البناء ليكون نطاقه `/`.

### مفتوح / معلّق

- **`public/brand/logo-qaswarah.png` غير موجود** — أُرسل كصورة في المحادثة فقط. يحتاج رفعه للمستودع ليُستخدم كقيمة ابتدائية في seeder الإعدادات (P1). لا يعطّل شيئًا: الواجهة تستخدم علامة عرين حتى يُرفع شعار النادي من لوحة الأدمن.
- لا يوجد خادم MySQL في بيئة التطوير السحابية هذه، لذلك `.env` المحلي يستخدم SQLite للتشغيل السريع بينما `.env.example` (وهو ما يُنشر) على MySQL. الاختبارات تعمل على SQLite in-memory عبر `phpunit.xml`.
- أسئلة P1 لم تُجب بعد: اسم النادي بالإنجليزي، العنوان، المدينة، رابط الخريطة، وسائل التواصل، الوحدة (kg ⭐).
