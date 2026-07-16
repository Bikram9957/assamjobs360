<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../lib/security.php';
$_SESSION = [];
session_destroy();
header('Location: ' . aj360_url('admin/login.php'));
exit;

