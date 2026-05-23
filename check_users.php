<?php
require_once 'config.php';

$stmt = $pdo->query("SELECT u.user_id, u.username, u.role, f.first_name, f.last_name 
                     FROM users u 
                     LEFT JOIN fisherfolk f ON u.user_id = f.user_id 
                     WHERE u.role = 'fisherfolk'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== Fisherfolk User Accounts ===\n";
foreach ($users as $u) {
    printf("user_id=%d | username=%s | name=%s %s\n", 
        $u['user_id'], $u['username'], $u['first_name'] ?? '?', $u['last_name'] ?? '?');
}
?>
