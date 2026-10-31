-- Evruni V17: referral program + bonus tasks
CREATE TABLE IF NOT EXISTS referral_settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  commission_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  cookie_days SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  updated_by BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO referral_settings(id,enabled,commission_percent,cookie_days) VALUES(1,1,10.00,30);

CREATE TABLE IF NOT EXISTS referral_codes (
  user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referral_clicks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NOT NULL,
  referrer_user_id BIGINT UNSIGNED NULL,
  visitor_hash CHAR(64) NULL,
  ip_hash CHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  landing_path VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(code,created_at), INDEX(referrer_user_id,created_at), INDEX(visitor_hash,created_at),
  FOREIGN KEY(referrer_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referrals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  referrer_user_id BIGINT UNSIGNED NOT NULL,
  referred_user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  code VARCHAR(32) NOT NULL,
  click_id BIGINT UNSIGNED NULL,
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  converted_at TIMESTAMP NULL,
  FOREIGN KEY(referrer_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(click_id) REFERENCES referral_clicks(id) ON DELETE SET NULL,
  INDEX(referrer_user_id,created_at), INDEX(code,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referral_commissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  referral_id BIGINT UNSIGNED NOT NULL,
  payment_id BIGINT UNSIGNED NOT NULL UNIQUE,
  referrer_user_id BIGINT UNSIGNED NOT NULL,
  referred_user_id BIGINT UNSIGNED NOT NULL,
  percent DECIMAL(5,2) NOT NULL,
  payment_usd DECIMAL(18,6) NOT NULL,
  commission_usd DECIMAL(18,6) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(referral_id) REFERENCES referrals(id) ON DELETE CASCADE,
  FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  FOREIGN KEY(referrer_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(referrer_user_id,created_at), INDEX(referred_user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bonus_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title_ru VARCHAR(180) NOT NULL,
  title_en VARCHAR(180) NULL,
  description_ru TEXT NOT NULL,
  description_en TEXT NULL,
  reward_usd DECIMAL(14,6) NOT NULL DEFAULT 0,
  task_type ENUM('link','manual') NOT NULL DEFAULT 'manual',
  target_url VARCHAR(500) NULL,
  proof_required TINYINT(1) NOT NULL DEFAULT 1,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(enabled,sort_order,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_clicks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(task_id,created_at), INDEX(user_id,created_at),
  FOREIGN KEY(task_id) REFERENCES bonus_tasks(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_submissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  proof_text TEXT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  review_note TEXT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_task_user(task_id,user_id),
  INDEX(status,submitted_at), INDEX(user_id,submitted_at),
  FOREIGN KEY(task_id) REFERENCES bonus_tasks(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
