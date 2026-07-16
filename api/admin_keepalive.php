<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/security.php';
aj360_require_admin();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
