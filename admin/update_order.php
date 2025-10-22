<?php
require_once __DIR__ . '/_db.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$pay_status = $_POST['pay_status'] ?? '';
$status     = $_POST['status'] ?? '';

$allowPay = ['unpaid','paid'];
$allowSt  = ['new','packed','shipped','cancel'];

if ($id <= 0 || !in_array($pay_status,$allowPay,true) || !in_array($status,$allowSt,true)) {
  http_response_code(400);
  exit('Bad request');
}

// อัปเดต
if ($pay_status === 'paid') {
  $sql = "UPDATE orders SET pay_status=?, status=?, paid_at = COALESCE(paid_at, NOW()) WHERE id=?";
  $params = [$pay_status, $status, $id];
} else {
  $sql = "UPDATE orders SET pay_status=?, status=? WHERE id=?";
  $params = [$pay_status, $status, $id];
}

$st = $pdo->prepare($sql);
$st->execute($params);

// กลับไปหน้าเดิม
header('Location: index.php');
exit;