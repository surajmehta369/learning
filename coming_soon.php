<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo $courseName; ?> - Coming Soon</title>
  <style>
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f6f7fb;color:#111}
    .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
    .card{max-width:880px;width:100%;background:#fff;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.08);overflow:hidden;display:grid;grid-template-columns:1.2fr .8fr}
    .left{padding:44px}
    .badge{display:inline-block;padding:6px 12px;border-radius:999px;background:#f0ecff;color:#4b2aad;font-weight:700;font-size:13px}
    h1{margin:14px 0 10px;font-size:38px;line-height:1.12}
    p{margin:0 0 20px;color:#4b5563;font-size:16px;line-height:1.65}
    .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}
    .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:12px 18px;text-decoration:none;font-weight:800}
    .btn-primary{background:#5b2eff;color:#fff}
    .btn-ghost{background:#eef2ff;color:#111827}
    .right{background:linear-gradient(135deg,#e9e6ff,#e6f7ff);display:flex;align-items:center;justify-content:center;padding:24px}
    .icon{width:150px;height:150px;border-radius:28px;background:rgba(255,255,255,.88);display:flex;align-items:center;justify-content:center;font-size:66px;box-shadow:0 10px 25px rgba(0,0,0,.08)}
    @media (max-width:820px){.card{grid-template-columns:1fr}.right{display:none}}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="left">
        <span class="badge">Coming Soon</span>
        <h1><?php echo $courseName; ?> is on the way 🚀</h1>
        <p>We’re preparing this course and will publish it soon. Please check back again.</p>

        <div class="actions">
          <a class="btn btn-primary" href="index.php">← Back to Courses</a>
          <a class="btn btn-ghost" href="contactus.php">Contact</a>
        </div>
      </div>

      <div class="right">
        <div class="icon">⏳</div>
      </div>
    </div>
  </div>
</body>
</html>
