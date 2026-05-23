-- Database migration for new features
-- Run this SQL to add new tables and columns
USE u669650505_fisherfolk_ims;

-- Create fisherfolk_posts table for community feed
CREATE TABLE IF NOT EXISTS fisherfolk_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    fisherfolk_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE,
    INDEX (created_at)
);

-- Create post likes table
CREATE TABLE IF NOT EXISTS post_likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    fisherfolk_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (post_id, fisherfolk_id),
    FOREIGN KEY (post_id) REFERENCES fisherfolk_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE
);

-- Create post comments table
CREATE TABLE IF NOT EXISTS post_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    fisherfolk_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES fisherfolk_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (fisherfolk_id) REFERENCES fisherfolk(fisherfolk_id) ON DELETE CASCADE,
    INDEX (created_at)
);

-- Add theme settings to admin_settings if they don't exist
INSERT IGNORE INTO admin_settings (setting_name, setting_value) VALUES
('theme_sidebar_color', '#2c6aa2'),
('theme_body_color', '#ffffff'),
('theme_text_color', '#333333'),
('theme_accent_color', '#2c6aa2'),
('theme_mode', 'light'),
('system_name', 'Fisherfolk Information Management System');

-- Add new columns to activity_logs for more detailed tracking
ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS table_name VARCHAR(100) NULL;
ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS record_id INT NULL;
ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS old_value LONGTEXT NULL;
ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS new_value LONGTEXT NULL;
