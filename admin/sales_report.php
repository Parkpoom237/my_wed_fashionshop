<?php
// fashionshop/admin/sales_report.php
declare(strict_types=1);

require_once __DIR__ . '/../wed_fashion/config.php';
require_once __DIR__ . '/../wed_fashion/_db.php';

if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$pdo   = db();
$mode  = ($_GET['mode'] ?? 'day') === 'month' ? 'month' : 'day';
$start = isset($_GET['start']) ? trim($_GET['start']) : '';
$end   = isset($_GET['end'])   ? trim($_GET['end'])   : '';

$cond   = " WHERE o.payment_status='PAID' AND COALESCE(o.status,'NEW') <> 'CANCELLED' ";
$params = [];
if ($start !== '') { $cond .= " AND DATE(o.created_at) >= :start "; $params[':start'] = $start; }
if ($end   !== '') { $cond .= " AND DATE(o.created_at) <= :end ";   $params[':end']   = $end;  }

if ($mode === 'month') {
  $sql = "
    SELECT DATE_FORMAT(o.created_at,'%Y-%m') AS label,
           COUNT(DISTINCT o.id)              AS orders,
           COALESCE(SUM(oi.qty),0)           AS items,
           COALESCE(SUM(oi.price*oi.qty),0)  AS total
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    $cond
    GROUP BY DATE_FORMAT(o.created_at,'%Y-%m')
    ORDER BY label DESC
    LIMIT 36
  ";
} else {
  $sql = "
    SELECT DATE(o.created_at)                AS label,
           COUNT(DISTINCT o.id)              AS orders,
           COALESCE(SUM(oi.qty),0)           AS items,
           COALESCE(SUM(oi.price*oi.qty),0)  AS total
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    $cond
    GROUP BY DATE(o.created_at)
    ORDER BY label DESC
    LIMIT 180
  ";
}

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$sumSql = "
  SELECT COUNT(DISTINCT o.id)             AS orders,
         COALESCE(SUM(oi.qty),0)          AS items,
         COALESCE(SUM(oi.price*oi.qty),0) AS total
  FROM orders o
  LEFT JOIN order_items oi ON oi.order_id = o.id
  $cond
";
$st2 = $pdo->prepare($sumSql);
$st2->execute($params);
$sum = $st2->fetch(PDO::FETCH_ASSOC) ?: ['orders'=>0,'items'=>0,'total'=>0];

/* ---------- NEW: Top-selling products (10 อันดับ) ---------- */
$topSql = "
  SELECT 
      oi.product_id,
      COALESCE(p.name, oi.name) AS product_name,
      SUM(oi.qty) AS total_qty,
      SUM(
        CASE 
          WHEN oi.line_total IS NOT NULL AND oi.line_total > 0
          THEN oi.line_total
          ELSE oi.qty * oi.price
        END
      ) AS total_amount
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  LEFT JOIN products p ON p.id = oi.product_id
  $cond
  GROUP BY oi.product_id, product_name
  ORDER BY total_amount DESC
  LIMIT 10
";
$st3 = $pdo->prepare($topSql);
$st3->execute($params);
$top_products = $st3->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>รายงานยอดขาย</title>
<style>
  body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#f4f6fa;margin:0;color:#222}
  main{max-width:1100px;margin:40px auto;background:#fff;border-radius:12px;padding:30px 40px;box-shadow:0 8px 20px rgba(0,0,0,.05)}
  h1{font-size:1.8rem;margin:0 0 18px}
  .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px}
  .toolbar input{padding:6px 10px;border:1px solid #d5d9e2;border-radius:6px}
  .toolbar a.btn,.toolbar button{background:#4f6ed9;color:#fff;padding:7px 12px;border-radius:6px;text-decoration:none;border:none;cursor:pointer}
  .tabs a{padding:6px 10px;border:1px solid #d5d9e2;border-radius:6px;text-decoration:none;color:#333}
  .tabs a.active{background:#2b3a67;color:#fff;border-color:#2b3a67}
  table{width:100%;border-collapse:collapse}
  th,td{padding:10px;border-bottom:1px solid #eef0f5;text-align:center}
  th{background:#f6f8fc}
  .summary{display:flex;gap:16px;margin:14px 0 24px;flex-wrap:wrap}
  .card{background:#f6f8fc;border:1px solid #e6e9f2;border-radius:10px;padding:12px 16px;min-width:200px}
  .section-title{margin-top:36px;font-size:1.2rem}
</style>
</head>
<body>
<main>
  <h1>📈 รายงานยอดขาย (<?= $mode==='month' ? 'รายเดือน' : 'รายวัน' ?>)</h1>

  <form method="get" class="toolbar" action="">
    <div class="tabs">
      <a href="?mode=day<?= $start ? '&start='.h($start) : '' ?><?= $end ? '&end='.h($end) : '' ?>" class="<?= $mode==='day'?'active':'' ?>">รายวัน</a>
      <a href="?mode=month<?= $start ? '&start='.h($start) : '' ?><?= $end ? '&end='.h($end) : '' ?>" class="<?= $mode==='month'?'active':'' ?>">รายเดือน</a>
    </div>
    <span>ช่วงวันที่:</span>
    <input type="date" name="start" value="<?= h($start) ?>">
    <input type="date" name="end"   value="<?= h($end) ?>">
    <input type="hidden" name="mode" value="<?= h($mode) ?>">
    <button type="submit">กรอง</button>
    <a class="btn" href="sales_report.php?mode=<?= h($mode) ?>">ล้างตัวกรอง</a>
    <a class="btn" href="index.php" style="margin-left:auto;">⬅ กลับหน้าออเดอร์</a>
  </form>

  <div class="summary">
    <div class="card"><div>จำนวนออเดอร์</div><div style="font-size:1.3rem;font-weight:600"><?= number_format((int)$sum['orders']) ?></div></div>
    <div class="card"><div>รวมจำนวนชิ้น</div><div style="font-size:1.3rem;font-weight:600"><?= number_format((int)$sum['items']) ?></div></div>
    <div class="card"><div>ยอดรวม (บาท)</div><div style="font-size:1.3rem;font-weight:600">฿<?= number_format((float)$sum['total'],2) ?></div></div>
  </div>

  <table>
    <thead>
      <tr>
        <th><?= $mode==='month' ? 'เดือน (YYYY-MM)' : 'วันที่' ?></th>
        <th>จำนวนออเดอร์</th>
        <th>จำนวนชิ้น</th>
        <th>ยอดรวม (บาท)</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="4">ไม่พบข้อมูลในช่วงที่เลือก</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['label']) ?></td>
          <td><?= number_format((int)$r['orders']) ?></td>
          <td><?= number_format((int)$r['items']) ?></td>
          <td>฿<?= number_format((float)$r['total'],2) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <!-- NEW: Top-selling products table -->
  <h3 class="section-title">🏆 สินค้าที่ขายดีที่สุด (Top 10)</h3>
  <table>
    <thead>
      <tr>
        <th>ลำดับ</th>
        <th>สินค้า</th>
        <th>จำนวนที่ขาย</th>
        <th>ยอดรวม (บาท)</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$top_products): ?>
        <tr><td colspan="4">ยังไม่มีข้อมูลสินค้าขายดีในช่วงที่เลือก</td></tr>
      <?php else: foreach ($top_products as $i => $p): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= h($p['product_name']) ?></td>
          <td><?= number_format((int)$p['total_qty']) ?></td>
          <td>฿<?= number_format((float)$p['total_amount'], 2) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</main>
</body>
</html>
