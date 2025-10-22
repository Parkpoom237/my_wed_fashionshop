<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

function product_by_id(string $id): ?array {
$stmt = pdo()->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) return null;
foreach (['tags','colors','sizes'] as $k) $p[$k] = json_decode($p[$k], true) ?: [];
$p['images'] = product_images($p['id']);
return $p;
}
function product_images(string $id): array {
$s = pdo()->prepare("SELECT url FROM product_images WHERE product_id=? ORDER BY sort_order,id");
$s->execute([$id]);
return array_column($s->fetchAll(), 'url');
}
function products_all(): array {
$rows = pdo()->query("SELECT * FROM products ORDER BY created DESC")->fetchAll();
foreach ($rows as &$r) { foreach (['tags','colors','sizes'] as $k) $r[$k] = json_decode($r[$k], true) ?: []; }
return $rows;
}

function inventory_get(string $pid, string $size): int {
$s = pdo()->prepare("SELECT stock FROM inventory WHERE product_id=? AND size=?");
$s->execute([$pid,$size]);
$r = $s->fetch();
return $r ? (int)$r['stock'] : 0;
}
function inventory_decrease(string $pid, string $size, int $qty): bool {
$s = pdo()->prepare("UPDATE inventory SET stock=stock-? WHERE product_id=? AND size=? AND stock>=?");
return $s->execute([$qty,$pid,$size,$qty]);
}

function coupon_lookup_db(?string $code): ?array {
if (!$code) return null;
$s = pdo()->prepare("SELECT * FROM coupons WHERE code=? AND active=1 AND (expires_at IS NULL OR expires_at>NOW())");
$s->execute([strtoupper(trim($code))]);
$c = $s->fetch();
if (!$c) return null;
return [
'code'=>$c['code'],
'type'=>$c['type'],
'value'=>(int)$c['value'],
'min'=>(int)$c['min_subtotal']
];
}

function calc_totals(array $lines, ?array $coupon): array {
$items = []; $subtotal = 0;
foreach ($lines as $l) {
$p = product_by_id($l['id']); if (!$p) continue;
$line_total = (int)$p['price'] * (int)$l['qty'];
$items[] = [
'id'=>$p['id'],'name'=>$p['name'],'size'=>$l['size'],'qty'=>(int)$l['qty'],
'price'=>(int)$p['price'],'colors'=>$p['colors'],'category'=>$p['category'],
'line_total'=>$line_total
];
$subtotal += $line_total;
}
// discount
$discount = 0;
if ($coupon) {
if ($coupon['type']==='percent') $discount = (int) round($subtotal * ($coupon['value']/100));
else $discount = (int)$coupon['value'];
$discount = max(0, min($discount, $subtotal));
}
$after = max(0, $subtotal - $discount);
$shipping = ($after >= SHIPPING_FREE_THRESHOLD || $after===0) ? 0 : SHIPPING_FLAT;
$vat_base = $after + $shipping;
$vat = (int) round($vat_base * VAT_RATE);
$grand = $vat_base + $vat;
return compact('items','subtotal','discount','shipping','vat','grand');
}

/* -------- Auth helpers -------- */
function current_user_id(): ?int {
if (!session_id()) session_start();
return $_SESSION['uid'] ?? null;
}
function require_login(): void {
if (!current_user_id()) { http_response_code(401); echo "Please login"; exit; }
}
