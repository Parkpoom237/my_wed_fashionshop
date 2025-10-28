<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_db.php';

/* ====== PATH/URL สำหรับรูป ====== */
define('FSHOP_WEBROOT', '/fashionshop');                       // แก้ถ้าโปรเจกต์ไม่ได้อยู่ใต้ /fashionshop
define('UPLOAD_BASE_DIR', realpath(__DIR__ . '/..') . '/uploads'); // htdocs/fashionshop/uploads
define('UPLOAD_BASE_URL', rtrim(FSHOP_WEBROOT, '/') . '/uploads'); // /fashionshop/uploads

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ====== Utils ====== */
function go(string $url): void {
  if (!headers_sent()) { header('Location: '.$url); exit; }
  $e = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
  echo '<meta http-equiv="refresh" content="0;url='.$e.'">';
  echo '<script>location.replace('.json_encode($url).')</script>';
  exit;
}
set_error_handler(fn($no,$str,$file,$line)=>error_log("[PHP-$no] $str @ $file:$line"));
set_exception_handler(fn(Throwable $e)=>error_log("[EXC] ".$e->getMessage()."\n".$e->getTraceAsString()));

function ini_bytes(string $v): int {
  $v = trim($v); $last = strtolower($v[strlen($v)-1] ?? ''); $num = (float)$v;
  return match($last){'g'=>(int)($num*1024*1024*1024),'m'=>(int)($num*1024*1024),'k'=>(int)($num*1024),default=>(int)$num};
}
function post_too_large(): bool {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return false;
  $cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
  $limit = ini_bytes((string)ini_get('post_max_size'));
  return ($limit > 0 && $cl > $limit);
}

/* ====== Schema helpers ====== */
function column_exists(PDO $pdo, string $table, string $column): bool {
  $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?"); $st->execute([$column]);
  return (bool)$st->fetch();
}
function build_inventory_subquery(PDO $pdo): string {
  $candidates = ['XS'=>'stock_xs','S'=>'stock_s','M'=>'stock_m','L'=>'stock_l','XL'=>'stock_xl','XXL'=>'stock_xxl','F'=>'stock_f'];
  $parts = [];
  foreach ($candidates as $label=>$col) {
    if (column_exists($pdo,'products',$col)) {
      $parts[] = "SELECT id AS product_id, '$label' AS size, $col AS stock FROM products WHERE $col IS NOT NULL";
    }
  }
  return $parts ? implode("\nUNION ALL\n",$parts) : "SELECT NULL AS product_id, NULL AS size, NULL AS stock WHERE 1=0";
}
function stock_column_for_size(string $size): ?string {
  return match (strtoupper(trim($size))) {
    'XS'=>'stock_xs','S'=>'stock_s','M'=>'stock_m','L'=>'stock_l','XL'=>'stock_xl','XXL'=>'stock_xxl','F'=>'stock_f', default=>null,
  };
}
function sizes_available(PDO $pdo): array {
  $map = ['XS'=>'stock_xs','S'=>'stock_s','M'=>'stock_m','L'=>'stock_l','XL'=>'stock_xl','XXL'=>'stock_xxl','F'=>'stock_f'];
  $out=[]; foreach($map as $label=>$col){ if(column_exists($pdo,'products',$col)) $out[]=$label; } return $out;
}

/* ====== Ensure tables/columns & seed categories ====== */
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) UNIQUE NOT NULL
  ) ENGINE=InnoDB");

  // seed หมวดหมู่คงที่
  $DEFAULT_CATEGORIES = ['เดรส','เสื้อยืด','เสื้อเชิ้ต','กางเกง','นิต/ถัก'];
  $have = $pdo->query("SELECT name FROM categories")->fetchAll(PDO::FETCH_COLUMN) ?: [];
  foreach ($DEFAULT_CATEGORIES as $nm) {
    if (!in_array($nm,$have,true)) {
      $ins = $pdo->prepare("INSERT IGNORE INTO categories(name) VALUES(?)");
      $ins->execute([$nm]);
    }
  }

  $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
  if (!in_array('category_id',$cols,true)) {
    $pdo->exec("ALTER TABLE products ADD COLUMN category_id INT NULL");
    try {
      $pdo->exec("ALTER TABLE products
        ADD CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL");
    } catch(Throwable $e){}
  }
  if (!in_array('badge',$cols,true)) $pdo->exec("ALTER TABLE products ADD COLUMN badge VARCHAR(64) NULL");
  if (!in_array('color',$cols,true)) $pdo->exec("ALTER TABLE products ADD COLUMN color VARCHAR(64) NULL");

  $pdo->exec("CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(191) NOT NULL,
    url VARCHAR(500) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");
} catch(Throwable $e){ error_log("Schema ensure error: ".$e->getMessage()); }

/* ====== Misc helpers ====== */
function ensure_upload_dir(string $product_id): string {
  $dir = rtrim(UPLOAD_BASE_DIR,'/') . '/' . preg_replace('/[^a-zA-Z0-9_-]/','',$product_id) . '/';
  if (!is_dir($dir)) @mkdir($dir,0755,true);
  return $dir;
}
function allowed_image($tmp,$name): bool {
  $allowed_ext=['jpg','jpeg','png','gif','webp'];
  $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));
  if (!in_array($ext,$allowed_ext,true)) return false;
  $finfo=finfo_open(FILEINFO_MIME_TYPE); $mime=finfo_file($finfo,$tmp); finfo_close($finfo);
  return in_array($mime,['image/jpeg','image/png','image/gif','image/webp'],true);
}

/* ====== Data access ====== */
function get_products_with_inventory(PDO $pdo): array {
  $pdo->exec("SET SESSION group_concat_max_len = 100000");
  $invSub = build_inventory_subquery($pdo);
  $sql = "
    SELECT 
      p.id, p.name, p.color, p.price, p.badge, p.category_id,
      c.name AS category_name,
      COALESCE(CONCAT('[', GROUP_CONCAT(CONCAT('{\"size\":\"', i.size, '\",\"stock\":', COALESCE(i.stock,0), '}') ORDER BY i.size SEPARATOR ','), ']'), '[]') AS inventory,
      (
        SELECT COALESCE(CONCAT('[', GROUP_CONCAT(CONCAT('{\"id\":', pi.id, ',\"url\":\"', REPLACE(pi.url,'\"','\\\"'), '\",\"sort_order\":', COALESCE(pi.sort_order,0), '}') ORDER BY pi.sort_order SEPARATOR ','), ']'), '[]')
        FROM product_images pi WHERE pi.product_id = p.id
      ) AS images
    FROM products p
    LEFT JOIN ($invSub) AS i ON i.product_id = p.id
    LEFT JOIN categories c ON c.id = p.category_id
    GROUP BY p.id
    ORDER BY p.id DESC";
  return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
function get_product_detail(PDO $pdo, string $id): ?array {
  $pdo->exec("SET SESSION group_concat_max_len = 100000");
  $invSub = build_inventory_subquery($pdo);
  $sql = "
    SELECT 
      p.*, c.name AS category_name,
      COALESCE(CONCAT('[', GROUP_CONCAT(CONCAT('{\"size\":\"', i.size, '\",\"stock\":', COALESCE(i.stock,0), '}') ORDER BY i.size SEPARATOR ','), ']'), '[]') AS inventory,
      (
        SELECT COALESCE(CONCAT('[', GROUP_CONCAT(CONCAT('{\"id\":', pi.id, ',\"url\":\"', REPLACE(pi.url,'\"','\\\"'), '\",\"sort_order\":', COALESCE(pi.sort_order,0), '}') ORDER BY pi.sort_order SEPARATOR ','), ']'), '[]')
        FROM product_images pi WHERE pi.product_id = p.id
      ) AS images
    FROM products p
    LEFT JOIN ($invSub) AS i ON i.product_id = p.id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
    GROUP BY p.id";
  $st=$pdo->prepare($sql); $st->execute([$id]);
  $row=$st->fetch(PDO::FETCH_ASSOC); return $row ?: null;
}

/* อัปเดตสินค้า + สต็อก */
function update_product(PDO $pdo, string $id, string $name, int $price, array $inventory, ?int $category_id, ?string $badge, ?string $color): bool {
  $pdo->beginTransaction();
  try {
    $pdo->prepare("UPDATE products SET name=?, price=?, category_id=?, badge=?, color=? WHERE id=?")
        ->execute([$name,$price,$category_id,$badge,$color,$id]);

    $set=[]; $params=[];
    foreach (sizes_available($pdo) as $sz) {
      $col = stock_column_for_size($sz); if(!$col) continue;
      if (array_key_exists($sz,$inventory)) { $set[]="$col = ?"; $params[] = max(0,(int)$inventory[$sz]); }
    }
    if ($set) {
      $params[] = $id;
      $pdo->prepare("UPDATE products SET ".implode(', ',$set)." WHERE id = ?")->execute($params);
    }
    $pdo->commit(); return true;
  } catch(Throwable $e){ $pdo->rollBack(); error_log("update_product error: ".$e->getMessage()); return false; }
}

/* อัปโหลดรูป */
function save_images(PDO $pdo, string $product_id, array $files): array {
  $dir = ensure_upload_dir($product_id);
  $saved=[];
  $st  = $pdo->prepare("INSERT INTO product_images (product_id, url, sort_order) VALUES (?, ?, ?)");
  $cur = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?");
  $cur->execute([$product_id]); $order=(int)$cur->fetchColumn();

  foreach ($files['tmp_name'] as $i=>$tmp) {
    if (!$tmp) continue;
    $orig = $files['name'][$i];
    if (!allowed_image($tmp,$orig)) continue;

    $ext = strtolower(pathinfo($orig,PATHINFO_EXTENSION));
    $newname = 'p_' . preg_replace('/[^a-z0-9_-]/i','',$product_id) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir.$newname;
    if (@move_uploaded_file($tmp,$dest)) {
      @chmod($dest,0644);
      $public = rtrim(UPLOAD_BASE_URL,'/') . '/' . rawurlencode($product_id) . '/' . rawurlencode($newname);
      $order++; $st->execute([$product_id,$public,$order]);
      $saved[]=$public;
    }
  }
  return $saved;
}

/* ====== Routing ====== */
$action = $_GET['action'] ?? 'list';

/* ---- LIST ---- */
if ($action === 'list') {
  $q = trim((string)($_GET['q'] ?? ''));
  $cat = (string)($_GET['category'] ?? '');

  $products = get_products_with_inventory($pdo);
  if ($q !== '') {
    $q_mb = mb_strtolower($q,'UTF-8');
    $products = array_values(array_filter($products, fn($p)=> mb_stripos($p['name'] ?? '', $q_mb) !== false));
  }
  if ($cat !== '' && ctype_digit($cat)) {
    $products = array_values(array_filter($products, fn($p)=> (string)($p['category_id'] ?? '') === $cat));
  }

  $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
  $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
  ?>
  <!DOCTYPE html><html lang="th"><head>
  <meta charset="utf-8"><title>Product Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{font-family:Inter,system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial;color:#111}
    body{margin:20px;background:#f7f9fb}.container{max-width:1100px;margin:0 auto;background:#fff;border-radius:10px;padding:20px;box-shadow:0 6px 20px rgba(20,30,50,0.06)}
    h1{margin:0 0 12px;font-size:20px}.row{display:flex;gap:12px;align-items:center}
    .btn{display:inline-block;padding:8px 12px;border-radius:8px;text-decoration:none;background:#0b79ff;color:#fff}
    .btn.ghost{background:transparent;color:#0b79ff;border:1px solid #e6eefc}
    table{width:100%;border-collapse:collapse;margin-top:12px}th,td{padding:10px;text-align:left;border-bottom:1px solid #eef3f7;font-size:14px}
    th{color:#556;font-weight:600}.meta{font-size:13px;color:#667}.badge{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;background:#f0f7ff;color:#0366d6}
    .img-thumb{width:48px;height:48px;object-fit:cover;border-radius:6px}.muted{color:#8593a3;font-size:13px}
    .flash{padding:10px 12px;border-radius:8px;margin-bottom:12px}.ok{background:#eaffea;color:#146c2e;border:1px solid #b9e6be}.err{background:#fff1f0;color:#a8071a;border:1px solid #ffccc7}
  </style></head><body><div class="container">
    <div class="row" style="justify-content:space-between">
      <h1>จัดการสินค้า</h1>
      <div style="display:flex; gap:8px;">
        <a class="btn ghost" href="index.php">← กลับ</a>
        <a class="btn" href="?action=add">+ เพิ่มสินค้า</a>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="flash <?= $flash['type']==='ok'?'ok':'err' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <div style="margin-top:12px;display:flex;gap:12px;align-items:center">
      <form method="get" class="row" style="margin:0">
        <input type="hidden" name="action" value="list">
        <input type="text" name="q" placeholder="ค้นหาชื่อสินค้า" style="padding:8px;border-radius:8px;border:1px solid #e6eef7">
        <select name="category" style="padding:8px;border-radius:8px;border:1px solid #e6eef7">
          <option value="">ทุกหมวดหมู่</option>
          <?php foreach($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn ghost" type="submit">ค้นหา</button>
      </form>
    </div>

    <table><thead>
      <tr><th>ID</th><th>รูป</th><th>ชื่อ</th><th>สี</th><th>ราคา</th><th>สต็อก</th><th>หมวด</th><th>Badge</th><th>จัดการ</th></tr>
    </thead><tbody>
    <?php foreach ($products as $p):
      $inv = json_decode($p['inventory'] ?? '[]', true);
      $images = json_decode($p['images'] ?? '[]', true);
    ?>
      <tr>
        <td><?= htmlspecialchars($p['id']) ?></td>
        <td>
          <?php if (!empty($images) && !empty($images[0]['url'])): ?>
            <img src="<?= htmlspecialchars($images[0]['url']) ?>" alt="" class="img-thumb">
          <?php else: ?>
            <div style="width:48px;height:48px;border-radius:6px;background:#f2f6fb;display:flex;align-items:center;justify-content:center;color:#9bb0d9">No</div>
          <?php endif; ?>
        </td>
        <td><div><strong><?= htmlspecialchars($p['name']) ?></strong></div><div class="meta">ID: <?= htmlspecialchars($p['id']) ?></div></td>
        <td><?= htmlspecialchars((string)($p['color'] ?? '-')) ?></td>
        <td><?= number_format((int)$p['price']) ?> ฿</td>
        <td class="muted">
          <?php if ($inv) { foreach ($inv as $i) { echo htmlspecialchars($i['size']).': '.(int)$i['stock'].' &nbsp; '; } } else echo '-'; ?>
        </td>
        <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
        <td><?= $p['badge'] ? '<span class="badge">'.htmlspecialchars($p['badge']).'</span>' : '-' ?></td>
        <td>
          <a class="btn" href="?action=edit&id=<?= urlencode($p['id']) ?>">แก้ไข</a>
          <form method="post" action="?action=delete" style="display:inline" onsubmit="return confirm('ลบสินค้านี้จริงหรือไม่?');">
            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
            <button class="btn ghost" type="submit">ลบ</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div></body></html><?php
  exit;
}

/* ---- ADD form ---- */
if ($action === 'add') {
  $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
  $sizeList = sizes_available($pdo);
  ?>
  <!DOCTYPE html><html lang="th"><head>
  <meta charset="utf-8"><title>เพิ่มสินค้า</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:#f7f9fb;color:#111;padding:20px}
    .card{max-width:900px;margin:0 auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 6px 20px rgba(20,30,50,0.06)}
    label{display:block;margin:10px 0;font-size:14px}
    input[type=text], input[type=number], select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7}
    input, button { box-sizing: border-box; max-width: 100%; }
    .row{display:flex;gap:12px}.col{flex:1}.small{width:140px}
    .btn{display:inline-block;padding:10px 14px;border-radius:8px;background:#0b79ff;color:#fff;border:0}
  </style></head><body><div class="card">
    <h2>เพิ่มสินค้าใหม่</h2>
    <form method="post" action="?action=insert" enctype="multipart/form-data">
      <label>ID (unique)<input type="text" name="id" required></label>
      <label>ชื่อ<input type="text" name="name" required></label>
      <label>สีสินค้า<input type="text" name="color" placeholder="เช่น ดำ, เทา, ครีม"></label>
      <div class="row">
        <div class="col"><label>ราคา (บาท)<input type="number" name="price" min="0" value="0"></label></div>
        <div class="col"><label>Badge
          <select name="badge"><option value="">-</option><option value="New">New</option><option value="Hit">Hit</option></select>
        </label></div>
        <div class="col"><label>หมวดหมู่
          <select name="category_id"><option value="">-</option>
            <?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
          </select>
        </label></div>
      </div>

      <h3>สต็อกเริ่มต้น</h3>
      <div class="row">
        <?php foreach ($sizeList as $sz): ?>
          <div class="col small"><label><?= $sz ?><input type="number" name="stock[<?= $sz ?>]" min="0" value="0"></label></div>
        <?php endforeach; ?>
      </div>

      <h3>อัปโหลดรูป</h3>
      <input type="file" name="images[]" accept="image/*" multiple>

      <div style="margin-top:16px;display:flex;gap:8px">
        <button class="btn" type="submit">บันทึก</button>
        <a class="btn" style="background:#eee;color:#111" href="?action=list">ยกเลิก</a>
        <a class="btn ghost" href="?action=list">← กลับหน้ารายการ</a>
      </div>
    </form>
  </div></body></html><?php
  exit;
}

/* ---- INSERT ---- */
if ($action === 'insert' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post_too_large()) { $_SESSION['flash']=['type'=>'err','msg'=>'ไฟล์ที่แนบใหญ่เกินกำหนด']; go('?action=list'); }

  $id    = trim($_POST['id'] ?? '');
  $name  = trim($_POST['name'] ?? '');
  $color = trim((string)($_POST['color'] ?? '')) ?: null;
  $price = (int)($_POST['price'] ?? 0);
  $badge = $_POST['badge'] !== '' ? $_POST['badge'] : null;
  $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
  $stockArr = $_POST['stock'] ?? [];

  $cols = ['id','name','color','price','badge','category_id'];
  $vals = [$id,$name,$color,$price,$badge,$category_id];
  $ph   = ['?','?','?','?','?','?'];

  foreach (sizes_available($pdo) as $sz) {
    $col = stock_column_for_size($sz);
    $cols[] = $col;
    $vals[] = max(0,(int)($stockArr[$sz] ?? 0));
    $ph[]   = '?';
  }

  $pdo->beginTransaction();
  try {
    $sql = "INSERT INTO products (".implode(',',$cols).") VALUES (".implode(',',$ph).")";
    $pdo->prepare($sql)->execute($vals);
    if (!empty($_FILES['images']['tmp_name'][0])) save_images($pdo,$id,$_FILES['images']);
    $pdo->commit(); $_SESSION['flash']=['type'=>'ok','msg'=>'เพิ่มสินค้าสำเร็จ']; go('?action=list');
  } catch(Throwable $e){ $pdo->rollBack(); $_SESSION['flash']=['type'=>'err','msg'=>'Insert error: '.$e->getMessage()]; go('?action=list'); }
}

/* ---- EDIT ---- */
if ($action === 'edit' && isset($_GET['id'])) {
  $id = (string)$_GET['id'];
  $product = get_product_detail($pdo,$id);
  if (!$product) { http_response_code(404); exit('Not found'); }
  $inv = json_decode($product['inventory'] ?? '[]', true);
  $images = json_decode($product['images'] ?? '[]', true);
  $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
  $sizeList = sizes_available($pdo);
  $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
  ?>
  <!DOCTYPE html><html lang="th"><head>
  <meta charset="utf-8"><title>แก้ไขสินค้า</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:#f7f9fb;color:#111;padding:20px}
    .card{max-width:900px;margin:0 auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 6px 20px rgba(20,30,50,0.06)}
    label{display:block;margin:10px 0}
    input[type=text], input[type=number], select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef7}
    input, button { box-sizing: border-box; max-width: 100%; }
    .img-grid{display:flex;gap:12px;flex-wrap:wrap;margin-top:8px}
    .img-item{width:100px}.img-item img{width:100px;height:100px;object-fit:cover;border-radius:8px;display:block}
    .btn{display:inline-block;padding:8px 12px;border-radius:8px;background:#0b79ff;color:#fff;border:0;cursor:pointer}
    .btn.ghost{background:#f2f6fb;color:#0b79ff}
    .muted{color:#667;font-size:13px}.flash{padding:10px 12px;border-radius:8px;margin-bottom:12px}
    .ok{background:#eaffea;color:#146c2e;border:1px solid #b9e6be}.err{background:#fff1f0;color:#a8071a;border:1px solid #ffccc7}
    hr{border:none;border-top:1px solid #eef3f7;margin:20px 0}
  </style></head><body><div class="card">
    <?php if ($flash): ?><div class="flash <?= $flash['type']==='ok'?'ok':'err' ?>"><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>
    <h2>แก้ไขสินค้า: <?= htmlspecialchars($product['name']) ?></h2>

    <form method="post" action="?action=update&id=<?= urlencode($id) ?>" enctype="multipart/form-data">
      <label>ชื่อ <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>"></label>
      <label>สีสินค้า <input type="text" name="color" value="<?= htmlspecialchars((string)($product['color'] ?? '')) ?>" placeholder="เช่น ดำ, เทา, ครีม"></label>
      <label>ราคา <input type="number" name="price" value="<?= (int)$product['price'] ?>"></label>

      <div style="display:flex;gap:12px">
        <div style="flex:1"><label>Badge
          <select name="badge">
            <option value="">-</option>
            <option <?= ($product['badge'] ?? '')==='New' ? 'selected':'' ?> value="New">New</option>
            <option <?= ($product['badge'] ?? '')==='Hit' ? 'selected':'' ?> value="Hit">Hit</option>
          </select>
        </label></div>
        <div style="width:200px"><label>หมวดหมู่
          <select name="category_id"><option value="">-</option>
            <?php foreach ($categories as $c): ?>
              <option <?= ((int)($product['category_id'] ?? 0)===(int)$c['id']) ? 'selected':'' ?> value="<?= (int)$c['id'] ?>">
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label></div>
      </div>

      <h3>สต็อก</h3>
      <?php $invMap=[]; if($inv) foreach($inv as $r) $invMap[strtoupper($r['size'])]=(int)$r['stock'];
      foreach($sizeList as $sz): $cur=$invMap[$sz] ?? 0; ?>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
          <div style="width:120px"><strong><?= htmlspecialchars($sz) ?></strong></div>
          <div><input type="number" name="stock[<?= htmlspecialchars($sz) ?>]" value="<?= (int)$cur ?>"></div>
        </div>
      <?php endforeach; ?>

      <h3>อัปโหลดรูปเพิ่มเติม</h3>
      <input type="file" name="images[]" accept="image/*" multiple>
      <div style="margin-top:16px;display:flex;gap:8px">
        <button class="btn" type="submit">บันทึก</button>
        <a class="btn ghost" href="?action=list">ยกเลิก</a>
        <a class="btn ghost" href="?action=list">← กลับหน้ารายการ</a>
      </div>
    </form>

    <hr>
    <h3>รูปภาพปัจจุบัน</h3>
    <div class="img-grid">
      <?php if ($images): foreach ($images as $img): ?>
        <div class="img-item">
          <img src="<?= htmlspecialchars($img['url']) ?>" alt="">
          <form method="post" action="?action=delete_image" onsubmit="return confirm('ลบรูปนี้หรือไม่?');" style="margin-top:6px">
            <input type="hidden" name="img_id" value="<?= (int)$img['id'] ?>">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($id) ?>">
            <button class="btn ghost" type="submit">ลบรูป</button>
          </form>
        </div>
      <?php endforeach; else: ?><div class="muted">ยังไม่มีรูป</div><?php endif; ?>
    </div>
  </div></body></html><?php
  exit;
}

/* ---- UPDATE ---- */
if ($action === 'update' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post_too_large()) { $_SESSION['flash']=['type'=>'err','msg'=>'ไฟล์ที่แนบใหญ่เกินกำหนด']; go('?action=edit&id=' . urlencode((string)$_GET['id'])); }

  $id = (string)$_GET['id'];
  $name  = trim($_POST['name'] ?? '');
  $color = trim((string)($_POST['color'] ?? '')) ?: null;
  $price = (int)($_POST['price'] ?? 0);
  $stockArr = $_POST['stock'] ?? [];
  $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
  $badge = ($_POST['badge'] ?? '') !== '' ? (string)$_POST['badge'] : null;

  $invPost=[]; foreach($stockArr as $k=>$v) $invPost[strtoupper(trim((string)$k))]=max(0,(int)$v);

  $ok = update_product($pdo,$id,$name,$price,$invPost,$category_id,$badge,$color);
  if ($ok) {
    if (!empty($_FILES['images']['tmp_name'][0])) save_images($pdo,$id,$_FILES['images']);
    $_SESSION['flash']=['type'=>'ok','msg'=>'บันทึกการแก้ไขสำเร็จ'];
  } else {
    $_SESSION['flash']=['type'=>'err','msg'=>'บันทึกล้มเหลว กรุณาตรวจสอบค่าอีกครั้ง'];
  }
  go('?action=edit&id=' . urlencode($id));
}

/* ---- DELETE IMAGE ---- */
if ($action === 'delete_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $imgId = (int)($_POST['img_id'] ?? 0);
  $pid   = (string)($_POST['product_id'] ?? '');
  if ($imgId > 0) {
    $st=$pdo->prepare("SELECT url FROM product_images WHERE id = ?"); $st->execute([$imgId]);
    $url=(string)($st->fetchColumn() ?? '');
    $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imgId]);
    if ($url) {
      $rel = preg_replace('#^'.preg_quote(UPLOAD_BASE_URL,'#').'#','',$url);
      $abs = rtrim(UPLOAD_BASE_DIR,'/').'/'.ltrim($rel,'/');
      if (is_file($abs)) @unlink($abs);
    }
  }
  $_SESSION['flash']=['type'=>'ok','msg'=>'ลบรูปแล้ว'];
  go('?action=edit&id=' . urlencode($pid));
}

/* ---- DELETE PRODUCT ---- */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (string)($_POST['id'] ?? '');
  if ($id !== '') {
    $imgs = $pdo->prepare("SELECT id, url FROM product_images WHERE product_id = ?");
    $imgs->execute([$id]);
    foreach ($imgs->fetchAll(PDO::FETCH_ASSOC) as $im) {
      $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([(int)$im['id']]);
      $rel = preg_replace('#^'.preg_quote(UPLOAD_BASE_URL,'#').'#','',(string)$im['url']);
      $abs = rtrim(UPLOAD_BASE_DIR,'/').'/'.ltrim($rel,'/');
      if (is_file($abs)) @unlink($abs);
    }
    // ลบโฟลเดอร์สินค้า (ถ้าว่าง)
    $prodDir = rtrim(UPLOAD_BASE_DIR,'/') . '/' . preg_replace('/[^a-zA-Z0-9_-]/','',$id);
    if (is_dir($prodDir)) @rmdir($prodDir);

    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
  }
  $_SESSION['flash']=['type'=>'ok','msg'=>'ลบสินค้าแล้ว'];
  go('?action=list');
}

/* fallback */
go('?action=list');
