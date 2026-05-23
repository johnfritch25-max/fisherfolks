<?php
require_once 'config.php';

// Auto-create social/feed tables if they don't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        post_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        image_path VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS reactions (
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
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
        share_id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_share (post_id, user_id)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS mentions (
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
    )");
} catch (Exception $e) {
    // Silently continue - tables may already exist
}

// Get user info if logged in
$isLoggedIn = isLoggedIn();
$currentUser = null;
$currentUserName = '';

if ($isLoggedIn && !isAdmin()) {
    // Get current user info
    $stmt = $pdo->prepare("SELECT * FROM fisherfolk WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentUserName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
}

// Get feed posts
$posts = [];
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
} catch (Exception $e) {
    // Feed will just be empty if there's an error
}

// Get latest announcements for notifications
$announcements = [];
$announcementCount = 0;
try {
    $stmt = $pdo->query("SELECT title, message, date_posted FROM announcements ORDER BY date_posted DESC LIMIT 5");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // calculate unread count based on user's last_seen_announcements timestamp
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId) {
        // try to get last_seen_announcements (may not exist on older DBs)
        try {
            $check = $pdo->prepare("SELECT last_seen_announcements FROM users WHERE user_id = ? LIMIT 1");
            $check->execute([$userId]);
            $row = $check->fetch(PDO::FETCH_ASSOC);
            $lastSeen = $row['last_seen_announcements'] ?? null;
        } catch (Exception $ex) {
            $lastSeen = null;
        }

        if ($lastSeen) {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM announcements WHERE date_posted > ?");
            $countStmt->execute([$lastSeen]);
            $announcementCount = (int)$countStmt->fetchColumn();
        } else {
            // if never seen, everything is unread
            $announcementCount = count($announcements);
        }
    }
} catch (Exception $e) {
    // Notifications will be empty if announcements cannot be loaded
}
// Allow forcing the notifications modal open for debugging: ?show_notifications=1
$forceOpenNotifications = isset($_GET['show_notifications']) && $_GET['show_notifications'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fisherfolk Community Feed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="public/tailwind.css" />
    <link rel="stylesheet" href="design-system.css" />
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="gui-override.css" />
    <style>
        :root {
            --primary-blue: #0a56a2;
            --secondary-teal: #0af0ff;
            --light-bg: #031422;
            --card-bg: rgba(13, 31, 45, 0.7);
            --text-dark: #ffffff;
            --text-light: #a5c7d6;
            --border-color: rgba(10, 240, 255, 0.2);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 8px 32px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Manrope', sans-serif;
            background: radial-gradient(circle at top right, #0a2e4d, #031422) !important;
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background-attachment: fixed !important;
        }

        /* ========== HEADER ========== */
        .feed-header {
            background: rgba(3, 20, 34, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(10, 240, 255, 0.3);
            flex-shrink: 0;
        }

        .logo-text {
            font-family: 'Fraunces', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #0af0ff;
            text-shadow: 0 0 15px rgba(10, 240, 255, 0.5);
        }

        .header-content {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.8rem clamp(1rem, 2vw, 2rem);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .header-right {
            display: flex;
            gap: 1.2rem;
            align-items: center;
        }

        .header-tools {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .theme-toggle {
            background: linear-gradient(135deg, var(--primary-blue), #4a8bc2);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            font-size: 1.4rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .theme-toggle:hover {
            transform: scale(1.1) rotateZ(-15deg);
        }

        .notification-btn {
            position: relative;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--secondary-teal), var(--primary-blue));
            color: white;
            text-decoration: none;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            z-index: 999 !important;
            touch-action: manipulation;
        }

        .notification-btn:hover {
            transform: translateY(-2px) scale(1.04);
        }

        .notification-badge {
            position: absolute !important;
            top: 2px !important;
            right: 2px !important;
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            background: #ff416c !important;
            color: #fff !important;
            font-size: 9px !important;
            font-weight: 900 !important;
            border-radius: 50% !important;
            display: none;
            align-items: center !important;
            justify-content: center !important;
            border: 1.5px solid #031422 !important;
            line-height: 1 !important;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5) !important;
            z-index: 20 !important;
        }
 

        .notification-badge.show {
            display: inline-flex;
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        /* Toast Notifications */
        #notification-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        }

        .notification-toast {
            background: rgba(13, 31, 45, 0.95);
            border: 1px solid var(--secondary-teal);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 280px;
            max-width: 350px;
            transform: translateX(120%);
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: auto;
            backdrop-filter: blur(10px);
        }

        .notification-toast.show {
            transform: translateX(0);
        }

        .notification-toast .icon {
            font-size: 24px;
        }

        .notification-toast .content {
            flex: 1;
        }

        .notification-toast .notif-title {
            font-weight: 800;
            color: var(--secondary-teal);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .notification-toast .notif-msg {
            font-size: 14px;
            color: white;
            margin-top: 2px;
        }

        .login-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-teal));
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(44, 106, 162, 0.25);
            letter-spacing: 0.5px;
        }

        .login-btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 20px rgba(44, 106, 162, 0.4);
            filter: brightness(1.1);
        }

        .logout-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.25);
        }

        .logout-btn:hover {
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.4);
        }

        /* ========== MAIN CONTENT ========== */
        .feed-container {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .post-creation, 
        .login-prompt,
        .feed {
            width: 95%;
            max-width: 800px;
            margin: 1.5rem auto;
        }
        
        .feed {
            margin-top: 0;
            padding-bottom: 3rem;
        }

        /* ========== POST CREATION BOX ========== */
        .post-creation {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .post-creator-header {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.2rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-teal));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.6rem;
            flex-shrink: 0;
        }

        .post-input-area {
            flex: 1;
        }

        .post-input-field {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            background: var(--light-bg);
            color: var(--text-dark);
            font-family: 'Manrope', sans-serif;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .post-input-field:hover,
        .post-input-field:focus {
            background: white;
            border-color: var(--primary-blue);
            outline: none;
        }

        body.dark-mode .post-input-field:hover,
        body.dark-mode .post-input-field:focus {
            background: #1a1a1a;
        }

        .post-actions {
            display: flex;
            gap: 0.8rem;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .post-action-icons {
            display: flex;
            gap: 0.6rem;
        }

        .action-icon-btn {
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 1rem;
            cursor: pointer;
            color: var(--text-dark);
            font-weight: 600;
            transition: all 0.2s ease;
        }

        body.dark-mode .action-icon-btn {
            background: rgba(255, 255, 255, 0.05);
        }

        .action-icon-btn:hover {
            background: var(--border-color);
            transform: translateY(-2px);
        }

        .post-submit-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-teal));
            color: white;
            border: none;
            padding: 0.6rem 1.4rem;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .post-submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 106, 162, 0.2);
        }

        .post-submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ========== FEED POSTS ========== */
        .feed {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .post-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }

        .post-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .post-header {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: start;
            border-bottom: 1px solid var(--border-color);
        }

        .post-user-info {
            display: flex;
            gap: 1rem;
            flex: 1;
        }

        .post-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-teal));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .post-meta {
            flex: 1;
        }

        .post-author {
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }

        .post-time {
            font-size: 0.85rem;
            color: var(--text-light);
            margin: 0;
        }

        .post-menu-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--text-light);
        }

        .post-content {
            padding: 1rem 1.5rem;
        }

        .post-text {
            color: var(--text-dark);
            line-height: 1.6;
            margin: 0;
            word-wrap: break-word;
        }

        .post-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            margin-top: 1rem;
            border-radius: 8px;
        }

        .post-stats {
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-light);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .post-actions-bar {
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-around;
            gap: 0.5rem;
        }

        .post-action-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 0.6rem;
            color: var(--text-light);
            cursor: pointer;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.9rem;
        }

        .post-action-btn:hover {
            background: var(--light-bg);
            color: var(--text-dark);
        }

        .post-action-btn.active {
            color: var(--primary-blue);
        }

        /* ========== COMMENTS SECTION ========== */
        .comments-section {
            padding: 0 1.5rem 1rem;
            border-top: 1px solid var(--border-color);
        }

        .comments-list {
            margin-top: 0.8rem;
            max-height: 300px;
            overflow-y: auto;
        }

        .comment {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: var(--light-bg);
            border-radius: 8px;
        }

        body.dark-mode .comment {
            background: rgba(255, 255, 255, 0.05);
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-teal));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .comment-content {
            flex: 1;
            min-width: 0;
        }

        .comment-author {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
            margin: 0 0 0.2rem 0;
        }

        .comment-text {
            color: var(--text-dark);
            font-size: 0.9rem;
            margin: 0;
            word-wrap: break-word;
        }

        .comment-time {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-top: 0.3rem;
        }

        .add-comment {
            display: flex;
            gap: 0.6rem;
            align-items: center;
            margin-top: 0.8rem;
        }

        .comment-input {
            flex: 1;
            padding: 0.5rem 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 15px;
            background: var(--light-bg);
            color: var(--text-dark);
            font-family: 'Manrope', sans-serif;
            font-size: 0.9rem;
        }

        .comment-input:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        .comment-submit-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--primary-blue);
            transition: all 0.2s ease;
        }

        .comment-submit-btn:hover {
            transform: scale(1.2);
        }

        /* ========== LOGIN PROMPT FOR ANONYMOUS ========== */
        .login-prompt {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-teal));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin: 2rem auto;
            max-width: 600px;
        }

        .login-prompt h2 {
            margin: 0 0 1rem 0;
            font-size: 1.5rem;
        }

        #notifications {
            scroll-margin-top: 96px;
        }

        .notifications-panel {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .notifications-panel.hidden {
            display: none;
        }

        .notifications-modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1rem !important;
            background: rgba(0, 0, 0, 0.65) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            backdrop-filter: blur(8px) !important;
            z-index: 9999 !important;
            margin: 0 !important;
            border: 0 !important;
            overflow: auto !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            transition: opacity 120ms ease !important;
        }

        .notifications-modal.open {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        .notifications-modal-card {
            width: min(680px, 90vw) !important;
            max-height: min(75vh, 600px) !important;
            overflow-y: auto !important;
            background: var(--card-bg) !important;
            border-radius: 16px !important;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4) !important;
            border: 1px solid var(--border-color) !important;
            padding: 1.5rem !important;
            transform: translateY(-20px) scale(.95);
            opacity: 0;
            transition: transform 120ms cubic-bezier(.16,.68,.57,.97), opacity 120ms ease;
            outline: none !important;
            position: relative !important;
        }

        .notifications-modal.open .notifications-modal-card {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .notifications-modal-header {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            gap: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .notifications-modal-close {
            border: none !important;
            background: rgba(0, 0, 0, 0.08) !important;
            color: var(--text-dark) !important;
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
            border-radius: 999px !important;
            cursor: pointer !important;
            font-size: 1.3rem !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s ease !important;
        }

        body.dark-mode .notifications-modal-close {
            background: rgba(255, 255, 255, 0.12) !important;
            color: var(--text-dark) !important;
        }

        /* ========== LOGOUT MODAL MATCHING DASHBOARD DESIGN ========== */
        .logout-confirmation-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(2, 12, 27, 0.85) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 15000 !important;
            -webkit-backdrop-filter: blur(12px) !important;
            backdrop-filter: blur(12px) !important;
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .logout-confirmation-overlay.open {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }

        .logout-confirmation-card {
            background: rgba(10, 25, 47, 0.95) !important;
            border: 1px solid rgba(100, 255, 218, 0.1) !important;
            border-radius: 24px !important;
            padding: 2.5rem !important;
            width: 90% !important;
            max-width: 440px !important;
            text-align: center !important;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6) !important;
            transform: translateY(20px) scale(0.98) !important;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            position: relative !important;
        }

        .logout-confirmation-overlay.open .logout-confirmation-card {
            transform: translateY(0) scale(1) !important;
        }

        .logout-confirmation-card h3 {
            font-family: 'Merriweather', serif !important;
            font-size: 1.8rem !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            margin-bottom: 0.8rem !important;
            letter-spacing: -0.02em !important;
        }

        .logout-confirmation-card p {
            color: rgba(255, 255, 255, 0.8) !important;
            font-size: 1.05rem !important;
            margin-bottom: 2.5rem !important;
            line-height: 1.6 !important;
        }

        .modal-actions {
            display: flex !important;
            gap: 1.2rem !important;
            justify-content: center !important;
        }

        .modal-btn {
            padding: 0.9rem 2.2rem !important;
            border-radius: 14px !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            cursor: pointer !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: none !important;
            letter-spacing: 0.5px !important;
        }

        .modal-btn.cancel {
            background: rgba(33, 118, 199, 0.9) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(33, 118, 199, 0.3) !important;
        }

        .modal-btn.confirm {
            background: rgba(26, 75, 122, 0.95) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(26, 75, 122, 0.3) !important;
        }

        .modal-btn:hover {
            transform: translateY(-3px) !important;
            filter: brightness(1.2) !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4) !important;
        }

        .notifications-panel h2 {
            margin: 0 0 0.75rem 0;
            font-size: 1.1rem;
            color: var(--text-dark);
        }

        .notifications-panel .notice-meta {
            margin: 0 0 1rem 0;
            color: var(--text-light);
            font-size: 0.92rem;
        }

        /* prevent page scroll when modal open */
        body.modal-open {
            overflow: hidden !important;
        }

        .notification-item {
            padding: 0.9rem 1rem;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            background: rgba(44, 106, 162, 0.08);
            border-left: 4px solid var(--primary-blue);
        }

        body.dark-mode .notification-item {
            background: rgba(53, 167, 184, 0.12);
            border-left-color: var(--secondary-teal);
        }

        .notification-item:last-child {
            margin-bottom: 0;
        }

        .notification-title {
            font-weight: 700;
            margin-bottom: 0.35rem;
            color: var(--text-dark);
        }

        .notification-message {
            margin: 0;
            font-size: 0.92rem;
            color: var(--text-dark);
        }

        .notification-date {
            margin-top: 0.35rem;
            font-size: 0.8rem;
            color: var(--text-light);
        }
            margin: 0 0 1rem 0;
            font-size: 1.5rem;
        }

        .login-prompt p {
            margin: 0 0 1.5rem 0;
            opacity: 0.95;
        }

        .login-prompt a {
            background: white;
            color: var(--primary-blue);
            padding: 0.8rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .login-prompt a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .header-content {
                padding: 0.6rem 1rem;
                gap: 10px;
            }

            .logo-text {
                font-size: 1.1rem;
                display: none; /* Hide text on very small screens to give space to buttons */
            }
            
            .header-tools {
                display: flex;
                align-items: center;
                gap: 8px;
                flex: 0 0 auto;
                justify-content: flex-end;
                z-index: 1001;
                position: relative;
            }
            .notification-btn {
                width: 48px !important;
                height: 48px !important;
                font-size: 1.3rem !important;
            }
        }

        @media (min-width: 400px) {
            .logo-text {
                display: block;
            }
        }

        /* ========== POST MENU DROPDOWN ========== */
        .post-menu-container {
            position: relative;
        }

        .post-menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(13, 31, 45, 0.95);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            padding: 0.5rem;
            min-width: 150px;
            z-index: 100;
            display: none;
            backdrop-filter: blur(8px);
        }

        .post-menu-dropdown.show {
            display: block;
        }

        .post-menu-item {
            display: block;
            width: 100%;
            padding: 0.6rem 1rem;
            text-align: left;
            background: none;
            border: none;
            color: var(--text-dark);
            font-size: 0.9rem;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .post-menu-item:hover {
            background: rgba(10, 240, 255, 0.1);
            color: var(--secondary-teal);
        }

        .post-menu-item.delete:hover {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        @media (max-width: 768px) {
            .header-right span {
                display: none; /* Hide name on mobile */
            }

            .feed-container {
                margin: 0;
                padding: 0.5rem;
            }

            .post-creation, 
            .login-prompt,
            .feed {
                width: 100%;
                margin: 0.8rem 0;
                border-radius: 8px;
            }

            .post-header,
            .post-content,
            .post-stats,
            .post-actions-bar {
                padding: 0.75rem;
            }

            .action-icon-btn {
                padding: 6px 12px;
                font-size: 0.85rem;
            }

            .post-submit-btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }

            .notifications-modal {
                padding: 0.5rem !important;
                align-items: flex-start !important;
            }

            .notifications-modal-card {
                width: 100% !important;
                height: calc(100vh - 1rem) !important;
                max-height: none !important;
                padding: 1rem !important;
                border-radius: 12px !important;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                font-size: 1rem;
            }
            .post-action-btn {
                font-size: 0.8rem;
            }
        }

        .post-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .post-modal.open {
            display: flex;
        }

        .post-modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .post-modal-content h2 {
            margin: 0 0 1rem 0;
            color: var(--text-dark);
        }

        .post-modal-content textarea {
            width: 100%;
            height: 120px;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Manrope', sans-serif;
            color: var(--text-dark);
            background: var(--light-bg);
            resize: vertical;
        }

        .post-modal-content input[type="file"] {
            margin: 1rem 0;
            width: 100%;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .modal-buttons button {
            flex: 1;
            padding: 0.7rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .modal-cancel-btn {
            background: var(--text-light);
            color: white;
        }

        .modal-submit-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-teal));
            color: white;
        }
    </style>
</head>
<body>
    <!-- Notification Toasts -->
    <div id="notification-toast-container"></div>
    
    <!-- Header -->
    <header class="feed-header">
        <div class="header-content">
            <div class="header-left">
                <div class="logo-text">🐟 Fisherfolk Community</div>
                <div class="header-tools">
                    <button class="notification-btn" id="notificationBtn" type="button" title="View notifications" aria-label="View notifications" onclick="toggleNotifications()">
                        🔔
                        <span class="notification-badge" id="socialNotifBadge">0</span>
                        <?php if ($announcementCount > 0): ?>
                            <span class="notification-badge show" style="right: auto; left: -4px; background: #0af0ff; color: #031422;"><?php echo $announcementCount; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
            <div class="header-right">
                <?php if ($isLoggedIn): ?>
                    <span style="color: var(--text-dark); font-weight: 600;"><?php echo htmlspecialchars(explode(' ', $currentUserName)[0]); ?></span>
                    <a href="dashboard.php" class="login-btn">Dashboard</a>
                    <button type="button" class="login-btn logout-btn" onclick="openLogoutModal()">Logout</button>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Login</a>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($forceOpenNotifications) && $forceOpenNotifications === true): ?>
            <!-- Debug: forcing notifications modal open via ?show_notifications=1 -->
        <?php else: ?>
            <script>
                // Enforce notifications modal hidden state to prevent CSS overrides
                (function(){
                    var nm = document.getElementById('notificationsModal');
                    if(!nm) return;
                    nm.classList.remove('open');
                    nm.setAttribute('aria-hidden','true');
                })();
            </script>
        <?php endif; ?>
    </header>

    <!-- Main Feed -->
    <div class="feed-container">
        <?php if ($isLoggedIn): ?>
        <!-- Post Creation Box -->
        <div class="post-creation">
            <div class="post-creator-header">
                <div class="user-avatar">👤</div>
                <div class="post-input-area">
                    <input type="text" class="post-input-field" id="postInput" placeholder="What's on your mind, <?php echo htmlspecialchars(explode(' ', $currentUserName)[0]); ?>?" onclick="openPostModal()">
                </div>
            </div>
            <div class="post-actions">
                <div class="post-action-icons">
                    <button class="action-icon-btn" title="Add Photo" onclick="document.getElementById('postImageInput').click()">📷 Photo</button>
                    <input type="file" id="postImageInput" accept="image/*" style="display: none;">
                    <button class="action-icon-btn" title="Add Feeling">😊 Feeling</button>
                </div>
                <button class="post-submit-btn" id="quickPostBtn" onclick="quickPost()">Post</button>
            </div>
        </div>
        <?php else: ?>
        <!-- Login Prompt for Anonymous -->
        <div class="login-prompt">
            <h2>Join the Community</h2>
            <p>Login to share your experiences, post photos, and connect with fellow fisherfolks</p>
            <a href="login.php">Login Now</a>
        </div>
        <?php endif; ?>

        <!-- Feed -->
        <div class="feed">
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <!-- Post Header -->
                    <div class="post-header">
                        <div class="post-user-info">
                            <div class="post-avatar"><?php echo htmlspecialchars(substr($post['first_name'] ?? $post['username'], 0, 1)); ?></div>
                            <div class="post-meta">
                                <p class="post-author"><?php echo htmlspecialchars(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? '')); ?></p>
                                <p class="post-time"><?php echo date('M d, Y g:i A', strtotime($post['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="post-menu-container">
                            <button class="post-menu-btn" onclick="togglePostMenu(event, <?php echo $post['post_id']; ?>)">⋮</button>
                            <div class="post-menu-dropdown" id="postMenu-<?php echo $post['post_id']; ?>">
                                <?php if ($isLoggedIn && ($_SESSION['user_id'] == $post['user_id'] || isAdmin())): ?>
                                    <button class="post-menu-item" onclick="openEditModal(<?php echo $post['post_id']; ?>, '<?php echo base64_encode($post['content']); ?>', true)">✏️ Edit Post</button>
                                    <button class="post-menu-item delete" onclick="confirmDeletePost(<?php echo $post['post_id']; ?>)">🗑️ Delete Post</button>
                                <?php endif; ?>
                                <button class="post-menu-item" onclick="reportPost(<?php echo $post['post_id']; ?>)">🚩 Report Post</button>
                            </div>
                        </div>
                    </div>

                    <!-- Post Content -->
                    <div class="post-content">
                        <?php
                            $decodedContent = html_entity_decode($post['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        ?>
                        <p class="post-text"><?php echo nl2br(htmlspecialchars($decodedContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); ?></p>
                        <?php if ($post['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image" class="post-image">
                        <?php endif; ?>
                    </div>

                    <!-- Post Stats -->
                    <div class="post-stats">
                        <span>👍 <?php echo intval($post['like_count']) + intval($post['love_count']); ?> reactions</span>
                        <span><?php echo intval($post['comment_count']); ?> comments</span>
                        <span><?php echo intval($post['share_count']); ?> shares</span>
                    </div>

                    <!-- Post Actions -->
                    <div class="post-actions-bar">
                        <?php if ($isLoggedIn): ?>
                            <button class="post-action-btn <?php echo $post['user_reaction'] === 'like' ? 'active' : ''; ?>" onclick="reactToPost(<?php echo $post['post_id']; ?>, 'like')">👍 Like</button>
                            <button class="post-action-btn" onclick="toggleComments(<?php echo $post['post_id']; ?>)">💬 Comment</button>
                            <button class="post-action-btn" onclick="sharePost(<?php echo $post['post_id']; ?>)">📤 Share</button>
                        <?php else: ?>
                            <button class="post-action-btn" onclick="alert('Please login to interact')">👍 Like</button>
                            <button class="post-action-btn" onclick="alert('Please login to comment')">💬 Comment</button>
                            <button class="post-action-btn" onclick="alert('Please login to share')">📤 Share</button>
                        <?php endif; ?>
                    </div>

                    <!-- Comments Section -->
                    <div class="comments-section" id="comments-<?php echo $post['post_id']; ?>" style="display: none;">
                        <div class="comments-list" id="comments-list-<?php echo $post['post_id']; ?>">
                            <!-- Comments will load here -->
                        </div>
                        <?php if ($isLoggedIn): ?>
                        <div class="add-comment">
                            <input type="text" class="comment-input" id="comment-input-<?php echo $post['post_id']; ?>" placeholder="Write a comment...">
                            <button class="comment-submit-btn" onclick="addComment(<?php echo $post['post_id']; ?>)">➤</button>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; color: var(--text-light); padding: 1rem;">
                            <a href="login.php">Login to comment</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📝</div>
                    <h3>No posts yet</h3>
                    <p><?php echo $isLoggedIn ? 'Be the first to post!' : 'Login to see the community feed'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications Modal - Outside feed container for fixed overlay -->
    <div class="notifications-modal<?php echo $forceOpenNotifications ? ' open' : ''; ?>" id="notificationsModal" aria-hidden="<?php echo $forceOpenNotifications ? 'false' : 'true'; ?>">
        <div class="notifications-modal-card" role="dialog" aria-modal="true" aria-labelledby="notificationsTitle" tabindex="-1">
            <div class="notifications-modal-header">
                <div>
                    <h2 id="notificationsTitle">🔔 Notifications</h2>
                    <p class="notice-meta">Latest announcements appear here.</p>
                </div>
                <button class="notifications-modal-close" id="closeNotificationsBtn" type="button" aria-label="Close notifications" onclick="closeNotifications()">×</button>
            </div>
            <?php if (count($announcements) > 0): ?>
                <div class="announcements-section">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="notification-item">
                            <div class="notification-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                            <p class="notification-message"><?php echo htmlspecialchars(substr($announcement['message'], 0, 140)); ?><?php echo strlen($announcement['message']) > 140 ? '...' : ''; ?></p>
                            <div class="notification-date"><?php echo date('M d, Y', strtotime($announcement['date_posted'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div id="socialNotificationsList">
                <!-- Social notifications (likes, etc.) will load here -->
            </div>
            
            <?php if (count($announcements) === 0): ?>
                <div id="noAnnouncementsMsg" class="notice-meta">No announcements yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Post Modal -->
    <div id="postModal" class="post-modal">
        <div class="post-modal-content">
            <h2>Create a Post</h2>
            <form id="postForm" onsubmit="submitPost(event)">
                <textarea id="postContent" placeholder="What's on your mind?"></textarea>
                <input type="file" id="postImage" accept="image/*">
                <div class="modal-buttons">
                    <button type="button" class="modal-cancel-btn" onclick="closePostModal()">Cancel</button>
                    <button type="submit" class="modal-submit-btn">Post</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Logout Confirmation Modal -->
    <div class="logout-confirmation-overlay" id="logoutModal">
        <div class="logout-confirmation-card">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to log out?</p>
            <div class="modal-actions">
                <button class="modal-btn cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="modal-btn confirm" onclick="confirmLogout()">Logout</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="post-modal" style="z-index: 3000;">
        <div class="post-modal-content" style="text-align: center; padding: 3rem 2rem;">
            <div style="font-size: 4rem; color: #10a981; margin-bottom: 1rem;">✅</div>
            <h2 style="margin: 0; color: var(--text-dark);">Post successfully</h2>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="post-modal" style="z-index: 3000;">
        <div class="post-modal-content" style="text-align: center; padding: 3rem 2rem;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; background: rgba(239, 68, 68, 0.1); border: 2px solid rgba(239, 68, 68, 0.2); margin-bottom: 1.5rem; box-shadow: 0 8px 16px rgba(239, 68, 68, 0.15);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            <h2 style="margin: 0 0 0.5rem; color: var(--text-dark);">Post Invalid</h2>
            <p id="errorModalMessage" style="color: var(--text-muted); margin: 0;"></p>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Theme Toggle
        function initTheme() {
            const isDark = localStorage.getItem('fisherfolk_dark_mode') === 'true';
            if (isDark) document.body.classList.add('dark-mode');
        }

        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('fisherfolk_dark_mode', isDark);
        }

        // Success Modal
        function showSuccessModal(message = 'Success') {
            document.querySelector('#successModal h2').textContent = message;
            document.getElementById('successModal').classList.add('open');
            setTimeout(() => {
                location.reload();
            }, 1500);
        }

        function showErrorModal(message) {
            document.getElementById('errorModalMessage').textContent = message;
            document.getElementById('errorModal').classList.add('open');
            setTimeout(() => {
                document.getElementById('errorModal').classList.remove('open');
            }, 2500);
        }

        // Post Modal
        function openPostModal() {
            document.getElementById('postModal').classList.add('open');
            document.getElementById('postContent').focus();
        }

        function closePostModal() {
            document.getElementById('postModal').classList.remove('open');
            document.getElementById('postForm').reset();
        }

        function submitPost(e) {
            e.preventDefault();
            const content = document.getElementById('postContent').value;
            const image = document.getElementById('postImage').files[0];

            if (!content.trim() && !image) {
                showErrorModal('Please write something or upload a photo before posting.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'create_post');
            formData.append('content', content);
            if (image) formData.append('image', image);

            fetch('api_social.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closePostModal();
                        showSuccessModal();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        function quickPost() {
            const content = document.getElementById('postInput').value;
            if (!content.trim()) {
                showErrorModal('Please write something before posting.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'create_post');
            formData.append('content', content);

            fetch('api_social.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('postInput').value = '';
                        showSuccessModal();
                    }
                });
        }

        // Comments
        function toggleComments(postId) {
            const section = document.getElementById('comments-' + postId);
            const list = document.getElementById('comments-list-' + postId);
            
            if (section.style.display === 'none') {
                section.style.display = 'block';
                // Load comments
                fetch('api_social.php?action=get_comments&post_id=' + postId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (data.data.length === 0) {
                                list.innerHTML = '<div style="text-align: center; color: var(--text-light); padding: 1rem;">No comments yet</div>';
                            } else {
                                list.innerHTML = data.data.map(c => `
                                    <div class="comment">
                                        <div class="comment-avatar">${(c.first_name || c.username || 'U').charAt(0)}</div>
                                        <div class="comment-content">
                                            <p class="comment-author">${(c.first_name + ' ' + (c.last_name || '')).trim() || c.username}</p>
                                            <p class="comment-text">${c.content}</p>
                                            <div class="comment-time">${new Date(c.created_at).toLocaleString()}</div>
                                        </div>
                                    </div>
                                `).join('');
                            }
                        }
                    });
            } else {
                section.style.display = 'none';
            }
        }

        function addComment(postId) {
            const input = document.getElementById('comment-input-' + postId);
            const content = input.value;
            if (!content.trim()) return;

            const formData = new FormData();
            formData.append('action', 'add_comment');
            formData.append('post_id', postId);
            formData.append('content', content);

            fetch('api_social.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        toggleComments(postId);
                        toggleComments(postId);
                    }
                });
        }

        // Reactions
        function reactToPost(postId, reactionType) {
            const formData = new FormData();
            formData.append('action', 'add_reaction');
            formData.append('post_id', postId);
            formData.append('reaction_type', reactionType);

            fetch('api_social.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        refreshFeed(); // Dynamic update instead of reload
                    }
                });
        }

        // Share
        function sharePost(postId) {
            const formData = new FormData();
            formData.append('action', 'share_post');
            formData.append('post_id', postId);

            fetch('api_social.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Post shared!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        // Close modal on outside click
        document.getElementById('postModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('postModal')) closePostModal();
        });

        // Notifications toggle
        function toggleNotifications() {
            // (debug badge removed)
            const modal = document.getElementById('notificationsModal');
            
            // HIDE BADGE IMMEDIATELY for better UX
            const badge = document.getElementById('socialNotifBadge');
            if (badge) badge.classList.remove('show');

            if (modal.classList.contains('open')) {
                closeNotifications();
                return;
            }

            const isSmall = window.innerWidth <= 768 || (window.matchMedia && window.matchMedia('(max-width: 768px)').matches);

            // Ensure modal is attached to body to avoid transform/stacking issues
            if (modal && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            // clear any leftover display:none from previous close so CSS can show the modal
            try { modal.style.display = ''; } catch (e) {}

            const card = modal.querySelector('.notifications-modal-card');

            if (isSmall) {
                // On small screens, force overlay and fullscreen card to avoid stacking/context issues
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.right = '0';
                modal.style.bottom = '0';
                modal.style.width = '100%';
                modal.style.height = '100%';
                modal.style.display = 'flex';
                modal.style.zIndex = '2147483647';
                modal.style.background = 'rgba(0,0,0,0.9)';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                document.body.classList.add('modal-open');

                if (card) {
                    card.focus();
                    card.style.zIndex = '2147483648';
                    card.style.maxHeight = '100%';
                    card.style.overflowY = 'auto';
                    card.style.position = 'fixed';
                    card.style.top = '0';
                    card.style.left = '0';
                    card.style.right = '0';
                    card.style.bottom = '0';
                    card.style.width = '100%';
                    card.style.height = '100%';
                    card.style.borderRadius = '0';
                    card.style.padding = '1rem';
                    card.style.boxSizing = 'border-box';
                    // temporary visible outline to verify presence
                    card.style.border = '3px solid rgba(255,0,80,0.95)';
                }
            } else {
                // Desktop/tablet: rely on CSS classes
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                if (card) card.focus();
            }

            // Fetch and render social notifications
            fetch('api_social.php?action=get_notifications')
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('socialNotificationsList');
                    if (data.success && data.notifications.length > 0) {
                        list.innerHTML = `
                            <div style="margin: 1.5rem 0 0.8rem; font-weight: 800; color: var(--secondary-teal); font-size: 0.9rem; text-transform: uppercase;">Social Activity</div>
                            ${data.notifications.map(n => {
                                let icon = '👍';
                                let title = 'New Like';
                                let msg = `<strong>${n.sender_name}</strong> liked your post.`;
                                
                                if (n.type === 'comment') {
                                    icon = '💬';
                                    title = 'New Comment';
                                    msg = `<strong>${n.sender_name}</strong> commented on your post.`;
                                } else if (n.type === 'share') {
                                    icon = '📤';
                                    title = 'New Share';
                                    msg = `<strong>${n.sender_name}</strong> shared your post.`;
                                }

                                return `
                                    <div class="notification-item" style="border-left-color: var(--secondary-teal); background: rgba(10, 240, 255, 0.05);">
                                        <div class="notification-title">${icon} ${title}</div>
                                        <p class="notification-message">${msg}</p>
                                        <div class="notification-date">${new Date(n.created_at).toLocaleString()}</div>
                                    </div>
                                `;
                            }).join('')}
                        `;
                    } else {
                        list.innerHTML = '';
                    }
                });

            // Mark social notifications as read
            fetch('api_social.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'mark_read' })
            }).then(() => {
                const badge = document.getElementById('socialNotifBadge');
                if (badge) badge.classList.remove('show');
            });

            // Mark announcements read
            try {
                fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_announcements_read' })
                }).catch(() => {});
            } catch (ex) {
                // ignore
            }
        }

        function closeNotifications() {
            const modal = document.getElementById('notificationsModal');
            if (!modal) return;
            // remove open state
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');

            // clean up forced inline styles on modal
            ['position','top','left','right','bottom','width','height','display','zIndex','background','visibility','opacity','pointerEvents'].forEach(prop => {
                try { modal.style[prop] = ''; } catch(e) {}
            });

            // clean up any inline styles on the inner card (mobile fullscreen styles)
            try {
                const card = modal.querySelector('.notifications-modal-card');
                if (card) {
                    ['position','top','left','right','bottom','width','height','maxHeight','borderRadius','padding','overflowY','boxSizing','zIndex','border','boxShadow','background'].forEach(p => { try { card.style[p] = ''; } catch(e){} });
                }
            } catch (e) {}

            const btn = document.getElementById('notificationBtn');
            if (btn) btn.focus();
        }

        // Post Menu Actions
        function togglePostMenu(e, postId) {
            e.stopPropagation();
            const dropdown = document.getElementById(`postMenu-${postId}`);
            const allMenus = document.querySelectorAll('.post-menu-dropdown');
            allMenus.forEach(m => {
                if (m !== dropdown) m.classList.remove('show');
            });
            dropdown.classList.toggle('show');
        }

        // Close menus on click outside
        document.addEventListener('click', () => {
            const allMenus = document.querySelectorAll('.post-menu-dropdown');
            allMenus.forEach(m => m.classList.remove('show'));
        });

        function confirmDeletePost(postId) {
            if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'delete_post');
                formData.append('post_id', postId);

                fetch('api_social.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            showSuccessModal('Deleted successfully');
                        } else {
                            showErrorModal(res.message);
                        }
                    });
            }
        }

        let currentEditPostId = null;
        function openEditModal(postId, content, isBase64 = false) {
            currentEditPostId = postId;
            let decodedContent = content;
            if (isBase64) {
                try {
                    decodedContent = atob(content);
                } catch (e) {
                    console.error('Failed to decode content', e);
                }
            }
            document.getElementById('postContent').value = decodedContent;
            document.getElementById('postModal').classList.add('open');
            document.querySelector('.post-modal-content h2').textContent = 'Edit Post';
            document.querySelector('.modal-submit-btn').textContent = 'Update';
            
            // Change submit handler for the form
            document.getElementById('postForm').onsubmit = submitEdit;
            }

        function reportPost(postId) {
            if (confirm('Report this post for inappropriate content?')) {
                const formData = new FormData();
                formData.append('action', 'report_post');
                formData.append('post_id', postId);

                fetch('api_social.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            showSuccessModal('Reported successfully');
                        } else {
                            showErrorModal(res.message);
                        }
                    });
            }
        }

        // Toast Notifications Logic
        let lastTotalCount = 0;
        function checkNotifications() {
            fetch('api_social.php?action=get_notifications')
                .then(r => r.json())
                .then(data => {
                    console.log('Checking notifications:', data);
                    if (data.success && data.notifications) {
                        // Count ONLY unread for the badge
                        const unread = data.notifications.filter(n => parseInt(n.is_read) === 0);
                        const unreadCount = unread.length;
                        const totalCount = data.notifications.length;
                        
                        const badge = document.getElementById('socialNotifBadge');
                        if (badge) {
                            if (unreadCount > 0) {
                                badge.textContent = unreadCount;
                                badge.classList.add('show');
                            } else {
                                badge.classList.remove('show');
                            }
                        }

                        // Show toast ONLY if a NEW notification record was added to the database
                        if (totalCount > lastTotalCount && totalCount > 0) {
                            console.log('New notification record found!');
                            const latest = data.notifications[0];
                            // Only show toast if it's actually unread
                            if (parseInt(latest.is_read) === 0) {
                                showNotificationToast(latest);
                            }
                        }
                        lastTotalCount = totalCount;
                    }
                })
                .catch(err => console.error('Notification check failed:', err));
        }

        // Live Feed Refresh
        function refreshFeed() {
            fetch('api_social.php?action=get_feed')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        renderFeed(data.posts);
                    }
                });
        }

        function renderFeed(posts) {
            const feedContainer = document.querySelector('.feed');
            if (!feedContainer) return;

            

            // Save state of open comments
            const openComments = [];
            document.querySelectorAll('.comments-section').forEach(s => {
                if (s.style.display !== 'none') openComments.push(s.id.split('-')[1]);
            });

            if (posts.length === 0) {
                feedContainer.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📝</div>
                        <h3>No posts yet</h3>
                        <p>${<?php echo $isLoggedIn ? 'true' : 'false'; ?> ? 'Be the first to post!' : 'Login to see the community feed'}</p>
                    </div>
                `;
                return;
            }

            const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
            const isAdmin = <?php echo isAdmin() ? 'true' : 'false'; ?>;
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

            feedContainer.innerHTML = posts.map(post => `
                <div class="post-card">
                    <div class="post-header">
                        <div class="post-user-info">
                            <div class="post-avatar">${(post.first_name || post.username).charAt(0)}</div>
                            <div class="post-meta">
                                <p class="post-author">${(post.first_name || '') + ' ' + (post.last_name || '')}</p>
                                <p class="post-time">${new Date(post.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true })}</p>
                            </div>
                        </div>
                        <div class="post-menu-container">
                            <button class="post-menu-btn" onclick="togglePostMenu(event, ${post.post_id})">⋮</button>
                            <div class="post-menu-dropdown" id="postMenu-${post.post_id}">
                                ${ (isLoggedIn && (currentUserId == post.user_id || isAdmin)) ? `
                                    <button class="post-menu-item" onclick="openEditModal(${post.post_id}, '${btoa(post.content)}', true)">✏️ Edit Post</button>
                                    <button class="post-menu-item delete" onclick="confirmDeletePost(${post.post_id})">🗑️ Delete Post</button>
                                ` : '' }
                                <button class="post-menu-item" onclick="reportPost(${post.post_id})">🚩 Report Post</button>
                            </div>
                        </div>
                    </div>
                    <div class="post-content">
                        <p class="post-text">${post.content}</p>
                        ${post.image_path ? `<img src="${post.image_path}" alt="Post image" class="post-image">` : ''}
                    </div>
                    <div class="post-stats">
                        <span>👍 ${parseInt(post.like_count) + parseInt(post.love_count)} reactions</span>
                        <span>${post.comment_count} comments</span>
                        <span>${post.share_count} shares</span>
                    </div>
                    <div class="post-actions-bar">
                        ${isLoggedIn ? `
                            <button class="post-action-btn ${post.user_reaction === 'like' ? 'active' : ''}" onclick="reactToPost(${post.post_id}, 'like')">👍 Like</button>
                            <button class="post-action-btn" onclick="toggleComments(${post.post_id})">💬 Comment</button>
                            <button class="post-action-btn" onclick="sharePost(${post.post_id})">📤 Share</button>
                        ` : `
                            <button class="post-action-btn" onclick="alert('Please login to interact')">👍 Like</button>
                            <button class="post-action-btn" onclick="alert('Please login to comment')">💬 Comment</button>
                            <button class="post-action-btn" onclick="alert('Please login to share')">📤 Share</button>
                        `}
                    </div>
                    <div class="comments-section" id="comments-${post.post_id}" style="display: ${openComments.includes(post.post_id.toString()) ? 'block' : 'none'}">
                        <div class="comments-list" id="comments-list-${post.post_id}"></div>
                        ${isLoggedIn ? `
                            <div class="add-comment">
                                <input type="text" class="comment-input" id="comment-input-${post.post_id}" placeholder="Write a comment...">
                                <button class="comment-submit-btn" onclick="addComment(${post.post_id})">➤</button>
                            </div>
                        ` : '<div style="text-align: center; color: var(--text-light); padding: 1rem;"><a href="login.php">Login to comment</a></div>'}
                    </div>
                </div>
            `).join('');

            // Restore comments for open sections
            openComments.forEach(pid => {
                const list = document.getElementById('comments-list-' + pid);
                if (list) {
                    fetch('api_social.php?action=get_comments&post_id=' + pid)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                if (data.data.length === 0) {
                                    list.innerHTML = '<div style="text-align: center; color: var(--text-light); padding: 1rem;">No comments yet</div>';
                                } else {
                                    list.innerHTML = data.data.map(c => `
                                        <div class="comment">
                                            <div class="comment-avatar">${(c.first_name || c.username || 'U').charAt(0)}</div>
                                            <div class="comment-content">
                                                <p class="comment-author">${((c.first_name || '') + ' ' + (c.last_name || '')).trim() || c.username}</p>
                                                <p class="comment-text">${c.content}</p>
                                                <div class="comment-time">${new Date(c.created_at).toLocaleString()}</div>
                                            </div>
                                        </div>
                                    `).join('');
                                }
                            }
                        });
                }
            });
        }

        function showNotificationToast(notif) {
            const container = document.getElementById('notification-toast-container');
            const toast = document.createElement('div');
            toast.className = 'notification-toast';
            
            let icon = '🔔';
            let msg = '';
            if (notif.type === 'like') {
                icon = '👍';
                msg = `${notif.sender_name} liked your post.`;
            } else if (notif.type === 'comment') {
                icon = '💬';
                msg = `${notif.sender_name} commented on your post.`;
            } else if (notif.type === 'share') {
                icon = '📤';
                msg = `${notif.sender_name} shared your post.`;
            }

            toast.innerHTML = `
                <div class="icon">${icon}</div>
                <div class="content">
                    <div class="notif-title">New Interaction</div>
                    <div class="notif-msg">${msg}</div>
                </div>
            `;
            
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Auto remove
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        }

        // Initialize and attach listeners after DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            
            // Initial check
            checkNotifications();
            // Poll every 5 seconds (testing mode)
            setInterval(checkNotifications, 5000);
            // Auto refresh feed every 10 seconds
            setInterval(refreshFeed, 10000);

            const modal = document.getElementById('notificationsModal');
            if (modal) {
                // ensure modal starts closed
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeNotifications();
                });

                // close on Escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && window.getComputedStyle(modal).display === 'flex') {
                        closeNotifications();
                    }
                });
            }

            const btn = document.getElementById('notificationBtn');
            if (btn) {
                // Keep the inline onclick as the single trigger to avoid double-toggling on mobile.
            }

            // Ensure modal is attached to document.body to avoid stacking-context issues on mobile
            if (modal && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const closeBtn = document.getElementById('closeNotificationsBtn');
            if (closeBtn) closeBtn.addEventListener('click', closeNotifications);

            const mobileClose = document.getElementById('closeMobileNotificationsBtn');
            if (mobileClose) mobileClose.addEventListener('click', function(e){ e.stopPropagation(); closeMobileNotifications(); });

            // Close mobile modal when tapping overlay area
            const mobileModal = document.getElementById('mobileNotificationsModal');
            if (mobileModal) {
                mobileModal.addEventListener('click', function(e){ if (e.target === mobileModal) closeMobileNotifications(); });
            }

            const themeBtn = document.getElementById('themeToggleBtn');
            if (themeBtn) themeBtn.addEventListener('click', toggleTheme);
        });

        function openLogoutModal() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
        }

        function confirmLogout() {
            window.location.href = 'logout.php';
        }

        // Initialize Logout Modal state
        (function() {
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.classList.remove('open');
                logoutModal.setAttribute('aria-hidden', 'true');
            }
        })();

        
    </script>
</body>
</html>
