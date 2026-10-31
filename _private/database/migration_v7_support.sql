-- Evruni V7.0 Support Chat
CREATE TABLE IF NOT EXISTS support_threads (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  subject VARCHAR(180) NOT NULL DEFAULT 'Support',
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  closed_at TIMESTAMP NULL,
  PRIMARY KEY(id),
  KEY idx_support_threads_user(user_id,updated_at),
  KEY idx_support_threads_status(status,updated_at),
  CONSTRAINT fk_support_threads_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  thread_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  author_role ENUM('user','admin') NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id),
  KEY idx_support_messages_thread(thread_id,created_at),
  CONSTRAINT fk_support_messages_thread FOREIGN KEY(thread_id) REFERENCES support_threads(id) ON DELETE CASCADE,
  CONSTRAINT fk_support_messages_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
