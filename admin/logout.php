<?php
require_once '../config/admin-config.php';

// Destroy admin session
session_destroy();

// Redirect to admin login
redirect('login.php');
?>






