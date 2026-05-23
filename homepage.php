<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

// Get fisherfolk info
$stmt = $pdo->prepare("SELECT * FROM fisherfolk WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$fisherfolk = $stmt->fetch(PDO::FETCH_ASSOC);
$currentFisherfolkId = (int)($fisherfolk['fisherfolk_id'] ?? 0);

// Get announcements
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY date_posted DESC LIMIT 5");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
$announcementCount = count($announcements);

// Get system theme settings
$stmt = $pdo->query("SELECT setting_name, setting_value FROM admin_settings WHERE setting_name LIKE 'theme_%'");
$themeSettings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $themeSettings[$row['setting_name']] = $row['setting_value'];
}

    
// Get system settings
$stmt = $pdo->query("SELECT setting_name, setting_value FROM admin_settings WHERE setting_name IN ('system_name', 'theme_mode')");
$systemSettings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $systemSettings[$row['setting_name']] = $row['setting_value'];
}

// Default theme values
$defaultTheme = [
    'theme_mode' => 'light',
    'theme_sidebar_color' => '#2c6aa2',
    'theme_body_color' => '#ffffff',
    'theme_text_color' => '#333333',
    'theme_accent_color' => '#2c6aa2'
];

$currentTheme = array_merge($defaultTheme, $themeSettings, $systemSettings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($systemSettings['system_name'] ?? 'Fisherfolk Community'); ?> - Homepage</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/tailwind.css">
    <link rel="stylesheet" href="design-system.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="gui-override.css">
    <style>
        :root {
            --sidebar-color: <?php echo htmlspecialchars($currentTheme['theme_sidebar_color'] ?? '#2c6aa2'); ?>;
            --body-color: <?php echo htmlspecialchars($currentTheme['theme_body_color'] ?? '#ffffff'); ?>;
            --text-color: <?php echo htmlspecialchars($currentTheme['theme_text_color'] ?? '#333333'); ?>;
            --accent-color: <?php echo htmlspecialchars($currentTheme['theme_accent_color'] ?? '#2c6aa2'); ?>;
        }

        body.dark-mode {
            --body-color: #1a1a1a;
            --text-color: #e0e0e0;
        }

        * {
            --sidebar-color: <?php echo htmlspecialchars($currentTheme['theme_sidebar_color'] ?? '#2c6aa2'); ?>;
        }

        html {
            font-size: 16px;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--body-color);
            color: var(--text-color);
            font-family: 'Manrope', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .homepage-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
            background-color: var(--body-color);
        }

        .sidebar {
            background-color: var(--sidebar-color);
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 30px;
            font-family: 'Fraunces', serif;
            letter-spacing: -0.5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }

        .sidebar-menu li {
            margin-bottom: 12px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 16px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }

        .nav-item-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
        }

        .nav-badge {
            min-width: 22px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            flex-shrink: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-row {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
        }

        .notification-btn {
            position: relative;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(53, 167, 184, 0.95), rgba(44, 106, 162, 0.95));
            color: white;
            box-shadow: 0 8px 18px rgba(44, 106, 162, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
        }

        .notification-btn:hover {
            transform: translateY(-1px) scale(1.03);
            box-shadow: 0 10px 22px rgba(44, 106, 162, 0.28);
        }

        .notification-badge {
            position: absolute;
            top: -3px;
            right: -3px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #e74c3c;
            color: white;
            font-size: 11px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #1f1f1f;
        }

        .sidebar-footer-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .sidebar-theme-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
            color: white;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .sidebar-theme-toggle:hover {
            background: rgba(255, 255, 255, 0.16);
            transform: translateY(-1px);
        }

        .profile-theme-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.08);
            color: white;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .profile-theme-toggle:hover {
            background: rgba(255, 255, 255, 0.16);
            transform: translateY(-1px);
        }

        .sidebar-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .theme-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.1));
            border: 2px solid rgba(255, 255, 255, 0.4);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .theme-toggle-btn:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.2));
            border-color: rgba(255, 255, 255, 0.6);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }
        
        .theme-toggle-btn:active {
            transform: translateY(0);
        }

        .logout-btn {
            padding: 10px 16px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #c84a4a;
            border-color: #a83a3a;
        }

        .main-content {
            padding: 30px 40px;
            overflow-y: auto;
            background-color: var(--body-color);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .top-bar h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            font-family: 'Fraunces', serif;
        }

        .user-info {
            text-align: right;
        }

        .user-info p {
            margin: 4px 0;
            font-size: 14px;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 30px;
        }

        .feed-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .post-creator {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        body.dark-mode .post-creator {
            background: #2a2a2a;
            border-color: #3a3a3a;
        }

        .post-creator h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 600;
        }

        .post-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Manrope', sans-serif;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            background-color: var(--body-color);
            color: var(--text-color);
            margin-bottom: 12px;
        }

        body.dark-mode .post-textarea {
            border-color: #444;
            background-color: #333;
        }

        .post-textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(44, 106, 162, 0.1);
        }

        .post-button-row {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .post-btn {
            padding: 10px 24px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .post-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .post-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        body.dark-mode .post-card {
            background: #2a2a2a;
            border-color: #3a3a3a;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .post-author {
            font-weight: 600;
            font-size: 16px;
        }

        .post-date {
            font-size: 12px;
            color: #999;
        }

        body.dark-mode .post-date {
            color: #aaa;
        }

        .post-content {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .post-actions {
            display: flex;
            gap: 15px;
            padding-top: 12px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }

        body.dark-mode .post-actions {
            border-top-color: #3a3a3a;
        }

        .post-action-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        body.dark-mode .post-action-btn {
            color: #aaa;
        }

        .post-action-btn:hover {
            color: var(--accent-color);
        }

        .post-menu-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: #999;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }

        .post-menu-btn:hover {
            color: #333;
        }

        body.dark-mode .post-menu-btn {
            color: #666;
        }

        body.dark-mode .post-menu-btn:hover {
            color: #ccc;
        }

        .post-menu-dropdown {
            position: absolute;
            top: 30px;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 100;
            min-width: 120px;
            display: none;
        }

        .post-menu-dropdown.active {
            display: block;
        }

        .post-menu-dropdown button {
            width: 100%;
            padding: 8px 12px;
            border: none;
            background: none;
            cursor: pointer;
            text-align: left;
            font-size: 14px;
            color: #333;
            transition: background 0.2s ease;
        }

        .post-menu-dropdown button:hover {
            background: #f5f5f5;
        }

        .post-menu-dropdown button.delete {
            color: #ef4444;
        }

        body.dark-mode .post-menu-dropdown {
            background: #2a2a2a;
            border-color: #444;
        }

        body.dark-mode .post-menu-dropdown button {
            color: #e0e0e0;
        }

        body.dark-mode .post-menu-dropdown button:hover {
            background: #3a3a3a;
        }

        .sidebar-widget {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        #notifications {
            scroll-margin-top: 24px;
        }

        body.dark-mode .sidebar-widget {
            background: #2a2a2a;
            border-color: #3a3a3a;
        }

        .sidebar-widget h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 600;
        }

        .announcement-item {
            padding: 12px;
            margin-bottom: 12px;
            background: rgba(44, 106, 162, 0.08);
            border-left: 3px solid var(--accent-color);
            border-radius: 4px;
        }

        body.dark-mode .announcement-item {
            background: rgba(44, 106, 162, 0.15);
        }

        .announcement-title {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .announcement-message {
            font-size: 12px;
            line-height: 1.5;
            color: #666;
        }

        body.dark-mode .announcement-message {
            color: #ccc;
        }

        .announcement-date {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }

        body.dark-mode .announcement-date {
            color: #777;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .features-list li {
            padding: 12px;
            margin-bottom: 8px;
            background: rgba(44, 106, 162, 0.08);
            border-radius: 6px;
            border-left: 3px solid var(--accent-color);
            font-size: 13px;
            font-weight: 500;
        }

        body.dark-mode .features-list li {
            background: rgba(44, 106, 162, 0.15);
        }

        .no-posts {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        body.dark-mode .no-posts {
            color: #aaa;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .homepage-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                z-index: 1000;
            }

            .main-content {
                padding-bottom: 80px;
            }
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .post-creator {
                padding: 15px;
            }

            .main-content {
                padding: 15px;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .user-info {
                text-align: left;
            }
        }
    </style>
</head>
<body class="light-mode">
  <section class="app-shell" id="appShell">
    <div class="canvas">
      <aside class="sidebar">
        <div class="logo-block">
                    <div class="logo-row">
                        <img src="logo.jpg" alt="Fisherfolk IMS" class="sidebar-logo" />
                        <a class="notification-btn" href="#notifications" title="View announcements from admin" aria-label="View announcements from admin">
                                🔔
                                <?php if ($announcementCount > 0): ?>
                                        <span class="notification-badge"><?php echo $announcementCount; ?></span>
                                <?php endif; ?>
                        </a>
                    </div>
                    <div id="logoText">Fisherfolk Hub<span class="system-tag">User</span></div>
        </div>
        <nav class="nav-list" id="navList">
                    <a class="nav-item active" href="homepage.php"><span class="nav-item-content">🏠 Home</span></a>
                    <a class="nav-item" href="dashboard.php"><span class="nav-item-content">👤 My Profile</span></a>
                    <a class="nav-item" href="dashboard.php?view=my-id-details"><span class="nav-item-content">🆔 My ID</span></a>
                    <a class="nav-item" href="dashboard.php?view=announcements"><span class="nav-item-content">📢 Announcements</span></a>
                    <a class="nav-item" href="dashboard.php?view=subsidy-report"><span class="nav-item-content">💰 Subsidy Request</span></a>
          <hr style="border: none; border-top: 1px solid rgba(0,0,0,0.1); margin: 15px 0;">
        </nav>
      </aside>

      <main class="main-panel">
        <header class="topbar">
          <div class="breadcrumbs" id="breadcrumbs">
            <span class="breadcrumb-item active">Home / Welcome Back!</span>
          </div>
          <div class="profile-wrap" id="profileWrap">
            <div class="profile-info">
              <span class="profile-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
              <span class="profile-role"><?php echo htmlspecialchars($fisherfolk['barangay_id'] ?? 'Barangay'); ?></span>
            </div>
            <button class="btn secondary" onclick="confirmLogout()" style="margin-left: 15px; padding: 6px 12px; font-size: 13px;">Logout</button>
          </div>
        </header>

        <section class="content-grid" style="display: grid; grid-template-columns: 1fr 320px; flex-direction: row;">
                <!-- Feed Section -->
                <div class="feed-section">
                    <!-- Post Creator -->
                    <div class="post-creator">
                        <h3>Share Your Views</h3>
                        <textarea class="post-textarea" id="postContent" placeholder="What's on your mind today? Share your fishing experiences, tips, or views with the community..."></textarea>
                        <div class="post-button-row">
                            <button class="post-btn" onclick="submitPost()">Post</button>
                        </div>
                    </div>

                    <!-- Posts Feed -->
                    <div id="postsFeed">
                        <div class="no-posts">No posts yet. Be the first to share!</div>
                    </div>
                </div>

                <!-- Sidebar Widgets -->
                <aside class="sidebar-widgets">
                    <!-- Announcements -->
                    <div class="sidebar-widget" id="notifications">
                        <h3>🔔 Notifications</h3>
                        <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Announcements from admin will appear here.</p>
                        <?php if (count($announcements) > 0): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-item">
                                    <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                    <div class="announcement-message"><?php echo htmlspecialchars(substr($announcement['message'], 0, 100)); ?><?php echo strlen($announcement['message']) > 100 ? '...' : ''; ?></div>
                                    <div class="announcement-date"><?php echo date('M d, Y', strtotime($announcement['date_posted'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-size: 13px; color: #999;">No announcements yet</p>
                        <?php endif; ?>
                    </div>

                    <!-- Features -->
                    <div class="sidebar-widget">
                        <h3>✨ Features</h3>
                        <ul class="features-list">
                            <li>📋 Register & Manage Profile</li>
                            <li>🆔 Generate & Print ID Card</li>
                            <li>💰 Apply for Boat Damage Subsidy</li>
                            <li>📢 Read Announcements</li>
                            <li>📊 View Your Records</li>
                        </ul>
                    </div>
                </aside>
        </section>
      </main>
    </div>
  </section>

    <!-- Success Modal -->
    <div id="successModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; justify-content: center; align-items: center;">
        <div style="background: var(--card-bg, white); border-radius: 12px; padding: 3rem 2rem; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="font-size: 4rem; color: #10a981; margin-bottom: 1rem;">✅</div>
            <h2 id="successModalMessage" style="margin: 0; color: var(--text-dark, #333);">Post created successfully</h2>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 3000; justify-content: center; align-items: center;">
        <div style="background: var(--card-bg, white); border-radius: 12px; padding: 3rem 2rem; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; background: rgba(239, 68, 68, 0.1); border: 2px solid rgba(239, 68, 68, 0.2); margin-bottom: 1.5rem; box-shadow: 0 8px 16px rgba(239, 68, 68, 0.15);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            <h2 style="margin: 0 0 0.5rem; color: var(--text-dark, #333);">Post Invalid</h2>
            <p id="errorModalMessage" style="color: var(--text-muted, #666); margin: 0;"></p>
        </div>
    </div>

    <script>
        // Add spin animation for theme toggle
        const style = document.createElement('style');
        style.textContent = `
          @keyframes spin {
            0% { transform: rotateY(0deg) scale(1); }
            50% { transform: rotateY(90deg) scale(1.05); }
            100% { transform: rotateY(0deg) scale(1); }
          }
          
          @keyframes fadeInOut {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
          }
        `;
        document.head.appendChild(style);
        const currentFisherfolkId = <?php echo (int)$currentFisherfolkId; ?>;

        // Theme toggle functionality with smooth animation
        function toggleTheme() {
            const body = document.body;
            const themeBtn = document.querySelector('.theme-toggle-btn') || document.getElementById('themeToggleBtn');
            const isDarkMode = body.classList.contains('dark-mode');
            
            // Add animation to button
            themeBtn.style.animation = 'spin 0.6s ease-in-out';
            body.style.animation = 'fadeInOut 0.6s ease-in-out';
            
            if (isDarkMode) {
                body.classList.remove('dark-mode');
                body.classList.add('light-mode');
                localStorage.setItem('theme_mode', 'light');
                localStorage.setItem('fisherfolk_dark_mode', 'false');
                if (themeBtn) {
                  themeBtn.innerHTML = '<span>🌙 Dark Mode</span>';
                  themeBtn.title = 'Switch to Dark Mode';
                }
            } else {
                body.classList.remove('light-mode');
                body.classList.add('dark-mode');
                localStorage.setItem('theme_mode', 'dark');
                localStorage.setItem('fisherfolk_dark_mode', 'true');
                if (themeBtn) {
                  themeBtn.innerHTML = '<span>☀️ Light Mode</span>';
                  themeBtn.title = 'Switch to Light Mode';
                }
            }
            
            // Remove animation after it completes
            setTimeout(() => {
                themeBtn.style.animation = '';
                body.style.animation = '';
            }, 600);
        }

        // Load theme from localStorage with smooth initialization
        window.addEventListener('load', function() {
            const savedTheme = localStorage.getItem('theme_mode') || localStorage.getItem('fisherfolk_dark_mode') === 'true' ? 'dark' : 'light';
            const themeBtn = document.querySelector('.theme-toggle-btn') || document.getElementById('themeToggleBtn');
            
            if (savedTheme === 'dark') {
                document.body.classList.remove('light-mode');
                document.body.classList.add('dark-mode');
                if (themeBtn) {
                  themeBtn.innerHTML = '<span>☀️ Light Mode</span>';
                  themeBtn.title = 'Switch to Light Mode';
                }
                localStorage.setItem('theme_mode', 'dark');
                localStorage.setItem('fisherfolk_dark_mode', 'true');
            } else {
                document.body.classList.add('light-mode');
                document.body.classList.remove('dark-mode');
                if (themeBtn) {
                  themeBtn.innerHTML = '<span>🌙 Dark Mode</span>';
                  themeBtn.title = 'Switch to Dark Mode';
                }
                localStorage.setItem('theme_mode', 'light');
                localStorage.setItem('fisherfolk_dark_mode', 'false');
            }
        });

        // Success Modal
        function showSuccessModal(message = 'Post created successfully') {
            const modal = document.getElementById('successModal');
            document.getElementById('successModalMessage').textContent = message;
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 1500);
        }

        function togglePostMenu(btn, postId) {
            closeAllMenus();
            const dropdown = btn.nextElementSibling;
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        }

        function closeAllMenus() {
            document.querySelectorAll('.post-menu-dropdown.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.post-menu-btn') && !e.target.closest('.post-menu-dropdown')) {
                closeAllMenus();
            }
        });

        function confirmDeletePost(postId) {
            if (confirm('Delete this post?')) {
                deletePost(postId);
            }
        }

        function deletePost(postId) {
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_post',
                    post_id: postId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal('Post deleted successfully');
                    loadPosts();
                } else {
                    showErrorModal(data.message || 'Unable to delete post.');
                }
            })
            .catch(() => showErrorModal('Unable to delete post.'));
        }

        // Error Modal
        function showErrorModal(message) {
            document.getElementById('errorModalMessage').textContent = message;
            const modal = document.getElementById('errorModal');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 2500);
        }

        // Submit post
        function submitPost() {
            const content = document.getElementById('postContent').value.trim();
            if (!content) {
                showErrorModal('Please write something before posting.');
                return;
            }

            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_post',
                    content: content
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('postContent').value = '';
                    showSuccessModal();
                    loadPosts();
                } else {
                    alert('Error posting: ' + data.message);
                }
            })
            .catch(e => console.error('Error:', e));
        }

        // Load posts from feed
        function loadPosts() {
            fetch('api.php?action=get_posts')
                .then(r => r.json())
                .then(data => {
                    const feed = document.getElementById('postsFeed');
                    if (data.posts.length === 0) {
                        feed.innerHTML = '<div class="no-posts">No posts yet. Be the first to share!</div>';
                        return;
                    }

                    feed.innerHTML = data.posts.map(post => `
                        <div class="post-card" style="position: relative;">
                            <div class="post-header">
                                <div>
                                    <span class="post-author">${escapeHtml(post.author_name)}</span>
                                    <span class="post-date">${formatDate(post.created_at)}</span>
                                </div>
                                ${Number(post.fisherfolk_id) === Number(currentFisherfolkId) ? `
                                <div style="position: relative;">
                                    <button class="post-menu-btn" onclick="togglePostMenu(this, ${post.post_id})">⋮</button>
                                    <div class="post-menu-dropdown" data-post-id="${post.post_id}">
                                        <button class="delete" onclick="confirmDeletePost(${post.post_id}); closeAllMenus();">Delete</button>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                            <div class="post-content">${escapeHtml(post.content)}</div>
                            <div class="post-actions">
                                <button class="post-action-btn">👍 Like</button>
                                <button class="post-action-btn">💬 Comment</button>
                                <button class="post-action-btn">↗️ Share</button>
                            </div>
                        </div>
                    `).join('');
                })
                .catch(e => console.error('Error loading posts:', e));
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'just now';
            if (diffMins < 60) return diffMins + 'm ago';
            if (diffHours < 24) return diffHours + 'h ago';
            if (diffDays < 7) return diffDays + 'd ago';
            
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        // Load posts on page load
        loadPosts();
    </script>
    <script>
        // Mark announcements read when sidebar notification button is clicked
        (function(){
            const btn = document.querySelector('.notification-btn');
            if (!btn) return;
            btn.addEventListener('click', function(e){
                const badge = btn.querySelector('.notification-badge');
                if (badge) badge.remove();
                try {
                    fetch('api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'mark_announcements_read' })
                    }).catch(()=>{});
                } catch(ex) {}
            });
        })();
    </script>
</body>
</html>
