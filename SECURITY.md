# Security

TukaHR شامل اطلاعات حقوق و دستمزد است و باید به‌عنوان سامانه حساس سازمانی deploy شود.

- production فقط روی HTTPS اجرا شود.
- `.env` و backup دیتابیس خارج از web root باشند.
- API key کاوه‌نگار و credential دیتابیس commit نشوند.
- دسترسی DirectAdmin/SSH بر اساس least privilege باشد.
- backup روزانه و آزمون دوره‌ای restore توصیه می‌شود.
- لاگ‌های Audit از طریق UI برنامه قابل حذف نیستند.
- OTP خام در دیتابیس نگهداری نمی‌شود؛ فقط hash ذخیره می‌شود.
- تغییر داده، Snapshot تأییدشده قبلی را تغییر نمی‌دهد و نسخه جدید نیازمند تأیید متناسب با workflow است.
