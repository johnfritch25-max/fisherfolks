<?php
require_once 'config.php';

try {
    // Create posts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            post_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            image_path VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    echo "✓ Posts table created<br>";

    // Create comments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            comment_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    echo "✓ Comments table created<br>";

    // Create reactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reactions (
            reaction_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT,
            comment_id INT,
            user_id INT NOT NULL,
            reaction_type VARCHAR(50) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
            FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_reaction (post_id, comment_id, user_id)
        )
    ");
    echo "✓ Reactions table created<br>";

    // Create shares table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shares (
            share_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_share (post_id, user_id)
        )
    ");
    echo "✓ Shares table created<br>";

    // Create mentions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mentions (
            mention_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT,
            comment_id INT,
            user_id INT NOT NULL,
            mentioned_user_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
            FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (mentioned_user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    echo "✓ Mentions table created<br>";

    echo "<br><strong>All social media tables created successfully!</strong>";

} catch (Exception $e) {
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
}
?>
