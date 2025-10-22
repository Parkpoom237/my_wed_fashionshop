<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cart_lib.php';
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'items' => array_values($_SESSION['cart'] ?? []),
  'total' => cart_total(),
], JSON_UNESCAPED_UNICODE);
