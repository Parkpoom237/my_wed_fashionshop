<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$pdo = db();

/* -------- helper -------- */
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$msg = '';

/* ================== POST HANDLERS ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  /* 1) อัปเดตข้อมูลสินค้า */
  if (isset($_POST['update'], $_POST['id'])) {
    $id       = (int)$_POST['id'];
    $name     = trim($_POST['name'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $badge    = trim($_POST['badge'] ?? '');

    $pdo->prepare("UPDATE products SET name=?, price=?, category=?, badge=? WHERE id=?")
        ->execute([$name,$price,$category,$badge,$id]);

    $msg = "อัปเดตสินค้ารหัส #{$id} แล้ว";
  }

  /* 2) บันทึกสต็อกต่อสี/ไซซ์ (upsert) */
  if (isset($_POST['save_stock']) && !empty($_POST['pid'])) {
    $pid    = (int)$_POST['pid'];
    $colors = $_POST['color'] ?? [];
    $sizes  = $_POST['size']  ?? [];
    $qtys   = $_POST['qty']   ?? [];

    for ($i=0; $i<count($colors); $i++) {
      $c = trim((string)$colors[$i]);
      $s = trim((string)($sizes[$i] ?? ''));
      $q = max(0, (int)($qtys[$i] ?? 0));
      if ($c === '' || $s === '') continue;

      $pdo->prepare("
        INSERT INTO inventory (product_id,color,size,qty)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE qty = VALUES(qty)
      ")->execute([$pid,$c,$s,$q]);
    }
    $msg = 'อัปเดตสต็อกแล้ว';
  }

  /* 3) สร้างสินค้าใหม่ */
  if (isset($_POST['action']) && $_POST['action'] === 'create_product') {
    $name     = trim($_POST['name'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $badge    = trim($_POST['badge'] ?? '');
    $created  = $_POST['created'] ?: date('Y-m-d');

    if ($name !== '' && $category !== '' && $price > 0) {
      $pdo->prepare("
        INSERT INTO products (name, price, category, badge, created_at)
        VALUES (?, ?, ?, ?, ?)
      ")->execute([$name,$price,$category,$badge,$created]);
      $msg = 'บันทึกสินค้าใหม่แล้ว';
    } else {
      $msg = 'กรอกชื่อ หมวด และราคาให้ครบ';
    }
  }

  /* 4) อัปโหลดรูปเพิ่มท้ายสไลด์ของสินค้าที่มีอยู่ */
  if (isset($_POST['action']) && $_POST['action'] === 'upload_image' && isset($_POST['pid'], $_FILES['img'])) {
    $pid = (int)$_POST['pid'];

    if (is_uploaded_file($_FILES['img']['tmp_name'])) {
      // เก็บไฟล์จริงที่ ../uploads/<product_id>/YmdHis_filename.ext
      $fnRel = 'uploads/'.$pid.'/'.date('YmdHis').'_' . basename($_FILES['img']['name']);
      $dest  = __DIR__ . '/../' . $fnRel;  // ชี้ขึ้นจาก /admin ไป root โปรเจกต์

      if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0777, true);
      }
      move_uploaded_file($_FILES['img']['tmp_name'], $dest);

      // sort_order ถัดไปของสินค้านี้
      $st = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM product_images WHERE product_id=?");
      $st->execute([$pid]);
      $nextOrd = (int)$st->fetchColumn(); // 0 ถ้ายังไม่มีรูป

      // บันทึกลงตาราง product_images
      $pdo->prepare("INSERT INTO product_images (product_id,url,sort_order) VALUES (?,?,?)")
          ->execute([$pid,$fnRel,$nextOrd]);

      $msg = 'อัปโหลดรูปสำเร็จ (เพิ่มเป็นสไลด์สุดท้าย)';
    } else {
      $msg = 'ไม่พบไฟล์ที่อัปโหลด';
    }
  }

  /* 5) ลบ “รูปภาพ” ของสินค้า */
  if (isset($_POST['action']) && $_POST['action'] === 'delete_image' && isset($_POST['img_id'])) {
    $img_id = (int)$_POST['img_id'];

    $st = $pdo->prepare("SELECT url FROM product_images WHERE id=?");
    $st->execute([$img_id]);
    $url = $st->fetchColumn();

    if ($url) {
      $file = __DIR__ . '/../' . $url;
      if (file_exists($file)) { @unlink($file); }
      $pdo->prepare("DELETE FROM product_images WHERE id=?")->execute([$img_id]);
      $msg = "ลบรูป #{$img_id} แล้ว";
    }
  }

  /* 6) ลบ “ตัวสินค้า” ทั้งตัว */
  if (isset($_POST['action']) && $_POST['action'] === 'delete_product' && isset($_POST['pid'])) {
    $pid = (int)$_POST['pid'];

    // ลบไฟล์รูปจริงทั้งหมด
    $st = $pdo->prepare("SELECT url FROM product_images WHERE product_id=?");
    $st->execute([$pid]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $u) {
      $file = __DIR__ . '/../' . $u;
      if (file_exists($file)) { @unlink($file); }
    }

    // ลบข้อมูลฐานข้อมูล
    $pdo->prepare("DELETE FROM product_images WHERE product_id=?")->execute([$pid]);
    $pdo->prepare("DELETE FROM inventory      WHERE product_id=?")->execute([$pid]);
    $pdo->prepare("DELETE FROM products       WHERE id=?")->execute([$pid]);

    $msg = "ลบสินค้ารหัส #{$pid} ออกแล้ว";
  }
}
/* ================== /POST HANDLERS ================== */

/* ดึงสินค้า + รวมสต็อกไว้ให้เลย */
$ps = $pdo->query("
  SELECT p.*, COALESCE(SUM(i.qty),0) AS total_stock
  FROM products p
  LEFT JOIN inventory i ON p.id = i.product_id
  GROUP BY p.id
  ORDER BY p.created_at DESC, p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ดึงรูปทั้งหมดเพื่อพรีวิว */
$imgRows = $pdo->query("
  SELECT id, product_id, url
  FROM product_images
  ORDER BY sort_order, id
")->fetchAll(PDO::FETCH_ASSOC);

$imgMap = [];
foreach ($imgRows as $im) {
  $imgMap[$im['product_id']][] = $im; // เก็บทั้ง object (id,url)
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Products</title>
<style>
:root{
  --bg:#edf1f7; --card:#ffffff; --ink:#1f2937; --muted:#6b7280;
  --head:#223354; --head-txt:#e5ecff; --line:#e5e7eb;
  --btn:#2563eb; --btn-h:#1e40af;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.5 system-ui,Segoe UI,Roboto,Arial,sans-serif}
.wrap{max-width:1100px;margin:24px auto;padding:0 16px}
.card{background:var(--card);border-radius:14px;padding:18px;box-shadow:0 8px 26px rgba(0,0,0,.06);border:1px solid #e8ebf3;margin-bottom:18px}
h1{display:flex;align-items:center;gap:10px;margin:0 0 14px}
h3{margin:0 0 10px}
label{display:block;color:var(--muted);font-size:12px;margin:6px 0 4px}
input[type=text],input[type=number],input[type=date]{width:100%;padding:8px 10px;border:1px solid var(--line);border-radius:8px;background:#fff}
.row{display:grid;gap:12px}
@media(min-width:760px){ .row-2{grid-template-columns:1fr 1fr} }

.btn{appearance:none;border:0;border-radius:10px;background:var(--btn);color:#fff;padding:8px 12px;font-weight:600;cursor:pointer}
.btn:hover{background:var(--btn-h)}
.btn.ghost{background:#fff;color:var(--btn);border:1px solid var(--btn)}
.btn.sm{padding:6px 10px;font-size:12px}

.table-wrap{overflow:auto;border-radius:12px;border:1px solid var(--line)}
table{width:100%;border-collapse:separate;border-spacing:0}
thead th{
  background:var(--head);color:var(--head-txt);text-align:left;
  padding:12px 14px;font-weight:600;letter-spacing:.2px
}
thead th:first-child{border-top-left-radius:12px}
thead th:last-child{border-top-right-radius:12px}
tbody td{background:#fff;padding:12px 14px;border-top:1px solid var(--line);vertical-align:top}
tbody tr:nth-child(even) td{background:#fbfcff}
tbody tr:hover td{background:#f7faff}

th.col-id{width:90px}
th.col-name{min-width:220px}
th.col-price{width:260px}
th.col-cat{width:140px}
th.col-badge{width:100px}
th.col-upload{min-width:260px}
th.col-action{width:120px}

.price-pill{
  width:240px;padding:8px 12px;border:1px solid #e5e7eb;border-radius:10px;
  background:#f3f6ff;text-align:right;font-weight:600;color:#1f2937;
}
.w90{width:90px}.w70{width:70px}.w80{width:80px}
.stack{display:grid;gap:6px}
.hstack{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
img.thumb{height:60px;border-radius:6px;margin:4px;border:1px solid #e5e7eb}
.flash{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:8px 10px;border-radius:8px;margin:10px 0}
.header-line{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.header-line a{color:#fff;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">

  <!-- ฟอร์มสร้างสินค้า -->
  <div class="card">
    <h1>Products</h1>
    <?php if ($msg): ?><div class="flash"><b><?= h($msg) ?></b></div><?php endif; ?>

    <h3>สร้างสินค้า</h3>
    <form method="post" class="row row-2">
      <input type="hidden" name="action" value="create_product">
      <div><label>ชื่อสินค้า</label><input name="name" required></div>
      <div><label>หมวดหมู่</label><input name="category" required></div>
      <div><label>ราคา</label><input type="number" name="price" min="0" step="0.01" inputmode="decimal" class="price-pill" required></div>
      <div><label>Badge</label><input name="badge" placeholder="เช่น NEW / HIT"></div>
      <div><label>Created</label><input type="date" name="created" value="<?= date('Y-m-d') ?>"></div>
      <div class="hstack" style="align-items:flex-end"><button class="btn" type="submit">บันทึก</button></div>
    </form>
  </div>

  <!-- ตารางรายการ -->
  <div class="card">
    <div class="header-line">
      <h3 style="color:#223354;margin:0">รายการ</h3>
      <a class="btn ghost" href="index.php">← กลับแดชบอร์ด</a>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th class="col-id">ID</th>
            <th class="col-name">ชื่อ</th>
            <th class="col-price">ราคา</th>
            <th class="col-cat">หมวด</th>
            <th class="col-badge">Badge</th>
            <th>พรีวิวรูป / สต็อก</th>
            <th class="col-upload">อัปโหลดรูป</th>
            <th class="col-action" style="text-align:right">บันทึก</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($ps as $p): $rid = (int)$p['id']; ?>
          <tr>
            <td><b><?= $rid ?></b></td>

            <!-- ชื่อ -->
            <td><input name="name" form="upd<?= $rid ?>" value="<?= h($p['name']) ?>"></td>

            <!-- ราคา: แสดงผล + แก้ไขได้ -->
            <td>
              <div style="font-weight:600;color:#111;margin-bottom:4px">
                <?= $p['price'] !== null ? '฿'.number_format((float)$p['price'], 2) : '<span style="color:#999">ยังไม่ได้ตั้งราคา</span>' ?>
              </div>
              <input
                name="price" form="upd<?= $rid ?>"
                type="number" step="0.01" min="0"
                class="price-pill"
                value="<?= $p['price'] !== null ? number_format((float)$p['price'], 2, '.', '') : '' ?>">
            </td>

            <!-- หมวด -->
            <td><input name="category" form="upd<?= $rid ?>" value="<?= h($p['category'] ?? '') ?>"></td>

            <!-- Badge -->
            <td><input name="badge" form="upd<?= $rid ?>" value="<?= h($p['badge'] ?? '') ?>"></td>

            <!-- พรีวิวรูป + สต็อก -->
            <td>
              <!-- พรีวิวรูป + ปุ่มลบรูป -->
              <div class="hstack" style="margin-bottom:8px">
                <?php if (!empty($imgMap[$rid])): ?>
                  <?php foreach ($imgMap[$rid] as $im): ?>
                    <div style="text-align:center">
                      <img class="thumb" src="../<?= h($im['url']) ?>" alt="">
                      <form method="post" style="display:inline" onsubmit="return confirm('ลบรูปนี้?')">
                        <input type="hidden" name="action" value="delete_image">
                        <input type="hidden" name="img_id" value="<?= (int)$im['id'] ?>">
                        <button type="submit" class="btn sm" style="background:#dc2626">ลบ</button>
                      </form>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span style="color:#6b7280">ยังไม่มีรูป</span>
                <?php endif; ?>
              </div>

              <!-- รวมสต็อก -->
              <div style="font-weight:600;color:#111;margin:6px 0 8px">
                รวมสต็อก: <?= (int)$p['total_stock'] ?> ชิ้น
              </div>

              <!-- ฟอร์มสต็อก -->
              <form id="stk<?= $rid ?>" method="post" class="stack" style="margin:0">
                <input type="hidden" name="pid" value="<?= $rid ?>">
                <?php
                  $st = $pdo->prepare("SELECT color,size,qty FROM inventory WHERE product_id=? ORDER BY color,size");
                  $st->execute([$rid]);
                  $inv = $st->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php if ($inv): foreach ($inv as $i): ?>
                  <div class="hstack">
                    <input class="w90" name="color[]" value="<?= h($i['color']) ?>" placeholder="สี">
                    <input class="w70" name="size[]"  value="<?= h($i['size'])  ?>" placeholder="ไซซ์">
                    <input class="w80" name="qty[]"   type="number" value="<?= (int)$i['qty'] ?>">
                  </div>
                <?php endforeach; endif; ?>

                <div class="hstack">
                  <input class="w90" name="color[]" placeholder="สีใหม่">
                  <input class="w70" name="size[]"  placeholder="ไซซ์">
                  <input class="w80" name="qty[]"   type="number" placeholder="0">
                </div>
                <button class="btn sm" name="save_stock" value="1" type="submit">บันทึกสต็อก</button>
              </form>
            </td>

            <!-- อัปโหลดรูป (เพิ่มท้ายสไลด์) -->
            <td>
              <form id="img<?= $rid ?>" method="post" enctype="multipart/form-data" class="hstack" style="margin:0">
                <input type="hidden" name="action" value="upload_image">
                <input type="hidden" name="pid" value="<?= $rid ?>">
                <input type="file" name="img" accept="image/*" style="flex:1" required>
                <button class="btn sm" type="submit">อัปโหลด</button>
              </form>
            </td>

            <!-- ปุ่มบันทึก / ลบสินค้า -->
            <td style="text-align:right">
              <form id="upd<?= $rid ?>" method="post" style="margin:0 0 6px 0">
                <input type="hidden" name="id" value="<?= $rid ?>">
                <button class="btn" name="update" value="1" type="submit">บันทึก</button>
              </form>
              <form method="post" onsubmit="return confirm('ยืนยันลบสินค้านี้ทั้งตัวและรูปทั้งหมดหรือไม่?')">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="pid" value="<?= $rid ?>">
                <button type="submit" class="btn sm" style="background:#dc2626">ลบสินค้า</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>