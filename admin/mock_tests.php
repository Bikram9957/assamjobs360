<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/db.php';
aj360_require_admin();

$mysqli = db();
$selectedTestId = (int)($_GET['test_id'] ?? $_POST['test_id'] ?? 0);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create_test') {
        $title = trim((string)($_POST['title'] ?? ''));
        $category = trim((string)($_POST['exam_category'] ?? ''));
        if ($title !== '' && $category !== '') {
            $stmt = $mysqli->prepare('INSERT INTO mock_tests (title, exam_category) VALUES (?, ?)');
            $stmt->bind_param('ss', $title, $category); $stmt->execute();
            $selectedTestId = (int)$mysqli->insert_id;
            $message = 'Mock test created.';
        } else $message = 'Test title and exam category are required.';
    }

    if ($action === 'delete_test') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $mysqli->prepare('DELETE FROM mock_tests WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
        $selectedTestId = 0; $message = 'Mock test deleted.';
    }

    if ($action === 'add_question' && $selectedTestId > 0) {
        $text = trim((string)($_POST['question_text'] ?? ''));
        $difficulty = (string)($_POST['difficulty'] ?? 'medium');
        $options = array_map(static fn($value): string => trim((string)$value), $_POST['options'] ?? []);
        $correctIndex = (int)($_POST['correct_option'] ?? -1);
        $explanation = trim((string)($_POST['explanation'] ?? ''));
        if ($text === '' || count($options) !== 4 || in_array('', $options, true) || !in_array($correctIndex, [0, 1, 2, 3], true)) {
            $message = 'Enter the question, all four options, and the correct option.';
        } else {
            $mysqli->begin_transaction();
            try {
                $stmt = $mysqli->prepare('INSERT INTO questions (mock_test_id, question_text, difficulty, question_type) VALUES (?, ?, ?, "mcq")');
                $stmt->bind_param('iss', $selectedTestId, $text, $difficulty); $stmt->execute(); $questionId = (int)$mysqli->insert_id;
                $optionIds = [];
                foreach ($options as $order => $optionText) {
                    $stmt = $mysqli->prepare('INSERT INTO options (question_id, option_text, sort_order) VALUES (?, ?, ?)');
                    $stmt->bind_param('isi', $questionId, $optionText, $order); $stmt->execute(); $optionIds[] = (int)$mysqli->insert_id;
                }
                $correctJson = json_encode([$optionIds[$correctIndex]], JSON_THROW_ON_ERROR);
                $stmt = $mysqli->prepare('INSERT INTO answers (question_id, correct_option_ids, explanation) VALUES (?, ?, ?)');
                $stmt->bind_param('iss', $questionId, $correctJson, $explanation); $stmt->execute();
                $mysqli->commit(); $message = 'Question added to the test.';
            } catch (Throwable $e) { $mysqli->rollback(); $message = 'Could not save the question.'; }
        }
    }

    if ($action === 'delete_question') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $mysqli->prepare('DELETE FROM questions WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
        $message = 'Question deleted.';
    }
}

$csrf = aj360_csrf_token();
$tests = $mysqli->query('SELECT mt.id, mt.title, mt.exam_category, COUNT(q.id) AS question_count FROM mock_tests mt LEFT JOIN questions q ON q.mock_test_id = mt.id GROUP BY mt.id ORDER BY mt.created_at DESC')->fetch_all(MYSQLI_ASSOC);
$selectedTest = null; $questions = [];
if ($selectedTestId > 0) {
    $stmt = $mysqli->prepare('SELECT id, title, exam_category FROM mock_tests WHERE id = ?'); $stmt->bind_param('i', $selectedTestId); $stmt->execute(); $selectedTest = $stmt->get_result()->fetch_assoc();
    if ($selectedTest) { $stmt = $mysqli->prepare('SELECT id, question_text, difficulty FROM questions WHERE mock_test_id = ? ORDER BY id DESC'); $stmt->bind_param('i', $selectedTestId); $stmt->execute(); $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage Mock Tests | AssamJobs360 Admin</title><link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet"><link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet"></head>
<body class="admin-page"><main class="container py-4"><div class="admin-topbar d-flex justify-content-between align-items-center"><div><div class="admin-kicker">EXAM MANAGEMENT</div><h1>Manage Mock Tests</h1></div><a class="btn btn-outline-secondary btn-sm" href="<?= aj360_h(aj360_url('admin/')) ?>">Back</a></div><?php if ($message): ?><div class="alert alert-info py-2 small"><?= aj360_h($message) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-12 col-lg-4"><section class="card border-0 shadow-sm"><div class="card-body"><h2 class="h6 fw-bold">Create Mock Test</h2><form method="post"><input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>"><input type="hidden" name="action" value="create_test"><label class="form-label small">Test title</label><input class="form-control" name="title" placeholder="ADRE Grade III Practice Test 1" required><label class="form-label small mt-3">Exam category</label><input class="form-control" name="exam_category" placeholder="ADRE Grade III" required><button class="btn btn-search w-100 mt-3">Create Test</button></form></div></section><section class="card border-0 shadow-sm mt-3"><div class="card-body"><h2 class="h6 fw-bold">Your Tests</h2><?php if (!$tests): ?><p class="small text-muted mb-0">No tests created yet.</p><?php else: ?><div class="list-group list-group-flush"><?php foreach ($tests as $test): ?><div class="list-group-item px-0"><a class="fw-semibold small text-decoration-none" href="<?= aj360_h(aj360_url('admin/mock_tests.php', ['test_id'=>(int)$test['id']])) ?>"><?= aj360_h($test['title']) ?></a><div class="text-muted small"><?= aj360_h($test['exam_category']) ?> · <?= (int)$test['question_count'] ?> questions</div><form method="post" class="mt-1"><input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>"><input type="hidden" name="action" value="delete_test"><input type="hidden" name="id" value="<?= (int)$test['id'] ?>"><button class="btn btn-link text-danger p-0 small" onclick="return confirm('Delete this test and all its questions?')">Delete</button></form></div><?php endforeach; ?></div><?php endif; ?></div></section></div>
<div class="col-12 col-lg-8"><?php if (!$selectedTest): ?><div class="card border-0 shadow-sm"><div class="card-body text-muted">Create or select a mock test to manage its questions.</div></div><?php else: ?><section class="card border-0 shadow-sm"><div class="card-body"><span class="eyebrow"><?= aj360_h($selectedTest['exam_category']) ?></span><h1 class="h5 fw-bold mt-1"><?= aj360_h($selectedTest['title']) ?></h1><hr><h2 class="h6 fw-bold">Add Question</h2><form method="post"><input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>"><input type="hidden" name="action" value="add_question"><input type="hidden" name="test_id" value="<?= (int)$selectedTestId ?>"><label class="form-label small">Question</label><textarea class="form-control" name="question_text" rows="3" required></textarea><div class="row g-2 mt-1"><?php foreach (['A','B','C','D'] as $index => $letter): ?><div class="col-12 col-md-6"><label class="form-label small">Option <?= $letter ?></label><input class="form-control" name="options[]" required></div><?php endforeach; ?></div><div class="row g-2 mt-2"><div class="col-md-4"><label class="form-label small">Correct option</label><select class="form-select" name="correct_option"><option value="0">Option A</option><option value="1">Option B</option><option value="2">Option C</option><option value="3">Option D</option></select></div><div class="col-md-4"><label class="form-label small">Difficulty</label><select class="form-select" name="difficulty"><option value="easy">Easy</option><option value="medium" selected>Medium</option><option value="hard">Hard</option></select></div></div><label class="form-label small mt-3">Explanation (optional)</label><textarea class="form-control" name="explanation" rows="2"></textarea><button class="btn btn-search mt-3">Add Question</button></form></div></section><section class="card border-0 shadow-sm mt-3"><div class="card-body"><h2 class="h6 fw-bold">Questions (<?= count($questions) ?>)</h2><?php if (!$questions): ?><p class="small text-muted mb-0">No questions yet.</p><?php else: ?><ol class="mb-0"><?php foreach ($questions as $question): ?><li class="mb-3"><div class="small fw-semibold"><?= aj360_h($question['question_text']) ?></div><span class="badge text-bg-light"><?= aj360_h($question['difficulty']) ?></span><form method="post" class="d-inline ms-2"><input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>"><input type="hidden" name="action" value="delete_question"><input type="hidden" name="test_id" value="<?= (int)$selectedTestId ?>"><input type="hidden" name="id" value="<?= (int)$question['id'] ?>"><button class="btn btn-link text-danger p-0 small" onclick="return confirm('Delete this question?')">Delete</button></form></li><?php endforeach; ?></ol><?php endif; ?></div></section><?php endif; ?></div></div></main></body></html>
