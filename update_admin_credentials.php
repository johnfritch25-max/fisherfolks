<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $newEmail = isset($input['email']) ? trim($input['email']) : '';
        $newPassword = isset($input['password']) ? trim($input['password']) : '';
        
        // Validate email
        if (empty($newEmail)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Email is required']);
            exit;
        }
        
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Invalid email format']);
            exit;
        }
        
        // Validate password (minimum 8 characters)
        if (empty($newPassword) || strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Password must be at least 8 characters']);
            exit;
        }
        
        // Hash the password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update the admin user
        $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE user_id = ? AND role = 'admin'");
        $stmt->execute([$newEmail, $hashedPassword, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            $logStmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, table_name, record_id, timestamp)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([
                $_SESSION['user_id'],
                'Updated admin credentials',
                'users',
                $_SESSION['user_id']
            ]);
            
            http_response_code(200);
            echo json_encode(['ok' => true, 'message' => 'Admin credentials updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Failed to update credentials']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
}
?>
