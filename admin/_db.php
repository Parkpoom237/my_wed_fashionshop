<?php
require_once __DIR__ . '/config.php';

if (!function_exists('db')) {
    function db(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            $dsn = 'mysql:host=localhost;dbname=fashionshop;charset=utf8mb4';
            $opt = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            $pdo = new PDO($dsn, 'root', '', $opt);
        }
        return $pdo;
    }
}
