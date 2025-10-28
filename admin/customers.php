<?php
declare(strict_types=1);

/* ใช้ของฝั่ง wed_fashion เดิม */
require_once __DIR__ . '/../wed_fashion/config.php';
require_once __DIR__ . '/../wed_fashion/_db.php';
require_once __DIR__ . '/../wed_fashion/auth_lib.php';

/* เปิด session ถ้ายังไม่เปิด */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* ต้องเป็นแอดมินเท่านั้น */
if (!function_exists('is_admin') || !is_admin()) {
  header('Location: login.php');
  exit;
}

$pdo = db();

/* ---------- ค้นหา + เพจ ---------- */
$q     = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per   = 20;
$off   = ($page - 1) * $per;

$params = [];
$where  = '';
if ($q !== '') {
  $where   = "WHERE name LIKE :kw OR email LIKE :kw";
  $params[':kw'] = "%{$q}%";
}

/* นับจำนวนทั้งหมดจากตาราง customers */
$stTotal = $pdo->prepare("SELECT COUNT(*) FROM customers {$where}");
$stTotal->execute($params);
$total = (int)$stTotal->fetchColumn();
$pages = max(1, (int)ceil($total / $per));

/* ดึงรายการหน้า นี้ */
$sql = "SELECT id, name, email FROM customers {$where} ORDER BY id DESC LIMIT :off, :per";
$st  = $pdo->prepare($sql);
foreach ($params as $k => $v) {
  $st->bindValue($k, $v, PDO::PARAM_STR);
}
$st->bindValue(':off', $off, PDO::PARAM_INT);
$st->bindValue(':per', $per, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* helper */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Admin — ข้อมูลสมาชิก</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
  body{font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:#f4f6fa;margin:0}
  main{max-width:1100px;margin:40px auto;background:#fff;border-radius:12px;padding:26px 30px;box-shadow:0 8px 20px rgba(0,0,0,.05)}
  h1{margin:0 0 18px;font-size:1.6rem;border-bottom:3px solid #2b3a67;color:#2b3a67;padding-bottom:8px}
  form.search{display:flex;gap:8px;margin:8px 0 16px}
  form.search input{flex:1;padding:10px;border:1px solid #ccd3e0;border-radius:8px}
  form.search button{padding:10px 14px;border:0;background:#2b3a67;color:#fff;border-radius:8px;cursor:pointer}
  table{width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden}
  th,td{padding:10px;border-bottom:1px solid #eef2f7;text-align:left}
  th{background:#2b3a67;color:#fff}
  tr:nth-child(even){background:#f9fbff}
  .bar{display:flex;justify-content:space-between;align-items:center;margin-top:10px}
  .pager a{display:inline-block;margin:0 3px;padding:6px 10px;border-radius:6px;border:1px solid #ccd3e0;text-decoration:none;color:#2b3a67}
  .pager strong{display:inline-block;margin:0 3px;padding:6px 10px;border-radius:6px;background:#2b3a67;color:#fff}
  .links a{margin-left:8px;text-decoration:none;background:#4f6ed9;color:#fff;padding:6px 10px;border-radius:6px}
</style>
</head>
<body>
<main>
  <h1>👤 ข้อมูลสมาชิก</h1>

  <form class="search" method="get">
    <input type="text" name="q" placeholder="ค้นหาชื่อ หรืออีเมล..." value="<?= h($q) ?>">
    <button type="submit">ค้นหา</button>
  </form>

  <table>
    <thead>
  <tr>
    <th style="width:90px">ID</th>
    <th>ชื่อ</th>
    <th>อีเมล</th>
    <th style="width:120px;text-align:center">ลบ</th>
  </tr>
</thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="3">ไม่พบข้อมูล</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= (int)$r['id'] ?></td>
    <td><?= h($r['name'] ?? '') ?></td>
    <td><?= h($r['email'] ?? '') ?></td>
    <td style="text-align:center">
      <a href="customer_delete.php?id=<?= (int)$r['id'] ?>"
         onclick="return confirm('ยืนยันการลบสมาชิกอีเมล <?= h($r['email']) ?> หรือไม่?');"
         style="background:#dc2626;color:#fff;padding:6px 10px;border-radius:6px;text-decoration:none;">
         ลบ
      </a>
    </td>
  </tr>
<?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="bar">
    <div>รวม <?= number_format($total) ?> รายการ</div>
    <div class="pager">
      <?php if ($pages > 1): ?>
        <?php for ($i=1; $i <= $pages; $i++): ?>
          <?php if ($i === $page): ?>
            <strong><?= $i ?></strong>
          <?php else: ?>
            <a href="?page=<?= $i ?>&q=<?= urlencode($q) ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
    <div class="links">
      <a href="index.php">← กลับ Orders</a>
      <a href="logout.php">ออกจากระบบ</a>
    </div>
  </div>
</main>
</body>
</html>