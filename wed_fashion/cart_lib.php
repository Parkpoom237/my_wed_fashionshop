<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

function cart_add(array $item): void {
  // Build a composite key to merge identical items (id+color+size)
  $key = ($item['id'] ?? '') . '|' . ($item['color'] ?? '') . '|' . ($item['size'] ?? '');
  foreach ($_SESSION['cart'] as &$it) {
    $k = ($it['id'] ?? '') . '|' . ($it['color'] ?? '') . '|' . ($it['size'] ?? '');
    if ($k === $key) {
      $it['qty'] = (int)$it['qty'] + (int)($item['qty'] ?? 1);
      return;
    }
  }
  $_SESSION['cart'][] = [
    'id'    => (string)($item['id'] ?? ''),
    'name'  => (string)($item['name'] ?? ''),
    'price' => (float)($item['price'] ?? 0),
    'color' => (string)($item['color'] ?? ''),
    'size'  => (string)($item['size'] ?? ''),
    'qty'   => (int)($item['qty'] ?? 1),
  ];
}

function cart_update_qty(string $id, string $color, string $size, int $qty): void {
  foreach ($_SESSION['cart'] as $i => $it) {
    if ($it['id'] === $id && ($it['color'] ?? '') === $color && ($it['size'] ?? '') === $size) {
      if ($qty <= 0) { unset($_SESSION['cart'][$i]); }
      else { $_SESSION['cart'][$i]['qty'] = $qty; }
      $_SESSION['cart'] = array_values($_SESSION['cart']);
      return;
    }
  }
}

function cart_remove(string $id, string $color, string $size): void {
  cart_update_qty($id, $color, $size, 0);
}

function cart_count_items(): int {
  $c = 0;
  foreach ($_SESSION['cart'] as $it) $c += (int)$it['qty'];
  return $c;
}

function cart_total(): float {
  $t = 0.0;
  foreach ($_SESSION['cart'] as $it) $t += ((float)$it['price'] * (int)$it['qty']);
  return $t;
}
