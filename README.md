# عرين — Areen

منصة **جوال أولًا** تُثبَّت كتطبيق (**PWA**) لعرض وإدارة جداول التمارين: واجهة عامة، مكتبة تمارين، برامج خاصة برابط سري، لوحة أدمن، وحسابات متدربين مع تتبّع التقدّم.

عربي (افتراضي، RTL) وإنجليزي، **وتعمل داخل النادي بدون إنترنت**.

المنصة منتج مستقل: كل ما يخص النادي (الاسم، الشعار، العنوان، وسائل التواصل) يأتي من جدول `settings` — لا من الكود.

---

## المتطلّبات

| المكوّن | الإصدار |
|---|---|
| PHP | 8.3+ (مبني ومُختبر على 8.4) |
| Composer | 2.x |
| Node | 22+ |
| MySQL | 8.x |

---

## التشغيل من الصفر

```bash
git clone <repo> areen && cd areen

composer install
npm install

cp .env.example .env
php artisan key:generate
```

أنشئ قاعدة بيانات باسم `areen` وحدّث `DB_USERNAME` و `DB_PASSWORD` في `.env`، ثم:

```bash
php artisan migrate --seed

npm run build          # أو: npm run dev للتطوير
php artisan serve
```

افتح <http://127.0.0.1:8000>.

> **تشغيل سريع بدون MySQL:** لو أردت تجربة المشروع بلا خادم قاعدة بيانات، غيّر في `.env` فقط:
> `DB_CONNECTION=sqlite` و `DB_DATABASE=database/database.sqlite` و `SESSION_DRIVER=file` و `CACHE_STORE=file`،
> ثم `touch database/database.sqlite && php artisan migrate`.
> الإعداد المنشور يبقى MySQL كما في `.env.example`.

---

## الأوامر

```bash
npm run dev            # خادم Vite للتطوير
npm run build          # بناء الإنتاج + نقل الـ service worker إلى public/sw.js
php artisan test       # Pest
vendor/bin/pint        # تنسيق الكود
vendor/bin/pint --test # فحص التنسيق بدون تعديل
```

---

## بنية مهمة

| المسار | الدور |
|---|---|
| `resources/css/app.css` | كتلة `@theme` — كل ألوان وأنصاف أقطار النظام (Tailwind 4، بلا `tailwind.config.js`) |
| `resources/css/fonts.css` | `@font-face` لخط IBM Plex Sans Arabic المستضاف محليًا |
| `public/fonts/` | ملفات woff2 — بدون CDN |
| `public/brand/` | علامة **عرين** وأيقونات PWA |
| `config/areen.php` | اللغات المدعومة واتجاهها، الوحدة، أصول العلامة |
| `lang/ar` · `lang/en` | كل نصوص الواجهة — لا نص ثابت في Blade |
| `scripts/lift-service-worker.mjs` | ينقل الـ worker من `public/build` إلى `public/` بعد البناء |
| `DESIGN.md` | نظام التصميم — مرجع مُلزِم |
| `CLAUDE.md` | ذاكرة المشروع وقرارات المعمارية |

---

## ملاحظتان تكسران التطبيق لو أُهملتا

**١. الـ service worker يجب أن يبقى على `/sw.js`.**
Vite يبني إلى `public/build`، و worker موجود على `/build/sw.js` نطاقه `/build/` فقط — أي أن كل صفحات الموقع تبقى بلا تحكّم ولا يعمل الأوفلاين إطلاقًا. لذلك `npm run build` يشغّل `scripts/lift-service-worker.mjs` بعد البناء. **لا تشغّل `vite build` وحده.**

**٢. لا تخزّن طلبات Livewire في الـ service worker.**
Livewire 4 يشتقّ مسار نقطة النهاية من `APP_KEY`، فيصبح `/livewire-<8 hex>/update` لا `/livewire/update`. و `APP_KEY` يختلف بين جهاز البناء والخادم، لذلك المطابقة في `vite.config.js` تتم بالنمط `/^\/livewire[-/]/`. تحويلها إلى مسار حرفي يعني تخزين ردود Livewire — وهذا يكسر التطبيق **بصمت**.

---

## الاختبارات

```bash
php artisan test
```

الاختبارات تعمل على SQLite in-memory (مضبوط في `phpunit.xml`) فلا تحتاج خادم قاعدة بيانات.

---

## النشر

الدومين المستهدف: `areen.on-forge.com` على Laravel Forge.

خطوات النشر على الخادم:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

> `npm run build` يجب أن يُشغَّل على الخادم أو تُرفع مخرجاته، لأن `public/build` و `public/sw.js` غير متتبَّعين في git.
