<?php
declare(strict_types=1);
function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;
  $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
  $opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];
  try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
  } catch (Throwable $e) {
    http_response_code(500);
    exit('DB connect failed: ' . $e->getMessage());
  }
  return $pdo;
}
