<?php
session_start();

// Send visitors to login if they are not logged in.
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// Create a security token for forms, including logout.
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}