<?php require __DIR__.'/config.php'; ?>
<?php
require_once __DIR__ . '/_db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT id, name, description, price, stock, image_url FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
http_response_code(404);
echo "ไม่พบสินค้า";
exit;
}

// ไซซ์ตัวอย่าง (อยากดึงจาก DB จริง ค่อยต่อยอดภายหลัง)
$sizes = ['S','M','L','F'];
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($product['name']) ?> - รายละเอียดสินค้า</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;margin:0;padding:20px;background:#f7f7fb}
.wrap{max-width:980px;margin:0 auto}
.card{display:grid;grid-template-columns: 1fr 1.2fr;gap:24px;background:#fff;border-radius:14px;padding:20px;box-shadow:0 6px 20px rgba(0,0,0,.06)}
img{width:100%;border-radius:12px;background:#eef1f6;object-fit:cover;aspect-ratio:1/1}
.name{font-size:22px;font-weight:700;margin:0 0 8px}
.price{font-size:20px;color:#0d9488;margin:8px 0 16px}
.desc{color:#555;line-height:1.6;margin-bottom:18px}
.row{display:flex;gap:12px;align-items:center;margin:8px 0}
select,input[type=number]{padding:8px 10px;border:1px solid #e5e7eb;border-radius:10px}
button{background:#111827;color:#fff;border:none;border-radius:12px;padding:12px 18px;font-weight:700;cursor:pointer}
button:hover{opacity:.9}
a.link{color:#374151;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">
<a class="link" href="index.php">← กลับหน้าร้าน</a>
<h2>รายละเอียดสินค้า</h2>
<div class="card">
<div>
<img src="<?= htmlspecialchars($product['image_url'] ?: 'https://picsum.photos/seed/'.$product['id'].'/600/600') ?>" alt="">
</div>
<div>
<h1 class="name"><?= htmlspecialchars($product['name']) ?></h1>
<div class="price">฿<?= number_format($product['price'],2) ?></div>
<p class="desc"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>
<p>สต็อก: <?= intval($product['stock']) ?> ชิ้น</p>

<form method="post" action="cart.php?action=add">
  <input type="hidden" name="id"    value="<?= $p['id'] ?>">
  <input type="hidden" name="name"  value="<?= htmlspecialchars($p['name']) ?>">
  <input type="hidden" name="price" value="<?= $p['price'] ?>">
  <select name="size" required>
    <option value="S">S</option>
    <option value="M">M</option>
    <!-- ... -->
  </select>
  <input type="number" name="qty" value="1" min="1">
  <button type="submit">เพิ่มลงตะกร้า</button>
</form>
<div class="row">
<label>ขนาด</label>
<select name="size" required>
<?php foreach($sizes as $s): ?>
<option value="<?= $s ?>"><?= $s ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="row">
<label>จำนวน</label>
<input type="number" name="qty" value="1" min="1" max="<?= max(1,intval($product['stock'])) ?>" required>
</div>

<div class="row">
<button type="submit">เพิ่มลงตะกร้า</button>
<a class="link" href="cart.php" style="margin-left:12px">ไปตะกร้า →</a>
</div>
</form>
</div>
</div>
</div>
</body>
</html>