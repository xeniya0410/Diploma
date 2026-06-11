<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$lang = $_POST['lang'] ?? $_GET['lang'] ?? 'ru';
if (!in_array($lang, ['ru', 'kz', 'en'], true)) {
    $lang = 'ru';
}
persistLang($lang);

echo json_encode(['ok' => true, 'lang' => $lang]);
