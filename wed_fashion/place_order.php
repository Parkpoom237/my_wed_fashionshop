<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_db.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* ---------- ตรวจตะกร้า ---------- */
if (empty($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
  echo "Cart ว่าง";
  exit;
}

/* ---------- รับที่อยู่ + วิธีจ่าย ---------- */
$addr = [
  'full_name' => trim($_POST['full_name'] ?? ''),
  'phone'     => trim($_POST['phone'] ?? ''),
  'line1'     => trim($_POST['line1'] ?? ''),
  'line2'     => trim($_POST['line2'] ?? ''),
  'district'  => trim($_POST['district'] ?? ''),
  'province'  => trim($_POST['province'] ?? ''),
  'postcode'  => trim($_POST['postcode'] ?? '')
];
foreach (['full_name','phone','line1','district','province','postcode'] as $k) {
  if ($addr[$k] === '') { echo "ที่อยู่ไม่ครบ: $k"; exit; }
}

$method = (($_POST['payment_method'] ?? 'COD') === 'CARD') ? 'CARD' : 'COD';

/* ---------- คูปอง + ยอดรวม (ปล่อยให้ใช้ฟังก์ชันเดิมของคุณ ถ้ามี) ---------- */
$code   = trim($_POST['coupon_code'] ?? '') ?: null;
if (function_exists('coupon_lookup_db')) {
  $coupon = coupon_lookup_db($code);
  $_SESSION['coupon'] = $coupon;
} else {
  $coupon = null;
  $_SESSION['coupon'] = null;
}
if (function_exists('calc_totals')) {
  $tot = calc_totals($_SESSION['cart'], $coupon);
} else {
  // fallback ง่าย ๆ ถ้าไม่มีฟังก์ชัน
  $subtotal = 0;
  $items = [];
  foreach ($_SESSION['cart'] as $it) {
    $line = (float)$it['price'] * (int)$it['qty'];
    $subtotal += $line;
    $items[] = [
      'id'         => (int)$it['id'],
      'name'       => (string)$it['name'],
      'size'       => (string)($it['size'] ?? ''),
      'qty'        => (int)$it['qty'],
      'price'      => (float)$it['price'],
      'line_total' => $line
    ];
  }
  $tot = [
    'items'    => $items,
    'subtotal' => $subtotal,
    'discount' => 0,
    'shipping' => 0,
    'vat'      => 0,
    'grand'    => $subtotal
  ];
}

/* ---------- helper: map ไซซ์ -> ชื่อคอลัมน์ในตาราง products ---------- */
/* ปรับชื่อคอลัมน์ให้ตรงกับฐานข้อมูลของคุณ */
function stock_column_for_size(string $size): ?string {
  $s = strtoupper(trim($size));
  return match ($s) {
    'XS' => 'stock_xs',
    'S'  => 'stock_s',
    'M'  => 'stock_m',
    'L'  => 'stock_l',
    'XL' => 'stock_xl',
    'XXL'=> 'stock_xxl',
    'F', 'FREE', 'ONESIZE', 'ONE', '' => 'stock_f', // ฟรีไซซ์
    default => 'stock', // ถ้ามีคอลัมน์เดียวรวมสต็อกไว้
  };
}

/* ---------- ตรวจสต็อกแบบ SQL ---------- */
$pdo = db();

/* ตรวจสต็อกก่อนเริ่มธุรกรรม เพื่อบอกได้เร็ว */
foreach ($_SESSION['cart'] as $l) {
  $pid  = (int)$l['id'];
  $size = (string)($l['size'] ?? '');
  $qty  = max(1, (int)$l['qty']);

  $col = stock_column_for_size($size);
  if (!$col) { echo "ไม่รู้จะตัดสต็อกจากคอลัมน์ไหน (size=$size)"; exit; }

  // ดึงคงเหลือ
  $row = $pdo->prepare("SELECT {$col} AS remain FROM products WHERE id = ?");
  $row->execute([$pid]);
  $remain = (int)($row->fetchColumn() ?: 0);

  if ($remain < $qty) {
    echo "สต็อกไม่พอ: สินค้า {$pid} ไซซ์ {$size} (คงเหลือ {$remain})";
    exit;
  }
}

/* ---------- เริ่มบันทึกคำสั่งซื้อ + ตัดสต็อก (transaction) ---------- */
$pdo->beginTransaction();

try {
  $orderNo = 'ORD' . date('YmdHis') . substr((string)mt_rand(), 0, 4);

  // ใครคือผู้สั่งซื้อ
  $uid = $_SESSION['customer_id'] ?? null;
  if (function_exists('current_user_id')) {
    $uid = current_user_id();
  }

  // บันทึก order
  $ins = $pdo->prepare("
    INSERT INTO orders
      (order_no, user_id, address_json, coupon_code,
       subtotal, discount, shipping, vat, grand,
       payment_method, payment_status, status, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,? , NOW())
  ");
  $ins->execute([
    $orderNo,
    $uid,
    json_encode($addr, JSON_UNESCAPED_UNICODE),
    $coupon['code'] ?? null,
    $tot['subtotal'], $tot['discount'], $tot['shipping'], $tot['vat'], $tot['grand'],
    $method,
    'PENDING',      // อัปเดตเป็น PAID ภายหลัง
    'NEW'
  ]);
  $oid = (int)$pdo->lastInsertId();

  // เตรียม SQL
  $insItem = $pdo->prepare("
    INSERT INTO order_items(order_id, product_id, name, size, price, qty, line_total)
    VALUES (?,?,?,?,?,?,?)
  ");
  $selectForUpdate = $pdo->prepare("
    SELECT id, stock, stock_xs, stock_s, stock_m, stock_l, stock_xl, stock_xxl, stock_f
    FROM products
    WHERE id = ?
    FOR UPDATE
  ");

  // บันทึกรายการ + ตัดสต็อกแบบล็อกแถว
  foreach ($tot['items'] as $it) {
    $pid   = (int)$it['id'];
    $name  = (string)$it['name'];
    $size  = (string)($it['size'] ?? '');
    $qty   = max(1, (int)$it['qty']);
    $price = (float)$it['price'];
    $line  = (float)$it['line_total'];

    // รายการสินค้าใน order_items
    $insItem->execute([$oid, $pid, $name, $size, $price, $qty, $line]);

    // คอลัมน์สต็อก
    $col = stock_column_for_size($size);
    if (!$col) {
      throw new RuntimeException("ไม่พบคอลัมน์สต็อกสำหรับไซซ์ {$size}");
    }

    // ล็อกแถวสินค้า
    $selectForUpdate->execute([$pid]);
    $prod = $selectForUpdate->fetch(PDO::FETCH_ASSOC);
    if (!$prod) {
      throw new RuntimeException("ไม่พบสินค้า ID {$pid}");
    }

    $remain = (int)($prod[$col] ?? 0);
    if ($remain < $qty) {
      throw new RuntimeException("สต็อกไม่พอสำหรับ {$name} ไซซ์ {$size} (คงเหลือ {$remain})");
    }

    // ตัดสต็อก
    $pdo->prepare("UPDATE products SET {$col} = {$col} - :q WHERE id = :id")
        ->execute([':q' => $qty, ':id' => $pid]);
  }

  $pdo->commit();

  // ล้างตะกร้า
  $_SESSION['cart']   = [];
  $_SESSION['coupon'] = null;

  if ($method === 'CARD') {
    header("Location: payment_intent.php?order_no=" . urlencode($orderNo));
    exit;
  }

  echo "สั่งซื้อสำเร็จ (COD) เลขออร์เดอร์: " . htmlspecialchars($orderNo);

} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo "ผิดพลาด: " . $e->getMessage();
}
