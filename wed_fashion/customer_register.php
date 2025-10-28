<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_db.php';

$redirect = $_GET['redirect'] ?? '';
$msg='';

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    $msg = 'โทเค็นไม่ถูกต้อง';
  } else {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');

    if ($name==='' || $email==='' || $pass==='') {
      $msg = 'กรอกข้อมูลให้ครบ';
    } else {
      $pdo = db();
      // check dup
      $c = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE email=?');
      $c->execute([$email]);
      if ($c->fetchColumn() > 0) {
        $msg = 'อีเมลนี้ถูกใช้แล้ว';
      } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO customers(name,email,password_hash,created_at) VALUES(?,?,?,NOW())')
            ->execute([$name,$email,$hash]);
        // auto login
        $_SESSION['customer_id'] = (int)$pdo->lastInsertId();
        $_SESSION['customer_name'] = $name;
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        $dest = $_POST['redirect'] ?? '';
        header('Location: ' . ($dest ?: 'index.php'));
        exit;
      }
    }
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>สมัครสมาชิก</title>
<style>
body{margin:0;background:#0b0d12;color:#eef2ff;font-family:system-ui,Segoe UI,Roboto,Arial}
.box{max-width:360px;margin:60px auto;background:#141823;border:1px solid rgba(255,255,255,.08);padding:20px;border-radius:12px}
input{width:100%;margin:8px 0;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#0c0f15;color:#fff}
input, button { box-sizing: border-box; max-width: 100%; }
button{width:100%;padding:10px;border-radius:8px;border:0;background:#7c5cff;color:#fff;cursor:pointer}
a{color:#7c5cff}.msg{background:#3b1e27;color:#ffd7de;padding:8px 10px;border-radius:8px;margin-bottom:8px}
</style>
</head>
<body>
<div class="box">
  <h2 style="margin:0 0 10px">สมัครสมาชิก</h2>
  <?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="redirect" value="<?= h($redirect) ?>">
    <label>ชื่อ</label>
    <input name="name" required>
    <label>อีเมล</label>
    <input type="email" name="email" required>
    <label>รหัสผ่าน</label>
    <input type="password" name="password" required>
    <button type="submit">สมัครสมาชิก</button>
  </form>
  <div style="margin-top:10px">มีบัญชีแล้ว? <a href="customer_login.php<?= $redirect? ('?redirect='.rawurlencode($redirect)) : '' ?>">เข้าสู่ระบบ</a></div>
  <div style="margin-top:6px">← <a href="index.php">กลับหน้าร้าน</a></div>
</div>
</body>
</html>
