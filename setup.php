<?php
require_once 'config.php';

try {
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'fisherfolk',
        email VARCHAR(100),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME NULL
    )");

    // Create barangay table
    $pdo->exec("CREATE TABLE IF NOT EXISTS barangay (
        barangay_id INT AUTO_INCREMENT PRIMARY KEY,
        barangay_name VARCHAR(100) NOT NULL,
        municipality VARCHAR(100) NOT NULL,
        province VARCHAR(100) NOT NULL
    )");

    // Create fisherfolk table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fisherfolk (
        fisherfolk_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50),
        contact_number VARCHAR(20),
        address TEXT,
        barangay_id INT,
        fishing_type VARCHAR(100),
        livelihood VARCHAR(100),
        status VARCHAR(20) DEFAULT 'Active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (barangay_id) REFERENCES barangay(barangay_id)
    )");

    // Create id_cards table
    $pdo->exec("CREATE TABLE IF NOT EXISTS id_cards (
        id_card_id INT AUTO_INCREMENT PRIMARY KEY,
        fisherfolk_id INT NOT NULL,
        id_number VARCHAR(50) UNIQUE,
        photo_path VARCHAR(255),
        issue_date DATE,
        expiry_date DATE,
        status VARCHAR(20) DEFAULT 'Active',
        pdf_path VARCHAR(255),
        FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE
    )");

    // Create requests table
    $pdo->exec("CREATE TABLE IF NOT EXISTS requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        fisherfolk_id INT NOT NULL,
        request_type VARCHAR(50) NOT NULL,
        description TEXT,
        status VARCHAR(20) DEFAULT 'Pending',
        date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE
    )");

    // Create announcements table
    $pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
        announcement_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        created_by INT NOT NULL,
        date_posted DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    )");

    // Create activity_logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )");

    // Create admin_settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_settings (
        setting_id INT AUTO_INCREMENT PRIMARY KEY,
        setting_name VARCHAR(100) UNIQUE NOT NULL,
        setting_value VARCHAR(255),
        updated_by INT,
        FOREIGN KEY (updated_by) REFERENCES users(user_id)
    )");

    // Insert sample barangays
    $barangays = [
        ['San Isidro', 'Navotas', 'Metro Manila'],
        ['Poblacion', 'Navotas', 'Metro Manila'],
        ['Punta', 'Navotas', 'Metro Manila'],
        ['Bucana', 'Navotas', 'Metro Manila']
    ];

    foreach ($barangays as $barangay) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO barangay (barangay_name, municipality, province) VALUES (?, ?, ?)");
        $stmt->execute($barangay);
    }

    // Insert default admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role, email) VALUES (?, ?, 'admin', ?)");
    $stmt->execute(['admin', $adminPassword, 'admin@fisherfolk.gov.ph']);

    // Insert sample fisherfolk users
    $sampleUsers = [
        ['juan', 'user123', 'juan@email.com', 'Juan', 'Dela Cruz', '', '09171234567', '123 Main St', 1, 'Commercial Fishing', 'Fisherman'],
        ['maria', 'user234', 'maria@email.com', 'Maria', 'Santos', 'Garcia', '09179876543', '456 Oak Ave', 2, 'Fish Vending', 'Fish Vendor'],
        ['pedro', 'user345', 'pedro@email.com', 'Pedro', 'Reyes', 'Mendoza', '09171112222', '789 Pine St', 3, 'Small Scale Fishing', 'Fisherman'],
        ['anna', 'user456', 'anna@email.com', 'Anna', 'Lopez', 'Cruz', '09175556666', '321 Elm St', 4, 'Aquaculture', 'Fish Farmer'],
        ['kate_valerie', 'password123', 'kate@email.com', 'Kate', 'Valerie', 'Smith', '09171234567', '654 Maple Ave', 2, 'Commercial Fishing', 'Fisherman']
    ];

    foreach ($sampleUsers as $user) {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$user[0]]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingUser) {
            // Insert user account
            $password = password_hash($user[1], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'fisherfolk', ?)");
            $stmt->execute([$user[0], $password, $user[2]]);
            $userId = $pdo->lastInsertId();
        } else {
            $userId = $existingUser['user_id'];
        }

        // Check if fisherfolk profile already exists
        $stmt = $pdo->prepare("SELECT fisherfolk_id FROM fisherfolk WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existingFisherfolk = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingFisherfolk) {
            // Insert fisherfolk profile
            $stmt = $pdo->prepare("INSERT INTO fisherfolk (user_id, first_name, last_name, middle_name, contact_number, address, barangay_id, fishing_type, livelihood) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $user[3], $user[4], $user[5], $user[6], $user[7], $user[8], $user[9], $user[10]]);
            $fisherfolkId = $pdo->lastInsertId();
        } else {
            $fisherfolkId = $existingFisherfolk['fisherfolk_id'];
        }

        // Insert ID card if it doesn't exist
        $stmt = $pdo->prepare("SELECT id_card_id FROM id_cards WHERE fisherfolk_id = ?");
        $stmt->execute([$fisherfolkId]);
        $existingCard = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingCard) {
            $idNumber = 'FF-2024-' . str_pad($fisherfolkId, 3, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO id_cards (fisherfolk_id, id_number, photo_path, issue_date, expiry_date, status) VALUES (?, ?, ?, '2024-01-01', '2027-01-01', 'Active')");
            $stmt->execute([$fisherfolkId, $idNumber, 'photos/default.jpg']);
        }
    }

    // Insert sample announcement
    $stmt = $pdo->prepare("INSERT IGNORE INTO announcements (title, message, created_by) VALUES (?, ?, 1)");
    $stmt->execute(['Welcome to Fisherfolk IMS', 'Welcome to the new Fisherfolk Information Management System. Please update your profiles and check for important announcements.', 1]);

    // Insert default admin settings
    $settings = [
        ['id_card_validity_years', '3'],
        ['system_name', 'Fisherfolk Information Management System'],
        ['contact_email', 'admin@fisherfolk.gov.ph']
    ];

    foreach ($settings as $setting) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO admin_settings (setting_name, setting_value, updated_by) VALUES (?, ?, 1)");
        $stmt->execute($setting);
    }

    echo "Database and tables created successfully with normalized schema!";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>