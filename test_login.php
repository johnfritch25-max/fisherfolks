<?php
require_once 'config.php';

// Set up a test session for a fisherfolk user
// Find the first fisherfolk user
$stmt = $pdo->query("SELECT u.user_id, u.username, CONCAT(f.first_name, ' ', f.last_name) as full_name FROM users u JOIN fisherfolk f ON u.user_id = f.user_id WHERE u.role = 'fisherfolk' LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Set up session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = 'fisherfolk';
    
    echo "Test session created for: " . htmlspecialchars($user['full_name']) . " (" . htmlspecialchars($user['username']) . ")<br>";
    echo "Session User ID: " . $_SESSION['user_id'] . "<br>";
    echo "<a href='index.php'>Go to Community Feed</a>";
} else {
    echo "No fisherfolk users found in database";
}
?>
