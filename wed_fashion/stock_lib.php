<?php
declare(strict_types=1);
require_once __DIR__ . '/_db.php';

/** map ไซซ์ -> คอลัมน์ในตาราง products */
function stock_col_from_size(string $size): ?string {
    return match (strtoupper(trim($size))) {
        'XS' => 'stock_xs',
        'S'  => 'stock_s',
        'M'  => 'stock_m',
        'L'  => 'stock_l',
        'XL' => 'stock_xl',
        'XXL'=> 'stock_xxl',
        default => null,
    };
}

/** ดึงคงเหลือของไซซ์นั้น (ใช้เช็คโชว์หน้าเว็บหรือก่อนตัดจริง) */
function get_stock(PDO $pdo, int $product_id, string $size): ?int {
    $col = stock_col_from_size($size);
    if (!$col) return null;
    $st = $pdo->prepare("SELECT {$col} AS remain FROM products WHERE id = :id");
    $st->execute([':id' => $product_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['remain'] : null;
}

/**
 * พยายาม “ตัดสต็อกแบบมีเงื่อนไข” (ไม่พอจะไม่ตัดและคืน false)
 * ใช้ WHERE {$col} >= :q เพื่อกันติดลบและ race condition แบบง่าย
 */
function try_decrease_stock(PDO $pdo, int $product_id, string $size, int $qty): bool {
    $col = stock_col_from_size($size);
    if (!$col || $qty <= 0) return false;

    $sql = "UPDATE products
               SET {$col} = {$col} - :q
             WHERE id = :id
               AND {$col} >= :q";
    $st = $pdo->prepare($sql);
    $st->execute([':q' => $qty, ':id' => $product_id]);
    return $st->rowCount() > 0;  // ตัดได้จริงก็ต่อเมื่อสต็อกพอ
}

/** เพิ่มสต็อกคืน (เช่นยกเลิก/คืนของ) */
function increase_stock(PDO $pdo, int $product_id, string $size, int $qty): void {
    $col = stock_col_from_size($size);
    if (!$col || $qty <= 0) return;
    $st = $pdo->prepare("UPDATE products SET {$col} = {$col} + :q WHERE id = :id");
    $st->execute([':q' => $qty, ':id' => $product_id]);
}
