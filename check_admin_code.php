<?php
require_once 'config.php';

$stmt = $pdo->query("SELECT * FROM admin_accounts WHERE username = 'admin'");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "Admin account found:<br>";
    echo "Username: " . htmlspecialchars($admin['username']) . "<br>";
    echo "Code: " . htmlspecialchars($admin['code'] ?? 'N/A') . "<br>";
    print_r($admin);
} else {
    echo "Admin account not found";
}
?>
