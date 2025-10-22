<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('Bad request'); }

// ดึงออเดอร์
$st = $pdo->prepare("SELECT id, order_no, slip_path FROM orders WHERE id=?");
$st->execute([$id]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) { http_response_code(404); exit('Order not found'); }

$ok = false; 
$err = '';

// ถ้ายังไม่มีสลิปเท่านั้นจึงยอมรับอัปโหลด
if (empty($order['slip_path']) && $_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['slip'])) {
  if ($_FILES['slip']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['slip']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','pdf'];
    if (!in_array($ext, $allowed, true)) {
      $err = 'รองรับเฉพาะไฟล์ jpg, jpeg, png, pdf';
    } else {
      $newName = 'slip_'.$order['id'].'_'.date('Ymd_His').'.'.$ext;
      $destDir = dirname(__DIR__).'/uploads';
      if (!is_dir($destDir)) mkdir($destDir, 0777, true);
      $dest = $destDir.'/'.$newName;

      if (move_uploaded_file($tmp, $dest)) {
        // อัปเดตฐานข้อมูล
        $upd = $pdo->prepare("UPDATE orders SET slip_path=?, payment_status='paid', paid_at=NOW() WHERE id=?");
        $upd->execute([$newName, $order['id']]);
        $ok = true;

        // โหลดข้อมูลใหม่หลังอัปเดต
        $st->execute([$id]);
        $order = $st->fetch(PDO::FETCH_ASSOC);
      } else {
        $err = 'อัปโหลดไฟล์ไม่สำเร็จ';
      }
    }
  } else {
    $err = 'อัปโหลดมีปัญหา (error='.$_FILES['slip']['error'].')';
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>อัปโหลดสลิป — <?= htmlspecialchars($order['order_no']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#f6f7fb}
  .wrap{max-width:720px;margin:24px auto;padding:0 16px}
  .card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 6px 20px rgba(0,0,0,.05)}
  h1{margin:0 0 12px}
  .err{background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:10px;margin-bottom:10px}
  .ok{background:#ecfeff;border:1px solid #a5f3fc;color:#0e7490;border-radius:10px;padding:10px;margin-bottom:10px}
  img.preview{max-width:320px;border:1px solid #eee;border-radius:8px;margin:10px 0}
  .btn{background:#111827;color:#fff;border:none;border-radius:10px;padding:10px 16px;cursor:pointer;text-decoration:none;display:inline-block}
  .muted{color:#666}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>อัปโหลดสลิป: <?= htmlspecialchars($order['order_no']) ?></h1>

    <?php if($ok): ?>
      <div class="ok">อัปโหลดเรียบร้อยแล้ว ✓</div>
    <?php endif; ?>

    <?php if($err): ?>
      <div class="err"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if(!empty($order['slip_path'])): ?>
      <div class="muted">สลิปล่าสุด:</div>
      <img class="preview" src="../uploads/<?= htmlspecialchars($order['slip_path']) ?>" alt="Payment slip">

      <!-- ✅ ปุ่มกลับหน้าแรก (แสดงหลังอัปโหลดสำเร็จหรือมีสลิปแล้ว) -->
      <p style="margin-top:16px;">
        <a href="index.php" class="btn">← กลับไปหน้าแรก</a>
      </p>

    <?php else: ?>
      <!-- ยังไม่มีสลิป: แสดงฟอร์มอัปโหลดเฉพาะกรณีนี้ -->
      <form method="post" enctype="multipart/form-data" style="margin-top:10px">
        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
        <input type="file" name="slip" accept="image/*,.pdf" required>
        <button class="btn" type="submit">อัปโหลด</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>