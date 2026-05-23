<?php
require_once 'config.php';

// Log in as Juan Dela Cruz (fisherfolk user)
$_SESSION['user_id'] = 2;
$_SESSION['username'] = 'juan';
$_SESSION['role'] = 'fisherfolk';
$_SESSION['full_name'] = 'Juan Dela Cruz';

header('Location: dashboard.php');
exit();
?>
