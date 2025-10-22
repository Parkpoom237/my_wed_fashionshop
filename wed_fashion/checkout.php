<?php
declare(strict_types=1);
require_once __DIR__ . '/require_customer.php';
require_once __DIR__ . '/config.php';

function cart_total(): float {
  $s = 0.0; foreach ($_SESSION['cart'] ?? [] as $it) { $s += ((float)($it['price'] ?? 0))*((int)($it['qty'] ?? 0)); }
  return $s;
}
if (empty($_SESSION['cart'])) { header('Location: cart.php'); exit; }

$pdo = db();
const PROMPTPAY_ID = '0931257502';
function tlv(string $i,string $v):string{return $i.str_pad((string)strlen($v),2,'0',STR_PAD_LEFT).$v;}
function pp_crc16(string $s):int{$p=0x1021;$c=0xFFFF;$n=strlen($s);for($i=0;$i<$n;$i++){ $c^=(ord($s[$i])<<8);for($b=0;$b<8;$b++){ $c=($c&0x8000)?(($c<<1)^$p):($c<<1);$c&=0xFFFF;}}return $c;}
function pp_qr_payload(string $ppid,float $amount,string $ref=''):string{
  $ppid=preg_replace('/\D+/','',$ppid); if(preg_match('/^0\d+$/',$ppid)) $ppid='0066'.substr($ppid,1);
  $mai=tlv('00','A000000677010111').tlv('01',$ppid); $adf=$ref!==''?tlv('01',$ref):'';
  $pay=tlv('00','01').tlv('01','12').tlv('29',$mai).tlv('52','0000').tlv('53','764').tlv('54',number_format($amount,2,'.','')).tlv('58','TH').tlv('59','SHOP').tlv('60','BANGKOK').($adf!==''?tlv('62',$adf):'').'6304';
  $crc=strtoupper(dechex(pp_crc16($pay))); return $pay.str_pad($crc,4,'0',STR_PAD_LEFT);
}

$err='';$placed=false;$order_no='';$order_id=0;$amount_for_qr=0.0;$pay='cod';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['name']??'');$email=trim($_POST['email']??'');$phone=trim($_POST['phone']??'');$addr=trim($_POST['address']??'');$pay=$_POST['pay_method']??'cod';
  if($name===''||$phone===''||$addr===''){ $err='กรอกข้อมูลให้ครบถ้วน'; }
  else{
    $pdo->beginTransaction();
    try{
      $total=cart_total(); $amount_for_qr=$total; $order_no='ORD-'.str_pad((string)rand(1,9999),4,'0',STR_PAD_LEFT);
      $stmt=$pdo->prepare("INSERT INTO orders(order_no, customer_name, customer_email, customer_phone, address, total, payment_method, payment_status, status, created_at) VALUES (?,?,?,?,?,?,?, 'unpaid', 'new', NOW())");
      $stmt->execute([$order_no,$name,$email,$phone,$addr,$total,$pay]);
      $order_id=(int)$pdo->lastInsertId();
      $stmtItem=$pdo->prepare("INSERT INTO order_items(order_id, product_id, name, size, qty, price, subtotal) VALUES (?,?,?,?,?,?,?)");
      foreach($_SESSION['cart'] as $it){
        $stmtItem->execute([$order_id,$it['id']??null,$it['name']??'',$it['size']??'',(int)($it['qty']??0),(float)($it['price']??0),(float)($it['price']??0)*(int)($it['qty']??0)]);
      }
      $pdo->commit(); $_SESSION['cart']=[]; $placed=true;
    }catch(Throwable $e){ $pdo->rollBack(); $err='บันทึกออเดอร์ไม่สำเร็จ: '.$e->getMessage(); }
  }
}
?>
<!doctype html><html lang="th"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ชำระเงิน / ยืนยันคำสั่งซื้อ</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#f6f7fb;color:#111827}
.wrap{max-width:900px;margin:24px auto;padding:0 16px;display:grid;grid-template-columns:1.2fr .8fr;gap:16px}
.card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 6px 20px rgba(0,0,0,.05)}
h2{margin:0 0 12px}label{display:block;margin-top:10px;font-weight:600}
input,textarea,select{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px;background:#fff}
.sum{display:flex;justify-content:space-between;margin-top:8px}
.btn{background:#111827;color:#fff;border:none;border-radius:10px;padding:12px 16px;cursor:pointer;margin-top:14px;width:100%}
.err{background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:10px;margin-bottom:10px}
.ok{background:#ecfeff;border:1px solid #a5f3fc;color:#0e7490;border-radius:10px;padding:10px;margin-bottom:10px}
a.link{color:#374151;text-decoration:none}@media (max-width:840px){ .wrap{grid-template-columns:1fr} }
</style></head><body>
<?php if($placed): ?>
<div class="wrap" style="grid-template-columns:1fr"><div class="card">
<h2>สั่งซื้อสำเร็จ 🎉</h2><p>เลขที่คำสั่งซื้อของคุณ: <b><?= h($order_no) ?></b></p>
<?php if($pay==='transfer'): ?><?php $payload=pp_qr_payload(PROMPTPAY_ID,(float)$amount_for_qr,(string)$order_no); $qr='https://api.qrserver.com/v1/create-qr-code/?size=240x240&data='.urlencode($payload); ?>
<div style="display:flex;gap:22px;flex-wrap:wrap;align-items:flex-start;margin-top:10px">
  <div style="background:#fff;border-radius:12px;padding:14px 16px;box-shadow:0 6px 20px rgba(0,0,0,.06)">
    <h3 style="margin:6px 0 10px">ชำระเงินด้วยพร้อมเพย์</h3>
    <p style="margin:0 0 8px">ยอดชำระ: <b>฿<?= number_format((float)$amount_for_qr,2) ?></b></p>
    <img src="<?= $qr ?>" alt="PromptPay QR" width="240" height="240">
    <p style="margin:10px 0 0;color:#666">สแกน QR ด้วยแอปธนาคารของคุณ</p>
  </div>
  <div style="flex:1;min-width:260px">
    <h3>แนบสลิปการโอน</h3>
    <form method="post" action="upload_slip.php" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= (int)$order_id ?>">
      <input type="file" name="slip" accept="image/*" required>
      <button class="btn" type="submit">อัปโหลดสลิป</button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="ok">เลือกชำระแบบ <b>เก็บเงินปลายทาง (COD)</b> — ไม่จำเป็นต้องโอนเงินตอนนี้</div>
<p><a class="link" href="index.php">กลับหน้าแรก</a></p>
<?php endif; ?>
</div></div>
<?php else: ?>
<div class="wrap">
  <div class="card">
    <h2>ข้อมูลผู้รับสินค้า</h2>
    <?php if($err): ?><div class="err"><?= h($err) ?></div><?php endif; ?>
    <form method="post">
      <label>ชื่อ-นามสกุล *</label><input name="name" required>
      <label>อีเมล</label><input name="email" type="email">
      <label>เบอร์โทร *</label><input name="phone" required>
      <label>ที่อยู่จัดส่ง *</label><textarea name="address" rows="4" required></textarea>
      <label>วิธีชำระเงิน</label>
      <select name="pay_method">
        <option value="cod">เก็บเงินปลายทาง (COD)</option>
        <option value="transfer">โอน/พร้อมเพย์</option>
      </select>
      <button class="btn" type="submit">ยืนยันคำสั่งซื้อ</button>
      <p style="margin-top:10px"><a class="link" href="cart.php">← กลับไปตะกร้า</a></p>
    </form>
  </div>
  <div class="card">
    <h2>สรุปคำสั่งซื้อ</h2>
    <?php foreach (($_SESSION['cart'] ?? []) as $it): ?>
      <div class="sum">
        <div><?= h((string)($it['name'] ?? '')) ?> × <?= (int)($it['qty'] ?? 0) ?> (<?= h((string)($it['size'] ?? '')) ?>)</div>
        <div>฿<?= number_format(((float)($it['price'] ?? 0))*((int)($it['qty'] ?? 0)), 2) ?></div>
      </div>
    <?php endforeach; ?>
    <hr><div class="sum" style="font-weight:700"><div>ยอดรวม</div><div>฿<?= number_format(cart_total(), 2) ?></div></div>
  </div>
</div>
<?php endif; ?>
</body></html>
