-- Fisherfolk Information Management System Database Schema
-- Copy and paste this into Laragon's database query window

-- Create database
-- CREATE DATABASE IF NOT EXISTS fisherfolk_ims;
USE u669650505_fisherfolk_ims;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'fisherfolk',
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
);

-- Create barangay table
CREATE TABLE IF NOT EXISTS barangay (
    barangay_id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name VARCHAR(100) NOT NULL,
    municipality VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL
);

-- Create fisherfolk table
CREATE TABLE IF NOT EXISTS fisherfolk (
    fisherfolk_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    birthdate DATE,
    contact_number VARCHAR(20),
    address TEXT,
    barangay_id INT,
    fishing_type VARCHAR(100),
    livelihood VARCHAR(100),
    fishr_number VARCHAR(50),
    rsbsa_number VARCHAR(50),
    emergency_name VARCHAR(100),
    emergency_relation VARCHAR(50),
    emergency_address TEXT,
    emergency_contact VARCHAR(20),
    status VARCHAR(20) DEFAULT 'Active',
    gender VARCHAR(20) DEFAULT 'Not specified',
    id_design LONGTEXT NULL,
    archived_at DATETIME NULL,
    archived_by INT NULL,
    archive_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (barangay_id) REFERENCES barangay(barangay_id)
);

-- If you're updating an existing database, run these ALTER statements to add new columns
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS birthdate DATE;
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS fishr_number VARCHAR(50);
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS rsbsa_number VARCHAR(50);
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS emergency_name VARCHAR(100);
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS emergency_relation VARCHAR(50);
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS emergency_address TEXT;
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS emergency_contact VARCHAR(20);
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS gender VARCHAR(20) DEFAULT 'Not specified';
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS id_design LONGTEXT NULL;
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL;
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS archived_by INT NULL;
ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS archive_reason VARCHAR(255) NULL;

-- Create id_cards table
CREATE TABLE IF NOT EXISTS id_cards (
    id_card_id INT AUTO_INCREMENT PRIMARY KEY,
    fisherfolk_id INT NOT NULL,
    id_number VARCHAR(50) UNIQUE,
    photo_path VARCHAR(255),
    signature_path VARCHAR(255),
    issue_date DATE,
    expiry_date DATE,
    status VARCHAR(20) DEFAULT 'Active',
    pdf_path VARCHAR(255),
    FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE
);

-- Create requests table
CREATE TABLE IF NOT EXISTS requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    fisherfolk_id INT NOT NULL,
    request_type VARCHAR(50) NOT NULL,
    subject VARCHAR(200) NULL,
    description TEXT,
    admin_notes TEXT NULL,
    boat_name VARCHAR(100) NULL,
    boat_type VARCHAR(100) NULL,
    boat_color VARCHAR(50) NULL,
    boat_size VARCHAR(50) NULL,
    incident_date DATE NULL,
    estimated_damage DECIMAL(10,2) NULL,
    status VARCHAR(20) DEFAULT 'Pending',
    date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE
);

-- Create announcements table
CREATE TABLE IF NOT EXISTS announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_by INT NOT NULL,
    date_posted DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Create activity_logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(100) NULL,
    record_id INT NULL,
    old_value LONGTEXT NULL,
    new_value LONGTEXT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Create admin_settings table
CREATE TABLE IF NOT EXISTS admin_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) UNIQUE NOT NULL,
    setting_value VARCHAR(255),
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(user_id)
);

-- Create posts table (for community feed)
CREATE TABLE IF NOT EXISTS posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create comments table
CREATE TABLE IF NOT EXISTS comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create reactions table
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
);

-- Create shares table
CREATE TABLE IF NOT EXISTS shares (
    share_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_share (post_id, user_id)
);

-- Create mentions table
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
);

-- Insert sample barangays
INSERT IGNORE INTO barangay (barangay_name, municipality, province) VALUES
('San Isidro', 'Navotas', 'Metro Manila'),
('Poblacion', 'Navotas', 'Metro Manila'),
('Punta', 'Navotas', 'Metro Manila'),
('Bucana', 'Navotas', 'Metro Manila');

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (username, password, role, email) VALUES
('admin', '$2y$10$1ROUXqzGPInIv6dPRc0fxu3lkN3R62qpYq3elur/RlPATELZOBrqe', 'admin', 'admin@fisherfolk.gov.ph');

-- Insert sample fisherfolk users
INSERT IGNORE INTO users (username, password, role, email) VALUES
('juan', '$2y$10$GBKSk9ZpP5b9gw6TaBnpcuWh208Q64yOZEfglIFA36enirHwkWFVe', 'fisherfolk', 'juan@email.com'),
('maria', '$2y$10$4BpQIG0Z3nV098wDzHrRre15Qt5WOChkzxk1xTP/xWLCl1bRPyT0y', 'fisherfolk', 'maria@email.com'),
('pedro', '$2y$10$Vjscwlh/EaAkhsaKMpCm1ufkG65t8osGmzrhOutNhNrMbz6BERMLq', 'fisherfolk', 'pedro@email.com'),
('anna', '$2y$10$2C7.i7UN9ZFrg6N/71i/TeQjAaZOOKJHF/53HOWS73aSxXLyyVlA.', 'fisherfolk', 'anna@email.com'),
('kate_valerie', '$2y$10$419JsalQcvkm3uSN1F3xSeiil2pqyAVmhmrFWTNI49BS3JdWU0I2.', 'fisherfolk', 'kate@email.com');

-- Insert fisherfolk profiles
INSERT IGNORE INTO fisherfolk (user_id, first_name, last_name, middle_name, contact_number, address, barangay_id, fishing_type, livelihood) VALUES
(2, 'Juan', 'Dela Cruz', '', '09171234567', '123 Main St', 1, 'Commercial Fishing', 'Fisherman'),
(3, 'Maria', 'Santos', 'Garcia', '09179876543', '456 Oak Ave', 2, 'Fish Vending', 'Fish Vendor'),
(4, 'Pedro', 'Reyes', 'Mendoza', '09171112222', '789 Pine St', 3, 'Small Scale Fishing', 'Fisherman'),
(5, 'Anna', 'Lopez', 'Cruz', '09175556666', '321 Elm St', 4, 'Aquaculture', 'Fish Farmer'),
(6, 'Kate', 'Valerie', 'Smith', '09171234567', '654 Maple Ave', 2, 'Commercial Fishing', 'Fisherman');

-- Insert sample ID cards
INSERT IGNORE INTO id_cards (fisherfolk_id, id_number, photo_path, issue_date, expiry_date, status) VALUES
(1, 'FF-2024-001', 'photos/juan.jpg', '2024-01-01', '2027-01-01', 'Active'),
(2, 'FF-2024-002', 'photos/maria.jpg', '2024-01-01', '2027-01-01', 'Active'),
(3, 'FF-2024-003', 'photos/pedro.jpg', '2024-01-01', '2027-01-01', 'Expired'),
(4, 'FF-2024-004', 'photos/anna.jpg', '2024-01-01', '2027-01-01', 'Active'),
(5, 'FF-2024-005', 'photos/kate.jpg', '2024-01-01', '2027-01-01', 'Active');

-- If upgrading an existing database, add the signature_path column to id_cards
ALTER TABLE id_cards ADD COLUMN IF NOT EXISTS signature_path VARCHAR(255) NULL;

-- Insert sample announcement
INSERT IGNORE INTO announcements (title, message, created_by) VALUES
('Welcome to Fisherfolk IMS', 'Welcome to the new Fisherfolk Information Management System. Please update your profiles and check for important announcements.', 1);

-- Insert default admin settings
INSERT IGNORE INTO admin_settings (setting_name, setting_value, updated_by) VALUES
('id_card_validity_years', '3', 1),
('system_name', 'Fisherfolk Information Management System', 1),
('contact_email', 'admin@fisherfolk.gov.ph', 1);