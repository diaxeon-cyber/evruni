CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(120) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  user_category VARCHAR(40) NOT NULL DEFAULT 'ai_user',
  language VARCHAR(10) NOT NULL DEFAULT 'ru',
  currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
  avatar_path VARCHAR(255) NULL,
  credits DECIMAL(14,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(180) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ai_models (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  type ENUM('text','image','voice','video','research','document') NOT NULL,
  provider VARCHAR(80) NOT NULL,
  credit_cost DECIMAL(12,2) NOT NULL DEFAULT 1,
  enabled TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS generations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NULL,
  model_id BIGINT UNSIGNED NULL,
  type ENUM('text','image','voice','video','research','document') NOT NULL,
  prompt TEXT NOT NULL,
  status ENUM('queued','processing','completed','failed') NOT NULL DEFAULT 'queued',
  result_text LONGTEXT NULL,
  result_path VARCHAR(255) NULL,
  error_message TEXT NULL,
  credits_spent DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE SET NULL,
  FOREIGN KEY(model_id) REFERENCES ai_models(id) ON DELETE SET NULL,
  INDEX(user_id,status,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS credit_ledger (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  balance_after DECIMAL(14,2) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  reference_type VARCHAR(80) NULL,
  reference_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(40) NOT NULL,
  external_id VARCHAR(190) NULL UNIQUE,
  package_slug VARCHAR(80) NOT NULL,
  amount_cents INT UNSIGNED NOT NULL,
  currency VARCHAR(10) NOT NULL,
  credits DECIMAL(14,2) NOT NULL,
  status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  checkout_url TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at TIMESTAMP NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id,status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS uploads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  ip VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  meta JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rate_limits (
  bucket VARCHAR(190) PRIMARY KEY,
  hits INT NOT NULL DEFAULT 0,
  reset_at INT NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO ai_models(slug,name,type,provider,credit_cost) VALUES
('text-default','Evruni Text','text','openai-compatible',1),
('image-default','Evruni Image','image','openai-compatible',5),
('voice-default','Evruni Voice','voice','openai-compatible',8),
('video-default','Evruni Video','video','queue',20),
('research-default','Evruni Research','research','openai-compatible',3),
('document-default','Evruni Document','document','openai-compatible',2);
