<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/ai_faq.php';

header('Content-Type: application/json; charset=utf-8');

$lang = (string) ($_POST['lang'] ?? '');
if (in_array($lang, ['ru', 'kz', 'en'], true)) {
    persistLang($lang);
}

$msg = trim($_POST['message'] ?? $_GET['message'] ?? '');
if ($msg === '' || mb_strlen($msg) > 500) {
    echo json_encode(['answer' => __('ai.unknown'), 'whatsapp' => false, 'whatsapp_url' => WHATSAPP_URL]);
    exit;
}

$_SESSION['ai_request_count'] = (int) ($_SESSION['ai_request_count'] ?? 0);
if ($_SESSION['ai_request_count'] > 60) {
    http_response_code(429);
    echo json_encode(['answer' => __('ai.unknown'), 'whatsapp' => true, 'whatsapp_url' => WHATSAPP_URL]);
    exit;
}
$_SESSION['ai_request_count']++;

$result = findAiAnswer($msg);

echo json_encode([
    'answer'   => $result['answer'],
    'whatsapp' => $result['whatsapp'],
    'whatsapp_url' => WHATSAPP_URL,
]);
