<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

aj360_enforce_public_rate_limit(true);

header('Content-Type: application/json; charset=utf-8');

$mysqli = db();

$department = isset($_GET['department']) ? trim((string)$_GET['department']) : '';
$category = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
$district = isset($_GET['district']) ? trim((string)$_GET['district']) : '';
$qualification = isset($_GET['qualification']) ? trim((string)$_GET['qualification']) : '';

$sql = "SELECT j.id, j.job_name, j.department, j.category, j.district, j.qualification,
               j.age_limit, j.salary, j.last_date, j.apply_url, j.notification_pdf_url, j.official_website_url,
               j.job_slug
        FROM jobs j
        WHERE 1=1";

$params = [];
$types = '';

if ($department !== '') { $sql .= " AND j.department = ?"; $params[] = $department; $types .= 's'; }
if ($category !== '') { $sql .= " AND j.category = ?"; $params[] = $category; $types .= 's'; }
if ($district !== '') { $sql .= " AND j.district = ?"; $params[] = $district; $types .= 's'; }
if ($qualification !== '') { $sql .= " AND j.qualification = ?"; $params[] = $qualification; $types .= 's'; }

$sql .= " ORDER BY j.last_date ASC LIMIT 50";

$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($row = $res->fetch_assoc()) $out[] = $row;

echo json_encode(['jobs' => $out]);

