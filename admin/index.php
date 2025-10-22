<?php
// fashionshop/admin/index.php
require_once 'config.php';         // ใช้ config.php เพื่อเปิด session + โหลด db()

// ต้องล็อกอินก่อน (ให้ตรงกับที่ login.php ตั้งไว้)
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// ดึงรายการออร์เดอร์จากฐานข้อมูล
$sql = "
SELECT
  o.id,
  o.order_no,
  COALESCE(
    NULLIF(o.grand, 0),
    NULLIF(o.total, 0),
    (SELECT COALESCE(SUM(COALESCE(oi.subtotal, oi.price*oi.qty)), 0)
     FROM order_items oi
     WHERE oi.order_id = o.id)
  ) AS amount,
  o.payment_method,
  o.payment_status,
  o.status,
  o.created_at
FROM orders o
ORDER BY o.id DESC
LIMIT 100
";
$rows = db()->query($sql)->fetchAll();
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
body {
  font-family: 'Inter', sans-serif;
  background: #f4f6fa;
  margin: 0;
  padding: 0;
  color: #222;
}
main {
  max-width: 1100px;
  margin: 40px auto;
  background: #fff;
  border-radius: 12px;
  padding: 30px 40px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.05);
}
h1 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2b3a67;
  border-bottom: 3px solid #2b3a67;
  padding-bottom: 10px;
  margin-bottom: 25px;
}
table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 10px;
  overflow: hidden;
  font-size: 0.95rem;
}
th, td {
  padding: 12px 10px;
  text-align: center;
}
th {
  background-color: #2b3a67;
  color: #fff;
  font-weight: 500;
}
tr:nth-child(even) {
  background: #f9f9f9;
}
a {
  color: #2b3a67;
  text-decoration: none;
}
a:hover {
  text-decoration: underline;
}
a.btn {
  display: inline-block;
  background: #4f6ed9;
  color: #fff !important;
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
  transition: 0.2s;
}
a.btn:hover {
  background: #2b3a67;
}
footer {
  text-align: right;
  margin-top: 30px;
}
footer a {
  margin-left: 10px;
}
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
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <a href="order_detail.php?id=<?= (int)$r['id'] ?>">
                <?= htmlspecialchars($r['order_no']) ?>
              </a>
            </td>
            <td>฿<?= number_format((float)$r['amount'], 2) ?></td>
            <td><?= htmlspecialchars($r['payment_method']) ?></td>
            <td><?= htmlspecialchars($r['payment_status']) ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
            <td><a class="btn" href="order_detail.php?id=<?= (int)$r['id'] ?>">แก้ไข</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <footer>
    <a class="btn" href="products.php"> จัดการสินค้า</a>
    <a class="btn" href="logout.php">🚪 ออกจากระบบ</a>
  </footer>
</main>
</body>
</html>