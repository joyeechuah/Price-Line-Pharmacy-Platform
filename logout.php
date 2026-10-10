<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: homepage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Please use the Log Out button.');
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Please return to the previous page, refresh it and use Log Out again.');
}

// Clear login details, checkout tokens and messages from the previous account.
$_SESSION = array();
session_destroy();

header('Location: homepage.php');
exit;
