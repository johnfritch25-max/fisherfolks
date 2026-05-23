<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u669650505_fisherfolk_ims');
define('DB_USER', 'u669650505_root');
define('DB_PASS', '4=I|mB@P?kU');
define('ADMIN_LOGIN_CODE', '246810');

// SMTP / Mail settings (optional). If you want reliable OTP delivery, fill these and install PHPMailer via Composer.
// Example for Hostinger: SMTP_HOST=smtp.hostinger.com, SMTP_PORT=587, SMTP_USER=your@yourdomain.com, SMTP_PASS=app-password
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: '');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls'); // 'tls' or 'ssl'

// Load server-only local config if present (create config.local.php on the server with SMTP creds)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Create connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    // Ensure connection uses utf8mb4
    try {
        $pdo->exec("SET NAMES 'utf8mb4'");
    } catch (Exception $e) {
        // silently ignore
    }
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isValidAdminCode($code) {
    if (!isset($_SESSION['admin_otp'])) return false;
    return hash_equals(strtoupper((string)$_SESSION['admin_otp']), strtoupper(trim((string)$code)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function ensureArchiveColumns() {
    global $pdo;

    $columns = [
        'archived_at DATETIME NULL',
        'archived_by INT NULL',
        'archive_reason VARCHAR(255) NULL'
    ];

    foreach ($columns as $definition) {
        try {
            $columnName = strtok($definition, ' ');
            $check = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fisherfolk' AND COLUMN_NAME = ? LIMIT 1");
            $check->execute([$columnName]);
            if (!$check->fetchColumn()) {
                $pdo->exec("ALTER TABLE fisherfolk ADD COLUMN {$definition}");
            }
        } catch (Exception $e) {
            // Ignore schema migration failures and continue.
        }
    }
}

// Inject Theme Styles
function injectThemeStyles() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT setting_name, setting_value FROM admin_settings WHERE setting_name LIKE 'theme_%'");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $sidebar = $settings['theme_sidebar_color'] ?? '#0a56a2';
        $accent = $settings['theme_accent_color'] ?? '#0af0ff';
        $mode = $settings['theme_mode'] ?? 'dark';
        
        echo "<style>
            :root {
                --color-primary: $sidebar !important;
                --color-active: $accent !important;
                --color-secondary: $accent !important;
                --theme-sidebar: $sidebar !important;
                --theme-accent: $accent !important;
            }
        </style>";
        
        if ($mode === 'dark') {
            echo "<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('dark-mode'));</script>";
        } else {
            echo "<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.remove('dark-mode'));</script>";
        }
    } catch (Exception $e) {
        // Silently fail if settings table doesn't exist yet
    }
}
?>