<?php
declare(strict_types=1);

require_once 'config.php'; // อยู่โฟลเดอร์เดียวกัน ไม่ต้อง ../

if (!isset($_SESSION['admin_id'])) {  // ใช้คีย์เดียวกับ login.php
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    echo 'ไม่พบออร์เดอร์';
    exit;
}

$pdo = db();

// อัปเดตสถานะ (PRG กันยิงซ้ำ)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'NEW';
    $pay    = $_POST['payment_status'] ?? 'PENDING';

    $stmt = $pdo->prepare('UPDATE orders SET status = ?, payment_status = ? WHERE id = ?');
    $stmt->execute([$status, $pay, $id]);

    header('Location: order_detail.php?id=' . $id);
    exit;
}

// ดึงข้อมูลออร์เดอร์ + รายการสินค้า
$o = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$o->execute([$id]);
$order = $o->fetch();

if (!$order) {
    http_response_code(404);
    echo 'ไม่พบออร์เดอร์';
    exit;
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$id]);
$rows = $itemsStmt->fetchAll();

$addr = json_decode($order['address_json'] ?? '{}', true);
?>
<!doctype html>
<meta charset="utf-8">
<title>Order <?= htmlspecialchars((string)$order['order_no']) ?></title>
<link rel="stylesheet" href="https://unpkg.com/mvp.css">
<main>
  <h1>Order <?= htmlspecialchars((string)$order['order_no']) ?></h1>
  <p>
    ยอดสุทธิ: ฿<?= number_format((float)$order['grand'], 2) ?>
    | ชำระ: <?= htmlspecialchars((string)$order['payment_method']) ?>
    (<?= htmlspecialchars((string)$order['payment_status']) ?>)
  </p>

  <h3>ที่อยู่จัดส่ง</h3>
  <pre><?= htmlspecialchars(json_encode($addr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>

  <h3>สินค้า</h3>
  <table>
    <thead>
      <tr><th>สินค้า</th><th>ไซซ์</th><th>ราคา</th><th>จำนวน</th><th>รวม</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars((string)$r['name']) ?></td>
        <td><?= htmlspecialchars((string)$r['size']) ?></td>
        <td>฿<?= number_format((float)$r['price'], 2) ?></td>
        <td><?= (int)$r['qty'] ?></td>
        <td>฿<?= number_format((float)$r['line_total'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <form method="post" style="margin-top:16px">
    <label>สถานะออร์เดอร์
      <select name="status">
        <?php foreach (['ใหม่','กำลังดำเนินการ','จัดส่งแล้ว','เสร็จสิ้น','ยกเลิก'] as $st): ?>
          <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>สถานะการชำระเงิน
      <select name="payment_status">
        <?php foreach (['รอดำเนินการ','ชำระแล้ว','ล้มเหลว'] as $ps): ?>
          <option value="<?= $ps ?>" <?= $order['payment_status'] === $ps ? 'selected' : '' ?>><?= $ps ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <button type="submit">บันทึก</button>
  </form>

  <p><a href="index.php">← กลับ</a></p>
</main>