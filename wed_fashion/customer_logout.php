<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
unset($_SESSION['customer_id'], $_SESSION['customer_name']);
header('Location: index.php');
exit;
