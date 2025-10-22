<?php require __DIR__.'/config.php'; ?>
<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__.'/config.php';

function resp($ok,$data=[],$code=200){ http_response_code($code); echo json_encode(['ok'=>$ok]+$data,JSON_UNESCAPED_UNICODE); exit; }

$raw = file_get_contents('php://input');
$in = $raw ? json_decode($raw,true) : $_POST;
$action = $_GET['action'] ?? ($in['action'] ?? 'me');

if ($action==='register') {
$email = strtolower(trim($in['email']??''));
$name = trim($in['name']??'');
$pass = (string)($in['password']??'');
if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pass)<6 || !$name) resp(false,['error'=>'invalid_input'],400);
$pdo = pdo();
$st = $pdo->prepare("SELECT id FROM users WHERE email=?"); $st->execute([$email]);
if ($st->fetch()) resp(false,['error'=>'email_exists'],409);
$hash = password_hash($pass, PASSWORD_DEFAULT);
$ins = $pdo->prepare("INSERT INTO users(email,password_hash,name) VALUES(?,?,?)");
$ins->execute([$email,$hash,$name]);
$_SESSION['uid'] = (int)$pdo->lastInsertId();
resp(true,['user'=>['id'=>$_SESSION['uid'],'email'=>$email,'name'=>$name]]);
}

if ($action==='login') {
$email = strtolower(trim($in['email']??'')); $pass = (string)($in['password']??'');
$st = pdo()->prepare("SELECT id,password_hash,name FROM users WHERE email=?");
$st->execute([$email]); $u = $st->fetch();
if (!$u || !password_verify($pass, $u['password_hash'])) resp(false,['error'=>'bad_auth'],401);
$_SESSION['uid'] = (int)$u['id'];
resp(true,['user'=>['id'=>(int)$u['id'],'email'=>$email,'name'=>$u['name']]]);
}

if ($action==='logout') { $_SESSION = []; session_destroy(); resp(true); }

if ($action==='me') {
if (!isset($_SESSION['uid'])) resp(true,['user'=>null]);
$st = pdo()->prepare("SELECT id,email,name FROM users WHERE id=?"); $st->execute([$_SESSION['uid']]);
resp(true,['user'=>$st->fetch() ?: null]);
}

resp(false,['error'=>'unknown_action'],400);