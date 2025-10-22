<?php
// admin/config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


define('DB_HOST','localhost');
define('DB_NAME','fashionshop');
define('DB_USER','root');
define('DB_PASS','');
define('BASE_URL','http://localhost:8080/fashionshop/admin/');

require_once __DIR__ . '/_db.php';