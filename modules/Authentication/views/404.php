<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 | SFAS</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.min.css" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',system-ui,sans-serif;background:#f3fbf6;
      display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1.5rem}
    .box{text-align:center;max-width:440px}
    .box .num{font-size:7rem;font-weight:900;color:#2d9a4e;line-height:1;
      background:linear-gradient(135deg,#1a5c2a,#2d9a4e);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .box h2{font-size:1.4rem;font-weight:700;color:#1e293b;margin:.75rem 0 .5rem}
    .box p{color:#64748b;font-size:.9rem;line-height:1.6;margin-bottom:1.5rem}
    .back-btn{display:inline-flex;align-items:center;gap:.45rem;
      background:#2d9a4e;color:white;padding:.7rem 1.4rem;border-radius:99px;
      text-decoration:none;font-weight:600;font-size:.9rem;transition:background .2s}
    .back-btn:hover{background:#1a5c2a}
    .icon{font-size:3rem;display:block;margin-bottom:.5rem;opacity:.4}
  </style>
</head>
<body>
  <div class="box">
    <i class="ri-map-pin-off-line icon"></i>
    <div class="num">404</div>
    <h2>Page Not Found</h2>
    <p>The page you're looking for doesn't exist or has been moved.</p>
    <?php
    if (defined('BASE_URL')) {
        echo '<a href="'.BASE_URL.'/admin/dashboard" class="back-btn"><i class="ri-home-4-line"></i> Go to Dashboard</a>';
    } else {
        echo '<a href="/" class="back-btn"><i class="ri-home-4-line"></i> Go Home</a>';
    }
    ?>
  </div>
</body>
</html>
