<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_db.php';

function current_customer(): ?array {
  if (empty($_SESSION['customer_id'])) return null;
  $pdo = db();
  $st = $pdo->prepare('SELECT id, name, email FROM customers WHERE id = ?');
  $st->execute([$_SESSION['customer_id']]);
  $u = $st->fetch(PDO::FETCH_ASSOC);
  return $u ?: null;
}
