<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }
check_csrf();
$_SESSION = [];
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'secure' => cfg('environment') !== 'local', 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
go('login.php');
