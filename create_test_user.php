<?php
require_once 'config.php';

// Create a test fisherfolk user
$username = 'testfish';
$password = 'testfish123';
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        echo "User '$username' already exists. Password: $password";
    } else {
        // Create user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'fisherfolk', ?)");
        $stmt->execute([$username, $hashedPassword, $username . '@test.com']);
        $userId = $pdo->lastInsertId();
        
        // Create fisherfolk profile
        $stmt = $pdo->prepare("INSERT INTO fisherfolk (user_id, first_name, last_name, contact_number, address, barangay_id, fishing_type, livelihood) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, 'Test', 'Fisher', '09171234567', '123 Test St', 1, 'Commercial Fishing', 'Fisherman']);
        
        echo "✓ Test user created successfully!<br>";
        echo "Username: $username<br>";
        echo "Password: $password<br>";
        echo "User ID: $userId";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
