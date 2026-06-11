<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auth_handlers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url  = handleRegister($pdo);
    $ajax = isAjaxRequest();

    if ($url === 'pending') {
        authPendingResponse($ajax);
    }

    if ($url !== null) {
        syncSessionUserXp($pdo, (int)$_SESSION['user_id']);
        authSuccessResponse($url, $ajax);
    }

    $message = flash('auth_error') ?? __('auth.err_register');
    if ($ajax) {
        jsonResponse(['ok' => false, 'message' => $message]);
    }
    $ret = authReturnUrl();
    $sep = (strpos($ret, '?') !== false) ? '&' : '?';
    redirect($ret . $sep . 'auth=register');
}

redirect('index.php?auth=register');
