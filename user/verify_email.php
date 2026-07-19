<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/user_security.php';


session_start();
header('Location: ' . aj360_url('user/login.php'));
exit;
?>

