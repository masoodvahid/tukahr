# TukaHR

سامانه آنلاین مدیریت، ثبت، کنترل و تأیید حقوق و دستمزد پروژه‌های توکا.

## فناوری

- Laravel 13 / PHP 8.4
- MySQL 8.x یا MariaDB سازگار با Laravel 13
- Blade + Vite
- Tailwind CSS 4 به‌صورت local bundle (بدون CDN)
- فونت Vazirmatn Variable به‌صورت self-hosted از npm package
- PhpSpreadsheet برای Excel
- DomPDF برای PDF
- Kavenegar Verify/Lookup برای OTP

## قابلیت‌های اصلی

- شش نقش: اپراتور پروژه، مدیر پروژه، اپراتور منابع انسانی، مدیر منابع انسانی، مدیرعامل و مدیر مالی
- اتصال نقش‌های پروژه‌ای به پروژه مشخص
- چند اپراتور برای هر پروژه و حداکثر یک مدیر فعال برای هر پروژه
- حداکثر یک مدیر منابع انسانی، مدیرعامل و مدیر مالی فعال
- ورود کاربران با OTP پیامکی
- تعریف داینامیک پروژه‌ها
- ایجاد دوره حقوق شمسی و محاسبه خودکار قفل ویرایش از ابتدای روز ۱۵ ماه بعد
- کپی ساختار/پرسنل ماه قبل و امکان اختیاری کپی مقادیر
- تعریف تعداد نامحدود ستون حقوقی در هر ماه بدون تغییر schema دیتابیس
- Import پرسنل از XLSX/XLS/CSV
- نمایش پروژه‌ای اطلاعات بر مبنای پروژه کاربر
- ویرایش امن سلول‌ها با optimistic version + DB transaction + row locking
- همگام‌سازی زنده سبک با polling تغییرات ذخیره‌شده
- ثبت نهایی اپراتور پروژه
- تأیید مرحله‌ای با OTP و اتصال هر تأیید به Snapshot دقیق داده‌ها
- امکان override آگاهانه برای منابع انسانی در صورت ناقص بودن مراحل پایین‌تر
- Audit Log برای تغییرات و تأییدها
- قفل کامل پس از تأیید مدیر مالی
- خروجی نهایی Excel و PDF
- RTL کامل، Tailwind مینیمال و طیف سفید/خاکستری

## نصب محلی

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
```

در MySQL یک دیتابیس با collation مناسب UTF-8 بسازید و `.env` را تنظیم کنید:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tukahr
DB_USERNAME=...
DB_PASSWORD=...
```

سپس مدیر منابع انسانی اولیه را فقط برای seed اول مشخص کنید:

```env
TUKA_ADMIN_NAME="مدیر منابع انسانی"
TUKA_ADMIN_MOBILE=09123456789
```

و اجرا کنید:

```bash
php artisan migrate --force
php artisan db:seed --force
npm run build
```

پس از ساخت اولین مدیر، بهتر است دو متغیر `TUKA_ADMIN_*` از `.env` حذف شوند.

## کاوه‌نگار

کلید API و نام Patternها فقط در `.env` سرور قرار بگیرند و هرگز commit نشوند:

```env
KAVENEGAR_API_KEY=
KAVENEGAR_OTP_TEMPLATE=tukahr-login
KAVENEGAR_APPROVAL_TEMPLATE=tukahr-approval
OTP_TTL_SECONDS=120
OTP_MAX_ATTEMPTS=5
OTP_RESEND_SECONDS=60
```

الگوی ورود یک token شش‌رقمی دریافت می‌کند. برای تأییدهای حقوق نیز Pattern جداگانه پیشنهاد می‌شود.

## DirectAdmin

Document Root دامنه یا Subdomain باید به پوشه `public/` پروژه اشاره کند. پوشه ریشه Laravel نباید مستقیماً public شود.

حداقل موارد موردنیاز روی هاست:

- PHP 8.3+ (ترجیحاً 8.4)
- Composer 2
- PDO MySQL
- mbstring
- fileinfo
- dom/xml
- zip
- gd

Node.js فقط برای `npm install && npm run build` لازم است. اگر DirectAdmin شما Node ندارد، build فرانت‌اند را در CI یا سیستم توسعه انجام دهید و محتوای `public/build` را deploy کنید.

### دستورات deploy پیشنهادی

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

اگر build روی خود سرور انجام می‌شود:

```bash
npm ci
npm run build
```

## قواعد امنیتی مهم

- هیچ API Key، رمز دیتابیس، SSH key یا credential دیگری داخل GitHub قرار نگیرد.
- کنترل دسترسی همه عملیات در سمت سرور انجام می‌شود؛ پنهان بودن دکمه در UI معیار امنیت نیست.
- تأیید OTP به Snapshot و workflow revision متصل است؛ تغییر داده بعد از صدور OTP کد قبلی را بی‌اعتبار می‌کند.
- بعد از تأیید مالی، مسیرهای ویرایش بسته می‌شوند و فقط خروجی نهایی قابل دریافت است.
- برای محیط production، HTTPS اجباری و backup بازیابی‌پذیر MySQL ضروری است.

## تست

```bash
php artisan test
npm run build
```

GitHub Actions نیز همین مسیر را با PHP 8.4 و MySQL 8.4 اجرا می‌کند.
