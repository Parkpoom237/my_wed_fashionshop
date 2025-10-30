<?php
declare(strict_types=1);

/** map ชื่อไซซ์ -> คอลัมน์ใน products */
function stock_col_for_size(string $size): ?string {
    return match (strtoupper(trim($size))) {
        'XS'=>'stock_xs','S'=>'stock_s','M'=>'stock_m','L'=>'stock_l',
        'XL'=>'stock_xl','XXL'=>'stock_xxl','F'=>'stock_f', default=>null,
    };
}

/**
 * อัปเดตสต็อกจากออเดอร์ไปที่ตาราง products.stock_*
 * $mode = 'decrease' เมื่อตัดสต็อก / 'increase' ตอนคืนของ/ยกเลิก
 */
function update_inventory(PDO $pdo, int $orderId, string $mode = 'decrease'): void
{
    $mode = ($mode === 'increase') ? 'increase' : 'decrease';

    // ดึงรายการออเดอร์ (size เอาจาก oi.size, ถ้าไม่มีให้เป็น '')
    $sql = "
        SELECT
            oi.product_id,
            UPPER(TRIM(COALESCE(oi.size, ''))) AS size,
            COALESCE(oi.qty, 0) AS qty
        FROM order_items oi
        WHERE oi.order_id = ?
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$orderId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) return;

    $pdo->beginTransaction();
    try {
        foreach ($rows as $r) {
            $pid  = (int)($r['product_id'] ?? 0);
            $size = (string)($r['size'] ?? '');
            $qty  = (int)($r['qty'] ?? 0);
            if ($pid <= 0 || $qty <= 0) continue;

            $col = stock_col_for_size($size);
            if (!$col) continue; // ไซซ์ไม่รองรับ ก็ข้าม

            // decrease = -qty, increase = +qty
            $delta = ($mode === 'increase') ? $qty : -$qty;

            // อัปเดตให้ไม่ติดลบ
            $sqlUpd = "UPDATE products SET {$col} = GREATEST({$col} + :delta, 0) WHERE id = :pid";
            $u = $pdo->prepare($sqlUpd);
            $u->execute([':delta'=>$delta, ':pid'=>$pid]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
