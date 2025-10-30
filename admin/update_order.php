<?php
// fashionshop/admin/update_order.php
declare(strict_types=1);

session_start();

/* ----- Path base ----- */
define('APP_ROOT', dirname(__DIR__));
define('WEB_DIR',  APP_ROOT . '/wed_fashion');

require_once WEB_DIR . '/_db.php';
require_once WEB_DIR . '/config.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ===== ต้องล็อกอินก่อน ===== */
if (!isset($_SESSION['admin_id'])) {
  header('Location: login.php'); exit;
}

/* ===== รับพารามิเตอร์พื้นฐาน ===== */
$action  = (string)($_POST['action'] ?? '');
$orderId = (int)($_POST['id'] ?? 0);
$csrf    = (string)($_POST['csrf'] ?? '');

if ($orderId <= 0) { http_response_code(400); exit('Bad request'); }
if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) { http_response_code(403); exit('Bad CSRF'); }

/* ===== tables ใช้กันตัดซ้ำ/คืนซ้ำ ===== */
function ensure_inventory_log_table(PDO $pdo): void {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS inventory_log (
      order_id   INT PRIMARY KEY,
      applied    TINYINT(1) NOT NULL DEFAULT 0,
      reversed   TINYINT(1) NOT NULL DEFAULT 0,
      applied_at TIMESTAMP NULL DEFAULT NULL,
      reversed_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB
  ");
}
ensure_inventory_log_table($pdo);

function get_inventory_log(PDO $pdo, int $orderId): array {
  $st = $pdo->prepare("SELECT * FROM inventory_log WHERE order_id = ?");
  $st->execute([$orderId]);
  return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}
function mark_applied(PDO $pdo, int $orderId): void {
  $st = $pdo->prepare("
    INSERT INTO inventory_log (order_id, applied, reversed, applied_at)
    VALUES (?, 1, 0, NOW())
    ON DUPLICATE KEY UPDATE applied=1, applied_at=IFNULL(applied_at, NOW())
  ");
  $st->execute([$orderId]);
}
function mark_reversed(PDO $pdo, int $orderId): void {
  $st = $pdo->prepare("
    INSERT INTO inventory_log (order_id, applied, reversed, reversed_at)
    VALUES (?, 0, 1, NOW())
    ON DUPLICATE KEY UPDATE reversed=1, reversed_at=NOW()
  ");
  $st->execute([$orderId]);
}

/* ===== helpers ===== */
function fetch_order_items(PDO $pdo, int $orderId): array {
  $sql = "
    SELECT
      oi.product_id,
      UPPER(TRIM(COALESCE(oi.size, ''))) AS size,
      COALESCE(oi.qty,0)                 AS qty
    FROM order_items oi
    WHERE oi.order_id = ?
  ";
  $st = $pdo->prepare($sql);
  $st->execute([$orderId]);
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
function stock_column_for_size(string $size): ?string {
  return match (strtoupper(trim($size))) {
    'XS' => 'stock_xs',
    'S'  => 'stock_s',
    'M'  => 'stock_m',
    'L'  => 'stock_l',
    'XL' => 'stock_xl',
    'XXL'=> 'stock_xxl',
    'F'  => 'stock_f',
    default => null,
  };
}

/**
 * ตัด/คืนสต็อกที่ตาราง products โดยตรง
 * $mode = 'decrease' | 'increase'
 * ทำงานใน transaction (เปิดเองถ้ายังไม่ได้เปิด)
 */
function apply_inventory_from_order(PDO $pdo, int $orderId, string $mode = 'decrease'): void {
  $mode = ($mode === 'increase') ? 'increase' : 'decrease';
  $rows = fetch_order_items($pdo, $orderId);
  if (!$rows) return;

  $manageTx = !$pdo->inTransaction();
  if ($manageTx) $pdo->beginTransaction();

  try {
    foreach ($rows as $r) {
      $pid  = (int)$r['product_id'];
      $size = (string)$r['size'];
      $qty  = (int)$r['qty'];
      if ($pid <= 0 || $qty <= 0) continue;

      $col = stock_column_for_size($size);
      if (!$col) continue; // size ไม่แมตช์คอลัมน์ใน products

      if ($mode === 'decrease') {
        $sql = "UPDATE products SET $col = GREATEST(COALESCE($col,0) - ?, 0) WHERE id = ?";
        $pdo->prepare($sql)->execute([$qty, $pid]);
      } else { // increase
        $sql = "UPDATE products SET $col = COALESCE($col,0) + ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$qty, $pid]);
      }
    }
    if ($manageTx) $pdo->commit();
  } catch (Throwable $e) {
    if ($manageTx && $pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }
}

/* ===== main ===== */
try {
  if ($action === 'cancel') {
    // ยกเลิกออเดอร์ + คืนสต็อกถ้าเคยตัด
    $pdo->beginTransaction();

    $cur = $pdo->prepare("SELECT status, payment_status FROM orders WHERE id = ? FOR UPDATE");
    $cur->execute([$orderId]);
    $row = $cur->fetch(PDO::FETCH_ASSOC);
    if (!$row) { $pdo->rollBack(); throw new RuntimeException('Order not found'); }

    $pdo->prepare("
      UPDATE orders
         SET status='CANCELLED',
             payment_status = IF(payment_status='PAID','CANCELLED',payment_status)
       WHERE id=?
    ")->execute([$orderId]);

    $log = get_inventory_log($pdo, $orderId);
    if (!empty($log) && (int)$log['applied'] === 1 && (int)$log['reversed'] === 0) {
      apply_inventory_from_order($pdo, $orderId, 'increase');
      mark_reversed($pdo, $orderId);
    }

    $pdo->commit();
  }
  elseif ($action === 'update_fields') {
    $pm = strtoupper(trim((string)($_POST['payment_method'] ?? '')));
    $ps = strtoupper(trim((string)($_POST['payment_status'] ?? 'PENDING')));
    $os = strtoupper(trim((string)($_POST['status'] ?? 'NEW')));

    $pm_allowed = ['TRANSFER','COD',''];
    $ps_allowed = ['PENDING','PAID','FAILED','CANCELLED'];
    $os_allowed = ['NEW','PROCESSING','SHIPPED','DONE','CANCELLED'];

    if (!in_array($pm, $pm_allowed, true)) $pm = '';
    if (!in_array($ps, $ps_allowed, true)) $ps = 'PENDING';
    if (!in_array($os, $os_allowed, true)) $os = 'NEW';

    $pdo->beginTransaction();

    $cur = $pdo->prepare("SELECT payment_status, status FROM orders WHERE id = ? FOR UPDATE");
    $cur->execute([$orderId]);
    $before = $cur->fetch(PDO::FETCH_ASSOC);
    if (!$before) { $pdo->rollBack(); throw new RuntimeException('Order not found'); }

    $pdo->prepare("
      UPDATE orders
         SET payment_method = ?, payment_status = ?, status = ?
       WHERE id = ?
    ")->execute([$pm, $ps, $os, $orderId]);

    // ตัดเมื่อเพิ่งเปลี่ยนเป็นจ่ายแล้ว/สำเร็จ
    $shouldDeductNow =
      ($ps === 'PAID' || $os === 'DONE') &&
      !($before['payment_status'] === 'PAID' || $before['status'] === 'DONE');

    $log = get_inventory_log($pdo, $orderId);
    $alreadyApplied = !empty($log) && (int)$log['applied'] === 1;

    if ($shouldDeductNow && !$alreadyApplied) {
      apply_inventory_from_order($pdo, $orderId, 'decrease');
      mark_applied($pdo, $orderId);
    }

    // ถ้าถูกเปลี่ยนกลับเป็นสถานะที่ไม่ควรตัดแล้ว และเคยตัดไปแล้ว → คืน
    $becameNotApplicable =
      !($ps === 'PAID' || $os === 'DONE') &&
      ($before['payment_status'] === 'PAID' || $before['status'] === 'DONE');

    $alreadyReversed = !empty($log) && (int)$log['reversed'] === 1;

    if ($becameNotApplicable && !$alreadyReversed && $alreadyApplied) {
      apply_inventory_from_order($pdo, $orderId, 'increase');
      mark_reversed($pdo, $orderId);
    }

    $pdo->commit();
  }

  header('Location: order_detail.php?id='.(string)$orderId);
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) { $pdo->rollBack(); }
  http_response_code(500);
  echo 'Update error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
