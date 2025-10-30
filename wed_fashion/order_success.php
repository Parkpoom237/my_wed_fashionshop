<?php
// fashionshop/wed_fashion/order_success.php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('Bad request');
}

$st = $pdo->prepare("
  SELECT id, order_no, customer_name,
         COALESCE(payment_method,'') AS payment_method,
         COALESCE(payment_status,'PENDING') AS payment_status,
         COALESCE(slip_path,'') AS slip_path,
         created_at
  FROM orders
  WHERE id = ?
  LIMIT 1
");
$st->execute([$id]);
$o = $st->fetch(PDO::FETCH_ASSOC);
if (!$o) {
  http_response_code(404);
  exit('Order not found');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>สั่งซื้อสำเร็จ</title>
<style>
body{
  margin:0;
  background:#0b0d12;
  color:#eef2ff;
  font-family:system-ui,Segoe UI,Roboto,Inter,Arial;
}
.wrap{max-width:720px;margin:40px auto;padding:0 16px}
.card{
  background:#141823;
  border:1px solid rgba(255,255,255,.08);
  border-radius:12px;
  padding:20px;
  box-shadow:0 6px 16px rgba(0,0,0,.2);
}
h1{margin:0 0 10px;font-size:1.8em}
.badge{
  display:inline-block;
  padding:6px 12px;
  border-radius:999px;
  background:#1f2937;
  color:#fff;
  font-size:13px;
  margin-right:6px;
}
img.slip{
  max-width:320px;
  border-radius:12px;
  display:block;
  margin-top:10px;
  border:1px solid #2c2c2c;
}
.btn{
  display:inline-block;
  margin-top:18px;
  padding:12px 20px;
  border-radius:10px;
  background:#7c5cff;
  color:#fff;
  text-decoration:none;
  font-weight:600;
  transition:0.2s;
}
.btn:hover{background:#6a4ce5}
.small{color:#9ca3af}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>สั่งซื้อสำเร็จ 🎉</h1>
    <p>ขอบคุณคุณ <b><?= h($o['customer_name']) ?></b> สำหรับการสั่งซื้อค่ะ</p>
    <p>เลขที่คำสั่งซื้อของคุณ: <b><?= h($o['order_no']) ?></b></p>
  </p>

    <?php
      $slip = trim((string)$o['slip_path']);
      // ถ้า slip_path มี /wed_fashion/ ติดมา จะตัดออกก่อนแสดง
      $slip = preg_replace('#^/?wed_fashion/#', '', $slip);
    ?>

    <?php if ($slip !== ''): ?>
      <p class="small">หลักฐานการโอนเงิน:</p>
      <!-- ไฟล์นี้อยู่ใน /wed_fashion → ต้องใส่ ../ หน้า path -->
      <img class="slip" src="../<?= h($slip) ?>" alt="Slip">
    <?php else: ?>
      <p class="small">เลือกชำระแบบ COD ไม่ต้องแนบสลิป</p>
    <?php endif; ?>

    <a href="index.php" class="btn">กลับไปหน้าหลัก 🏠</a>
  </div>
</div>
</body>
</html>
