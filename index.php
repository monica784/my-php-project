<?php
$php_version = PHP_VERSION;
$server = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$date = date('d M Y, H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ApexPlanet Dev Environment</title>
    <style>
        body { font-family: sans-serif; background: #0f172a; color: #e2e8f0; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 40px; max-width: 500px; width: 90%; text-align: center; }
        h1 { color: #38bdf8; margin-bottom: 20px; }
        p { margin: 10px 0; color: #94a3b8; }
        span { color: #38bdf8; font-weight: bold; }
    </style>
</head>
<body>
<div class="card">
    <h1>✅ Server is Running!</h1>
    <p>PHP Version: <span><?= $php_version ?></span></p>
    <p>Server: <span><?= $server ?></span></p>
    <p>Time: <span><?= $date ?></span></p>
    <p style="margin-top:20px;color:#475569;">ApexPlanet Software Pvt Ltd | Monica</p>
</div>
</body>
</html>