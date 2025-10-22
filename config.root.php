<?php
declare(strict_types=1);

// เปิด error (ช่วงพัฒนา) — เสร็จงานแล้วค่อยปิด
ini_set('display_errors', '1');
error_reporting(E_ALL);

// ===== DB CONFIG =====
const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'fashionshop';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

// ===== PDO SINGLETON =====
function pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
    }
    return $pdo;
}

// ===== OTHER CONSTANTS (ใช้ตามเว็บ) =====
const VAT_RATE                = 0.07;
const SHIPPING_FLAT           = 40;
const SHIPPING_FREE_THRESHOLD = 1500;