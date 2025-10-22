<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$pdo = db(); $msg = '';
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    $msg = 'โทเค็นไม่ถูกต้อง กรุณาลองใหม่';
  } else {
    $email = trim($_POST['email'] ?? ''); $pass = (string)($_POST['password'] ?? '');
    if ($email==='' || $pass==='') {
      $msg = 'กรอกอีเมลและรหัสผ่านให้ครบ';
    } else {
      $st = $pdo->prepare('SELECT id,name,email,password_hash FROM customers WHERE email=? LIMIT 1');
      $st->execute([$email]); $u = $st->fetch();
      if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['customer_id'] = (int)$u['id'];
        $_SESSION['customer_name'] = (string)$u['name'];
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        $dest = $_POST['redirect'] ?? 'index.php';
        header('Location: ' . ($dest ?: 'index.php')); exit;
      } else { $msg = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'; }
    }
  }
  $_SESSION['csrf'] = bin2hex(random_bytes(16)); $csrf = $_SESSION['csrf'];
}
?>
<!doctype html><html lang="th"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>เข้าสู่ระบบ</title>
<style>
body{margin:0;background:#0b0d12;color:#eef2ff;font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial}
.box{max-width:360px;margin:60px auto;background:#141823;border:1px solid rgba(255,255,255,.08);padding:20px;border-radius:12px}
input{width:100%;margin:8px 0;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#0c0f15;color:#fff}
button{width:100%;padding:10px;border-radius:8px;border:0;background:#7c5cff;color:#fff;cursor:pointer}
a{color:#7c5cff}.msg{background:#3b1e27;color:#ffd7de;padding:8px 10px;border-radius:8px;margin-bottom:8px}
</style></head><body>
<div class="box">
<h2 style="margin:0 0 10px">เข้าสู่ระบบ</h2>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
<form method="post" action="customer_login.php">
  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
  <input type="hidden" name="redirect" value="<?= h($redirect) ?>">
  <label>อีเมล</label><input type="email" name="email" placeholder="you@example.com" required>
  <label>รหัสผ่าน</label><input type="password" name="password" required>
  <button type="submit">เข้าสู่ระบบ</button>
</form>
<div style="margin-top:10px">ยังไม่มีบัญชี? <a href="customer_register.php">สมัครสมาชิก</a></div>
<div style="margin-top:6px">← <a href="index.php">กลับหน้าร้าน</a></div>
</div></body></html>
