<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $middle_name = sanitize($_POST['middle_name'] ?? '');
    $birthdate = sanitize($_POST['birthdate'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $barangay_id = (int)($_POST['barangay_id'] ?? 0);
    $fishing_type = sanitize($_POST['fishing_type'] ?? '');
    $livelihood = sanitize($_POST['livelihood'] ?? '');
    $fishr_number = sanitize($_POST['fishr_number'] ?? '');
    $rsbsa_number = sanitize($_POST['rsbsa_number'] ?? '');
    $emergency_name = sanitize($_POST['emergency_name'] ?? '');
    $emergency_relation = sanitize($_POST['emergency_relation'] ?? '');
    $emergency_address = sanitize($_POST['emergency_address'] ?? '');
    $emergency_contact = sanitize($_POST['emergency_contact'] ?? '');
    $status = sanitize($_POST['status'] ?? 'Active');
    $gender = sanitize($_POST['gender'] ?? 'Not specified');

    // Validation
    if (empty($username) || empty($password) || empty($first_name) || empty($last_name)) {
        $errors[] = 'Please fill in all required fields.';
    }

    if (empty($barangay_id)) {
        $errors[] = 'Please select a barangay.';
    }

    // Check if username already exists
    if (!empty($username)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already exists.';
        }
    }

    // Handle photo upload
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'photos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = 'Invalid photo format. Only JPG, PNG, and GIF are allowed.';
        } else {
            $new_filename = $username . '_' . time() . '.' . $file_extension;
            $photo_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                $errors[] = 'Failed to upload photo.';
            }
        }
    }

    // If no errors, create the records
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'fisherfolk', ?)");
            $stmt->execute([$username, $hashed_password, $username . '@fisherfolk.local']);
            $user_id = $pdo->lastInsertId();

            // Create fisherfolk profile
            $stmt = $pdo->prepare("INSERT INTO fisherfolk (user_id, first_name, last_name, middle_name, birthdate, gender, contact_number, address, barangay_id, fishing_type, livelihood, fishr_number, rsbsa_number, emergency_name, emergency_relation, emergency_address, emergency_contact, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $first_name, $last_name, $middle_name, $birthdate, $gender, $contact_number, $address, $barangay_id, $fishing_type, $livelihood, $fishr_number, $rsbsa_number, $emergency_name, $emergency_relation, $emergency_address, $emergency_contact, $status]);

            // Generate ID number and create ID card
            $fisherfolk_id = $pdo->lastInsertId();
            $id_number = 'FF-' . date('Y') . '-' . str_pad($fisherfolk_id, 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO id_cards (fisherfolk_id, id_number, photo_path, issue_date, expiry_date, status) VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 YEAR), 'Active')");
            $stmt->execute([$fisherfolk_id, $id_number, $photo_path]);

            $pdo->commit();
            $message = 'Fisherfolk registered successfully! ID Number: ' . $id_number;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Get barangays for dropdown
$stmt = $pdo->query("SELECT * FROM barangay ORDER BY barangay_name");
$barangays = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Fisherfolk - Fisherfolk IMS</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="gui-override.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-text-dark);
            font-size: 13px;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .char-counter {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            background: #6b7280;
            border-radius: 20px;
            padding: 2px 10px;
            margin-top: 5px;
            letter-spacing: 0.3px;
        }
        .char-counter.near-limit {
            background: #e67e22;
            color: #fff;
        }
        .char-counter.at-limit {
            background: #e74c3c;
            color: #fff;
            animation: pulse-limit 0.4s ease;
        }
        @keyframes pulse-limit {
            0%   { transform: scale(1); }
            50%  { transform: scale(1.12); }
            100% { transform: scale(1); }
        }
        .field-error {
            display: none;
            font-size: 12px;
            color: #e74c3c;
            margin-top: 4px;
            font-weight: 600;
        }
        .field-error.visible { display: block; }
    </style>
</head>
<body>
    <section class="app-shell">
        <aside class="sidebar">
            <div class="logo-block">
                <img src="logo.jpg" alt="Fisherfolk IMS" class="sidebar-logo" />
                <div id="logoText">Fisherfolk IMS<span class="system-tag">Admin</span></div>
            </div>
            <nav class="nav-list" id="navList">
                <a class="nav-item" href="admin.php?view=dashboard"><span>Dashboard</span></a>
                <a class="nav-item active" href="admin.php?view=register-fisherfolk"><span>Register Fisherfolk</span></a>
                <a class="nav-item" href="admin.php?view=id-printing-queue"><span>ID Printing Queue</span></a>
                <a class="nav-item" href="admin.php?view=subsidy-reports-admin"><span>Subsidy Reports</span></a>
                <a class="nav-item" href="admin.php?view=announcements-admin"><span>Announcements</span></a>
                <a class="nav-item" href="admin.php?view=reports-analytics"><span>Reports & Analytics</span></a>
                <a class="nav-item" href="admin.php?view=settings-users"><span>Settings & Users</span></a>
                <hr style="border: none; border-top: 1px solid rgba(0,0,0,0.1); margin: 15px 0;">
                <a class="nav-item" href="theme_settings.php"><span>🎨 Theme Colors</span></a>
                <a class="nav-item" href="activity_logs.php"><span>📋 Activity Logs</span></a>
            </nav>
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <div class="breadcrumbs" id="breadcrumbs">
                    <span class="breadcrumb-item"><a href="admin.php">Admin</a></span>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-item active">Register Fisherfolk</span>
                </div>
                <div class="profile-wrap" id="profileWrap">
                    <div class="profile-info">
                        <span class="profile-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?></span>
                        <span class="profile-role">Admin</span>
                    </div>
                </div>
            </header>

            <section class="content-grid">
                <article class="panel">
                    <div class="panel-head">
                        Registration Form
                    </div>
                    <div class="panel-body">

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <p><?php echo $message; ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" class="input-field" required maxlength="50" oninput="updateCounter('username')" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                                <span class="char-counter" id="username-counter">0 / 50</span>
                            </div>

                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" class="input-field" required>
                            </div>

                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="input-field" required maxlength="50" oninput="updateCounter('first_name')" value="<?php echo htmlspecialchars($first_name ?? ''); ?>">
                                <span class="char-counter" id="first_name-counter">0 / 50</span>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="input-field" required maxlength="50" oninput="updateCounter('last_name')" value="<?php echo htmlspecialchars($last_name ?? ''); ?>">
                                <span class="char-counter" id="last_name-counter">0 / 50</span>
                            </div>

                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" class="input-field">
                                    <option value="Male" <?php echo (isset($gender) && $gender=='Male')? 'selected':''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($gender) && $gender=='Female')? 'selected':''; ?>>Female</option>
                                    <option value="Other" <?php echo (isset($gender) && $gender=='Other')? 'selected':''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" class="input-field" maxlength="50" oninput="updateCounter('middle_name')" value="<?php echo htmlspecialchars($middle_name ?? ''); ?>">
                                <span class="char-counter" id="middle_name-counter">0 / 50</span>
                            </div>

                            <div class="form-group">
                                <label for="birthdate">Birthday</label>
                                <input type="date" id="birthdate" name="birthdate" class="input-field" value="<?php echo htmlspecialchars($birthdate ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" id="contact_number" name="contact_number" class="input-field" inputmode="numeric" oninput="validatePhone('contact_number')" value="<?php echo htmlspecialchars($contact_number ?? ''); ?>">
                                <span class="field-error" id="contact_number-error">⚠ Invalid: Contact number must contain numbers only.</span>
                            </div>

                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" class="input-field" rows="3"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="barangay_id">Barangay *</label>
                                <select id="barangay_id" name="barangay_id" class="input-field" required>
                                    <option value="">Select Barangay</option>
                                    <?php foreach ($barangays as $barangay): ?>
                                        <option value="<?php echo $barangay['barangay_id']; ?>" <?php echo (isset($barangay_id) && $barangay_id == $barangay['barangay_id']) ? 'selected' : ''; ?>>
                                            <?php echo $barangay['barangay_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="fishing_type">Fishing Type</label>
                                <select id="fishing_type" name="fishing_type" class="input-field">
                                    <option value="">Select Type</option>
                                    <option value="Commercial Fishing" <?php echo (isset($fishing_type) && $fishing_type=='Commercial Fishing')? 'selected':''; ?>>Commercial Fishing</option>
                                    <option value="Small Scale Fishing" <?php echo (isset($fishing_type) && $fishing_type=='Small Scale Fishing')? 'selected':''; ?>>Small Scale Fishing</option>
                                    <option value="Fish Vending" <?php echo (isset($fishing_type) && $fishing_type=='Fish Vending')? 'selected':''; ?>>Fish Vending</option>
                                    <option value="Aquaculture" <?php echo (isset($fishing_type) && $fishing_type=='Aquaculture')? 'selected':''; ?>>Aquaculture</option>
                                    <option value="Other" <?php echo (isset($fishing_type) && $fishing_type=='Other')? 'selected':''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="livelihood">Livelihood</label>
                                <select id="livelihood" name="livelihood" class="input-field">
                                    <option value="">Select Livelihood</option>
                                    <option value="Fisherman" <?php echo (isset($livelihood) && $livelihood=='Fisherman')? 'selected':''; ?>>Fisherman</option>
                                    <option value="Fish Vendor" <?php echo (isset($livelihood) && $livelihood=='Fish Vendor')? 'selected':''; ?>>Fish Vendor</option>
                                    <option value="Fish Farmer" <?php echo (isset($livelihood) && $livelihood=='Fish Farmer')? 'selected':''; ?>>Fish Farmer</option>
                                    <option value="Boat Operator" <?php echo (isset($livelihood) && $livelihood=='Boat Operator')? 'selected':''; ?>>Boat Operator</option>
                                    <option value="Other" <?php echo (isset($livelihood) && $livelihood=='Other')? 'selected':''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="fishr_number">FishR Number</label>
                                <input type="text" id="fishr_number" name="fishr_number" class="input-field" value="<?php echo htmlspecialchars($fishr_number ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="rsbsa_number">RSBSA Number</label>
                                <input type="text" id="rsbsa_number" name="rsbsa_number" class="input-field" value="<?php echo htmlspecialchars($rsbsa_number ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="input-field">
                                    <option value="Active" <?php echo (isset($status) && $status=='Active')? 'selected':''; ?>>Active</option>
                                    <option value="Pending" <?php echo (isset($status) && $status=='Pending')? 'selected':''; ?>>Pending</option>
                                    <option value="Inactive" <?php echo (isset($status) && $status=='Inactive')? 'selected':''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="photo">Photo</label>
                                <input type="file" id="photo" name="photo" accept="image/*">
                                <small>Upload a clear photo of the fisherfolk (JPG, PNG, GIF)</small>
                            </div>

                            <div class="form-group full-width" style="margin-top:12px;">
                                <h3>Emergency Contact</h3>
                            </div>

                            <div class="form-group full-width">
                                <label for="emergency_name">Name</label>
                                <input type="text" id="emergency_name" name="emergency_name" class="input-field" maxlength="50" oninput="updateCounter('emergency_name')" value="<?php echo htmlspecialchars($emergency_name ?? ''); ?>">
                                <span class="char-counter" id="emergency_name-counter">0 / 50</span>
                            </div>

                            <div class="form-group">
                                <label for="emergency_relation">Relation</label>
                                <input type="text" id="emergency_relation" name="emergency_relation" class="input-field" maxlength="50" oninput="updateCounter('emergency_relation')" value="<?php echo htmlspecialchars($emergency_relation ?? ''); ?>">
                                <span class="char-counter" id="emergency_relation-counter">0 / 50</span>
                            </div>

                            <div class="form-group">
                                <label for="emergency_contact">Contact No.</label>
                                <input type="text" id="emergency_contact" name="emergency_contact" class="input-field" inputmode="numeric" oninput="validatePhone('emergency_contact')" value="<?php echo htmlspecialchars($emergency_contact ?? ''); ?>">
                                <span class="field-error" id="emergency_contact-error">⚠ Invalid: Contact number must contain numbers only.</span>
                            </div>

                            <div class="form-group full-width">
                                <label for="emergency_address">Complete Address</label>
                                <textarea id="emergency_address" name="emergency_address" class="input-field" rows="2"><?php echo htmlspecialchars($emergency_address ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <button type="submit" class="btn primary">Register Fisherfolk</button>
                            <a href="admin.php" class="btn">Cancel</a>
                        </div>
                    </form>
                    </div>
                </article>
            </section>
        </main>
    </section>

<script>
    // Character counter
    function updateCounter(fieldId) {
        const input   = document.getElementById(fieldId);
        const counter = document.getElementById(fieldId + '-counter');
        if (!input || !counter) return;
        const len = input.value.length;
        const max = parseInt(input.getAttribute('maxlength')) || 50;
        counter.textContent = len + ' / ' + max;
        counter.classList.remove('near-limit', 'at-limit');
        if (len >= max)         counter.classList.add('at-limit');
        else if (len >= max * 0.8) counter.classList.add('near-limit');
    }

    // Numbers-only validation for phone fields
    function validatePhone(fieldId) {
        const input = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + '-error');
        if (!input || !error) return;
        const val = input.value;
        // Remove any non-numeric except leading +
        const cleaned = val.replace(/[^0-9+\-\s()]/g, '');
        if (val !== cleaned) {
            input.value = cleaned;
        }
        // Show error if non-digits (other than allowed chars) remain
        const hasInvalid = /[^0-9+\-\s()]/.test(val);
        const hasLetters = /[a-zA-Z]/.test(val);
        if (hasLetters) {
            error.classList.add('visible');
        } else {
            error.classList.remove('visible');
        }
    }

    // Init counters on page load for pre-filled values
    window.addEventListener('load', function () {
        ['username','first_name','last_name','middle_name','emergency_name','emergency_relation']
            .forEach(id => updateCounter(id));
    });
</script>
</body>
</html>