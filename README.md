# عرين — Areen

منصة **جوال أولًا** تُثبَّت كتطبيق (**PWA**) لعرض وإدارة جداول التمارين: واجهة عامة، مكتبة تمارين، برامج خاصة برابط سري، لوحة أدمن، وحسابات متدربين مع تتبّع التقدّم.

عربي (افتراضي، RTL) وإنجليزي، **وتعمل داخل النادي بدون إنترنت**.

المنصة منتج مستقل: كل ما يخص النادي (الاسم، الشعار، العنوان، وسائل التواصل) يأتي من جدول `settings` — لا من الكود. تسليمها لنادٍ آخر لا يحتاج تعديل سطر واحد.

---

## المتطلّبات

| المكوّن | الإصدار | ملاحظة |
|---|---|---|
| PHP | 8.3+ (مبني ومُختبر على 8.4) | امتدادات: `dom curl libxml mbstring zip pcntl pdo intl` |
| Composer | 2.x | |
| Node | 22+ | للبناء فقط، لا يعمل وقت التشغيل |
| MySQL | 8.x | الإعداد المنشور. توجد مخرجة SQLite للتجربة السريعة أدناه |
| Chromium | أي إصدار حديث | **اختياري** — لتوليد PDF وصورة المشاركة وأيقونات PWA. غيابه لا يعطّل الموقع |

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
npm run build
php artisan serve
```

افتح <http://127.0.0.1:8000>.

### تشغيل سريع بدون MySQL

لتجربة المشروع بلا خادم قاعدة بيانات، غيّر في `.env`:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
```

ثم:

```bash
touch database/database.sqlite
php artisan migrate --seed
```

**الإعداد المنشور يبقى MySQL** كما في `.env.example`. الاختبارات تعمل على SQLite in-memory (مضبوط في `phpunit.xml`) فلا تحتاج خادمًا أصلًا.

---

## الدخول لأول مرة — لا يوجد تسجيل

**المنصة بلا صفحة تسجيل حساب، بالتصميم.** المتدربون يُنشَؤون من لوحة الأدمن، والأدمن الأول يُنشأ من سطر الأوامر. هذا هو الباب الوحيد إلى نشر جديد:

```bash
php artisan areen:create-admin
```

يسأل الأمر عن الاسم والبريد وكلمة المرور تفاعليًا، أو مرّرها مباشرة:

```bash
php artisan areen:create-admin --name="مدير النادي" --email=admin@example.com --generate
```

`--generate` يولّد كلمة مرور قوية ويطبعها **مرة واحدة**. الخيارات: `--name` `--email` `--password` `--generate` `--role=admin|coach`.

ثم ادخل من `/admin/login`. **بدون تشغيل هذا الأمر لا يوجد أي طريق إلى اللوحة** — لا صفحة تسجيل، ولا حساب افتراضي، ولا كلمة مرور في الـ seeder.

---

## شعار النادي والأيقونات

ضع شعار النادي في `public/brand/logo-qaswarah.png` ثم:

```bash
npm run icons
```

يولّد الأمر من ذلك الملف الواحد: أيقونات PWA، أيقونة iOS، شاشة الإقلاع، **و `public/favicon.png`**. إن لم يوجد شعار النادي فالمصدر هو علامة عرين `public/brand/areen-mark.svg`.

يحتاج الأمر Chromium — يبحث عنه في `/opt/pw-browsers/…` أو في `CHROMIUM_PATH`.

> **لماذا الأيقونات كلها 512؟** Chromium بلا واجهة يرفض تخطيط صفحة أصغر من ~500px ويضغط النتيجة، فتخرج الأيقونة الصغيرة مشوّهة. لذلك تُرسم كلها عند 512 ويُترك التصغير للنظام. الاستثناء `favicon.png`: يُرسم داخل الصفحة على `<canvas>` بحجم 48 وتُقرأ بايتاته عبر `--dump-dom` — لأن أيقونة التبويب يجب أن تكون **صغيرة** (٤ ك.ب لا ٥٩).

**الشعار في الواجهة لا يأتي من هذا المسار** بل من `settings.logo_path` — يُرفع من لوحة الأدمن. الملف أعلاه قيمة ابتدائية للـ seeder فقط.

---

## الأوامر

```bash
npm run dev             # خادم Vite للتطوير
npm run build           # بناء الإنتاج + نقل الـ service worker إلى public/sw.js
npm run icons           # توليد الأيقونات والـ favicon من شعار النادي
php artisan test        # Pest
vendor/bin/pint         # تنسيق الكود
vendor/bin/pint --test  # فحص التنسيق بدون تعديل
php artisan areen:create-admin
```

---

## بنية مهمة

| المسار | الدور |
|---|---|
| `resources/css/app.css` | كتلة `@theme` — كل ألوان وأنصاف أقطار النظام (Tailwind 4، بلا `tailwind.config.js`) |
| `resources/css/fonts.css` | `@font-face` لخط IBM Plex Sans Arabic المستضاف محليًا |
| `public/fonts/` | ثمانية ملفات woff2 — بدون CDN. وزن 400 العربي وحده يُحمَّل مسبقًا (انظر التعليق في `layouts/app.blade.php`) |
| `public/brand/` | علامة **عرين**، شعار النادي الابتدائي، وأيقونات PWA |
| `config/areen.php` | اللغات المدعومة واتجاهها، الوحدة، أصول العلامة |
| `lang/ar` · `lang/en` | كل نصوص الواجهة — لا نص ثابت في Blade |
| `routes/web.php` · `admin.php` · `trainee.php` · `print.php` · `seo.php` | خريطة المسارات، مقسّمة بالسطح. الثلاثة الأخيرة تُسجَّل من `AppServiceProvider::boot()` |
| `app/Support/Seo.php` | العناوين والوصف والـ canonical و `hreflang` — مصدر واحد |
| `app/Support/OpenGraphImage.php` | صورة المشاركة 1200×630 من هوية النادي، مرسومة بـ Chromium ومخزَّنة ببصمة |
| `app/Support/PdfRenderer.php` | تحويل HTML إلى PDF عبر Chromium مباشرة — بلا Node ولا Puppeteer |
| `scripts/lift-service-worker.mjs` | ينقل الـ worker من `public/build` إلى `public/` بعد البناء |
| `DESIGN.md` | نظام التصميم — مرجع مُلزِم |
| `CLAUDE.md` | ذاكرة المشروع وقرارات المعمارية |

---

## ⚠️ ملاحظتان تكسران التطبيق لو أُهملتا

كلتاهما مفحوصتان في `.github/workflows/tests.yml` بعد كل بناء.

### ١. الـ service worker يجب أن ينتهي على `public/sw.js` — لا `public/build/sw.js`

Vite يبني إلى `public/build`. لو بقي الـ worker هناك فنطاقه `/build/` فقط، أي أنه **لا يتحكم في أي صفحة من الموقع**: لا precache، ولا صفحة `/offline`، ولا مزامنة خلفية، ولا شريط تحديث. والأسوأ أن التسجيل ينجح ولا يظهر أي خطأ — التطبيق يبدو سليمًا وقد فقد كل عمله أوفلاين.

لذلك `npm run build` = `vite build && node scripts/lift-service-worker.mjs`، ومعه في `vite.config.js`:

- `inlineWorkboxRuntime: true` — ملف واحد مكتفٍ بذاته، فنقله آمن.
- `modifyURLPrefix: { '': '/build/' }` — مسارات الـ precache تُكتب نسبيةً للـ worker، وبدون هذا تُحلّ مجلدًا أعلى وتُرجع 404.

**لا تشغّل `vite build` وحده.** التحقق:

```bash
npm run build
test -f public/sw.js && echo SW-AT-ROOT
test ! -f public/build/sw.js && echo NOT-IN-BUILD
```

### ٢. تجاوز Livewire في الـ worker يُطابَق بالشكل — لا بمسار حرفي

Livewire 4 يشتقّ مسار نقطة النهاية من `APP_KEY`، فيصبح `/livewire-<8 hex>/update` لا `/livewire/update`. و `APP_KEY` **يختلف بين جهاز البناء والخادم**، فأي مسار حرفي يُبنى محليًا لن يطابق شيئًا في الإنتاج.

لذلك القاعدة في `vite.config.js` هي:

```js
urlPattern: ({ url, request }) =>
    request.method !== 'GET' || /^\/livewire[-/]/.test(url.pathname),
handler: 'NetworkOnly',
```

تحويلها إلى نص ثابت يعني تخزين ردود Livewire في الكاش: النموذج يعود لحالة قديمة، والنماذج تُرسل مرتين، والجولة المسجَّلة تختفي — **بصمت، وفي الإنتاج فقط**. التحقق:

```bash
rg -o 'livewire\[-/\]' public/sw.js   # يجب أن يطبع livewire[-/]
rg -o 'url:"[^"]*"' public/sw.js      # يجب ألا يظهر فيها أي مسار livewire
```

---

## الاختبارات

```bash
php artisan test
```

تعمل على SQLite in-memory، فلا تحتاج خادم قاعدة بيانات. من المفيد معرفته:

- `tests/Feature/Accessibility/ViewConventionsTest.php` يمنع نصًا عربيًا ثابتًا في Blade، ويمنع أدوات الاتجاه الفيزيائي (`ml-` `mr-` `left-` `right-`). يستخدم **`rg`** لا `grep -P`: الأخير على هذه البيئة يرفض `\x{0600}` ويخرج بالرمز 2، فيقرأه فحص ساذج كنجاح. الاختبار يتحقق من الأداة نفسها أولًا.
- `tests/Feature/Seo/SitemapTest.php` يتحقق صراحةً أن **البرنامج الخاص لا يظهر في خريطة الموقع** — لا اسمه ولا رمزه.
- `tests/Feature/Accessibility/KeyboardReachTest.php` يتحقق أن إعادة الترتيب بالسحب في اللوحة لها طريق بلوحة المفاتيح.

---

## النشر — Laravel Forge

الدومين المستهدف: `areen.on-forge.com`.

### إعدادات البيئة على الخادم

```dotenv
APP_ENV=production
APP_DEBUG=false          # مهم للأداء: Livewire يخدم النسخة المصغَّرة فقط عندما يكون false
APP_URL=https://areen.on-forge.com

DB_CONNECTION=mysql
DB_DATABASE=areen

CHROMIUM_PATH=/usr/bin/chromium-browser   # اختياري — لـ PDF وصورة المشاركة
```

### سكربت النشر

```bash
cd /home/forge/areen.on-forge.com

git pull origin $FORGE_SITE_BRANCH

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build            # يبني وينقل الـ service worker إلى public/sw.js

# الفحصان اللذان لا يجوز تخطّيهما
test -f public/sw.js || { echo 'service worker is not at the site root'; exit 1; }
grep -q 'livewire\[-/\]' public/sw.js || { echo 'the Livewire bypass is not a shape match'; exit 1; }

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan storage:link         # مرة واحدة — لشعار النادي المرفوع من اللوحة

( flock -w 10 9 || exit 1; sudo -S service php8.4-fpm reload ) 9>/tmp/fpmlock
```

بعد أول نشر، مرة واحدة:

```bash
php artisan areen:create-admin
```

### ملاحظات نشر

- `public/build` و `public/sw.js` **غير متتبَّعين في git** — لذلك `npm run build` إلزامي على الخادم.
- `php artisan config:cache` يعطّل `env()` خارج ملفات الإعداد. كل قراءات البيئة في هذا المشروع تمرّ من `config/`.
- الأصول ثابتة ببصمة في اسم الملف، فيمكن تخزينها سنة كاملة. `sw.js` و `manifest.json` **يجب ألا يُخزَّنا** — وإلا لن يصل أي تحديث للمثبِّتين.
- `robots.txt` و `sitemap.xml` **يخدمهما Laravel لا ملفات ثابتة**، حتى يذكر السطر `Sitemap:` الدومين الحقيقي. لا تُعِد ملفًا ثابتًا إلى `public/` — خادم الويب سيقدّمه قبل المسار بصمت.
