<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php'; // มีฟังก์ชัน db() และ session
require_once __DIR__ . '/_db.php';    // ถ้าในโปรเจกต์คุณใช้แยก PDO ให้คงไว้ได้

// ต้องล็อกอินก่อน
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('Bad request'); }

/* ---------- อัปเดตสถานะ (ถ้ามี POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim((string)($_POST['status'] ?? ''));
    $pay    = trim((string)($_POST['payment_status'] ?? ''));

    $upd = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
    $upd->execute([$status, $pay, $id]);

    header('Location: order_detail.php?id=' . $id);
    exit;
}

/* ---------- ดึงข้อมูลออร์เดอร์ ---------- */
/* หมายเหตุ: ไม่อ้างคอลัมน์ address_json เพราะฐานข้อมูลของคุณไม่มี */
$st = $pdo->prepare("
    SELECT
      id, order_no, customer_name, customer_email, customer_phone,
      address,
      COALESCE(grand, total, 0)                    AS grand,
      COALESCE(payment_method, pay_method)         AS payment_method,
      COALESCE(payment_status, pay_status, 'unpaid') AS payment_status,
      status, created_at, slip_path
    FROM orders
    WHERE id = ?
");
$st->execute([$id]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) { http_response_code(404); exit('Order not found'); }

/* ---------- ดึงรายการสินค้า ---------- */
$it = $pdo->prepare("
  SELECT product_id, name, size, qty, price,
         COALESCE(subtotal, price*qty) AS line_total
  FROM order_items
  WHERE order_id = ?
  ORDER BY id ASC
");
$it->execute([$id]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);

/* ---------- คำนวณยอดรวม (fallback ถ้า grand ว่าง) ---------- */
$calc = 0.0;
foreach ($items as $row) $calc += (float)$row['line_total'];
$grand_to_show = ((float)$order['grand'] > 0) ? (float)$order['grand'] : $calc;

/* ---------- ที่อยู่จัดส่ง ---------- */
$address_text = (string)($order['address'] ?? '');

/* ---------- helper ---------- */
function baht($n){ return '฿' . number_format((float)$n, 2); }

/* ---------- เตรียมพาธสลิป (ไฟล์สลิปเก็บไว้ใน /uploads/ ระดับบน admin) ---------- */
$slipRel = !empty($order['slip_path']) ? ('../uploads/' . $order['slip_path']) : '';
$ext = $slipRel ? strtolower(pathinfo($slipRel, PATHINFO_EXTENSION)) : '';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Order #<?= htmlspecialchars((string)$order['order_no']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#f6f7fb}
.wrap{max-width:980px;margin:24px auto;padding:0 16px}
.card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 6px 20px rgba(0,0,0,.05);margin-bottom:16px}
h1{margin:0 0 8px}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;background:#eef}
.row{display:flex;gap:16px;flex-wrap:wrap}
.col{flex:1 1 300px}
a.link{color:#374151;text-decoration:none}
button{background:#111827;color:#fff;border:0;border-radius:10px;padding:10px 14px;cursor:pointer}
select{padding:6px 8px;border-radius:8px;border:1px solid #ddd}

/* slip */
.slip-box{background:#fff;border-radius:12px;padding:14px 16px;box-shadow:0 6px 20px rgba(0,0,0,.05);margin:12px 0}
.slip-box h3{margin:0 0 10px;font-size:1rem}
.slip-box .muted{color:#666;font-size:.95rem}
.slip-img{max-width:340px;height:auto;border:1px solid #eee;border-radius:8px;display:block}
.btn-link{display:inline-block;padding:8px 12px;border-radius:8px;background:#111827;color:#fff;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">

  <div class="card">
    <h1>Order: <?= htmlspecialchars((string)$order['order_no']) ?></h1>
    <div class="row">
      <div class="col">
        <p><b>ยอดรวม:</b> <?= baht($grand_to_show) ?></p>
        <p><b>วิธีชำระเงิน:</b> <?= htmlspecialchars((string)$order['payment_method']) ?></p>
        <p><b>สถานะชำระเงิน:</b> <span class="badge"><?= htmlspecialchars((string)$order['payment_status']) ?></span></p>
        <p><b>สถานะออเดอร์:</b> <span class="badge"><?= htmlspecialchars((string)$order['status']) ?></span></p>
        <p><b>เมื่อ:</b> <?= htmlspecialchars((string)$order['created_at']) ?></p>
      </div>
      <div class="col">
        <p><b>ที่อยู่จัดส่ง</b></p>
        <pre style="white-space:pre-wrap"><?= htmlspecialchars($address_text) ?></pre>
      </div>
    </div>

    <!-- สลิปการโอน -->
    <div class="slip-box">
      <h3>สลิปการโอน</h3>
      <?php if ($slipRel): ?>
        <?php if ($ext === 'pdf'): ?>
          <p class="muted">อัปโหลดเป็นไฟล์ PDF</p>
          <a class="btn-link" href="<?= htmlspecialchars($slipRel) ?>" target="_blank" rel="noopener">เปิดไฟล์สลิป (PDF)</a>
        <?php else: ?>
          <img class="slip-img" src="<?= htmlspecialchars($slipRel) ?>" alt="Payment slip">
          <div style="margin-top:8px">
            <a class="btn-link" href="<?= htmlspecialchars($slipRel) ?>" target="_blank" rel="noopener">เปิดรูปสลิปในแท็บใหม่</a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p class="muted">ยังไม่มีสลิปอัปโหลด</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h2>รายการสินค้า</h2>
    <table>
      <thead>
        <tr><th>สินค้า</th><th>ไซซ์</th><th>จำนวน</th><th>ราคา</th><th>รวม</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $r): ?>
          <tr>
            <td><?= htmlspecialchars((string)$r['name']) ?></td>
            <td><?= htmlspecialchars((string)$r['size']) ?></td>
            <td><?= (int)$r['qty'] ?></td>
            <td><?= baht($r['price']) ?></td>
            <td><?= baht($r['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h2>เปลี่ยนสถานะ</h2>
    <form method="post">
      <label>สถานะออเดอร์
        <select name="status">
          <?php foreach (['ใหม่','จัดเตรียม','จัดส่ง','เสร็จสิ้น','ยกเลิก'] as $st): ?>
            <option value="<?= $st ?>" <?= ($order['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      &nbsp;&nbsp;
      <label>สถานะการชำระเงิน
        <select name="payment_status">
          <?php foreach (['ยังไม่ชำระเงิน','ชำระเงินแล้ว','ชำระเงินล้มเหลว','รอดำเนินการ'] as $ps): ?>
            <option value="<?= $ps ?>" <?= ($order['payment_status'] ?? '') === $ps ? 'selected' : '' ?>><?= $ps ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      &nbsp;&nbsp;
      <button type="submit">บันทึก</button>
      &nbsp; <a class="link" href="index.php">← กลับรายการออเดอร์</a>
    </form>
  </div>

</div>
</body>
</html>