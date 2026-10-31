-- Evruni V6.0
-- Editable development notice + admin analytics support.
CREATE TABLE IF NOT EXISTS site_notices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  notice_key VARCHAR(64) NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  title_ru VARCHAR(255) NOT NULL,
  text_ru TEXT NOT NULL,
  title_en VARCHAR(255) NOT NULL,
  text_en TEXT NOT NULL,
  updated_by INT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_site_notices_key (notice_key),
  KEY idx_site_notices_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_notices
  (notice_key, enabled, title_ru, text_ru, title_en, text_en)
VALUES
  ('development', 1,
   'Evruni находится в разработке',
   'Некоторые функции временно недоступны. Мы работаем над их запуском.',
   'Evruni is under development',
   'Some features are temporarily unavailable. We are working on bringing them online.')
ON DUPLICATE KEY UPDATE notice_key=VALUES(notice_key);
