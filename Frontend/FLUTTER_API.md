# Flutter API — معمل الحلويات

Base URL: `http://127.0.0.1:8000/api/v1`  
Envelope الموحّد: `{ "success": bool, "message": string|null, "data": ..., "errors": ... }`  
Auth: header `Authorization: Bearer {token}` + `Accept: application/json`

---

## 1) المصادقة والحساب

| Method | Path | Auth | الوصف |
|--------|------|------|--------|
| POST | `/auth/register` | لا | تسجيل: `name`, `phone`, `password`, `password_confirmation`, `email?` |
| POST | `/auth/login` | لا | دخول: `phone`, `password` |
| POST | `/auth/guest` | لا | جلسة ضيف (token) |
| GET | `/me` | نعم | بيانات الحساب الشخصية |
| PATCH | `/me` | نعم | تعديل: `name?`, `phone?`, `email?`, `password?` + `password_confirmation` |
| POST | `/auth/logout` | نعم | تسجيل الخروج (حذف التوكن الحالي) |

> `/auth/me` و `PATCH /auth/me` متاحان كـ alias لنفس `/me`.

**استجابة `/me`:**
```json
{
  "id": 1,
  "name": "أحمد",
  "phone": "0912345678",
  "email": null,
  "is_guest": false,
  "created_at": "..."
}
```

---

## 2) الصفحة الرئيسية

| Method | Path | Auth | الوصف |
|--------|------|------|--------|
| GET | `/home` | لا | بانرات + أقسام + الأكثر طلباً (مختصر) + عروض + باقات (مختصر) |

`data`:
- `banners[]`
- `categories[]` (أقسام رئيسية فقط)
- `popular_products[]` (حتى 8 — للقسم المختصر)
- `offers[]`
- `collections[]` (حتى 6 — للقسم المختصر)

---

## 3) البانر

| Method | Path | Auth |
|--------|------|------|
| GET | `/banners` | لا |

كل بانر: `id`, `title`, `link_type`, `link_id`, `image_url`, ...

---

## 4) الأقسام والأصناف

| Method | Path | Auth | الوصف |
|--------|------|------|--------|
| GET | `/categories` | لا | أقسام + **سب كاتاقوري** (`children`) |
| GET | `/categories/{id}` | لا | القسم + الأبناء + **كل الأصناف** المتاحة داخل كل مستوى |
| GET | `/categories/{id}/products` | لا | أصناف مع pagination + `search` + `tag_id` |
| GET | `/products/{id}` | لا | تفاصيل الصنف كاملة |

تدفق مقترح في Flutter:
1. قائمة الأقسام من `/categories` أو `/home`
2. دخول قسم → `/categories/{id}` (يعرض السب + الأصناف)
3. تفاصيل → `/products/{id}`

---

## 5) الأكثر طلباً

| Method | Path | Auth | الوصف |
|--------|------|------|--------|
| — | داخل `/home` → `popular_products` | لا | عرض مختصر |
| GET | `/products/popular?limit=50` | لا | **عرض الكل** عند فتح القسم |

---

## 6) الباقات

| Method | Path | Auth | الوصف |
|--------|------|------|--------|
| — | داخل `/home` → `collections` | لا | عرض مختصر |
| GET | `/collections?limit=50` | لا | **عرض الكل** |
| GET | `/collections/{id}` | لا | تفاصيل الباقة + أصنافها |

---

## 7) السلة

| Method | Path | Auth | Body |
|--------|------|------|------|
| GET | `/cart` | نعم | محتوى السلة + الإجمالي |
| POST | `/cart/items` | نعم | `{ "item_type": "product\|collection", "item_id": 1, "quantity": 1 }` |
| PATCH | `/cart/items/{itemId}` | نعم | `{ "quantity": 2 }` — الكمية `0` تحذف السطر |
| DELETE | `/cart/items/{itemId}` | نعم | حذف عنصر |
| DELETE | `/cart` | نعم | تفريغ السلة |
| POST | `/cart/coupon` | نعم | `{ "code": "WELCOME10" }` |
| DELETE | `/cart/coupon` | نعم | إزالة الكوبون |

اختياري قبل الربط بالسلة: `POST /coupons/apply` للتحقق فقط بدون حفظ.

---

## 8) تأكيد الطلب بالكامل

الترتيب المقترح في التطبيق:

1. **محتوى السلة** → `GET /cart`
2. **نوع الاستلام** → `pickup` أو `delivery`
3. **التواريخ المتاحة** → `GET /fulfillment/available-dates?delivery_type=pickup`
4. **الفترات** → `GET /fulfillment/slots?delivery_type=pickup&date=2026-11-10`
5. **إنشاء الطلب** → `POST /orders`
6. **الدفع (مقدم/كامل)** → `POST /orders/{id}/pay` إن لزم
7. **إلغاء** (إن سُمح) → `POST /orders/{id}/cancel`

### `POST /orders`
```json
{
  "delivery_type": "delivery",
  "fulfillment_date": "2026-11-12",
  "fulfillment_time": "16:00",
  "address_text": "طرابلس — حي الأندلس",
  "lat": 32.88,
  "lng": 13.18,
  "recipient_is_self": true,
  "recipient_name": null,
  "recipient_phone": null,
  "customer_note": "بدون مكسرات"
}
```

### `POST /orders/{id}/pay`
```json
{ "payment_plan": 50 }
```
`payment_plan`: `25` | `50` | `100`

---

## 9) طلباتي

| Method | Path | Auth |
|--------|------|------|
| GET | `/orders?per_page=15` | نعم |
| GET | `/orders/{id}` | نعم |
| POST | `/orders/{id}/cancel` | نعم |

التفاصيل تشمل: الحالة، الأصناف، المدفوعات، timeline، سبب الرفض إن وُجد.

---

## 10) الإشعارات

| Method | Path | Auth |
|--------|------|------|
| GET | `/notifications?per_page=20` | نعم | يتضمن `unread_count` في `data` |
| POST | `/notifications/{id}/read` | نعم |
| DELETE | `/notifications/{id}` | نعم |

تسجيل جهاز FCM:
- `POST /devices` — `{ "token": "...", "platform": "android|ios" }`
- `DELETE /devices` — إزالة توكن الجهاز

---

## خريطة شاشات Flutter → Endpoints

| الشاشة | Endpoints |
|--------|-----------|
| تسجيل / دخول | `auth/register`, `auth/login`, `auth/guest` |
| الرئيسية | `home` |
| الأقسام | `categories`, `categories/{id}` |
| تفاصيل صنف | `products/{id}` |
| الأكثر طلباً (الكل) | `products/popular` |
| الباقات (الكل) | `collections`, `collections/{id}` |
| السلة | `cart*` + `cart/coupon` |
| تأكيد الطلب | `fulfillment/*` → `orders` → `orders/{id}/pay` |
| طلباتي | `orders`, `orders/{id}` |
| الإشعارات | `notifications*` |
| حسابي | `me`, `PATCH me`, `auth/logout` |

---

## ملاحظات للتطبيق

- الهاتف الليبي: `09[1-5]xxxxxxx` (يُطبَّع تلقائياً من `+218...`)
- العملة: `LYD`
- الضيف يمكنه السلة؛ لتأكيد طلب كامل غالباً يلزم حساب غير ضيف (حسب قواعد الخادم)
- بعد `POST /orders` السلة تُفرَّغ والطلب يبدأ `pending_review`
