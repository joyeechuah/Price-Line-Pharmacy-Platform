<?php
session_start();

// Remove the logged-in user's details.
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
    unset($_SESSION['csrf_token']);
}

// Return to the homepage.
header('Location: homepage.php');
exit;