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

**الألوان ثابتة ولا تأتي من `settings`** (قرار المستخدم). `#1A2E34` و `#61B5D1` مشتقّان أصلًا من شعار قسورة، وتباينهما مفحوص — لذلك لا توجد أعمدة ألوان في جدول `settings`، ولا تُضِف واحدة دون طلب صريح.

### الأيقونات

`npm run icons` يولّد كل أحجام PWA من مصدر واحد: شعار النادي `public/brand/logo-qaswarah.png` إن وُجد، وإلا علامة عرين. **أيقونة التطبيق يجب أن تكون أسد قسورة** (قرار المستخدم) — تتحقّق تلقائيًا لحظة وضع الملف وإعادة تشغيل الأمر. المواصفات في `public/brand/README.md`.

- علامة المنصة: `public/brand/areen-mark.svg` + مكوّن `<x-brand.mark/>` (يرث `currentColor`).
- شعار النادي: من `settings.logo_path` — **لا يُكتب في الكود**.
- الخط محلي في `public/fonts/` (IBM Plex Sans Arabic، ٨ ملفات woff2، بدون CDN) و `@font-face` في `resources/css/fonts.css`.
- الوضع الداكن هو الأساس.

### الترجمة

- **ممنوع أي نص ثابت في Blade** — كل شيء عبر `__()`.
- `lang/ar/*.php` و `lang/en/*.php` مقسّمة: `common`, `program`, `exercise`, `admin`, `auth`, `pwa`.
- تعدد اللغة **بالـ session** (لا بادئة `/{locale}/`) — `App\Http\Middleware\SetLocale` مضاف لمجموعة `web`، والتبديل عبر `POST /locale/{locale}`.
- اللغات المدعومة واتجاهها في `config/areen.php`.
- حقول المحتوى في DB مزدوجة `_ar` / `_en` عبر `App\Support\Concerns\HasTranslatableAttributes`: النموذج يعلن `protected array $translatable = ['name', 'description'];` فيصير `$exercise->name` متاحًا.
  - يتجاوز `getAttribute()` لا `__get`، لأن Blade و `toArray` و `data_get` كلها تمرّ من هناك.
  - الـ fallback ثابت على `ar` لا على `app.fallback_locale` — لاحقة العمود حرفية (`name_ar`)، وتغيير الإعداد كان سيشير لعمود غير موجود.
  - النص الفارغ `""` يُعامل كترجمة مفقودة، فتظهر العربية.
  - **`toArray()` لا يُخرج المفتاح المترجَم** — يُخرج `name_ar`/`name_en` فقط. مهم عند بناء حمولة الأوفلاين في P5: سطّح الاسم يدويًا أو أضف `attributesToArray()`.
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
program_days     program_id، day_number، title_ar/en، focus_muscle_id، is_rest_day، notes_ar/en
program_exercises program_day_id، exercise_id، sort، sets، reps، rest_seconds، tempo،
                 weight_note، coach_notes_ar/en، superset_group
program_user     program_id، user_id، started_at، is_active
workout_logs     user_id، program_exercise_id، performed_on، set_number،
                 reps_done، weight، is_completed، note،
                 client_uuid (unique) ← لمنع تكرار مزامنة الأوفلاين، synced_at
body_metrics     user_id، measured_on، weight، body_fat، notes
```

فهارس على: `programs.slug`, `programs.access_code`, `exercises.slug`, `workout_logs(user_id, performed_on)`.

### مزالق في الطبقة الحالية — اقرأها قبل P4 و P5

- **`settings` بلا أعمدة ألوان** عمدًا — الألوان ثابتة في `DESIGN.md`. الجدول يحوي `tagline_ar/en` و `email` إضافةً لما سبق.
- **`workout_logs.client_uuid` إلزامي وفريد** ولا يوجد له default في النموذج — المتصفح يولّده قبل الإرسال، والخادم يعمل upsert عليه. أي `create()` بدونه يفشل.
- **`decimal:N` في Eloquent يرجع نصًا** (`'60.50'`) لا float — حوّل قبل أي حساب في رسوم التقدّم. `body_fat` هو `decimal:1` لأن العمود `decimal(4,1)`.
- **`program_user.started_at` عمود `date`** فيرجع من الـ pivot كنص خام لا ككائن Carbon.
- **`program_days.title_*` و `notes_*` و `program_exercises.coach_notes_*` كلها nullable** — بعكس `name_ar` في بقية الجداول. الواجهة يجب أن تحتمل يومًا بلا عنوان.
- **`User::activeProgram()` دالّة لا علاقة** — نادِها بأقواس؛ `$user->activeProgram` يرمي `LogicException`.
- **`difficulty` و `level` أعمدة نصية بلا قيد في قاعدة البيانات** — الـ enum cast هو الحارس الوحيد، وأي كتابة خارج Eloquent ستنفجر عند القراءة.
- النطاقات تستخدم سمة Laravel 13 `#[Scope]` لا بادئة `scopeX` — الاستدعاء نفسه: `Exercise::query()->active()`.
- **`equipment` و `goal` تُخزَّن كـ slugs إنجليزية ثابتة** (`barbell` `dumbbell` `cable` `machine` `bodyweight` · `general-fitness` `hypertrophy` `fat-loss`) والواجهة تترجمها عبر `exercise.equipment.*` و `program.goal.*`. لا تخزّن العربية في العمود.
- مصانع الاختبار تُلحق لاحقة رقمية بالـ slug (`chest-0`) حتى لا تصطدم بالـ seeders في اختبار يستخدم الاثنين؛ `MuscleGroupFactory::named('chest')` يعطي الـ slug النظيف عند الحاجة.

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

- **المرحلة الحالية:** P7 — الصقل والنشر ✅ مكتملة. **المشروع جاهز للنشر.**
- **آخر تحديث:** 2026-07-27

### الحالة النهائية

| | |
|---|---|
| الاختبارات | خضراء بالكامل — انظر `php artisan test` |
| `vendor/bin/pint --test` | نظيف |
| `npm run build` | ينجح، والـ worker على `public/sw.js` |
| نص عربي ثابت في Blade | لا شيء — مفروض باختبار |
| أدوات اتجاه فيزيائي في الواجهات | لا شيء — مفروض باختبار |

**ما أضافته P7:**

- **الاكتشاف والمشاركة:** `App\Support\Seo` مصدر واحد للعنوان والوصف و canonical و `og:`/`twitter:` و `hreflang`. الصفحات تعرّف نفسها بـ `@push('head') <x-seo.page …/>` والتخطيط يرسم `<x-seo.meta>` مرة واحدة. `robots.txt` و `sitemap.xml` من Laravel (`routes/seo.php`)، وصورة مشاركة 1200×630 تُرسم من هوية النادي بـ Chromium وتُخزَّن ببصمة.
- **الوصولية:** حلقة تركيز مرئية فعلًا، طريق بلوحة المفاتيح لإعادة الترتيب بالسحب، ترتيب عناوين بلا قفزات، وتباين ألوان مُقاس لا مُدّعى.
- **الأداء:** ملف خط واحد يُحمَّل مسبقًا (مُقاس)، و `favicon` صغير بدل ٥٩ ك.ب.
- **CI:** `pint` + `pest` + `build` + فحص شرطَي الـ service worker.

**درس مدفوع الثمن (P1):** لا تخزّن كائن Eloquent في الكاش. أي مخزن يسلسل (file/database/redis) يعيده `__PHP_Incomplete_Class` بعد أول تسخين. `Setting::current()` يخزّن مصفوفة السمات ويعيد بناء النموذج منها.

**درس مدفوع الثمن (P7):** `app()->setLocale()` يكتب اللغة الحالية في `config('app.locale')`. فقراءتها لمعرفة «اللغة الافتراضية» تجعلها تعني «لغة هذا القارئ»، وكانت روابط `hreflang` تنقلب حسب الزائر. اللغة الافتراضية تُقرأ من `app.fallback_locale` — وهو الإعداد الوحيد الذي لا يُعاد كتابته أثناء الطلب.

### قرارات معمارية

- الاسم «عرين» معتمد · جوال أولًا + PWA متطلّب أساسي · بيانات النادي من `settings` لا من الكود.
- قاعدة البيانات **MySQL** (اختيار المستخدم) — `.env.example` مضبوط عليها.
- الدومين `areen.on-forge.com` — الاستضافة Laravel Forge.
- لوحة الأدمن **تُبنى يدويًا بـ Livewire 4** (لا Filament).
- تعدد اللغة **بالـ session** لا ببادئة مسار. وأُضيف في P7 معامل `?lang=` **للزواحف فقط** — بدونه لا يمكن أن يكون لـ `hreflang` عنوانان مختلفان أصلًا. المبدّل في الهيدر يبقى POST.
- علامة عرين **منفصلة عن شعار النادي** — العلامة في الكود، شعار النادي في `settings`.
- `manifest` ديناميكي من Laravel (مصدر واحد)، و `manifest: false` في إعداد Vite PWA.
- الـ service worker يُنقل إلى `public/sw.js` بعد البناء ليكون نطاقه `/`.
- **`robots.txt` و `sitemap.xml` من Laravel لا كملفات ثابتة** — حتى يذكر سطر `Sitemap:` الدومين الحقيقي. أي ملف ثابت في `public/` سيقدّمه خادم الويب قبل المسار بصمت، ولذلك يوجد اختبار يمنع عودته.
- **Chromium بدل حزمة صور** — يولّد PDF وصورة المشاركة وكل الأيقونات. غيابه لا يعطّل الموقع: كل مسار يتراجع بأمانة.
- **لا تسجيل حسابات** — `php artisan areen:create-admin` هو الباب الوحيد إلى نشر جديد.

### قياسات P7 (مُقاسة لا مُقدَّرة)

- **التباين، مقيسًا على DOM المعروض لا على جدول رموز:** ٩٩ تركيبة لون/خلفية/حجم على الصفحات العامة الثماني ⇐ **صفر أقل من حد AA** (أضيق تركيبة 5.36:1 لحد 4.5). و٦٤ تركيبة على اللوحة ⇐ ثلاث حالات حدّية موثّقة في «مفتوح/معلّق». تحقّق ذلك بعد نقل كل نص `ink-400`/`ink-500` إلى `ink-300`.
- **حلقة التركيز:** `brand-400/40` وحدها = **2.27:1** على `ink-950` و **2.06:1** على `ink-800` (دون حد 3:1 لـ SC 2.4.11). بإضافة نواة صلبة `brand-400`: **7.27:1** و **5.22:1**.
- **الخطوط:** بتحميل مسبق لوزنين ⇐ FCP/LCP 1588ms · CLS 0.0005. بلا تحميل مسبق ⇐ 1140ms · CLS 0.0173. **بوزن واحد (400 عربي) ⇐ 1144ms · CLS 0.0027** — أفضل الثلاثة، وهو المعتمد. (Chromium بلا واجهة، 390×844، خنق 1.6Mbit/150ms، وسيط ٧ محاولات.)
- **الاستعلامات لكل صفحة عامة:** 3–9، بلا N+1.
- **`favicon.png`:** من 60,356 بايت إلى 3,981.

### مفتوح / معلّق

- **`Lighthouse` غير مثبَّت في هذه البيئة** — لذلك لا يوجد رقم «الوصولية 90» أو «الأداء 90» في هذا الملف. الأرقام أعلاه مُقاسة مباشرةً من المتصفح. شغّل Lighthouse على `areen.on-forge.com` بعد النشر لتثبيت الدرجة.
- **حدود الحقول:** `ink-700` على `ink-900` = **1.45:1**، دون حد 3:1 لـ WCAG 1.4.11. أُبقيت كما هي لأن DESIGN.md §4 ينصّ عليها، ولأن كل حقل يحمل `<label>` مرئيًا وحالة التركيز تجعل الحد `brand-400`. قرار واعٍ لا سهو.
- **حالتان على حافة الحد، مقيستان على DOM اللوحة، أُبقيتا عمدًا:**
  - نجمة «حقل مطلوب» `danger` فوق بطاقة `ink-800` = **4.38:1** (الحد 4.5). النجمة `aria-hidden` ومعناها محمول في نص `sr-only` بجانبها، وتغيير اللون يعني اختراع درجة ليست في DESIGN.md.
  - نص الزر الأساسي `brand-950` فوق `brand-500` **أثناء التحويم** = **4.36:1**. الحالة الساكنة (`brand-400`) = 6.16:1 وتمرّ. DESIGN.md §5 ينصّ على `brand-500` للتحويم، ولا وظيفة في المنصة تعتمد على hover أصلًا.
- **شعار النادي 59 ك.ب** يُقدَّم بحجمه الكامل في الهيدر (40px). لم يُمسّ لأنه أصل النادي ويأتي من `settings`؛ الـ service worker يخزّنه بعد أول زيارة.
- لا يوجد خادم MySQL في بيئة التطوير السحابية هذه، لذلك `.env` المحلي يستخدم SQLite للتشغيل السريع بينما `.env.example` (وهو ما يُنشر) على MySQL. الاختبارات تعمل على SQLite in-memory عبر `phpunit.xml`.
