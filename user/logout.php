<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/security.php';

session_start();

$_SESSION = [];
session_destroy();

header('Location: ' . aj360_url('/', ['p' => 'home']));
exit;

