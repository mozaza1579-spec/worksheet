<?php
require_once 'config.php';

session_unset();
session_destroy();

if (isset($_GET['backend']) && $_GET['backend'] === 'true') {
    header("location: " . BASE_URL . "backendz_login.php"); 
    exit;
}

header("location: " . BASE_URL);
exit;
?>