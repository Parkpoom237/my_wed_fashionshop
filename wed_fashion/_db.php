<?php
declare(strict_types=1);

function db(): PDO {
  static $pdo;
  if ($pdo instanceof PDO) return $pdo;

  // ปรับค่าตามเครื่องคุณ
  $host = '127.0.0.1';
  $port = '3306';
  $dbname = 'fashionshop';
  $user = 'root';
  $pass = '';         // XAMPP ปกติว่าง

  $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
  $opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];
  return $pdo = new PDO($dsn, $user, $pass, $opts);
}
