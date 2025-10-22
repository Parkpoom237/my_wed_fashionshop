<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$orderNo = $_GET['order_no'] ?? '';
$s = pdo()->prepare("SELECT id,grand,payment_status FROM orders WHERE order_no=?");
$s->execute([$orderNo]); $o = $s->fetch();
if (!$o){ echo "ไม่พบออร์เดอร์"; exit; }

echo "<h1>Stripe Demo</h1>";
echo "<p>Order: ".htmlspecialchars($orderNo)."</p>";
echo "<p>ยอดที่ต้องชำระ: ฿".(int)$o['grand']."</p>";
echo "<p>(ที่นี่คุณเสียบ Stripe Checkout / Payment Element ได้เลย)</p>";