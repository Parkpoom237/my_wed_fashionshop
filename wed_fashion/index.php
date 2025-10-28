<?php
declare(strict_types=1);

/* includes */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_db.php';
require_once __DIR__ . '/cart_lib.php';
require_once __DIR__ . '/auth_lib.php';

if (!function_exists('h')) {
  function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo  = db();
$user = function_exists('current_customer') ? current_customer() : null;

/* ---------- helper: ให้ “สี” ตามชื่อสินค้า (ทนต่อการสะกด/ช่องว่าง) ---------- */
function color_opts_for(string $name): array {
  $n = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name)), 'UTF-8');
  // normalize คำที่มักสะกดหลายแบบ
  $n = str_replace(['สเเลค','คาร์ดิเเกน','oversize'], ['สแลค','คาร์ดิแกน','oversize'], $n);

  if (mb_stripos($n,'เดรสผ้าลินิน') !== false)           return ['ดำ','ขาว','ชมพู'];
  if (mb_stripos($n,'oversize') !== false)                 return ['ดำ','แดง'];
  if (mb_stripos($n,'สแลค') !== false || mb_stripos($n,'tapered') !== false)
                                                          return ['ดำ','กรม'];
  if (mb_stripos($n,'เชิ้ต') !== false && (mb_stripos($n,'คอตตอน') !== false || mb_stripos($n,'soft') !== false))
                                                          return ['ดำ','ขาว'];
  if (mb_stripos($n,'ริบครอป') !== false)                return ['น้ำตาล','ดำ'];
  if (mb_stripos($n,'คอกลม') !== false && mb_stripos($n,'premium') !== false)
                                                          return ['ขาว','ดำ'];
  if (mb_stripos($n,'วอร์ม') !== false || mb_stripos($n,'jogger') !== false)
                                                          return ['ดำ'];
  if (mb_stripos($n,'สลิปซาติน') !== false)              return ['ขาว','ดำ','ชมพู'];
  if (mb_stripos($n,'เบลเซอร์') !== false)               return ['ดำ','กรม'];
  if (mb_stripos($n,'คาร์ดิ') !== false)                 return ['ดำ','กรม','เทา'];
  if (mb_stripos($n,'บักเก็ต') !== false)                return ['ดำ','เทา'];
  if (mb_stripos($n,'กระเป๋า') !== false && mb_stripos($n,'mini') !== false)
                                                          return ['ดำ'];
  return ['ดำ','ขาว']; // default
}

/* ---------- helper: ให้ “ไซซ์” ตามชื่อสินค้า ---------- */
function size_opts_for(string $name): array {
  $n = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name)), 'UTF-8');

  // สิ่งของที่เป็น Free size
  if (mb_stripos($n,'หมวก') !== false)   return ['F'];
  if (mb_stripos($n,'กระเป๋า') !== false) return ['F'];

  // เสื้อถักริบครอป = S,M
  if (mb_stripos($n,'ริบครอป') !== false) return ['S','M'];

  // เดรส = S,M,L
  if (mb_stripos($n,'เดรส') !== false)     return ['S','M','L'];

  // เสื้อยืด = S,M,L,XL
  if (mb_stripos($n,'เสื้อยืด') !== false) return ['S','M','L','XL'];

  // กางเกง = S,M,L,XL
  if (mb_stripos($n,'กางเกง') !== false)   return ['S','M','L','XL'];

  // เชิ้ต/เบลเซอร์/คาร์ดิแกน = S,M,L
  if (mb_stripos($n,'เชิ้ต') !== false)    return ['S','M','L'];
  if (mb_stripos($n,'เบลเซอร์') !== false) return ['S','M','L'];
  if (mb_stripos($n,'คาร์ดิ') !== false)   return ['S','M','L'];

  return ['S','M','L']; // default
}

/* ---------- ดึงข้อมูลจาก DB ---------- */
$rows = $pdo->query("
  SELECT id, name, price, category, badge, created_at AS created
  FROM products
  ORDER BY created_at DESC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$imgs = $pdo->query("
  SELECT product_id, url
  FROM product_images
  ORDER BY sort_order, id
")->fetchAll(PDO::FETCH_ASSOC);

/* map รูปเป็น product_id => [urls...] (มุมมองจาก /wed_fashion/) */
$imgMap = [];
foreach ($imgs as $im) {
  $url = (strpos($im['url'], 'uploads/') === 0) ? '../'.$im['url'] : $im['url'];
  $imgMap[(string)$im['product_id']][] = $url;
}

/* ---------- ประกอบ PRODUCTS สำหรับฝั่ง JS ---------- */
$PRODUCTS = [];
if ($rows) {
  foreach ($rows as $r) {
    $id   = (string)$r['id'];
    $name = (string)$r['name'];

    $PRODUCTS[] = [
      'id'        => $id,
      'name'      => $name,
      'price'     => (float)$r['price'],
      'category'  => (string)$r['category'],
      'tags'      => [],
      'colors'    => ['#0b1220','#243042'],     // พื้นหลังการ์ด
      'color_opts'=> color_opts_for($name),     // สีที่เลือกได้ (ตามชื่อสินค้า)
      'sizes'     => size_opts_for($name),      // ✅ ไซซ์อัตโนมัติ (แก้จุดที่ไม่ตรง)
      'badge'     => (string)($r['badge'] ?? ''),
      'created'   => (string)($r['created'] ?? date('Y-m-d')),
      'img'       => $imgMap[$id] ?? ['assets/no-image.svg'],
    ];
  }
} else {
  // ---------- fallback ถ้า DB ว่าง (ใช้ชุด static เดิมของคุณ) ----------
  $PRODUCTS = [
    [
      'id'=>'D001','name'=>'เดรสผ้าลินิน A-line','price'=>690,'category'=>'เดรส',
      'tags'=>['ลินิน','มินิมอล'],
      'colors'=>['#c6d1cf','#eae4de'],'color_opts'=>['ดำ','ขาว','ชมพู'],
      'sizes'=>['S','M','L'],'badge'=>'NEW','created'=>'2025-09-20',
      'img'=>['assets/เดรสผ้าลินินสีขาว.jpg','assets/เดรสผ้าลินินสีชมพู.jpg','assets/เดรสผ้าลินินสีดำ.jpg'],
    ],
    [
      'id'=>'T101','name'=>'เสื้อยืด Oversize Essential','price'=>259,'category'=>'เสื้อยืด',
      'tags'=>['โอเวอร์ไซซ์'],
      'colors'=>['#e0e7ff','#111827'],'color_opts'=>['ดำ','แดง'],
      'sizes'=>['S','M','L','XL'],'badge'=>'HIT','created'=>'2025-09-10',
      'img'=>['assets/เสื้อโอเวอร์ไซร้สีดำ.jpg','assets/เสื้อโอเวอร์ไซร้สีเเดง.jpg'],
    ],
    [
      'id'=>'S501','name'=>'กางเกงสแลค Tapered','price'=>590,'category'=>'กางเกง',
      'tags'=>['ทำงาน','ทรงสวย'],
      'colors'=>['#0f172a','#334155'],'color_opts'=>['ดำ','กรม'],
      'sizes'=>['S','M','L','XL'],'badge'=>'','created'=>'2025-09-12',
      'img'=>['assets/กางเกงสเเลคสีกรม.jpg','assets/กางเกงสเเลคสีดำ.jpg'],
    ],
    [
      'id'=>'O301','name'=>'โอเวอร์เชิ้ตซอฟท์คอตตอน','price'=>299,'category'=>'เสื้อเชิ้ต',
      'tags'=>['ซอฟท์','ลุยได้'],
      'colors'=>['#d1d5db','#94a3b8'],'color_opts'=>['ดำ','ขาว'],
      'sizes'=>['S','M','L'],'badge'=>'NEW','created'=>'2025-09-23',
      'img'=>['assets/เสื้อเชิ้ตสีขาว.jpg','assets/เสื้อเชิ้ตสีดำ.jpg'],
    ],
    [
      'id'=>'K221','name'=>'เสื้อถักริบครอป','price'=>490,'category'=>'นิต/ถัก',
      'tags'=>['ยืดหยุ่น'],
      'colors'=>['#f5d0fe','#fce7f3'],'color_opts'=>['น้ำตาล','ดำ'],
      'sizes'=>['S','M'],'badge'=>'','created'=>'2025-08-28',
      'img'=>['assets/เสื้อถักริบครอปสีน้ำตาล.jpg','assets/เสื้อถักริบครอปสีดำ.jpg'],
    ],
    [
      'id'=>'T102','name'=>'เสื้อยืดคอกลม Premium','price'=>200,'category'=>'เสื้อยืด',
      'tags'=>['พรีเมียม'],
      'colors'=>['#f3f4f6','#0b1320'],'color_opts'=>['ขาว','ดำ'],
      'sizes'=>['S','M','L','XL'],'badge'=>'','created'=>'2025-09-02',
      'img'=>['assets/เสื้อยืดสีขาว.jpg','assets/เสื้อยืดสีดำ.jpg'],
    ],
    [
      'id'=>'P777','name'=>'กางเกงวอร์ม Jogger','price'=>390,'category'=>'กางเกง',
      'tags'=>['ลำลอง'],
      'colors'=>['#111827','#374151'],'color_opts'=>['ดำ'],
      'sizes'=>['S','M','L','XL'],'badge'=>'','created'=>'2025-09-18',
      'img'=>['assets/กางเกงวอม.jpg'],
    ],
    [
      'id'=>'D009','name'=>'เดรสสลิปซาติน','price'=>790,'category'=>'เดรส',
      'tags'=>['งานปาร์ตี้'],
      'colors'=>['#ffe4e6','#fecdd3'],'color_opts'=>['ขาว','ดำ','ชมพู'],
      'sizes'=>['S','M','L'],'badge'=>'HIT','created'=>'2025-09-05',
      'img'=>['assets/เดรสซาตินสีขาว.jpg','assets/เดรสซาตินสีชมพู.jpg','assets/เดรสซาตินสีดำ.jpg'],
    ],
    [
      'id'=>'O302','name'=>'เบลเซอร์โครงสวย','price'=>890,'category'=>'เสื้อเชิ้ต',
      'tags'=>['ทำงาน','เบลเซอร์'],
      'colors'=>['#0b1220','#243042'],'color_opts'=>['ดำ','กรม'],
      'sizes'=>['S','M','L'],'badge'=>'','created'=>'2025-09-15',
      'img'=>['assets/เบลเซอร์สีกรม.jpg','assets/เบลเซอร์สีดำ.jpg'],
    ],
    [
      'id'=>'K301','name'=>'คาร์ดิแกนผ้านุ่ม','price'=>490,'category'=>'นิต/ถัก',
      'tags'=>['นุ่ม'],
      'colors'=>['#dbeafe','#e0f2fe'],'color_opts'=>['ดำ','กรม','เทา'],
      'sizes'=>['S','M','L'],'badge'=>'NEW','created'=>'2025-09-26',
      'img'=>['assets/คาดิเเกนสีเทา.jpg','assets/คาดิเเกนสีกรม.jpg','assets/คาดิเเกนสีดำ.jpg'],
    ],
    [
      'id'=>'A111','name'=>'หมวกทรงบักเก็ต','price'=>200,'category'=>'แอคเซสซอรี่',
      'tags'=>['หมวก'],
      'colors'=>['#111827','#f3f4f6'],'color_opts'=>['ดำ','เทา'],
      'sizes'=>['F'],'badge'=>'','created'=>'2025-08-30',
      'img'=>['assets/หมวกสีดำ.jpg','assets/หมวกสีเทา.jpg'],
    ],
    [
      'id'=>'B555','name'=>'กระเป๋าสะพาย Mini','price'=>690,'category'=>'แอคเซสซอรี่',
      'tags'=>['กระเป๋า'],
      'colors'=>['#e5e7eb','#0f172a'],'color_opts'=>['ดำ'],
      'sizes'=>['F'],'badge'=>'','created'=>'2025-09-08',
      'img'=>['assets/กระเป๋า.jpg'],
    ],
  ];
}

/* หมวดหมู่ที่จะแสดงบน UI */
$CATEGORIES = ['ทั้งหมด','เดรส','เสื้อยืด','เสื้อเชิ้ต','กางเกง','นิต/ถัก','แอคเซสซอรี่'];

/* initial from query */
$init_query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$init_cat = isset($_GET['category']) ? (string)$_GET['category'] : 'ทั้งหมด';
$init_sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'featured';
if (!in_array($init_cat, $CATEGORIES, true)) $init_cat = 'ทั้งหมด';

/* expose to JS */
$JS_PRODUCTS = json_encode($PRODUCTS, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$JS_CATEGORIES = json_encode($CATEGORIES, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$JS_INIT = json_encode(['query'=>$init_query,'category'=>$init_cat,'sort'=>$init_sort], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>fashionshop — ร้านเสื้อผ้าแฟชั่น</title>
<style>
:root{ --bg:#0f1115; --card:#141823; --muted:#99a1b3; --txt:#eef2ff; --accent:#7c5cff; --accent2:#49d3b4; }
*{box-sizing:border-box} html,body{margin:0;background:#0b0d12;color:var(--txt);font-family:system-ui,Segoe UI,Roboto,Arial}
a{color:inherit;text-decoration:none} button,input,select{font:inherit;color:inherit}
.container{max-width:1200px;margin:0 auto;padding:0 16px}

/* header */
header{position:sticky;top:0;z-index:50;background:rgba(15,17,21,.72);backdrop-filter:blur(10px);border-bottom:1px solid rgba(255,255,255,.06)}
.nav{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 0}
.brand{display:flex;gap:10px;align-items:center}
.logo{width:36px;height:36px;border-radius:10px;background:conic-gradient(from 180deg at 50% 50%, var(--accent), var(--accent2), #ffd166, var(--accent))}
.search{display:flex;gap:8px;align-items:center;flex:1;max-width:520px;margin:0 12px}
.search input{flex:1;background:#0c0f15;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:10px 12px}
.btn{padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,.1);background:#181c27;cursor:pointer}
.btn.primary{background:var(--accent);border-color:transparent}
.pillbar{display:flex;gap:8px;flex-wrap:wrap}
.pill{padding:8px 12px;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:#0c0f15}
.pill.active{background:var(--accent);border-color:transparent}
.cart-btn{position:relative}.badge{position:absolute;top:-6px;right:-6px;background:#ff6b6b;color:#fff;border-radius:999px;padding:2px 6px;font-size:12px}

/* hero */
.hero{border-bottom:1px solid rgba(255,255,255,.06)}
.hero .inner{display:grid;grid-template-columns:1.2fr .8fr;gap:16px;align-items:center;padding:28px 0}
.hero .image{aspect-ratio:4/3;border-radius:16px;background:
radial-gradient(120% 120% at 10% 10%, rgba(124,92,255,.45), transparent 60%),
radial-gradient(120% 120% at 90% 20%, rgba(73,211,180,.45), transparent 60%),
linear-gradient(135deg,#141a26,#0e131d)}

/* catalog */
main{padding:18px 0 40px}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:8px 0 16px}
.select{background:#0c0f15;border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:10px 12px}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
@media (max-width:1000px){.grid{grid-template-columns:repeat(3,1fr)} .hero .inner{grid-template-columns:1fr}}
@media (max-width:720px){.grid{grid-template-columns:repeat(2,1fr)}}
.card{background:var(--card);border:1px solid rgba(255,255,255,.06);border-radius:16px;overflow:hidden;display:flex;flex-direction:column}
.thumb{aspect-ratio:1/1;display:grid;place-items:center;position:relative}
.thumb .chip{position:absolute;left:10px;top:10px;font-size:12px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1)}
.card .body{padding:12px}.title{font-weight:600;margin:0 0 4px}.price{font-weight:700;margin:6px 0}
.muted{color:var(--muted);font-size:14px}.row{display:flex;gap:8px;align-items:center;margin-top:10px}.row .select{flex:1}

/* slider */
.slider{position:relative;width:100%;aspect-ratio:1/1;overflow:hidden;border-radius:12px;background:#0a0e15}
.slider .slides{display:flex;height:100%;transition:transform .3s ease}
.slider .slides img{flex:0 0 100%;width:100%;height:100%;object-fit:contain;background:#0a0e15}
.slider .nav{position:absolute;top:50%;transform:translateY(-50%);border:0;width:32px;height:32px;border-radius:999px;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;display:grid;place-items:center}
.slider .prev{left:8px}.slider .next{right:8px}
.slider .dots{position:absolute;left:50%;bottom:8px;transform:translateX(-50%);display:flex;gap:6px}
.slider .dot{width:8px;height:8px;border-radius:999px;border:0;background:rgba(255,255,255,.35)} .slider .dot.active{background:#fff}

/* drawer */
.drawer{position:fixed;inset:auto 0 0 auto;width:380px;max-width:100%;background:#0f131c;border-left:1px solid rgba(255,255,255,.08);transform:translateX(100%);transition:.25s;display:flex;flex-direction:column;z-index:60}
.drawer.open{transform:translateX(0)}
.drawer header{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.06)}
.drawer .items{padding:10px 16px;overflow:auto}
.line{display:grid;grid-template-columns:auto 1fr auto;gap:10px;padding:10px 0;border-bottom:1px dashed rgba(255,255,255,.08)}
.mini{width:54px;height:54px;border-radius:8px;background:#131a26}
.drawer .footer{margin-top:auto;border-top:1px solid rgba(255,255,255,.06);padding:14px 16px}
.empty{color:var(--muted);text-align:center;padding:24px}
footer{border-top:1px solid rgba(255,255,255,.06);padding:18px 0;color:var(--muted)}
</style>
</head>
<body>

<header>
<div class="container nav">
<div class="brand">
<div class="logo" aria-hidden="true"></div>
<h1 style="margin:0;font-size:18px">fashionshop</h1>
</div>
<div class="search">
<input id="q" type="search" placeholder="ค้นหา (พิมพ์ 'เดรส', 'เสื้อเชิ้ต' ...)" value="<?= h($init_query) ?>">
<button class="btn" id="clearSearch">ล้าง</button>
</div>
<div style="display:flex;gap:8px;align-items:center">
<div class="pillbar" id="pillBarSecondary"></div>
<button id="openCart" class="btn cart-btn">ตะกร้า (<span id="cartCount"><?= (int)cart_count_items() ?></span>)</button>
<?php if ($user): ?>
<a class="btn" href="customer_account.php">สวัสดี, <?= h($user['name']) ?></a>
<a class="btn" href="customer_logout.php">ออกจากระบบ</a>
<?php else: ?>
<a class="btn" href="customer_login.php">เข้าสู่ระบบ</a>
<a class="btn primary" href="customer_register.php">สมัครสมาชิก</a>
<?php endif; ?>
</div>
</div>
</header>

<section class="hero">
<div class="container inner">
<div>
<h2 style="margin:0 0 8px">แฟชั่นใหม่ประจำสัปดาห์</h2>
<p style="margin:0 0 16px;color:#99a1b3">เลือกไซซ์และเพิ่มลงตะกร้าได้ทันที</p>
<div class="pillbar">
<a href="#catalog" class="btn primary">ช้อปเลย</a>
<button class="btn" id="scrollToNew">ดูสินค้าใหม่</button>
</div>
</div>
<div class="image" aria-hidden="true"></div>
</div>
</section>

<main class="container" id="catalog">
<div class="toolbar">
<div></div>
<div style="display:flex;gap:8px;align-items:center">
<span style="color:#99a1b3">จัดเรียง</span>
<select id="sort" class="select">
<option value="featured">แนะนำ</option>
<option value="price-asc">ราคาต่ำ → สูง</option>
<option value="price-desc">ราคาสูง → ต่ำ</option>
<option value="newest">มาใหม่</option>
</select>
</div>
</div>
<div class="grid" id="grid" role="list"></div>
</main>

<!-- Drawer -->
<aside class="drawer" id="drawer" aria-label="ตะกร้าสินค้า">
<header>
<b>ตะกร้าสินค้า</b>
<button class="btn" id="closeCart">ปิด</button>
</header>
<div class="items" id="cartItems"></div>
<div class="footer">
<div style="display:flex;justify-content:space-between;align-items:center">
<span>รวม</span>
<b id="cartTotal">฿0</b>
<a href="checkout.php" class="btn primary">ชำระเงิน</a>
</div>
</div>
</aside>

<footer><div class="container">© <?= date('Y') ?> fashionshop</div></footer>

<script>
/* hydrate */
const PRODUCTS = <?= $JS_PRODUCTS ?>;
const CATEGORIES = <?= $JS_CATEGORIES ?>;
const INITIAL = <?= $JS_INIT ?>;

const state = { query: INITIAL.query||'', category: INITIAL.category||'ทั้งหมด', sort: INITIAL.sort||'featured' };
const fmt = n => '฿' + Number(n).toLocaleString('th-TH');

/* dom */
const grid=document.getElementById('grid');
const pillBarSecondary=document.getElementById('pillBarSecondary');
const sortSel=document.getElementById('sort');
const q=document.getElementById('q');
const clearSearch=document.getElementById('clearSearch');
const cartCountEl=document.getElementById('cartCount');
const drawer=document.getElementById('drawer');
const cartItems=document.getElementById('cartItems');
const cartTotal=document.getElementById('cartTotal');
const openCartBtn=document.getElementById('openCart');
const closeCartBtn=document.getElementById('closeCart');

/* filters */
function getFiltered(){
  let list = [...PRODUCTS];

  // ✅ ตัวช่วย normalize ใช้ได้ทุกบรรทัด
  const norm = s => (s || '')
    .toString()
    .trim()
    .replace(/\s+/g, ' ')
    .toLowerCase()
    .normalize('NFC');

  /* ---------- กรองหมวด ---------- */
  if (state.category && state.category !== 'ทั้งหมด') {
  const norm = s => (s || '').toString().normalize('NFC').toLowerCase().replace(/\s+/g,' ').trim();
  const cat = norm(state.category);

  const inCat = (p) => {
    const pc = norm(p.category);
    const pn = norm(p.name);

    if (pc) return pc === cat; // มีหมวดใน DB ก็ใช้ตรง ๆ

    // --- fallback เดาจากชื่อสินค้า ---
    if (cat === 'เสื้อยืด')      return pn.includes('เสื้อยืด') || pn.includes('t-shirt') || pn.includes('ทีเชิ้ต');
    if (cat === 'นิต/ถัก')       return pn.includes('นิต') || pn.includes('ถัก') || pn.includes('คาร์ดิแกน') || pn.includes('คาร์ดิ');
    if (cat === 'แอคเซสซอรี่')   return pn.includes('หมวก') || pn.includes('กระเป๋า') || pn.includes('accessory') || pn.includes('แอคเซส');
    if (cat === 'เดรส')          return pn.includes('เดรส');
    if (cat === 'เสื้อเชิ้ต')     return pn.includes('เชิ้ต');
    if (cat === 'กางเกง')        return pn.includes('กางเกง');

    return false;
  };

  list = list.filter(inCat);
}


  /* ---------- ค้นหาคีย์เวิร์ด ---------- */
  if (state.query && state.query.trim()){
    const k = norm(state.query);
    list = list.filter(p =>
      norm(p.name).includes(k) ||
      norm(p.category).includes(k) ||
      (p.tags || []).some(t => norm(t).includes(k))
    );
  }

  /* ---------- จัดเรียง ---------- */
  switch (state.sort){
    case 'newest':     list.sort((a,b)=> new Date(b.created)-new Date(a.created)); break;
    case 'price-asc':  list.sort((a,b)=> a.price-b.price); break;
    case 'price-desc': list.sort((a,b)=> b.price-a.price); break;
  }

  return list;
}


/* ---------- การ์ดสินค้า + สไลด์ ---------- */
function productCard(p){
const colorGrad = `linear-gradient(135deg, ${p.colors?.[0]||'#0b1220'}, ${p.colors?.[1]||p.colors?.[0]||'#243042'})`;
const sizes = (p.sizes||[]).map(s => `<option value="${s}">${s}</option>`).join('');
const colors = (p.color_opts||[]).map(c => `<option value="${c}">${c}</option>`).join('');

const imgs = Array.isArray(p.img) ? p.img : (p.img ? [p.img] : []);
const slides = imgs.map(src => `<img src="${src}" alt="${p.name}">`).join('');

const sliderHtml = imgs.length
? `
<div class="slider" data-count="${imgs.length}" style="background:${colorGrad}">
<div class="slides">${slides}</div>
${imgs.length>1 ? `
<button class="nav prev" aria-label="ก่อนหน้า">‹</button>
<button class="nav next" aria-label="ถัดไป">›</button>
<div class="dots">
${imgs.map((_,i)=>`<button class="dot${i===0?' active':''}" data-i="${i}"></button>`).join('')}
</div>` : ``}
</div>`
: `
<div class="slider" style="background:${colorGrad}">
<div class="slides">
<svg viewBox="0 0 100 100" aria-hidden="true" style="width:100%;height:100%">
<path d="M35 18c10 6 20 6 30 0l13 10-7 10v42a6 6 0 0 1-6 6H35a6 6 0 0 1-6-6V38l-7-10 13-10z" fill="rgba(255,255,255,.9)"/>
</svg>
</div>
</div>`;

return `
<article class="card" role="listitem" aria-label="${p.name}">
<div class="thumb">
${p.badge ? `<span class="chip">${p.badge}</span>` : ``}
${sliderHtml}
</div>
<div class="body">
<h3 class="title">${p.name}</h3>
<div class="muted">${p.category}${(p.tags&&p.tags.length)?' · '+p.tags.join(' · '):''}</div>
<div class="price">${fmt(p.price)}</div>

<form class="row" action="cart_add.php" method="post">
<input type="hidden" name="id" value="${p.id}">
<input type="hidden" name="name" value="${p.name.replace(/"/g,'&quot;')}">
<input type="hidden" name="price" value="${p.price}">
${colors ? `
<select class="select" name="color" required>
<option value="" hidden>สี</option>${colors}
</select>` : ``}
${sizes ? `<select class="select" name="size">${sizes}</select>` : `<input type="hidden" name="size" value="">`}
<input type="hidden" name="redirect" value="back">
<button class="btn" type="submit">เพิ่มลงตะกร้า</button>
</form>
</div>
</article>`;
}

/* init sliders */
function initSliders(){
document.querySelectorAll('.slider').forEach(slider=>{
const slides=slider.querySelector('.slides');
const count=+slider.dataset.count || slides.children.length;
if(!count) return;
let i=0;
const go=n=>{
i=(n+count)%count;
slides.style.transform=`translateX(${i*-100}%)`;
slider.querySelectorAll('.dot').forEach((d,idx)=>d.classList.toggle('active', idx===i));
};
slider.querySelector('.prev')?.addEventListener('click',()=>go(i-1));
slider.querySelector('.next')?.addEventListener('click',()=>go(i+1));
slider.querySelectorAll('.dot').forEach(d=>d.addEventListener('click',()=>go(+d.dataset.i)));
go(0);
});
}

/* render */
function render(){
const list=getFiltered();
grid.innerHTML = list.length ? list.map(productCard).join('') : `<div class="empty">ไม่พบสินค้า</div>`;
sortSel.value = state.sort;
q.value = state.query;
initSliders();
}

/* pills */
function renderPills(){
  if (!pillBarSecondary) return;

  pillBarSecondary.innerHTML = CATEGORIES.map(c => `
    <button type="button"
            class="pill ${ (state.category||'') === c ? 'active' : '' }"
            data-cat="${c}"
            aria-pressed="${ (state.category||'') === c }">
      ${c}
    </button>
  `).join('');

  pillBarSecondary.querySelectorAll('.pill').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      e.preventDefault();
      e.stopPropagation();
      state.category = (btn.dataset.cat || '').trim();
      pushQuery();
      renderPills();
      render();
    });
  });
}

/* sync URL */
function pushQuery(){
const p=new URLSearchParams();
if(state.query) p.set('q',state.query);
if(state.category && state.category!=='ทั้งหมด') p.set('category',state.category);
if(state.sort && state.sort!=='featured') p.set('sort',state.sort);
history.replaceState({},'',location.pathname+(p.toString()?`?${p}`:''));
}

/* drawer + cart api */
async function drawCartFromServer(){
try{
const r=await fetch('cart_snapshot.php',{cache:'no-store'});
const data=await r.json(); // {items,total}
if(!data.items || !data.items.length){
cartItems.innerHTML='<div class="empty">ตะกร้าเปล่า</div>';
cartTotal.textContent='฿0';
return;
}
cartItems.innerHTML=data.items.map(it=>`
<div class="line">
<div class="mini"></div>
<div>
<b>${it.name}</b>
<small>${it.color?`สี ${it.color} · `:''}ไซซ์ ${it.size||'-'} · ${fmt(it.price)} × ${it.qty}</small>
</div>
<div>
<form action="cart_update.php" method="post" style="display:inline">
<input type="hidden" name="id" value="${it.id}">
<input type="hidden" name="color" value="${it.color||''}">
<input type="hidden" name="size" value="${it.size||''}">
<button class="btn" name="qty" value="${Math.max(1,it.qty-1)}">–</button>
<button class="btn" name="qty" value="${it.qty+1}">+</button>
</form>
<form action="cart_remove.php" method="post" style="display:inline;margin-left:6px">
<input type="hidden" name="id" value="${it.id}">
<input type="hidden" name="color" value="${it.color||''}">
<input type="hidden" name="size" value="${it.size||''}">
<button class="btn">ลบ</button>
</form>
</div>
</div>`).join('');
cartTotal.textContent=fmt(data.total);
await refreshCartBadge();
}catch(e){
cartItems.innerHTML='<div class="empty">โหลดตะกร้าไม่สำเร็จ</div>';
cartTotal.textContent='฿0';
}
}
function openDrawer(){ drawer.classList.add('open'); drawCartFromServer(); }
function closeDrawer(){ drawer.classList.remove('open'); }
openCartBtn?.addEventListener('click',e=>{e.preventDefault();openDrawer();});
closeCartBtn?.addEventListener('click',e=>{e.preventDefault();closeDrawer();});

/* cart badge */
async function refreshCartBadge(){
try{
const r=await fetch('cart_count.php',{cache:'no-store'});
cartCountEl.textContent=await r.text();
}catch{}
}
refreshCartBadge();
if(new URLSearchParams(location.search).has('added')){
refreshCartBadge(); history.replaceState({},'',location.pathname);
}

/* search/sort */
q?.addEventListener('input',e=>{state.query=e.target.value;pushQuery();render();});
clearSearch?.addEventListener('click',()=>{q.value='';state.query='';pushQuery();render();});
sortSel?.addEventListener('change',e=>{state.sort=e.target.value;pushQuery();render();});
document.getElementById('scrollToNew')?.addEventListener('click',()=>{
sortSel.value='newest'; state.sort='newest';
document.getElementById('catalog').scrollIntoView({behavior:'smooth'});
pushQuery(); render();
});

/* init */
renderPills(); render();
window.addEventListener('keydown',e=>{ if(e.key==='Escape') closeDrawer(); });
</script>
</body>
</html>