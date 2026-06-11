<?php
declare(strict_types=1);

function isAuth(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): array
{
    return $_SESSION['user'] ?? [];
}

function hasRole(string $role): bool
{
    return ($_SESSION['user']['role'] ?? '') === $role;
}

function isTeacherOrAdmin(): bool
{
    $r = $_SESSION['user']['role'] ?? '';
    return $r === 'admin' || $r === 'teacher';
}

function requireAuth(): void
{
    if (!isAuth()) {
        redirect('index.php?auth=login');
    }
}

function requireAdmin(): void
{
    requireAuth();
    if (!hasRole('admin')) {
        http_response_code(403);
        die(__('error.forbidden_admin'));
    }
}

function requireTeacher(): void
{
    requireAuth();
    if (!isTeacherOrAdmin()) {
        http_response_code(403);
        die(__('error.forbidden_teacher'));
    }
}

function dashboardUrl(): string
{
    $role = $_SESSION['user']['role'] ?? 'student';
    if ($role === 'admin') {
        return 'admin.php';
    }
    if ($role === 'teacher') {
        return 'teacher.php';
    }
    return 'profile.php';
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user']    = [
        'id'             => (int)$user['id'],
        'name'           => $user['name'],
        'email'          => $user['email'],
        'role'           => $user['role'],
        'xp'             => (int)($user['xp'] ?? 0),
        'teacher_status' => $user['teacher_status'] ?? 'none',
    ];
}

/** Успешный вход/регистрация: проверка сессии, сохранение cookie, ответ клиенту. */
function authSuccessResponse(string $relativeUrl, bool $ajax): void
{
    if (!isAuth()) {
        if ($ajax) {
            jsonResponse([
                'ok'      => false,
                'error'   => 'session',
                'message' => __('auth.err_session'),
            ], 500);
        }
        redirect('index.php?auth=login');
    }
    commitSession();
    if ($ajax) {
        jsonResponse(['ok' => true, 'redirect' => asset($relativeUrl)]);
    }
    redirect($relativeUrl);
}

/** Успех без входа (заявка преподавателя). */
function authPendingResponse(bool $ajax): void
{
    commitSession();
    if ($ajax) {
        jsonResponse(['ok' => true, 'pending' => true]);
    }
    redirect('index.php?auth=pending');
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
