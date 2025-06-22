<?php
require_once dirname(__DIR__) . '/includes/functions.php';

// Destroy session and redirect to login
session_destroy();
header('Location: ../index.php');
exit();
?>

