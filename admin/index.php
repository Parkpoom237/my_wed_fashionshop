<?php
// fashionshop/admin/index.php
declare(strict_types=1);

// ใช้ของโฟลเดอร์ wed_fashion
require_once __DIR__ . '/../wed_fashion/config.php';
require_once __DIR__ . '/../wed_fashion/_db.php';
require_once __DIR__ . '/../wed_fashion/inventory_lib.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $pid = $_POST['id'];
  $sizes = ['XS','S','M','L','XL','XXL'];
  foreach ($sizes as $sz) {
    $field = 'stock_'.strtolower($sz);
    if (isset($_POST[$field])) {
      $qty = (int)$_POST[$field];
      $st = $pdo->prepare("
        INSERT INTO inventory (product_id, size, stock)
        VALUES (:pid, :sz, :qty)
        ON DUPLICATE KEY UPDATE stock = :qty
      ");
      $st->execute([':pid'=>$pid, ':sz'=>$sz, ':qty'=>$qty]);
    }
  }
  echo "<p>✅ อัปเดตสต็อกสำเร็จ!</p>";
}

if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$pdo = db();

/* ====== ดึงรายการออเดอร์ ====== */
$sql = "
  SELECT
    o.id,
    o.order_no,
    o.customer_name,
    UPPER(COALESCE(o.payment_method,'')) AS payment_method_raw,
    CASE
      WHEN UPPER(COALESCE(o.payment_method,'')) IN ('CARD','TRANSFER','BANK','PROMPTPAY') THEN 'โอนเงิน'
      WHEN UPPER(COALESCE(o.payment_method,'')) = 'COD' THEN 'COD'
      WHEN COALESCE(o.slip_path,'') <> '' THEN 'โอนเงิน'
      ELSE '—'
    END AS pay_label,
    UPPER(COALESCE(o.payment_status,'PENDING')) AS payment_status,
    COALESCE(o.status,'NEW') AS status,
    o.created_at,
    COALESCE(SUM(COALESCE(oi.price,0) * COALESCE(oi.qty,0)),0) AS amount,
    COALESCE(SUM(COALESCE(oi.qty,0)),0) AS items
  FROM orders o
  LEFT JOIN order_items oi ON oi.order_id = o.id
  GROUP BY o.id, o.order_no, o.customer_name, o.payment_method, o.slip_path, o.payment_status, o.status, o.created_at
  ORDER BY o.id DESC
  LIMIT 100
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* ฟังก์ชันช่วย */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* แปลงสถานะการจ่ายเงินเป็นภาษาไทย */
function payStatusTH($status) {
  $map = [
    'PENDING'   => 'รอตรวจสอบ',
    'PAID'      => 'ชำระแล้ว',
    'FAILED'    => 'ไม่สำเร็จ',
    'CANCELLED' => 'ยกเลิกแล้ว',
  ];
  $up = strtoupper((string)$status);
  return $map[$up] ?? $up;
}

/* แปลงสถานะออเดอร์เป็นไทย */
function orderStatusTH($status) {
  $map = [
    'NEW'        => 'ใหม่',
    'PROCESSING' => 'กำลังดำเนินการ',
    'SHIPPED'    => 'จัดส่งแล้ว',
    'DONE'       => 'สำเร็จ',
    'CANCELLED'  => 'ยกเลิกแล้ว'
  ];
  $up = strtoupper((string)$status);
  return $map[$up] ?? $up;
}

/* ✅ ตัดสต็อกเมื่อสถานะเปลี่ยน (ชำระแล้ว/ยกเลิก) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $orderId = (int)$_POST['id'];
    $action  = trim($_POST['action']);

    // อ่านสถานะปัจจุบันของออเดอร์
    $st = $pdo->prepare("SELECT payment_status, status FROM orders WHERE id=?");
    $st->execute([$orderId]);
    $old = $st->fetch(PDO::FETCH_ASSOC);

    if ($old) {
        $wasPaid = ($old['payment_status'] === 'PAID');
        $wasCancelled = ($old['status'] === 'CANCELLED');

        if ($action === 'cancel' && !$wasCancelled) {
            // ยกเลิกออเดอร์ → คืนสต็อก
            $pdo->prepare("UPDATE orders SET status='CANCELLED' WHERE id=?")->execute([$orderId]);
            update_inventory($pdo, $orderId, 'increase');
        }

        if ($action === 'mark_paid' && !$wasPaid) {
            // ทำเครื่องหมายว่าชำระแล้ว → ตัดสต็อก
            $pdo->prepare("UPDATE orders SET payment_status='PAID' WHERE id=?")->execute([$orderId]);
            update_inventory($pdo, $orderId, 'decrease');
        }
    }

    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Admin — Orders</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
  body{font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:#f4f6fa;margin:0;color:#222}
  main{max-width:1100px;margin:40px auto;background:#fff;border-radius:12px;padding:30px 40px;box-shadow:0 8px 20px rgba(0,0,0,.05)}
  h1{font-size:1.8rem;font-weight:600;color:#2b3a67;border-bottom:3px solid #2b3a67;padding-bottom:10px;margin-bottom:25px}
  table{width:100%;border-collapse:collapse;font-size:.95rem}
  th,td{padding:12px 10px;text-align:center;border-bottom:1px solid #e6e9f2}
  th{background:#2b3a67;color:#fff;font-weight:500}
  tr:nth-child(even){background:#f9f9f9}
  .btn{display:inline-block;background:#4f6ed9;color:#fff;padding:6px 12px;border-radius:6px;text-decoration:none}
  .btn.red{background:#e54848}.btn.red:hover{background:#b12e2e}
  footer{text-align:right;margin-top:30px}
  footer .btn{margin-left:8px}
  form.inline{display:inline}
  .badge{display:inline-block;padding:3px 8px;border-radius:999px;background:#eef;color:#333;font-size:.8rem}
</style>
</head>
<body>
<main>
  <h1>📦 Orders</h1>
  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>Total</th>
        <th>Pay</th>
        <th>Pay Status</th>
        <th>Status</th>
        <th>When</th>
        <th>Manage</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="7">ยังไม่มีคำสั่งซื้อ</td></tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td><a href="order_detail.php?id=<?= (int)$r['id'] ?>"><?= h($r['order_no']) ?></a></td>
        <td>฿<?= number_format((float)$r['amount'],2) ?> <span class="badge">(<?= (int)$r['items'] ?> ชิ้น)</span></td>
        <td><?= h($r['pay_label']) ?></td>
        <td><?= h(payStatusTH($r['payment_status'])) ?></td>
        <td><?= h(orderStatusTH($r['status'])) ?></td>
        <td><?= h($r['created_at']) ?></td>
        <td>
          <a class="btn" href="order_detail.php?id=<?= (int)$r['id'] ?>">เปิด</a>
          <?php if (!in_array(strtoupper((string)$r['status']), ['CANCELLED','DONE'], true)): ?>
            <form class="inline" method="post" action=""
                  onsubmit="return confirm('ยกเลิกออเดอร์นี้ใช่ไหม?');">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="cancel">
              <button class="btn red" type="submit">ยกเลิก</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>

  <footer>
  <a class="btn" href="products.php">จัดการสินค้า</a>
  <a class="btn" href="customers.php">ข้อมูลลูกค้า</a>
  <a class="btn" href="sales_report.php">รายงานยอดขาย</a>
  <a class="btn" href="logout.php">🚪 ออกจากระบบ</a>
</footer>
</main>
</body>
</html>
