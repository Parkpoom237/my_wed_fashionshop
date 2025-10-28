<?php
// fashionshop/admin/order_detail.php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ต้องล็อกอินก่อน
if (!isset($_SESSION['admin_id'])) {
  header('Location: login.php');
  exit;
}

// CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('Bad request'); }

// ====== ดึงข้อมูลออเดอร์ ======
$st = $pdo->prepare("
  SELECT
    id, order_no, customer_name, customer_email, customer_phone, address,
    COALESCE(payment_method,'') AS payment_method,
    COALESCE(payment_status,'PENDING') AS payment_status,
    COALESCE(status,'NEW') AS status,
    COALESCE(slip_path,'') AS slip_path,
    created_at
  FROM orders
  WHERE id = ?
  LIMIT 1
");
$st->execute([$id]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) { http_response_code(404); exit('Order not found'); }

// ====== ดึงรายการสินค้า ======
$it = $pdo->prepare("
  SELECT product_id, name, size, qty, price,
         (COALESCE(price,0) * COALESCE(qty,0)) AS line_total
  FROM order_items
  WHERE order_id = ?
  ORDER BY id ASC
");
$it->execute([$id]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// label ภาษาไทย
function payLabelTH($pm, $slip){
  $up = strtoupper((string)$pm);
  if (in_array($up, ['CARD','TRANSFER','BANK','PROMPTPAY'], true)) return 'โอนเงิน';
  if ($up === 'COD') return 'COD';
  if (!empty($slip)) return 'โอนเงิน';
  return '—';
}
function payStatusTH($s){
  $map=['PENDING'=>'รอตรวจสอบ','PAID'=>'ชำระแล้ว','FAILED'=>'ไม่สำเร็จ','CANCELLED'=>'ยกเลิกแล้ว'];
  $up = strtoupper((string)$s);
  return $map[$up] ?? $up;
}
function orderStatusTH($s){
  $map=['NEW'=>'ใหม่','PROCESSING'=>'กำลังดำเนินการ','SHIPPED'=>'จัดส่งแล้ว','DONE'=>'สำเร็จ','CANCELLED'=>'ยกเลิกแล้ว'];
  $up = strtoupper((string)$s);
  return $map[$up] ?? $up;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Order #<?= h($order['order_no']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{--ink:#1f2937;--muted:#6b7280;--bg:#f6f7fb;--brand:#4f6ed9;--danger:#e11d48;}
body{margin:0;background:var(--bg);color:var(--ink);font-family:system-ui,Segoe UI,Roboto,Inter,Arial}
.wrap{max-width:1000px;margin:26px auto;padding:0 16px}
.card{background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 8px 24px rgba(0,0,0,.06);margin-bottom:16px}
.h1{font-size:1.6rem;margin:0 0 6px}
.badge{display:inline-block;padding:6px 10px;border-radius:999px;background:#eef2ff;color:#3730a3;font-weight:600;font-size:12px;margin-right:6px}
.badge.gray{background:#e5e7eb;color:#374151}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:right}
.table th:first-child,.table td:first-child{text-align:left}
.small{color:var(--muted);font-size:13px}
.btn{display:inline-block;background:var(--brand);color:#fff;text-decoration:none;border-radius:8px;padding:10px 14px}
.btn.gray{background:#374151}
.btn.danger{background:var(--danger)}
img.slip{max-width:300px;border-radius:10px;border:1px solid #e5e7eb;display:block}
form.inline{display:inline}
.grid{display:grid;grid-template-columns:1fr 360px;gap:16px}
@media (max-width:960px){.grid{grid-template-columns:1fr}}
label{display:block;font-weight:600;margin:10px 0 6px}
select{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px;background:#fff}
.save{margin-top:12px;width:100%}
</style>
</head>
<body>
<div class="wrap">

  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <div class="h1">คำสั่งซื้อ: <b><?= h($order['order_no']) ?></b></div>
        <div>
          <span class="badge">Pay: <?= h(payLabelTH($order['payment_method'], $order['slip_path'])) ?></span>
          <span class="badge">Pay Status: <?= h(payStatusTH($order['payment_status'])) ?></span>
          <span class="badge gray">Status: <?= h(orderStatusTH($order['status'])) ?></span>
          <span class="badge gray small">เมื่อ: <?= h((string)$order['created_at']) ?></span>
        </div>
      </div>
      <form class="inline" method="post" action="update_order.php" onsubmit="return confirm('ยืนยันยกเลิกออเดอร์นี้?');">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
        <input type="hidden" name="action" value="cancel">
        <button class="btn danger" type="submit">ยกเลิกออเดอร์</button>
      </form>
    </div>

    <p class="small" style="margin-top:10px">
      ลูกค้า: <?= h($order['customer_name']) ?>
      | โทร: <?= h($order['customer_phone']) ?>
      | อีเมล: <?= h($order['customer_email']) ?><br>
      ที่อยู่: <?= nl2br(h($order['address'])) ?>
    </p>
  </div>

  <div class="grid">

    <!-- ซ้าย: สลิป + รายการสินค้า -->
    <div class="card">
      <?php if (!empty($order['slip_path'])): ?>
        <p class="small" style="margin:0 0 6px">สลิปชำระเงิน:</p>
        <img class="slip" src="../<?= h($order['slip_path']) ?>" alt="Slip">
      <?php else: ?>
        <p class="small">ยังไม่มีสลิปแนบ</p>
      <?php endif; ?>

      <h3 style="margin:16px 0 10px">รายการสินค้า</h3>
      <table class="table">
        <thead>
          <tr>
            <th>สินค้า</th>
            <th>ไซซ์</th>
            <th>จำนวน</th>
            <th>ราคา/ชิ้น</th>
            <th>รวม</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= h($it['name']) ?></td>
              <td><?= h($it['size']) ?></td>
              <td><?= (int)$it['qty'] ?></td>
              <td>฿<?= number_format((float)$it['price'],2) ?></td>
              <td>฿<?= number_format((float)$it['line_total'],2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ขวา: ฟอร์มแก้ไขสถานะ -->
    <div class="card">
      <h3 style="margin:0 0 10px">แก้ไขสถานะคำสั่งซื้อ</h3>
      <form method="post" action="update_order.php">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
        <input type="hidden" name="action" value="update_fields">

        <label>วิธีชำระเงิน (Pay)</label>
        <select name="payment_method" required>
          <?php
            $pm = strtoupper((string)$order['payment_method']);
            $opts = [
              'TRANSFER' => 'โอนเงิน',
              'COD'      => 'COD',
              ''         => '— (ไม่ระบุ)'
            ];
            foreach ($opts as $val=>$txt):
          ?>
            <option value="<?= h($val) ?>" <?= ($pm===$val)?'selected':''; ?>><?= h($txt) ?></option>
          <?php endforeach; ?>
        </select>

        <label>สถานะการชำระเงิน (Pay Status)</label>
        <select name="payment_status" required>
          <?php
            $ps = strtoupper((string)$order['payment_status']);
            $ps_opts = [
              'PENDING'   => 'รอตรวจสอบ',
              'PAID'      => 'ชำระแล้ว',
              'FAILED'    => 'ไม่สำเร็จ',
              'CANCELLED' => 'ยกเลิกแล้ว'
            ];
            foreach ($ps_opts as $val=>$txt):
          ?>
            <option value="<?= h($val) ?>" <?= ($ps===$val)?'selected':''; ?>><?= h($txt) ?></option>
          <?php endforeach; ?>
        </select>

        <label>สถานะออเดอร์ (Status)</label>
        <select name="status" required>
          <?php
            $os = strtoupper((string)$order['status']);
            $os_opts = [
              'NEW'        => 'ใหม่',
              'PROCESSING' => 'กำลังดำเนินการ',
              'SHIPPED'    => 'จัดส่งแล้ว',
              'DONE'       => 'สำเร็จ',
              'CANCELLED'  => 'ยกเลิกแล้ว'
            ];
            foreach ($os_opts as $val=>$txt):
          ?>
            <option value="<?= h($val) ?>" <?= ($os===$val)?'selected':''; ?>><?= h($txt) ?></option>
          <?php endforeach; ?>
        </select>

        <button class="btn save" type="submit">บันทึกการเปลี่ยนแปลง</button>
      </form>
    </div>

  </div>

  <div style="margin-top:10px">
    <a class="btn gray" href="index.php">← กลับไป Orders</a>
  </div>

</div>
</body>
</html>
