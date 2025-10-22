<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cart_lib.php';

$id    = $_POST['id'] ?? '';
$color = $_POST['color'] ?? '';
$size  = $_POST['size'] ?? '';
$qty   = max(0, (int)($_POST['qty'] ?? 1));

cart_update_qty($id, $color, $size, $qty);

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
exit;
