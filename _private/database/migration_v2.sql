-- Evruni V2 migration. Run once in phpMyAdmin on the existing Evruni database.
CREATE TABLE IF NOT EXISTS chat_threads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL DEFAULT 'New chat',
  model_slug VARCHAR(120) NULL,
  system_prompt TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id,updated_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role ENUM('system','user','assistant','tool') NOT NULL,
  content LONGTEXT NOT NULL,
  model_slug VARCHAR(120) NULL,
  input_tokens INT UNSIGNED NULL,
  output_tokens INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(thread_id,created_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS api_keys (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  key_prefix VARCHAR(24) NOT NULL,
  key_hash CHAR(64) NOT NULL UNIQUE,
  budget DECIMAL(14,2) NULL,
  spent DECIMAL(14,2) NOT NULL DEFAULT 0,
  expires_at DATETIME NULL,
  last_used_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id,is_active)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS presets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  model_slug VARCHAR(120) NOT NULL,
  system_prompt TEXT NULL,
  temperature DECIMAL(4,2) NOT NULL DEFAULT 0.7,
  max_tokens INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  extracted_text LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id,created_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS api_usage (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  api_key_id BIGINT UNSIGNED NULL,
  model_slug VARCHAR(120) NOT NULL,
  endpoint VARCHAR(120) NOT NULL,
  input_tokens INT UNSIGNED NOT NULL DEFAULT 0,
  output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
  cost DECIMAL(14,6) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL,
  INDEX(user_id,created_at), INDEX(model_slug,created_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS masks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  pattern_text VARCHAR(255) NOT NULL,
  replacement VARCHAR(120) NOT NULL DEFAULT '[REDACTED]',
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS balance_topups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  amount_cents INT UNSIGNED NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'RUB',
  credits DECIMAL(14,2) NOT NULL,
  provider VARCHAR(40) NOT NULL DEFAULT 'manual',
  status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  external_id VARCHAR(190) NULL UNIQUE,
  checkout_url TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at TIMESTAMP NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX(user_id,status)
) ENGINE=InnoDB;

INSERT IGNORE INTO ai_models(slug,name,type,provider,credit_cost) VALUES
('chat-default','Evruni Chat','text','openai-compatible',1),
('image-default','Evruni Image','image','openai-compatible',5),
('speech-default','Evruni Speech','voice','openai-compatible',3),
('transcribe-default','Evruni Transcribe','voice','openai-compatible',2),
('video-default','Evruni Video','video','openai-compatible',20),
('research-default','Evruni Research','research','openai-compatible',3),
('document-default','Evruni Document','document','openai-compatible',2),
('embedding-default','Evruni Embeddings','research','openai-compatible',1);
