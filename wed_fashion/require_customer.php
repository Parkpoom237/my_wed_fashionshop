<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['customer_id'])) {
  $uri = $_SERVER['REQUEST_URI'] ?? 'index.php';
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $dest = ($host && strpos($uri,'http')!==0) ? ('http://' . $host . $uri) : $uri;
  header('Location: customer_login.php?redirect=' . urlencode($dest));
  exit;
}
