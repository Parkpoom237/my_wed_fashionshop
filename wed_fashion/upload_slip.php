<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* อนุญาตเฉพาะ POST */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

/* รับค่า order_id + ไฟล์ */
$order_id = (int)($_POST['id'] ?? 0);
if ($order_id <= 0 || empty($_FILES['slip']['tmp_name'])) {
  http_response_code(400);
  exit('Bad request');
}

/* ตรวจสอบว่าออร์เดอร์มีอยู่จริง */
$st = $pdo->prepare("SELECT id, order_no FROM orders WHERE id = ? LIMIT 1");
$st->execute([$order_id]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) {
  http_response_code(404);
  exit('Order not found');
}

/* ตรวจสอบชนิดไฟล์อย่างปลอดภัย */
$tmp  = $_FILES['slip']['tmp_name'];
$name = (string)($_FILES['slip']['name'] ?? '');
$ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$allow_ext  = ['jpg','jpeg','png','webp','gif'];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $tmp);
finfo_close($finfo);
$allow_mime = ['image/jpeg','image/png','image/webp','image/gif'];

if (!in_array($ext, $allow_ext, true) || !in_array($mime, $allow_mime, true)) {
  http_response_code(415);
  exit('Unsupported file');
}

/* โฟลเดอร์ปลายทาง “กลางโปรเจกต์” = PROJECT_ROOT/uploads/slips/{order_id}/
   - ไฟล์นี้อยู่ใน wed_fashion/ จึงย้อนขึ้นไปหนึ่งระดับ */
$projectRoot = realpath(__DIR__ . '/..');          // .../fashionshop
$diskBase    = $projectRoot . '/uploads/slips';    // .../fashionshop/uploads/slips
$dir         = $diskBase . '/' . $order_id;

if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
  http_response_code(500);
  exit('Cannot create upload directory');
}

/* ตั้งชื่อไฟล์ใหม่ให้ไม่ชนกัน */
$ext = in_array($ext, $allow_ext, true) ? $ext : 'jpg';
$fname = 'SLIP-' . $order_id . '-' . date('Ymd_His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
$dest  = $dir . '/' . $fname;

/* ย้ายไฟล์ขึ้นโฟลเดอร์ปลายทาง */
if (!@move_uploaded_file($tmp, $dest)) {
  http_response_code(500);
  exit('Upload failed');
}

/* เซ็ต permission แบบอ่านได้โดยเว็บเซิร์ฟเวอร์ (ไม่บังคับ) */
@chmod($dest, 0644);

/* === พาธที่เก็บลงฐานข้อมูล: “สัมพัทธ์จากรากโปรเจกต์” ===
   ตัวอย่าง: uploads/slips/5/SLIP-5-20251028_193000-abc123.jpg */
$webPath = 'uploads/slips/' . $order_id . '/' . $fname;

/* อัปเดต DB */
$up = $pdo->prepare("
  UPDATE orders
     SET slip_path = :p, payment_status = 'PENDING'
   WHERE id = :id
");
$up->execute([':p' => $webPath, ':id' => $order_id]);

/* กลับไปหน้า success */
header('Location: order_success.php?id=' . $order_id);
exit;
