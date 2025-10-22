<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cart_lib.php';

$id    = $_POST['id']    ?? '';
$name  = $_POST['name']  ?? '';
$price = (float)($_POST['price'] ?? 0);
$color = $_POST['color'] ?? '';
$size  = $_POST['size']  ?? '';
$qty   = max(1, (int)($_POST['qty'] ?? 1));

cart_add([
  'id'    => $id,
  'name'  => $name,
  'price' => $price,
  'color' => $color,
  'size'  => $size,
  'qty'   => $qty,
]);

$dest = $_POST['redirect'] ?? '';
if ($dest === 'back' && isset($_SERVER['HTTP_REFERER'])) {
  // append ?added=1
  $sep = (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY) ? '&' : '?');
  header('Location: ' . $_SERVER['HTTP_REFERER'] . $sep . 'added=1');
} elseif ($dest) {
  header('Location: ' . $dest);
} else {
  header('Location: cart.php');
}
exit;
