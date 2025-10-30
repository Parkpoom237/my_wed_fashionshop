<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cart_lib.php';

$id    = (string)($_POST['id'] ?? '');
$color = (string)($_POST['color'] ?? '');
$size  = (string)($_POST['size'] ?? '');
$qty   = max(0, (int)($_POST['qty'] ?? 1));

cart_update_qty($id, $color, $size, $qty);

if (!empty($_POST['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'ok'    => true,
    'count' => cart_count_items(),
    'total' => cart_total(),
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
exit;
