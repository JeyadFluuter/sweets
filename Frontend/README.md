# Sweet Store

تطبيق Flutter لمعمل الحلويات — **MVC + GetX**.

## الهيكل

```
lib/
  bindings/          # AppBinding (DI)
  controllers/       # Auth · Home · Notifications
  services/          # Auth · Catalog · Notification
  models/
  views/             # splash · auth · home · catalog · notifications
  widgets/
  core/
    constants/       # ApiEndpoints · ألوان · نصوص
    network/         # ApiClient
    storage/         # AppStorage (توكن + كاش الرئيسية/الأقسام)
    routes/
    theme/           # Cairo + Fredoka
```

## الميزات الحالية

- Splash يستدعي `GET /me` عند وجود توكن
- الرئيسية: Hero بانرات · أقسام · الأكثر طلباً · باقات + **عرض المزيد**
- Skeleton loading + كاش محلي للـ home/categories
- أيقونة إشعارات مع badge بعدد غير المقروء

## التشغيل

```bash
# backend
cd backend && php artisan serve

# app
cd Frontend && flutter pub get && flutter run
```
