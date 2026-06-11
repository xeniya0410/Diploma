<?php
declare(strict_types=1);

require_once __DIR__ . '/teacher_applications.php';

function handleLogin(PDO $pdo): ?string
{
    applyLangFromRequest();
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        flash('auth_error', __('auth.err_fill'));
        return null;
    }

    $st = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        flash('auth_error', __('auth.err_credentials'));
        return null;
    }

    if (($user['role'] ?? '') === 'teacher' && ($user['teacher_status'] ?? 'none') === 'pending') {
        flash('auth_error', __('auth.teacher_pending_login'));
        return null;
    }

    loginUser($user);
    return dashboardUrl();
}

function mergeTrialProgress(PDO $pdo, int $userId): void
{
    if (empty($_SESSION['trial_completed'])) {
        return;
    }
    $lessonId = (int)($_SESSION['trial_lesson_id'] ?? TRIAL_LESSON_ID);
    if ($lessonId < 1) {
        return;
    }
    $pdo->prepare(
        'INSERT IGNORE INTO lesson_completions (user_id, lesson_id, completed_at) VALUES (?,?,NOW())'
    )->execute([$userId, $lessonId]);
    unset($_SESSION['trial_completed'], $_SESSION['trial_lesson_id']);
}

function parseRegisterAge(): int
{
    if (!empty($_POST['age_band'])) {
        return (int)$_POST['age_band'];
    }
    return (int)($_POST['age'] ?? 0);
}

function handleRegister(PDO $pdo): ?string
{
    applyLangFromRequest();
    verifyCsrf();
    $name  = trim($_POST['name'] ?? '');
    $age   = parseRegisterAge();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] ?? 'student';
    $org   = trim($_POST['organization'] ?? '');
    $exp   = trim($_POST['experience'] ?? '');
    $adminCode = $_POST['admin_code'] ?? '';

    if ($role === 'admin') {
        if ($adminCode !== ADMIN_SECRET_CODE) {
            flash('auth_error', __('auth.err_admin_code'));
            return null;
        }
    } elseif (!in_array($role, ['student', 'teacher'], true)) {
        $role = 'student';
    }

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
        flash('auth_error', __('auth.err_register'));
        return null;
    }

    if ($role === 'student' && ($age < 6 || $age > 18)) {
        flash('auth_error', __('auth.err_age'));
        return null;
    }

    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$email]);
    if ($st->fetch()) {
        flash('auth_error', __('auth.err_email_exists'));
        return null;
    }

    if ($role === 'teacher') {
        ensureTeacherApplicationSchema($pdo);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $teacherStatus = $role === 'teacher' ? 'pending' : 'none';

    try {
        $pdo->prepare(
            'INSERT INTO users (name, age, email, password_hash, role, teacher_status) VALUES (?,?,?,?,?,?)'
        )->execute([$name, $age ?: null, $email, $hash, $role, $teacherStatus]);
    } catch (PDOException $e) {
        $pdo->prepare('INSERT INTO users (name, age, email, password_hash, role) VALUES (?,?,?,?,?)')
            ->execute([$name, $age ?: null, $email, $hash, $role]);
        if ($role === 'teacher') {
            $stFix = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stFix->execute([$email]);
            $fixRow = $stFix->fetch();
            if ($fixRow) {
                setUserTeacherPending($pdo, (int)$fixRow['id']);
            }
        }
    }

    $st = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch();
    if (!$user) {
        flash('auth_error', __('auth.err_register'));
        return null;
    }
    $userId = (int)$user['id'];

    if ($role === 'teacher') {
        saveTeacherApplication($pdo, $userId, $org, $exp);
        $_SESSION['register_pending'] = true;
        return 'pending';
    }

    loginUser($user);
    mergeTrialProgress($pdo, $userId);
    return dashboardUrl();
}

function authReturnUrl(): string
{
    $ref = (string)($_POST['return'] ?? $_GET['return'] ?? '');
    $ref = strtok($ref, '#') ?: '';
    $base = computeAppBase();
    if ($base !== '' && str_starts_with($ref, $base)) {
        $ref = substr($ref, strlen($base));
    }
    $ref = ltrim($ref, '/');
    if ($ref === '' || str_contains($ref, '//')) {
        return 'index.php';
    }
    if (!preg_match('#^[a-z0-9_][a-z0-9_./?=&%\-]*$#i', $ref)) {
        return 'index.php';
    }
    return $ref;
}
