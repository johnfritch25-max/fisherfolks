<?php
require_once 'config.php';

try {
    echo "Database connection: OK\n";

    // Check users table
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Users table exists with " . $result['count'] . " records\n";

    // Check admin user
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        echo "Admin user exists: " . $admin['username'] . " (role: " . $admin['role'] . ")\n";
        echo "Password hash: " . substr($admin['password'], 0, 20) . "...\n";
    } else {
        echo "Admin user NOT found\n";
    }

    // Check fisherfolk table
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM fisherfolk');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Fisherfolk table exists with " . $result['count'] . " records\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>