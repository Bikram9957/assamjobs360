<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

session_start();

if (!empty($_SESSION['aj360_user_id'])) {
    header('Location: ' . aj360_url('/', ['p' => 'mock-tests']));
    exit;
}

$mysqli = db();
$error = '';
$indiaStates = [
    'Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh',
    'Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland',
    'Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal',
    'Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir',
    'Ladakh','Lakshadweep','Puducherry'
];
$assamDistricts = [
    'Baksa','Barpeta','Bishwanath','Bongaigaon','Cachar','Charaideo','Chirang','Darrang','Dhemaji','Dhubri',
    'Dibrugarh','Dima Hasao','Goalpara','Golaghat','Hailakandi','Hojai','Jorhat','Kamrup','Kamrup Metropolitan',
    'Karbi Anglong','Karimganj','Kokrajhar','Lakhimpur','Majuli','Morigaon','Nagaon','Nalbari','Sivasagar',
    'Sonitpur','South Salmara-Mankachar','Tinsukia','Udalguri','West Karbi Anglong'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));

    $cfg = require __DIR__ . '/../config/config.php';
    $maxAttempts = (int)($cfg['USER_SIGNUP_RATE_LIMIT_MAX_REQUESTS'] ?? 10);
    $windowSeconds = (int)($cfg['USER_SIGNUP_RATE_LIMIT_WINDOW_SECONDS'] ?? 3600);

    $bucket = 'user-signup|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower((string)($_POST['email'] ?? ''));
    if (!aj360_consume_rate_limit($bucket, $maxAttempts, $windowSeconds)) {
        $error = 'Too many signup attempts. Please try again after some time.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $state = trim((string)($_POST['state'] ?? ''));
        $district = trim((string)($_POST['district'] ?? ''));
        $pinCode = trim((string)($_POST['pin_code'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($name === '') {
            $error = 'Enter your name.';
        } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif ($password === '' || strlen($password) < 8) {
            $error = 'Password must contain at least 8 characters.';
        } elseif (!hash_equals($password, $confirmPassword)) {
            $error = 'Passwords do not match.';
        } elseif ($address === '') {
            $error = 'Enter your address.';
        } elseif ($state === '') {
            $error = 'Select your state.';
        } elseif ($district === '') {
            $error = 'Select your district.';
        } elseif ($pinCode === '' || !preg_match('/^\d{6}$/', $pinCode)) {
            $error = 'Enter a valid 6-digit pin code.';
        }

        if ($error === '' && $phone !== '' && !preg_match('/^[0-9+\-\s]{6,30}$/', $phone)) {
            $error = 'Enter a valid phone number (digits only).';
        }

        if ($error === '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $phoneValue = $phone === '' ? null : $phone;
            $districtValue = $state === 'Assam' ? $district : $district;

            try {
                $stmt = $mysqli->prepare('INSERT INTO users (name, email, phone, address, state, district, pin_code, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssssss', $name, $email, $phoneValue, $address, $state, $districtValue, $pinCode, $passwordHash);
                $stmt->execute();

                $userId = (int)$mysqli->insert_id;
                $_SESSION['aj360_user_id'] = $userId;
                $_SESSION['aj360_user_last_activity'] = time();

                header('Location: ' . aj360_url('/', ['p' => 'mock-tests']));
                exit;
            } catch (Throwable $e) {
                if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                    $error = 'This email is already registered. Please log in instead.';
                } else {
                    $error = 'Unable to create your account. Please try again.';
                    if (defined('AJ360_DEBUG') && AJ360_DEBUG) {
                        $error .= ' ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$csrf = aj360_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | AssamJobs360</title>
    <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= aj360_h(aj360_url('assets/aj360.css')) ?>" rel="stylesheet">
<link href="<?= aj360_h(aj360_url('assets/auth-user.css')) ?>" rel="stylesheet">
</head>
<body class="register-page">
<main class="container py-5 auth-shell">
    <a class="back-link" href="<?= aj360_h(aj360_url('/', ['p' => 'home'])) ?>">← Home</a>
    <section class="card shadow-sm border-0 mt-3 user-auth-card">
        <div class="card-body p-4 p-md-5">
            <span class="eyebrow">CREATE ACCOUNT</span>
            <h1 class="user-auth-title mt-2">Jobseeker Registration</h1>
            <p class="text-muted small mb-4">Register with email and password to start using your account right away.</p>


            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= aj360_h($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>">

                <label class="form-label small mt-2">Name</label>
                <input name="name" type="text" class="form-control" required autocomplete="name" placeholder="Your full name">

                <label class="form-label small mt-2">Email</label>
                <input name="email" type="email" class="form-control" required autocomplete="email" placeholder="you@example.com">

                <label class="form-label small mt-3">Phone (optional)</label>
                <input name="phone" type="text" class="form-control" autocomplete="tel" placeholder="+91 9xxxxxxxxx">

                <label class="form-label small mt-3">Address</label>
                <textarea name="address" class="form-control" required rows="2" placeholder="House no, street, area"></textarea>

                <div class="row g-3 mt-0">
                    <div class="col-12 col-md-6">
                        <label class="form-label small mt-3">State</label>
                        <select name="state" id="stateSelect" class="form-select" required>
                            <option value="">Select state</option>
                            <?php foreach ($indiaStates as $st): ?>
                                <option value="<?= aj360_h($st) ?>"><?= aj360_h($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small mt-3">District</label>
                        <select id="districtSelect" class="form-select">
                            <option value="">Select district</option>
                            <?php foreach ($assamDistricts as $dist): ?>
                                <option value="<?= aj360_h($dist) ?>"><?= aj360_h($dist) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input id="districtText" type="text" class="form-control d-none mt-1" placeholder="Enter district">
                    </div>
                </div>

                <label class="form-label small mt-3">Pin Code</label>
                <input name="pin_code" type="text" class="form-control" required inputmode="numeric" maxlength="6" placeholder="781001">

                <label class="form-label small mt-3">Password</label>
                <input name="password" type="password" class="form-control" required minlength="8" autocomplete="new-password">

                <label class="form-label small mt-3">Confirm password</label>
                <input name="confirm_password" type="password" class="form-control" required minlength="8" autocomplete="new-password">

                <div class="d-grid mt-4">
                    <button class="btn btn-search" type="submit">Create Account</button>
                </div>
            </form>

            <div class="auth-help mt-3">
                Already registered? <a href="<?= aj360_h(aj360_url('user/login.php')) ?>">Login</a>
            </div>
        </div>
    </section>
</main>
<script>
(() => {
    const stateSelect = document.getElementById('stateSelect');
    const districtSelect = document.getElementById('districtSelect');
    const districtText = document.getElementById('districtText');

    function syncDistrictField() {
        const isAssam = stateSelect.value === 'Assam';
        districtSelect.classList.toggle('d-none', !isAssam);
        districtText.classList.toggle('d-none', isAssam);
        districtSelect.name = isAssam ? 'district' : '';
        districtText.name = isAssam ? '' : 'district';
        districtSelect.required = isAssam;
        districtText.required = !isAssam;
    }

    stateSelect.addEventListener('change', () => {
        if (stateSelect.value !== 'Assam') districtText.value = '';
        syncDistrictField();
    });

    syncDistrictField();
})();
</script>
</body>
</html>

