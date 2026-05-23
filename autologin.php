<?php
require_once 'config.php';

// Set admin session
$_SESSION['user_id'] = 1; 
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

header('Location: admin.php');
exit();
?>
