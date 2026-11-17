# باك إند منصة معمل الحلويات

Laravel 11 REST API لتطبيق Flutter + لوحة Filament عربية RTL.

## المتطلبات

- PHP 8.2+
- Composer
- MySQL 8 (أو SQLite للتطوير)
- Redis (للطوابير والكاش في الإنتاج)

## التشغيل السريع

```bash
cd backend
cp .env.example .env
php artisan key:generate
# عدّل إعدادات قاعدة البيانات في .env
php artisan migrate --seed
php artisan serve
```

### لوحة التحكم (Filament)

افتح: [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)

- المدير: `admin@sweets.ly` / `password`
- موظفون إضافيون (نفس كلمة المرور): `sales@` · `orders@` · `accountant@` · `content@` · `purchasing@` `@sweets.ly`
- عملاء تجريبيون: هواتف `0912001001`… وكلمة المرور `password`
- كوبونات: `WELCOME10` · `FIXED15`

إعادة تعبئة البيانات:

```bash
php artisan migrate:fresh --seed
```

## هيكل المعمارية

```
Controller → FormRequest → Service → Model
                ↓
           API Resource / Policy / Enum
```

استجابة API الموحّدة: `{ success, message, data, errors }`

## المراحل المنفّذة

| المرحلة | المحتوى |
|--------|---------|
| 1 | مصادقة هاتف ليبي + Sanctum + أدوار Spatie + كتالوج |
| 2 | بانرات / باقات / كوبونات / `GET /home` |
| 3 | ساعات العمل + أيام إغلاق + فترات + `FulfillmentService` |
| 4 | سلة → طلب `pending_review` → قبول/رفض → حالات |
| 5 | طبقة دفع (`manual` / `fake`) + webhook |
| 6 | أجهزة FCM + قوالب إشعارات + اختبار Firebase من اللوحة |
| 7 | موردون / مخزون / مشتريات / مصروفات / تقارير |
| 8 | مساعد ذكاء اصطناعي (مزوّدات متوافقة مع OpenAI / Anthropic) |
| 9 | اختبارات Pest + Filament + أوامر مجدولة |
| hardening | حماية `admin` API بـ `staff` + FormRequests/Resources + سياسات الطلبات/المشتريات |
| لوحة | إنشاء طلبية من Filament + محاسبة مزدوجة القيد + تشارتس Overview |

## مسارات API مهمة (Flutter)

التوثيق الكامل: [`docs/FLUTTER_API.md`](docs/FLUTTER_API.md)

```
# عام
POST /api/v1/auth/register|login|guest
GET  /api/v1/home|banners
GET  /api/v1/categories|categories/{id}|categories/{id}/products
GET  /api/v1/products/popular|products/{id}
GET  /api/v1/collections|collections/{id}
POST /api/v1/coupons/apply

# عميل (Sanctum)
GET|PATCH /api/v1/me
POST /api/v1/auth/logout
GET|POST|PATCH|DELETE /api/v1/cart...  (+ /cart/coupon)
GET  /api/v1/fulfillment/available-dates|slots
GET|POST /api/v1/orders...  + pay|cancel
GET|DELETE /api/v1/notifications...  + read
POST|DELETE /api/v1/devices
POST /api/v1/ai/chat
```

## إعدادات مهمة في `.env`

```
PAYMENT_DRIVER=fake          # أو manual
FIREBASE_CREDENTIALS=        # مسار اختياري لملف service account
APP_TIMEZONE=Africa/Tripoli
```

## أوامر مجدولة

```bash
php artisan catalog:refresh-popular
php artisan orders:expire-unpaid
php artisan firebase:test
```

## الاختبارات

```bash
./vendor/bin/pest
```

## ملاحظات

- العملة: دينار ليبي (LYD)
- الهاتف الليبي: `09[1-5]xxxxxxx`
- رفع اعتماد Firebase واختباره من: **التكاملات → Firebase**
- إعداد المساعد من: **التكاملات → الذكاء الاصطناعي** (يلزم اختبار اتصال ناجح للتفعيل في التطبيق)
