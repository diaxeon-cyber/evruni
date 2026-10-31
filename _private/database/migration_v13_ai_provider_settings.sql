-- Evruni v13: AI provider settings for admin panel
CREATE TABLE IF NOT EXISTS ai_provider_settings (
  provider VARCHAR(80) NOT NULL PRIMARY KEY,
  api_url TEXT NULL,
  docs_url TEXT NULL,
  health_url TEXT NULL,
  load_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(24) NOT NULL DEFAULT 'unknown',
  note VARCHAR(255) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
