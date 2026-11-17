<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>معمل الحلويات</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Cairo", "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(160deg, #fff7ed 0%, #ffedd5 45%, #fed7aa 100%);
            color: #431407;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .card {
            width: min(560px, 100%);
            background: rgba(255,255,255,.88);
            border: 1px solid #fdba74;
            border-radius: 24px;
            padding: 36px 28px;
            box-shadow: 0 20px 50px rgba(154, 52, 18, .12);
            text-align: center;
        }
        h1 { margin: 0 0 8px; font-size: 2rem; }
        p { margin: 0 0 28px; color: #9a3412; line-height: 1.7; }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        a {
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 999px;
            font-weight: 700;
        }
        .primary { background: #c2410c; color: #fff; }
        .secondary { background: #fff7ed; color: #9a3412; border: 1px solid #fdba74; }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="card">
        <h1>معمل الحلويات</h1>
        <p>لوحة التحكم والـ API جاهزان للتشغيل المحلي.</p>
        <div class="actions">
            <a class="primary" href="{{ url('/admin') }}">فتح لوحة التحكم</a>
            <a class="secondary" href="{{ url('/api/v1/home') }}">واجهة الـ API</a>
        </div>
    </div>
</body>
</html>
