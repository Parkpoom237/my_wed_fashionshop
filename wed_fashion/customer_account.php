<?php
declare(strict_types=1);
require_once __DIR__ . '/require_customer.php';
require_once __DIR__ . '/auth_lib.php';
$u = current_customer();
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>บัญชีของฉัน</title>
<style>body{font-family:system-ui,Segoe UI,Roboto,Arial;margin:0;background:#0b0d12;color:#eef2ff}
.wrap{max-width:900px;margin:24px auto;padding:0 16px}.card{background:#141823;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:16px}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h2>บัญชีของฉัน</h2>
    <p>สวัสดี, <b><?= h($u['name'] ?? '') ?></b> (<?= h($u['email'] ?? '') ?>)</p>
    <p><a href="customer_logout.php" style="color:#7c5cff">ออกจากระบบ</a> · <a href="index.php" style="color:#7c5cff">กลับหน้าร้าน</a></p>
  </div>
</div>
</body>
</html>
