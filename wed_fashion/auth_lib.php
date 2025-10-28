<?php
declare(strict_types=1);

/**
 * auth_lib.php
 * รวม helper เรื่อง session + ผู้ใช้ฝั่งลูกค้า/แอดมิน
 * - ออกแบบให้ "ไม่กระทบของเดิม" โดยเช็ก function_exists ก่อนประกาศทุกฟังก์ชัน
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_db.php';

/* =========================
 *  ฝั่งลูกค้า (Customer)
 * ========================= */

/** คืนข้อมูลลูกค้าปัจจุบันจาก session (หรือ null ถ้าไม่ได้ล็อกอิน) */
if (!function_exists('current_customer')) {
  function current_customer(): ?array {
    if (empty($_SESSION['customer_id'])) return null;

    $pdo = db();
    $st  = $pdo->prepare('SELECT id, name, email FROM customers WHERE id = ? LIMIT 1');
    $st->execute([ (int)$_SESSION['customer_id'] ]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    return $u ?: null;
  }
}

/** บังคับให้ต้องเป็นลูกค้าล็อกอิน; ถ้าไม่ใช่ให้ redirect ไปหน้า login พร้อม redirect กลับ */
if (!function_exists('require_customer')) {
  function require_customer(): void {
    if (current_customer() !== null) return;

    $back = $_SERVER['REQUEST_URI'] ?? 'index.php';
    // หน้า login อยู่โฟลเดอร์เดียวกันกับไฟล์นี้ (เช่น /wed_fashion/customer_login.php)
    header('Location: customer_login.php?redirect=' . urlencode($back));
    exit;
  }
}

/** login ลูกค้า: เซ็ตตัวแปร session ให้โค้ดเก่าใช้ต่อได้ */
if (!function_exists('login_customer')) {
  function login_customer(int $id, string $name): void {
    $_SESSION['customer_id']   = $id;
    $_SESSION['customer_name'] = $name;
  }
}

/** logout ลูกค้า: ลบตัวแปร session ที่เกี่ยวข้อง */
if (!function_exists('logout_customer')) {
  function logout_customer(): void {
    unset($_SESSION['customer_id'], $_SESSION['customer_name']);
  }
}


/* =========================
 *  ฝั่งแอดมิน (Admin) — เผื่อไฟล์ใน /admin ต้องใช้
 *  ใช้แบบหลวม ๆ: รองรับทั้งรูปแบบเก่า (admin_id) และแบบใหม่ (user[role]=admin)
 * ========================= */

if (!function_exists('is_admin')) {
  function is_admin(): bool {
    if (!empty($_SESSION['admin_id'])) return true;                // โค้ดเก่าใช้ admin_id
    if (!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') return true; // โค้ดใหม่
    return false;
  }
}

if (!function_exists('require_admin')) {
  function require_admin(): void {
    if (is_admin()) return;
    // เรียกจากโฟลเดอร์ /admin ให้เด้งไป login ภายในโฟลเดอร์เดียวกัน
    header('Location: login.php');
    exit;
  }
}