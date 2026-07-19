-- AssamJobs360 update for existing databases
-- Run this ONCE in phpMyAdmin after taking a database backup.
-- Do not run it on a fresh install; database/assamjobs360.sql already includes these columns.

USE assamjobs360;

ALTER TABLE jobs ADD COLUMN overview TEXT NULL AFTER official_website_url;
ALTER TABLE jobs ADD COLUMN selection_process TEXT NULL AFTER overview;
ALTER TABLE jobs ADD COLUMN application_fee TEXT NULL AFTER selection_process;
ALTER TABLE jobs ADD COLUMN vacancy_details TEXT NULL AFTER application_fee;
ALTER TABLE jobs ADD COLUMN how_to_apply TEXT NULL AFTER vacancy_details;
ALTER TABLE jobs ADD COLUMN faqs TEXT NULL AFTER how_to_apply;

ALTER TABLE admins ADD COLUMN display_name VARCHAR(120) NULL AFTER password_hash;
ALTER TABLE admins ADD COLUMN email VARCHAR(120) NULL AFTER display_name;
ALTER TABLE admins ADD COLUMN profile_photo VARCHAR(255) NULL AFTER email;

ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER password_hash;

CREATE TABLE IF NOT EXISTS admin_login_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  device_id VARCHAR(100) NOT NULL,
  user_agent VARCHAR(500) NULL,
  location VARCHAR(160) NULL,
  login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_login_logs_admin_id (admin_id),
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users_email_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  otp_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_email_otps_user_id (user_id),
  INDEX idx_users_email_otps_expires_at (expires_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
