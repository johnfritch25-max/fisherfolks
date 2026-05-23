<?php
require_once 'config.php';

// Determine if this is being called via web or CLI
$isWeb = php_sapi_name() !== 'cli';
$setupResults = [];
$setupErrors = [];

// Helper function to log results
function logSetup($message, $success = true) {
    global $setupResults, $setupErrors, $isWeb;
    if ($success) {
        $setupResults[] = $message;
    } else {
        $setupErrors[] = $message;
    }
}

// ============ DATABASE SETUP ============

try {
    // Create fisherfolk_posts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fisherfolk_posts (
            post_id INT AUTO_INCREMENT PRIMARY KEY,
            fisherfolk_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE,
            INDEX (created_at)
        )
    ");
    logSetup("✓ Created fisherfolk_posts table");
} catch (Exception $e) {
    logSetup("✗ Error creating fisherfolk_posts table: " . $e->getMessage(), false);
}

try {
    // Create post_likes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_likes (
            like_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            fisherfolk_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (post_id, fisherfolk_id),
            FOREIGN KEY (post_id) REFERENCES fisherfolk_posts(post_id) ON DELETE CASCADE,
            FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE
        )
    ");
    logSetup("✓ Created post_likes table");
} catch (Exception $e) {
    logSetup("✗ Error creating post_likes table: " . $e->getMessage(), false);
}

try {
    // Create post_comments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_comments (
            comment_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            fisherfolk_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES fisherfolk_posts(post_id) ON DELETE CASCADE,
            FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE,
            INDEX (created_at)
        )
    ");
    logSetup("✓ Created post_comments table");
} catch (Exception $e) {
    logSetup("✗ Error creating post_comments table: " . $e->getMessage(), false);
}

try {
    // Add columns to activity_logs
    $pdo->exec("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS table_name VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS record_id INT NULL");
    $pdo->exec("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS old_value LONGTEXT NULL");
    $pdo->exec("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS new_value LONGTEXT NULL");
    logSetup("✓ Updated activity_logs table with new columns");
} catch (Exception $e) {
    logSetup("✗ Error updating activity_logs table: " . $e->getMessage(), false);
}

try {
    // Add default theme settings
    $defaultSettings = [
        'theme_sidebar_color' => '#2c6aa2',
        'theme_body_color' => '#ffffff',
        'theme_text_color' => '#333333',
        'theme_accent_color' => '#2c6aa2',
        'theme_mode' => 'light',
        'system_name' => 'Fisherfolk Information Management System'
    ];

    foreach ($defaultSettings as $name => $value) {
        $pdo->exec("
            INSERT IGNORE INTO admin_settings (setting_name, setting_value)
            VALUES ('$name', '$value')
        ");
    }
    logSetup("✓ Added default theme settings");
} catch (Exception $e) {
    logSetup("✗ Error adding theme settings: " . $e->getMessage(), false);
}

// ============ FILE VERIFICATION ============

$requiredFiles = [
    'homepage.php' => 'Fisherfolk homepage',
    'theme_settings.php' => 'Admin theme customization',
    'activity_logs.php' => 'Admin activity logs viewer',
    'api.php' => 'API endpoints (modified)',
    'admin.php' => 'Admin panel (modified)',
    'login.php' => 'Login (modified)',
    'gui-override.css' => 'Styles (modified)',
    'config.php' => 'Configuration'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . $file)) {
        logSetup("✓ Found $file ($description)");
    } else {
        logSetup("✗ Missing $file ($description)", false);
    }
}

// ============ TABLE VERIFICATION ============

$requiredTables = [
    'fisherfolk_posts',
    'post_likes',
    'post_comments',
    'activity_logs',
    'admin_settings'
];

foreach ($requiredTables as $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            logSetup("✓ Database table '$table' exists");
        } else {
            logSetup("✗ Database table '$table' not found", false);
        }
    } catch (Exception $e) {
        logSetup("✗ Error checking table '$table': " . $e->getMessage(), false);
    }
}

// ============ FEATURE VERIFICATION ============

// Check if dark mode CSS exists in gui-override.css
$guiCss = file_get_contents(__DIR__ . '/gui-override.css');
if (strpos($guiCss, 'body.dark-mode') !== false) {
    logSetup("✓ Dark mode CSS found in gui-override.css");
} else {
    logSetup("✗ Dark mode CSS not found in gui-override.css", false);
}

// Check if homepage has post functionality
$homepage = file_get_contents(__DIR__ . '/homepage.php');
if (strpos($homepage, 'submitPost') !== false) {
    logSetup("✓ Homepage post functionality found");
} else {
    logSetup("✗ Homepage post functionality not found", false);
}

// Check if API has new endpoints
$api = file_get_contents(__DIR__ . '/api.php');
if (strpos($api, 'getPosts') !== false && strpos($api, 'updateThemeSettings') !== false) {
    logSetup("✓ New API endpoints found (getPosts, updateThemeSettings, etc.)");
} else {
    logSetup("✗ New API endpoints not found", false);
}

// Check if admin.php has new nav links
$admin = file_get_contents(__DIR__ . '/admin.php');
if (strpos($admin, 'theme_settings.php') !== false && strpos($admin, 'activity_logs.php') !== false) {
    logSetup("✓ Admin sidebar navigation links added");
} else {
    logSetup("✗ Admin sidebar navigation links not found", false);
}

// Check if login.php redirects to homepage
if (strpos($admin, 'homepage.php') !== false || strpos(file_get_contents(__DIR__ . '/login.php'), 'homepage.php') !== false) {
    logSetup("✓ Login redirect to homepage configured");
} else {
    logSetup("✗ Login redirect to homepage not found", false);
}

// ============ OUTPUT RESULTS ============

if ($isWeb) {
    // Web output
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Fisherfolk IMS - Setup Verification</title>
        <style>
            body {
                font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
                margin: 0;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border-radius: 12px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            h1 {
                color: #333;
                margin-top: 0;
                text-align: center;
                font-size: 32px;
            }
            .status-section {
                margin-bottom: 30px;
            }
            .status-section h2 {
                color: #667eea;
                font-size: 18px;
                border-bottom: 2px solid #667eea;
                padding-bottom: 10px;
                margin-bottom: 15px;
            }
            .status-item {
                padding: 12px;
                margin-bottom: 8px;
                border-radius: 6px;
                border-left: 4px solid #28a745;
                background-color: #f0f9ff;
            }
            .status-item.error {
                border-left-color: #dc3545;
                background-color: #fff5f5;
                color: #721c24;
            }
            .status-item.success {
                color: #155724;
            }
            .summary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                font-size: 18px;
                font-weight: 600;
                margin-top: 30px;
            }
            .button-group {
                display: flex;
                gap: 10px;
                margin-top: 20px;
                justify-content: center;
            }
            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s ease;
            }
            .btn-primary {
                background-color: #667eea;
                color: white;
            }
            .btn-primary:hover {
                background-color: #5568d3;
                transform: translateY(-2px);
            }
            .btn-secondary {
                background-color: #f0f0f0;
                color: #333;
                border: 1px solid #ddd;
            }
            .btn-secondary:hover {
                background-color: #e0e0e0;
            }
            .progress-bar {
                width: 100%;
                height: 30px;
                background-color: #f0f0f0;
                border-radius: 15px;
                overflow: hidden;
                margin-bottom: 20px;
            }
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #28a745, #20c997);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 12px;
                transition: width 0.3s ease;
            }
            .icon {
                font-size: 20px;
                margin-right: 8px;
                vertical-align: middle;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎣 Fisherfolk IMS - Setup Verification</h1>
            
            <?php
            $totalChecks = count($setupResults) + count($setupErrors);
            $successPercentage = $totalChecks > 0 ? round((count($setupResults) / $totalChecks) * 100) : 0;
            ?>
            
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $successPercentage; ?>%;">
                    <?php echo $successPercentage; ?>%
                </div>
            </div>

            <div class="status-section">
                <h2><span class="icon">✅</span>Successful Checks (<?php echo count($setupResults); ?>)</h2>
                <?php foreach ($setupResults as $result): ?>
                    <div class="status-item success"><?php echo htmlspecialchars($result); ?></div>
                <?php endforeach; ?>
            </div>

            <?php if (count($setupErrors) > 0): ?>
                <div class="status-section">
                    <h2><span class="icon">❌</span>Issues Found (<?php echo count($setupErrors); ?>)</h2>
                    <?php foreach ($setupErrors as $error): ?>
                        <div class="status-item error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="summary">
                <?php if (count($setupErrors) === 0): ?>
                    ✨ All systems ready! Your Fisherfolk IMS is fully configured.
                <?php else: ?>
                    ⚠️ <?php echo count($setupErrors); ?> issue(s) detected. Please review above.
                <?php endif; ?>
            </div>

            <div class="button-group">
                <a href="admin.php" class="btn btn-primary">Go to Admin Panel →</a>
                <a href="login.php" class="btn btn-secondary">← Back to Login</a>
            </div>

            <hr style="margin-top: 40px; border: none; border-top: 1px solid #e0e0e0;">
            
            <h3 style="margin-top: 30px;">📖 Quick Start</h3>
            <ol style="line-height: 1.8; color: #555;">
                <li><strong>For Fisherfolk Users:</strong> Log in with your credentials to access the new homepage with dark mode and community posts.</li>
                <li><strong>For Admins:</strong> Log in with admin credentials to access theme customization and activity logs.</li>
                <li><strong>Test Dark Mode:</strong> On the homepage, click the "🌙 Dark Mode" button in the sidebar.</li>
                <li><strong>Customize Colors:</strong> As admin, click "🎨 Theme Colors" in the admin sidebar.</li>
                <li><strong>View Activity Logs:</strong> As admin, click "📋 Activity Logs" in the admin sidebar.</li>
            </ol>
        </div>
    </body>
    </html>
    <?php
} else {
    // CLI output
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "    🎣 FISHERFOLK IMS - SETUP VERIFICATION\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "✅ SUCCESSFUL CHECKS (" . count($setupResults) . "):\n";
    echo "───────────────────────────────────────────────────────────────\n";
    foreach ($setupResults as $result) {
        echo "  $result\n";
    }

    if (count($setupErrors) > 0) {
        echo "\n❌ ISSUES FOUND (" . count($setupErrors) . "):\n";
        echo "───────────────────────────────────────────────────────────────\n";
        foreach ($setupErrors as $error) {
            echo "  $error\n";
        }
    }

    $totalChecks = count($setupResults) + count($setupErrors);
    $successPercentage = $totalChecks > 0 ? round((count($setupResults) / $totalChecks) * 100) : 0;

    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "Overall Status: " . $successPercentage . "% Complete\n";
    
    if (count($setupErrors) === 0) {
        echo "✨ All systems ready! Your Fisherfolk IMS is fully configured.\n";
    } else {
        echo "⚠️  Please review and fix the issues above.\n";
    }
    
    echo "═══════════════════════════════════════════════════════════════\n\n";
}
?>
