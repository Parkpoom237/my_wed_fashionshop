<?php
// fashionshop/admin/update_order.php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = (string)($_POST['action'] ?? '');
$id     = (int)($_POST['id'] ?? 0);
$csrf   = (string)($_POST['csrf'] ?? '');

if ($id <= 0) { http_response_code(400); exit('Bad request'); }
if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) { http_response_code(403); exit('Bad CSRF'); }

try {
  if ($action === 'cancel') {
    // ยกเลิกออเดอร์
    $st = $pdo->prepare("UPDATE orders SET status='CANCELLED' WHERE id=?");
    $st->execute([$id]);
  }
  elseif ($action === 'update_fields') {
    // อัปเดต Pay / Pay Status / Status
    $pm = strtoupper(trim((string)($_POST['payment_method'] ?? '')));
    $ps = strtoupper(trim((string)($_POST['payment_status'] ?? 'PENDING')));
    $os = strtoupper(trim((string)($_POST['status'] ?? 'NEW')));

    // ป้องกันค่าที่ไม่รู้จัก
    $pm_allowed = ['TRANSFER','COD',''];
    $ps_allowed = ['PENDING','PAID','FAILED','CANCELLED'];
    $os_allowed = ['NEW','PROCESSING','SHIPPED','DONE','CANCELLED'];

    if (!in_array($pm, $pm_allowed, true)) $pm = '';
    if (!in_array($ps, $ps_allowed, true)) $ps = 'PENDING';
    if (!in_array($os, $os_allowed, true)) $os = 'NEW';

    // บันทึก
    $st = $pdo->prepare("UPDATE orders
                         SET payment_method = ?, payment_status = ?, status = ?
                         WHERE id = ?");
    $st->execute([$pm, $ps, $os, $id]);
  }

  // กลับไปหน้าเดิม
  header('Location: order_detail.php?id='.(string)$id);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo 'Update error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
