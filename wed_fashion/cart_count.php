<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cart_lib.php';
header('Content-Type: text/plain; charset=utf-8');
echo (string)cart_count_items();
