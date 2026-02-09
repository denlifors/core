<?php
require_once 'config/config.php';

// Destroy regular user session
session_destroy();

// Redirect to home page
redirect('index.php');
?>






