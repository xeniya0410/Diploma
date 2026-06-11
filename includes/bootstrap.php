<?php
/**
 * Подключать первым в entry-point файлах.
 * Показывает ошибки PHP (для UniServer / разработки).
 */
require_once __DIR__ . '/config.php';

error_reporting(E_ALL);
$debug = !defined('FINKID_DEBUG') || FINKID_DEBUG;
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
