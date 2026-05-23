<?php
require_once 'config.php';

try {
    // Create admin user
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = 'admin';
    
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, role)
        VALUES (?, ?, ?)
    ");
    
    $result = $stmt->execute([$username, $password, $role]);
    
    if ($result) {
        echo "✓ Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating admin user";
    }
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'UNIQUE constraint')) {
        echo "✓ Admin user already exists!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
