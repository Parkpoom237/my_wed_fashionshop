<?php
declare(strict_types=1);

require_once __DIR__ . '/../wed_fashion/config.php';
require_once __DIR__ . '/../wed_fashion/_db.php';
require_once __DIR__ . '/../wed_fashion/auth_lib.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!function_exists('is_admin') || !is_admin()) {
  header('Location: login.php');
  exit;
}

$pdo = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ตรวจว่ามีสมาชิกนี้อยู่ไหม
$st = $pdo->prepare("SELECT id, email FROM customers WHERE id=?");
$st->execute([$id]);
$customer = $st->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
  echo "<script>alert('ไม่พบสมาชิกที่ต้องการลบ');window.location='customers.php';</script>";
  exit;
}

// ลบข้อมูลในฐานข้อมูล
$del = $pdo->prepare("DELETE FROM customers WHERE id=?");
$del->execute([$id]);

echo "<script>alert('ลบสมาชิกอีเมล {$customer['email']} เรียบร้อยแล้ว');window.location='customers.php';</script>";
exit;
