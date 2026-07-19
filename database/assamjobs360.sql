  -- AssamJobs360 schema (scaffold)
  -- Import into MySQL/MariaDB
  -- Also supports creating the database if it doesn't exist.

  CREATE DATABASE IF NOT EXISTS assamjobs360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  USE assamjobs360;



  CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(120) NULL,
    email VARCHAR(120) NULL,
    profile_photo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

  CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255) NOT NULL,
    department VARCHAR(120) NOT NULL,
    category VARCHAR(120) NOT NULL,
    district VARCHAR(120) NOT NULL,
    qualification VARCHAR(160) NOT NULL,
    age_limit VARCHAR(160) NULL,
    salary VARCHAR(160) NULL,
    last_date DATE NULL,
    apply_url VARCHAR(500) NULL,
    notification_pdf_url VARCHAR(500) NULL,
    official_website_url VARCHAR(500) NULL,
    overview TEXT NULL,
    selection_process TEXT NULL,
    application_fee TEXT NULL,
    vacancy_details TEXT NULL,
    how_to_apply TEXT NULL,
    faqs TEXT NULL,
    job_slug VARCHAR(220) NOT NULL UNIQUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE TABLE IF NOT EXISTS mock_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_category VARCHAR(160) NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mock_test_id INT NOT NULL,
    question_text TEXT NOT NULL,
    difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
    question_type ENUM('mcq','single_correct','multiple_correct','assertion_reason','image','paragraph') DEFAULT 'mcq',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mock_test_id) REFERENCES mock_tests(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE TABLE IF NOT EXISTS options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    option_image_url VARCHAR(500) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    correct_option_ids TEXT NOT NULL, -- JSON array in string form
    explanation TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  mock_test_id INT NOT NULL,
  score DECIMAL(6,2) NOT NULL,
  total_questions INT NOT NULL,
  percentile DECIMAL(5,2) DEFAULT 0,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (mock_test_id) REFERENCES mock_tests(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE TABLE IF NOT EXISTS leaderboard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mock_test_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT NOT NULL,
    percentile DECIMAL(5,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mock_test_id) REFERENCES mock_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(100) NOT NULL UNIQUE,
    site_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  -- Seed default admin (username: admin, password: admin123)
  -- Password hash is generated in PHP normally. For scaffold, we provide a placeholder hash.
  -- Replace hash if your PHP bcrypt differs.
  INSERT INTO admins (username, password_hash)
  SELECT 'admin', '$2y$10$oc35AO.WWHycttPe6jvOnORUVYVaUHeRpGTI6vwfPWhDytGUlrHhW'
  WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username='admin');

