<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
redirect('index.php');
