<?php
require_once 'includes/functions.php';
requireLogin();

if (isset($_GET['logout'])) {
    logout();
}
?>

