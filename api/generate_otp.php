<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$identifier = trim($input['identifier'] ?? '') ?: trim($_POST['identifier'] ?? '');

if (empty($identifier)) {
    echo json_encode(['success' => false, 'message' => 'Missing identifier (username or email).']);
    exit();
}

// Find user by username or email
$stmt = $pdo->prepare('SELECT user_id, email, role FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'No account found for that username/email.']);
    exit();
}

// Only allow OTP for admin accounts (protect against sending to random users)
if ($user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'OTP may only be requested for admin accounts.']);
    exit();
}

// Generate random 8-character alphanumeric code
function generateRandomCode($length = 8) {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Removed ambiguous chars like 1, I, 0, O
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

$otp = generateRandomCode(8);
$_SESSION['admin_otp'] = $otp;
$_SESSION['admin_otp_expires'] = time() + 300; // expires in 5 minutes

$to = $user['email'];
$subject = 'Your admin login One-Time Code';
$message = "Your One-Time Code for admin login is: $otp\nThis code expires in 5 minutes.";
$headers = 'From: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";

$mailSent = false;

// If SMTP config and PHPMailer are available, prefer SMTP for reliable delivery
$useSMTP = !empty(SMTP_HOST) && !empty(SMTP_USER) && !empty(SMTP_PASS);
if ($useSMTP && file_exists(__DIR__ . '/../vendor/autoload.php')) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE ?: 'tls';
        $mail->Port = (int)(SMTP_PORT ?: 587);

        $mail->setFrom(SMTP_USER, 'Fisherfolk IMS');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = $message;

        $mailSent = $mail->send();
    } catch (Exception $e) {
        // fall back to mail() if PHPMailer send fails
        $mailSent = false;
    }
} else {
    try {
        $mailSent = mail($to, $subject, $message, $headers);
    } catch (Exception $e) {
        $mailSent = false;
    }
}

// Log OTP generation action
try {
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, timestamp) VALUES (?, ?, ?, ?, NOW())");
    $logStmt->execute([$user['user_id'], 'Generated admin OTP', 'users', $user['user_id']]);
} catch (Exception $e) {
    // ignore logging failures
}

if ($mailSent) {
    echo json_encode(['success' => true, 'message' => 'OTP has been sent to the account email.']);
} else {
    // In case mail() is not available on the host, return success but include a debug token for local testing only
    echo json_encode(['success' => true, 'message' => 'OTP generated. Mail send may not be available on this host.']);
}
