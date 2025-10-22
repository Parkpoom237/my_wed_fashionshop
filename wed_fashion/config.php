<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'fashionshop');
define('DB_USER', 'root');
define('DB_PASS', '');

if (!defined('WF_DIR')) define('WF_DIR', __DIR__);

if (!function_exists('h')) {
  function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

require_once __DIR__ . '/_db.php';
