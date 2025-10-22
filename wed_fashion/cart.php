<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cart_lib.php';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ตะกร้าสินค้า</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;margin:0;background:#0b0d12;color:#eef2ff}
.wrap{max-width:900px;margin:24px auto;padding:0 16px}
.card{background:#141823;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:16px}
.line{display:grid;grid-template-columns:1fr auto auto;gap:10px;padding:10px 0;border-bottom:1px dashed rgba(255,255,255,.1)}
.btn{padding:8px 10px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#1c2230;color:#fff;cursor:pointer}
.total{display:flex;justify-content:space-between;font-weight:700;margin-top:12px}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h2>ตะกร้าสินค้า</h2>
    <?php if (empty($_SESSION['cart'])): ?>
      <p>ยังไม่มีสินค้าในตะกร้า</p>
    <?php else: foreach($_SESSION['cart'] as $it): ?>
      <div class="line">
        <div>
          <b><?= h($it['name']) ?></b><br>
          <small><?= h($it['color'] ?: '-') ?> · <?= h($it['size'] ?: '-') ?></small>
        </div>
        <form method="post" action="cart_update.php" style="display:inline">
          <input type="hidden" name="id" value="<?= h($it['id']) ?>">
          <input type="hidden" name="color" value="<?= h($it['color']) ?>">
          <input type="hidden" name="size" value="<?= h($it['size']) ?>">
          <button class="btn" name="qty" value="<?= max(1,(int)$it['qty']-1) ?>">–</button>
          <span style="padding:0 8px"><?= (int)$it['qty'] ?></span>
          <button class="btn" name="qty" value="<?= (int)$it['qty']+1 ?>">+</button>
        </form>
        <form method="post" action="cart_remove.php" style="display:inline">
          <input type="hidden" name="id" value="<?= h($it['id']) ?>">
          <input type="hidden" name="color" value="<?= h($it['color']) ?>">
          <input type="hidden" name="size" value="<?= h($it['size']) ?>">
          <button class="btn">ลบ</button>
        </form>
      </div>
    <?php endforeach; ?>
      <div class="total">
        <div>รวม</div><div>฿<?= number_format(cart_total(),2) ?></div>
      </div>
      <p><a class="btn" href="checkout.php">สั่งซื้อ</a> <a class="btn" href="index.php">← กลับหน้าร้าน</a></p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
