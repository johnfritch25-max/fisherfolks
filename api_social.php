<?php
require_once 'config.php';
header('Content-Type: application/json');

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

// Get feed - public or user-specific
if ($action === 'get_feed') {
    $limit = $_GET['limit'] ?? 10;
    $offset = $_GET['offset'] ?? 0;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.post_id,
                p.user_id,
                p.content,
                p.image_path,
                p.created_at,
                u.username,
                f.first_name,
                f.last_name,
                (SELECT COUNT(*) FROM reactions WHERE post_id = p.post_id AND reaction_type = 'like') as like_count,
                (SELECT COUNT(*) FROM reactions WHERE post_id = p.post_id AND reaction_type = 'love') as love_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
                (SELECT COUNT(*) FROM shares WHERE post_id = p.post_id) as share_count,
                (SELECT reaction_type FROM reactions WHERE post_id = p.post_id AND user_id = ? LIMIT 1) as user_reaction
            FROM posts p
            JOIN users u ON p.user_id = u.user_id
            LEFT JOIN fisherfolk f ON u.user_id = f.user_id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $userId = $_SESSION['user_id'] ?? 0;
        $stmt->execute([$userId, $limit, $offset]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $posts]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Create post
else if ($action === 'create_post') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in']);
        exit;
    }

    $content = sanitize($_POST['content'] ?? '');
    $userId = $_SESSION['user_id'];

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Content cannot be empty']);
        exit;
    }

    try {
        // Handle image upload if present
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/posts/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $fileName = 'post_' . time() . '_' . basename($_FILES['image']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $imagePath = $uploadPath;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $content, $imagePath]);

        echo json_encode(['success' => true, 'message' => 'Post created', 'post_id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get comments for a post
else if ($action === 'get_comments') {
    $postId = $_GET['post_id'] ?? 0;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.comment_id,
                c.post_id,
                c.user_id,
                c.content,
                c.created_at,
                u.username,
                f.first_name,
                f.last_name,
                (SELECT COUNT(*) FROM reactions WHERE comment_id = c.comment_id AND reaction_type = 'like') as like_count,
                (SELECT reaction_type FROM reactions WHERE comment_id = c.comment_id AND user_id = ? LIMIT 1) as user_reaction
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            LEFT JOIN fisherfolk f ON u.user_id = f.user_id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        
        $userId = $_SESSION['user_id'] ?? 0;
        $stmt->execute([$userId, $postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $comments]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Add comment
else if ($action === 'add_comment') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in']);
        exit;
    }

    $postId = $_POST['post_id'] ?? 0;
    $content = sanitize($_POST['content'] ?? '');
    $userId = $_SESSION['user_id'];

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $userId, $content]);
        $commentId = $pdo->lastInsertId();

        // NOTIFICATION FOR COMMENT
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
            $stmt->execute([$postId]);
            $postOwner = $stmt->fetchColumn();
            if ($postOwner && intval($postOwner) !== intval($userId)) {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, post_id, type) VALUES (?, ?, ?, 'comment')");
                $stmt->execute([$postOwner, $userId, $postId]);
            }
        } catch (Exception $e) {}

        echo json_encode(['success' => true, 'message' => 'Comment added', 'comment_id' => $commentId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Add reaction
else if ($action === 'add_reaction') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in']);
        exit;
    }

    $postId = $_POST['post_id'] ?? null;
    $commentId = $_POST['comment_id'] ?? null;
    $commentId = ($commentId === '' ? null : $commentId);
    $reactionType = sanitize($_POST['reaction_type'] ?? 'like');
    $userId = $_SESSION['user_id'];

    if (!$postId && !$commentId) {
        echo json_encode(['success' => false, 'message' => 'Post or comment ID required']);
        exit;
    }

    try {
        // Check if user already reacted
        $stmt = $pdo->prepare("SELECT reaction_id FROM reactions WHERE post_id = ? AND comment_id <=> ? AND user_id = ?");
        $stmt->execute([$postId, $commentId, $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Same reaction clicked again removes it; a different reaction replaces it.
            $stmt = $pdo->prepare("SELECT reaction_type FROM reactions WHERE reaction_id = ?");
            $stmt->execute([$existing['reaction_id']]);
            $currentReaction = $stmt->fetchColumn();

            if ($currentReaction === $reactionType) {
                // UNLIKE: Remove reaction AND associated notification
                $stmt = $pdo->prepare("DELETE FROM reactions WHERE reaction_id = ?");
                $stmt->execute([$existing['reaction_id']]);
                
                if ($postId) {
                    $stmt = $pdo->prepare("DELETE FROM notifications WHERE post_id = ? AND sender_id = ? AND type = 'like'");
                    $stmt->execute([$postId, $userId]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Reaction removed', 'state' => 'removed']);
            } else {
                $stmt = $pdo->prepare("UPDATE reactions SET reaction_type = ? WHERE reaction_id = ?");
                $stmt->execute([$reactionType, $existing['reaction_id']]);
                echo json_encode(['success' => true, 'message' => 'Reaction updated', 'state' => 'updated']);
            }
        } else {
            // Insert new reaction
            $stmt = $pdo->prepare("INSERT INTO reactions (post_id, comment_id, user_id, reaction_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$postId, $commentId, $userId, $reactionType]);
            
            // CREATE NOTIFICATION (SAFE VERSION)
            try {
                if ($postId) {
                    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
                    $stmt->execute([$postId]);
                    $postOwner = $stmt->fetchColumn();
                    
                    if ($postOwner && intval($postOwner) !== intval($userId)) {
                        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, post_id, type, is_read) VALUES (?, ?, ?, 'like', 0)");
                        $stmt->execute([$postOwner, $userId, $postId]);
                    }
                }
            } catch (Exception $e) {
                // Ignore notification error to keep the reaction working
            }
            
            echo json_encode(['success' => true, 'message' => 'Reaction recorded', 'state' => 'added']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Share post
else if ($action === 'share_post') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in']);
        exit;
    }

    $postId = $_POST['post_id'] ?? 0;
    $userId = $_SESSION['user_id'];

    try {
        // Check if already shared
        $stmt = $pdo->prepare("SELECT share_id FROM shares WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $userId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already shared']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO shares (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$postId, $userId]);
            
            // NOTIFICATION FOR SHARE
            try {
                $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
                $stmt->execute([$postId]);
                $postOwner = $stmt->fetchColumn();
                if ($postOwner && intval($postOwner) !== intval($userId)) {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, post_id, type) VALUES (?, ?, ?, 'share')");
                    $stmt->execute([$postOwner, $userId, $postId]);
                }
            } catch (Exception $e) {}

            echo json_encode(['success' => true, 'message' => 'Post shared']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Edit post
else if ($action === 'edit_post') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in']);
        exit;
    }

    $postId = $_POST['post_id'] ?? 0;
    $content = sanitize($_POST['content'] ?? '');
    $userId = $_SESSION['user_id'];

    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post || $post['user_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE posts SET content = ? WHERE post_id = ?");
        $stmt->execute([$content, $postId]);

        echo json_encode(['success' => true, 'message' => 'Post updated']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Delete post
else if ($action === 'delete_post') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in']);
        exit;
    }

    $postId = $_POST['post_id'] ?? 0;
    $userId = $_SESSION['user_id'];

    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post || ($post['user_id'] != $userId && !isAdmin())) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
        $stmt->execute([$postId]);

        echo json_encode(['success' => true, 'message' => 'Post deleted']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Report post
else if ($action === 'report_post') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in']);
        exit;
    }

    $postId = $_POST['post_id'] ?? 0;
    $userId = $_SESSION['user_id'];
    $reason = isset($_POST['reason']) ? sanitize($_POST['reason']) : 'Inappropriate content';

    try {
        // Check if already reported by this user to prevent spam
        $stmt = $pdo->prepare("SELECT report_id FROM post_reports WHERE post_id = ? AND reporter_id = ?");
        $stmt->execute([$postId, $userId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You have already reported this post.']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO post_reports (post_id, reporter_id, reason) VALUES (?, ?, ?)");
            $stmt->execute([$postId, $userId, $reason]);
            echo json_encode(['success' => true, 'message' => 'Post reported successfully. Admin will review it.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Dismiss report
else if ($action === 'dismiss_report') {
    if (!isLoggedIn() || !isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $reportId = $_POST['report_id'] ?? 0;

    try {
        $stmt = $pdo->prepare("DELETE FROM post_reports WHERE report_id = ?");
        $stmt->execute([$reportId]);
        echo json_encode(['success' => true, 'message' => 'Report dismissed']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get notifications (Persistent History)
else if ($action === 'get_notifications') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, COALESCE(CONCAT(f.first_name, ' ', f.last_name), u.username) as sender_name 
            FROM notifications n 
            JOIN users u ON n.sender_id = u.user_id 
            LEFT JOIN fisherfolk f ON u.user_id = f.user_id
            WHERE n.user_id = ? 
            ORDER BY n.created_at DESC
            LIMIT 15
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();
        echo json_encode(['success' => true, 'notifications' => $notifications]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Mark notifications read
else if ($action === 'mark_read') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
// Get Feed (for live updates)
else if ($action === 'get_feed') {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.post_id,
                p.user_id,
                p.content,
                p.image_path,
                p.created_at,
                u.username,
                f.first_name,
                f.last_name,
                (SELECT COUNT(*) FROM reactions WHERE post_id = p.post_id AND reaction_type = 'like') as like_count,
                (SELECT COUNT(*) FROM reactions WHERE post_id = p.post_id AND reaction_type = 'love') as love_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
                (SELECT COUNT(*) FROM shares WHERE post_id = p.post_id) as share_count,
                (SELECT reaction_type FROM reactions WHERE post_id = p.post_id AND user_id = ? LIMIT 1) as user_reaction
            FROM posts p
            JOIN users u ON p.user_id = u.user_id
            LEFT JOIN fisherfolk f ON u.user_id = f.user_id
            ORDER BY p.created_at DESC
            LIMIT 20
        ");
        
        $userId = $_SESSION['user_id'] ?? 0;
        $stmt->execute([$userId]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'posts' => $posts]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
