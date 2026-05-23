<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin.php' : 'index.php');
}

// Login attempt tracking
const MAX_ATTEMPTS = 3;
const LOCKOUT_DURATION = 60; // 1 minute in seconds

// Initialize per-user lockout storage in session
if (!isset($_SESSION['lockout_by_user'])) {
    $_SESSION['lockout_by_user'] = [];
}

// Helper: get lockout data for a specific username
function getLockoutData($username) {
    if (!isset($_SESSION['lockout_by_user'][$username])) {
        $_SESSION['lockout_by_user'][$username] = [
            'attempts'      => 0,
            'lockout_until' => null,
        ];
    }
    return $_SESSION['lockout_by_user'][$username];
}

function saveLockoutData($username, $data) {
    $_SESSION['lockout_by_user'][$username] = $data;
}

$message = '';
$loginStatus = ''; // 'success', 'failure', 'locked_out'
$lockoutTimeRemaining = 0;
$isLockedOut = false;
$activePortal = 'userPortalView';
$attemptsRemaining = MAX_ATTEMPTS; // default before we know the username

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portal   = sanitize($_POST['portal']    ?? 'user');
    $username = sanitize($_POST['username']  ?? '');
    $password = $_POST['password']           ?? '';
    $adminCode = sanitize($_POST['admin_code'] ?? '');

    $activePortal = $portal === 'admin' ? 'adminPortalView' : 'userPortalView';

    if (empty($username) || empty($password)) {
        $message     = 'Please fill in all fields.';
        $loginStatus = 'failure';
    } else {
        // Load per-username lockout data
        $lockoutData = getLockoutData($username);
        $currentTime = time();

        // Check if this specific account is locked out
        if ($lockoutData['lockout_until'] !== null && $currentTime < $lockoutData['lockout_until']) {
            $isLockedOut          = true;
            $lockoutTimeRemaining = $lockoutData['lockout_until'] - $currentTime;
            $loginStatus          = 'locked_out';
            $message              = "Too many failed attempts. Please try again in {$lockoutTimeRemaining} seconds.";
        } else {
            // If lockout expired, reset
            if ($lockoutData['lockout_until'] !== null && $currentTime >= $lockoutData['lockout_until']) {
                $lockoutData['attempts']      = 0;
                $lockoutData['lockout_until'] = null;
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($portal === 'admin') {
                    if ($user['role'] !== 'admin') {
                        $message     = 'This account can only log in from the User Portal.';
                        $loginStatus = 'failure';
                        $lockoutData['attempts']++;
                    } elseif ($adminCode === '') {
                        $message     = 'Please enter the One-Time Code.';
                        $loginStatus = 'failure';
                        $lockoutData['attempts']++;
                    } elseif (!isValidAdminCode($adminCode)) {
                        $message     = 'Invalid One-Time Code.';
                        $loginStatus = 'failure';
                        $lockoutData['attempts']++;
                    } else {
                        // Successful admin login — reset this account's lockout
                        $lockoutData['attempts']      = 0;
                        $lockoutData['lockout_until'] = null;
                        $_SESSION['user_id']          = $user['user_id'];
                        $_SESSION['username']         = $user['username'];
                        $_SESSION['role']             = $user['role'];
                        $_SESSION['full_name']        = 'Administrator';
                        $loginStatus = 'success';
                        $message     = 'Login successful!';
                        redirect('admin.php');
                    }
                } else {
                    if ($user['role'] !== 'fisherfolk') {
                        $message     = 'This account can only log in from the Admin Portal.';
                        $loginStatus = 'failure';
                        $lockoutData['attempts']++;
                    } else {
                        // Successful user login — reset this account's lockout
                        $lockoutData['attempts']      = 0;
                        $lockoutData['lockout_until'] = null;
                        $_SESSION['user_id']          = $user['user_id'];
                        $_SESSION['username']         = $user['username'];
                        $_SESSION['role']             = $user['role'];

                        $stmt = $pdo->prepare("SELECT first_name, last_name FROM fisherfolk WHERE user_id = ?");
                        $stmt->execute([$user['user_id']]);
                        $fisherfolk = $stmt->fetch(PDO::FETCH_ASSOC);
                        $_SESSION['full_name'] = $fisherfolk
                            ? $fisherfolk['first_name'] . ' ' . $fisherfolk['last_name']
                            : $user['username'];

                        $loginStatus = 'success';
                        $message     = 'Login successful!';
                        redirect('index.php');
                    }
                }
            } else {
                $message     = 'Invalid username or password.';
                $loginStatus = 'failure';
                $lockoutData['attempts']++;
            }

            // Check if max attempts reached for THIS account
            if ($loginStatus === 'failure' && $lockoutData['attempts'] >= MAX_ATTEMPTS) {
                $lockoutData['lockout_until'] = time() + LOCKOUT_DURATION;
                $isLockedOut          = true;
                $loginStatus          = 'locked_out';
                $lockoutTimeRemaining = LOCKOUT_DURATION;
                $message = "Maximum login attempts reached. Please try again in {$lockoutTimeRemaining} seconds.";
            }

            saveLockoutData($username, $lockoutData);
        }

        $attemptsRemaining = max(0, MAX_ATTEMPTS - ($lockoutData['attempts'] ?? 0));
    }
} else {
    // GET request — attemptsRemaining is unknown until user types a username
    $attemptsRemaining = MAX_ATTEMPTS;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fisherfolk Information Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/tailwind.css">
    <link rel="stylesheet" href="design-system.css">
    <link rel="stylesheet" href="styles.css?v=20260428b">
    <link rel="stylesheet" href="gui-override.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="printStyles.css" media="print">
    <style>
        .portal-switch {
            position: absolute !important;
            top: 20px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            z-index: 500 !important;
            display: flex !important;
            background: rgba(0, 0, 0, 0.4) !important;
            padding: 5px !important;
            border-radius: 30px !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            width: auto !important;
            pointer-events: auto !important;
        }

        .portal-tab {
            padding: 10px 24px !important;
            border-radius: 25px !important;
            font-size: 13px !important;
            text-transform: uppercase !important;
            font-weight: 700 !important;
            letter-spacing: 1px !important;
            border: none !important;
            cursor: pointer !important;
            background: transparent !important;
            color: rgba(255, 255, 255, 0.7) !important;
            transition: all 0.3s ease !important;
            pointer-events: auto !important;
        }

        .portal-tab.active {
            background: #13e8ff !important;
            color: #050a0f !important;
            box-shadow: 0 5px 15px rgba(19, 232, 255, 0.3) !important;
        }
        .portal-view {
            display: none;
        }
        .portal-view.active {
            display: block;
        }
        .portal-jump {
            text-align: center;
            margin-top: var(--spacing-lg);
            font-size: var(--font-size-sm);
            color: var(--color-text-light);
        }
        .portal-jump-btn {
            background: none;
            border: none;
            color: var(--color-primary);
            cursor: pointer;
            text-decoration: underline;
            font-size: var(--font-size-sm);
            transition: color var(--transition-fast);
        }
        .portal-jump-btn:hover {
            color: var(--color-primary-dark);
        }
        .form-input-wrap {
            position: relative;
            background: rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 12px;
            margin-bottom: var(--spacing-md);
            overflow: hidden;
            transition: all var(--transition-fast);
            backdrop-filter: blur(5px);
        }
        .password-input-wrap input {
            padding-right: var(--spacing-2xl);
        }
        .form-input-wrap:focus-within {
            border-color: var(--color-primary);
            background-color: var(--color-bg-white);
            box-shadow: 0 0 0 3px var(--color-primary-lighter), var(--shadow-md);
            transform: translateY(-1px);
        }
        input.form-input {
            width: 100%;
            min-height: 48px;
            border: 0;
            padding: 12px 16px;
            background: transparent;
            color: #ffffff;
            font-size: 15px;
            font-weight: 500;
            font-family: var(--font-sans);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.05);
        }
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        .form-input:hover {
            color: rgba(255, 255, 255, 0.95);
        }
        .form-input:-webkit-autofill,
        .form-input:-webkit-autofill:hover,
        .form-input:-webkit-autofill:focus,
        .form-input:-webkit-autofill:active {
            -webkit-text-fill-color: rgba(255, 255, 255, 0.95);
            -webkit-box-shadow: inset 0 0 0 1000px transparent;
            box-shadow: inset 0 0 0 1000px transparent;
            transition: background-color 9999s ease-in-out 0s;
            caret-color: #4a8bc2;
        }
        .form-input::selection {
            background: rgba(44, 106, 162, 0.4);
            color: rgba(255, 255, 255, 0.95);
        }
        .password-toggle-btn {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            border-radius: 8px;
            padding: 7px 14px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.08));
            color: rgba(255, 255, 255, 0.9);
            font: 600 12px var(--font-sans);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            scale: 0.85;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .password-input-wrap.has-value .password-toggle-btn {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            scale: 1;
        }
        .password-toggle-btn:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.15));
            color: rgba(255, 255, 255, 1);
            border-color: rgba(255, 255, 255, 0.6);
            transform: translateY(-50%) scale(1.08);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        .password-toggle-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 106, 162, 0.3), 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        .button-row {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
        }
        .button-row .btn {
            flex: 1;
            padding: 16px 32px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
            min-height: 56px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(44, 106, 162, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .button-row .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        .button-row .btn:hover::before {
            left: 100%;
        }
        .button-row .btn-primary {
            background: linear-gradient(135deg, #2c6aa2 0%, #1e4d73 100%);
            color: white;
            border: none;
        }
        .button-row .btn-primary:hover {
            background: linear-gradient(135deg, #1e4d73 0%, #16385a 100%);
            box-shadow: 0 12px 32px rgba(44, 106, 162, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.15) inset;
            transform: translateY(-3px);
        }
        .button-row .btn-primary:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 106, 162, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
        }
        .button-row .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .auth-title {
            font-size: 28px;
            font-weight: 800;
            color: white;
            margin: 0 0 32px 0;
            letter-spacing: -0.5px;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .otp-input-wrap {
            display: flex !important;
            gap: 10px !important;
            background: transparent !important;
            border: none !important;
            overflow: visible !important;
            box-shadow: none !important;
        }
        .otp-input-wrap .form-input {
            background: rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 12px !important;
            flex: 1;
        }
        .btn-otp-send {
            background: linear-gradient(135deg, #2c6aa2 0%, #1e4d73 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 0 24px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            cursor: pointer !important;
            white-space: nowrap !important;
            transition: all 0.3s ease !important;
            height: 48px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 4px 15px rgba(44, 106, 162, 0.3) !important;
        }
        .btn-otp-send:hover {
            background: #1e4d73 !important;
            transform: translateY(-1px) !important;
        }
        .btn-otp-send:active {
            transform: scale(0.95) !important;
        }
        .btn-otp-send.loading {
            opacity: 0.6 !important;
            pointer-events: none !important;
        }
        .form-kicker {
            font-size: 12px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0 0 16px 0;
        }

        /* Animated Panning Background using an absolute div */
        html, body {
            background: transparent !important;
            background-color: transparent !important;
            height: 100%;
        }

        body {
            margin: 0;
            height: 100vh;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            overflow: hidden !important;
            background-color: #050a0f !important;
            padding: 20px;
            box-sizing: border-box;
        }

        #videoBg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: url('background_photo.jpg') no-repeat center center;
            background-size: cover;
            z-index: -1; 
            animation: none !important;
            pointer-events: none;
        }

        @keyframes panBackground {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(10%, 5%) scale(1.1); }
        }

        /* Glassmorphism Form */
        body section.login-wireframe#loginShell {
            position: relative !important;
            z-index: 10 !important;
            background: rgba(10, 20, 30, 0.45) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5) !important;
            width: min(780px, 95vw) !important;
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            overflow: hidden !important;
            border-radius: 16px !important;
            isolation: isolate !important;
            flex-shrink: 0 !important;
        }

        /* Force stacking order for slanted design */
        body section.login-wireframe#loginShell::before,
        body section.login-wireframe#loginShell::after,
        .login-left-panel::before,
        .login-left-panel::after,
        .login-right-panel::before,
        .login-right-panel::after {
            display: block !important;
            z-index: 1 !important;
            pointer-events: none !important;
        }

        /* Elevate the content to sit ABOVE everything */
        .welcome-card {
            position: relative !important;
            z-index: 20 !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
        }

        .welcome-logo, 
        .welcome-card h3, 
        .welcome-card p,
        .welcome-card .welcome-metrics {
            position: relative !important;
            z-index: 11 !important;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
                overflow-y: auto !important;
            }

            body section.login-wireframe#loginShell {
                grid-template-columns: 1fr !important;
                width: 100% !important;
                max-height: none !important;
                height: auto !important;
                margin-top: 60px !important;
                margin-bottom: 20px !important;
            }

            .login-left-panel, 
            body section.login-wireframe#loginShell .login-right-panel {
                min-height: auto !important;
                padding: 1.5rem !important;
            }

            .login-right-panel {
                border-left: none !important;
                border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
                order: -1; /* Show welcome card on top */
            }

            .auth-title {
                font-size: 1.4rem !important;
            }

            .welcome-logo img {
                width: 60px !important;
                height: 60px !important;
            }

            .portal-switch {
                top: 10px !important;
                width: 90% !important;
                justify-content: center !important;
            }

            .portal-tab {
                padding: 8px 16px !important;
                font-size: 11px !important;
            }
        }

        .login-left-panel {
            background: transparent !important;
            padding: 20px !important;
            min-height: 380px !important;
            overflow-y: auto !important;
        }

        body section.login-wireframe#loginShell .login-right-panel {
            background: rgba(19, 113, 119, 0.15) !important;
            backdrop-filter: blur(4px) !important;
            padding: 20px !important;
            min-height: 380px !important;
            overflow-y: auto !important;
            border-left: 1px solid rgba(255, 255, 255, 0.1) !important;
            clip-path: none !important;
            -webkit-clip-path: none !important;
            width: 100% !important;
            position: relative !important;
            z-index: 10 !important;
        }
        
        .welcome-logo {
            margin-bottom: 15px !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            width: 100% !important;
            position: relative !important;
            left: 0 !important;
            top: 0 !important;
            clear: both !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            animation: none !important;
        }

        .welcome-logo img {
            width: 80px !important;
            height: 80px !important;
            object-fit: contain !important;
            border-radius: 12px !important;
            border: 2px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3) !important;
            display: block !important;
            margin: 0 auto !important;
            position: relative !important;
            left: 0 !important;
            right: 0 !important;
            top: 0 !important;
        }

        .auth-title {
            font-size: 18px !important;
            margin-bottom: 12px !important;
            line-height: 1.2 !important;
        }
        
        .welcome-card h3 {
            font-size: 22px !important;
            margin-top: 5px !important;
        }
        
        .welcome-card p {
            font-size: 13px !important;
            margin-bottom: 10px !important;
        }
        
        .button-row {
            margin-top: 12px !important;
        }
        
        .button-row .btn {
            min-height: 40px !important;
            padding: 8px 16px !important;
            font-size: 14px !important;
        }
        
        .login-mode-switch {
            position: absolute !important;
            top: 12px !important;
            left: 20px !important;
            z-index: 20 !important;
            margin-bottom: 0 !important;
            scale: 0.8 !important;
            transform-origin: left top !important;
        }
        
        /* Adjust for portal switch positioning */
        .login-left-panel form {
            margin-top: 40px !important;
        }

        /* Toast Notification Style for Login Status */
        #loginStatusModal.modal-overlay {
            background: transparent !important;
            pointer-events: none !important;
            display: flex !important;
            justify-content: flex-end !important;
            align-items: flex-start !important;
            padding: 20px !important;
            z-index: 10001 !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            position: fixed !important;
            inset: 0 !important;
        }

        #loginStatusModal.hidden {
            display: none !important;
        }

        #loginStatusCard.modal-card {
            pointer-events: auto !important;
            margin: 0 !important;
            width: 320px !important;
            background: linear-gradient(135deg, rgba(15, 37, 53, 0.95), rgba(10, 20, 30, 0.98)) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 12px !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5) !important;
            position: relative !important;
            padding: 20px !important;
            text-align: left !important;
            animation: toastSlideInRight 0.5s cubic-bezier(0.23, 1, 0.32, 1) both !important;
            transform: translateX(120%);
        }

        @keyframes toastSlideInRight {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        #loginStatusCard h3 {
            font-size: 16px !important;
            font-weight: 700 !important;
            margin: 0 0 8px 0 !important;
            color: #fff !important;
            text-align: left !important;
        }

        #loginStatusCard p {
            font-size: 13px !important;
            line-height: 1.4 !important;
            margin: 0 0 12px 0 !important;
            color: rgba(255, 255, 255, 0.9) !important;
            text-align: left !important;
        }

        #loginStatusCard .button-row {
            display: flex !important;
            gap: 8px !important;
            margin-top: 0 !important;
            justify-content: flex-start !important;
        }

        #loginStatusCard .btn {
            min-height: 32px !important;
            padding: 6px 14px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            box-shadow: none !important;
            transition: all 0.2s ease !important;
        }

        #loginStatusCard .btn.primary {
            background: #137177 !important; /* Project Teal */
            color: #fff !important;
            border: none !important;
        }

        #loginStatusCard .btn:not(.primary) {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
        }

        #loginStatusCard .btn:hover {
            transform: translateY(-1px) !important;
            opacity: 0.9 !important;
        }

        .form-label {
            font-size: 11px !important;
            margin-bottom: 6px !important;
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            display: block !important;
        }
    </style>
</head>
<body>
    <div id="videoBg"></div>
    <section class="login-wireframe" id="loginShell">
        <div class="login-left-panel">
            <!-- User Portal -->
            <div class="portal-view<?php echo $activePortal === 'userPortalView' ? ' active' : ''; ?>" id="userPortalView" role="tabpanel" aria-labelledby="userPortalTab">
                <form method="POST" aria-label="Fisherfolk Login">
                    <input type="hidden" name="portal" value="user" />
                    <p class="form-kicker">Secure Access Portal</p>
                    <h2 class="auth-title">Fisherfolk Information Management with ID System</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-danger" style="display: block; margin-top: var(--spacing-lg);"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <div class="form-input-wrap">
                            <input class="form-input" id="username" name="username" type="text" placeholder="Enter username" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="form-input-wrap password-input-wrap">
                            <input class="form-input" id="password" name="password" type="password" placeholder="Enter password" required />
                            <button class="password-toggle-btn" type="button" data-target="password" aria-label="Show password">Show</button>
                        </div>
                    </div>

                    <div class="button-row">
                        <button class="btn btn-primary" type="submit">Login</button>
                    </div>

                    <p class="portal-jump">Admin access? <button class="portal-jump-btn" type="button" onclick="switchPortal('adminPortalView')">Go to Admin Portal</button></p>
                </form>
            </div>

            <!-- Admin Portal -->
            <div class="portal-view<?php echo $activePortal === 'adminPortalView' ? ' active' : ''; ?>" id="adminPortalView" role="tabpanel" aria-labelledby="adminPortalTab">
                <form method="POST" aria-label="Admin Login">
                    <input type="hidden" name="portal" value="admin" />
                    <p class="form-kicker">Municipal Control Panel</p>
                    <h2 class="auth-title">Fisherfolk Information Management with ID System</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-danger" style="display: block; margin-top: var(--spacing-lg);"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="admin_username">Admin Username or Email</label>
                        <div class="form-input-wrap">
                            <input class="form-input" id="admin_username" name="username" type="text" placeholder="admin" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="admin_password">Password</label>
                        <div class="form-input-wrap password-input-wrap">
                            <input class="form-input" id="admin_password" name="password" type="password" placeholder="Enter password" required />
                            <button class="password-toggle-btn" type="button" data-target="admin_password" aria-label="Show password">Show</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="admin_code">One-Time Code</label>
                        <div class="form-input-wrap otp-input-wrap">
                            <input class="form-input" id="admin_code" name="admin_code" type="text" placeholder="8-character code" required />
                            <button class="btn-otp-send" type="button" id="sendOtpBtn">Generate</button>
                        </div>
                        <div id="otpDisplay" style="margin-top: 8px; font-size: 11px; color: #13e8ff; font-weight: 700; text-align: center; display: none; background: rgba(19, 232, 255, 0.1); padding: 5px; border-radius: 6px; border: 1px dashed rgba(19, 232, 255, 0.3);">
                            Generated Code: <span id="otpValue" style="letter-spacing: 1px;"></span>
                        </div>
                    </div>
                    <script src="app.js?v=<?php echo $assetVersion; ?>"></script>

                    <script>
                        (function(){
                            const sendBtn = document.getElementById('sendOtpBtn');
                            const adminUsernameInput = document.getElementById('admin_username');
                            const otpDisplay = document.getElementById('otpDisplay');
                            const otpValue = document.getElementById('otpValue');

                            if (!sendBtn || !adminUsernameInput) return;

                            sendBtn.addEventListener('click', function(){
                                const identifier = adminUsernameInput.value.trim();
                                if (!identifier) {
                                    alert('Please enter your admin username or email first.');
                                    return;
                                }

                                sendBtn.disabled = true;
                                sendBtn.textContent = 'Sending...';

                                fetch('api/generate_otp.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ identifier })
                                }).then(r => r.json()).then(data => {
                                    if (data.success) {
                                        // Show a friendly notice
                                        otpDisplay.style.display = 'block';
                                        otpValue.textContent = data.debug_code ? data.debug_code : 'Sent to your registered email. Check your inbox.';
                                        // If debug_code present (local/dev), show it in the UI for testing only
                                        if (!data.debug_code) {
                                            otpValue.textContent = 'Sent to your registered email. Check your inbox.';
                                        }
                                    } else {
                                        otpDisplay.style.display = 'block';
                                        otpValue.textContent = data.message || 'Failed to generate OTP.';
                                    }
                                }).catch(err => {
                                    otpDisplay.style.display = 'block';
                                    otpValue.textContent = 'Error generating OTP.';
                                    console.error(err);
                                }).finally(()=>{
                                    sendBtn.disabled = false;
                                    sendBtn.textContent = 'Generate';
                                });
                            });
                        })();
                    </script>

                    <div class="button-row">
                        <button class="btn btn-primary" type="submit">Login</button>
                    </div>

                    <p class="portal-jump">Use regular account? <button class="portal-jump-btn" type="button" onclick="switchPortal('userPortalView')">Back to User Portal</button></p>
                </form>
            </div>
        </div>

        <aside class="login-right-panel">
            <div class="welcome-card">
                <p class="welcome-kicker">Fisherfolk IMS</p>
                <div class="welcome-logo">
                    <img src="logo.jpg" alt="Fisherfolk IMS Logo" />
                </div>
                <h3 id="loginWelcomeTitle">WELCOME BACK!</h3>
                <p id="loginWelcomeSubtitle">Please login to access your account.</p>
                <div class="welcome-metrics">
                    <div><strong>Registry</strong><span>Track fisherfolk records</span></div>
                    <div><strong>ID Studio</strong><span>Generate and print IDs</span></div>
                    <div><strong>Reports</strong><span>View analytics and queue</span></div>
                </div>
            </div>
        </aside>
    </section>

    <script>
        // Portal switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const portalTabs = document.querySelectorAll('.portal-tab');
            const portalViews = document.querySelectorAll('.portal-view');
            const passwordToggleButtons = document.querySelectorAll('.password-toggle-btn');
            const passwordInputs = document.querySelectorAll('.password-input-wrap .form-input');

            function syncPasswordToggle(input) {
                const wrap = input.closest('.password-input-wrap');
                if (!wrap) return;

                wrap.classList.toggle('has-value', input.value.trim().length > 0);
            }

            passwordInputs.forEach(input => {
                syncPasswordToggle(input);
                input.addEventListener('input', function() {
                    syncPasswordToggle(this);
                });
                input.addEventListener('blur', function() {
                    syncPasswordToggle(this);
                });
            });

            // Tab switching
            portalTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetView = this.getAttribute('data-portal-target');
                    window.switchPortal(targetView);
                });
            });

            // Make switchPortal globally accessible for the onclick handlers
            window.switchPortal = function(targetViewId) {
                console.log('Switching to portal:', targetViewId);
                const views = document.querySelectorAll('.portal-view');
                const tabs = document.querySelectorAll('.portal-tab');
                
                // Hide all
                views.forEach(v => v.classList.remove('active'));
                tabs.forEach(t => t.classList.remove('active'));
                
                // Show target
                const targetView = document.getElementById(targetViewId);
                const targetTab = document.querySelector(`[data-portal-target="${targetViewId}"]`);
                
                if (targetView) targetView.classList.add('active');
                if (targetTab) targetTab.classList.add('active');
            };

            // Portal jump buttons are handled by onclick attributes in HTML

            passwordToggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    const isHidden = input.type === 'password';

                    input.type = isHidden ? 'text' : 'password';
                    this.textContent = isHidden ? 'Hide' : 'Show';
                    this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                });
            });
            // OTP Generation
            const sendOtpBtn = document.getElementById('sendOtpBtn');
            sendOtpBtn.addEventListener('click', function() {
                const btn = this;
                btn.classList.add('loading');
                btn.textContent = 'Sending...';

                fetch('api/generate_otp.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    btn.classList.remove('loading');
                    btn.textContent = 'Generate';
                    
                    if (data.success) {
                        // Display the code directly on the form
                        const otpDisplay = document.getElementById('otpDisplay');
                        const otpValue = document.getElementById('otpValue');
                        otpValue.textContent = data.debug_code;
                        otpDisplay.style.display = 'block';
                        
                        // Optional: Smooth fade in for the code
                        otpDisplay.style.animation = 'globalFadeIn 0.5s ease-out both';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    btn.classList.remove('loading');
                    btn.textContent = 'Send Code';
                    alert('Network error. Please try again.');
                });
            });
        });
    </script>

    <!-- Login Status Notifications (Toast Style) -->
    <div class="modal-overlay<?php echo ($loginStatus === 'failure' || $loginStatus === 'locked_out') ? '' : ' hidden'; ?>" id="loginStatusModal">
        <div class="modal-card" id="loginStatusCard">
            <?php if ($loginStatus === 'failure'): ?>
                <h3>Login attempt failed</h3>
                <p><?php echo htmlspecialchars($message); ?> (Attempts: <?php echo $attemptsRemaining; ?>/<?php echo MAX_ATTEMPTS; ?>)</p>
                <div class="button-row">
                    <button class="btn primary" type="button" onclick="closeLoginModal()">Try Again</button>
                    <button class="btn" type="button" onclick="closeLoginModal()">Dismiss</button>
                </div>
            <?php elseif ($loginStatus === 'locked_out'): ?>
                <h3>Account locked</h3>
                <p>Too many failed attempts. Please wait <span id="countdownTimer" style="color: #fff; font-weight: 700;"><?php echo sprintf("%02d:%02d", intval($lockoutTimeRemaining / 60), $lockoutTimeRemaining % 60); ?></span></p>
                <div class="button-row">
                    <button class="btn primary" type="button" disabled style="opacity: 0.5;">Locked</button>
                    <button class="btn" type="button" onclick="closeLoginModal()">Dismiss</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function closeLoginModal() {
            document.getElementById('loginStatusModal').classList.add('hidden');
        }

        // Countdown timer for lockout
        <?php if ($loginStatus === 'locked_out'): ?>
            let lockoutSeconds = <?php echo $lockoutTimeRemaining; ?>;
            
            function updateCountdown() {
                const minutes = Math.floor(lockoutSeconds / 60);
                const seconds = lockoutSeconds % 60;
                const timeStr = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                document.getElementById('countdownTimer').textContent = timeStr;
                
                if (lockoutSeconds > 0) {
                    lockoutSeconds--;
                    setTimeout(updateCountdown, 1000);
                } else {
                    // Reset complete, show message
                    const timerDiv = document.getElementById('countdownTimer').parentElement;
                    timerDiv.innerHTML = '<p style="margin: 0; color: #28a745; font-size: 16px; font-weight: 700;">Lockout Reset!</p>';
                    setTimeout(function() {
                        closeLoginModal();
                        // Use a clean GET request instead of reload to avoid re-submitting POST data
                        window.location.href = 'login.php';
                    }, 1500);
                }
            }
            
            updateCountdown();
        <?php endif; ?>
    </script>

</body>
</html>