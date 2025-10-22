<?php
require_once 'config.php';

function verify_password(string $plain, string $hashOrPlain): bool {
    if (str_starts_with($hashOrPlain, '$2y$') || str_starts_with($hashOrPlain, '$argon2')) {
        return password_verify($plain, $hashOrPlain);
    }
    if (strlen($hashOrPlain) === 64 && ctype_xdigit($hashOrPlain)) {
        return hash_equals($hashOrPlain, hash('sha256', $plain));
    }
    if (strlen($hashOrPlain) === 32 && ctype_xdigit($hashOrPlain)) {
        return hash_equals($hashOrPlain, md5($plain));
    }
    return hash_equals($hashOrPlain, $plain);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, email, password FROM admin_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u) {
            $error = 'ไม่พบบัญชีนี้';
        } elseif (!verify_password($password, $u['password'])) {
            $error = 'Email / Password ไม่ถูกต้อง';
        } else {
            $_SESSION['admin_id'] = (int)$u['id'];
            $_SESSION['admin']    = $u['email'];
            header('Location: index.php');
            exit;
        }
    } catch (Throwable $e) {
        $error = 'Database Error: '.$e->getMessage();
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Admin Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
  font-family: "Segoe UI", system-ui, sans-serif;
  background: #f3f4f6;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  margin: 0;
}
.card {
  background: white;
  padding: 2rem 2.5rem;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  width: 100%;
  max-width: 380px;
}
h2 {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #111827;
}
input {
  width: 100%;
  padding: 10px 12px;
  margin-bottom: 1rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 15px;
}
input:focus {
  border-color: #2563eb;
  outline: none;
  box-shadow: 0 0 0 2px rgba(37,99,235,0.3);
}
button {
  width: 100%;
  background: #2563eb;
  color: white;
  border: none;
  padding: 10px;
  border-radius: 6px;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s;
}
button:hover { background: #1e40af; }
.error {
  color: #dc2626;
  text-align: center;
  margin-bottom: 1rem;
  font-size: 0.95rem;
}
.footer {
  text-align: center;
  margin-top: 1.2rem;
  color: #6b7280;
  font-size: 0.85rem;
}
</style>
</head>
<body>
  <div class="card">
    <h2>เข้าสู่ระบบผู้ดูแล</h2>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="email" name="email" placeholder="อีเมล" required 
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <input type="password" name="password" placeholder="รหัสผ่าน" required>
      <button type="submit">เข้าสู่ระบบ</button>
    </form>
    <div class="footer">© <?= date('Y') ?> FashionShop Admin</div>
  </div>
</body>
</html>