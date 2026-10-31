-- Evruni V11.0 Project Intelligence. Run once in phpMyAdmin.
ALTER TABLE projects
  ADD COLUMN instructions TEXT NULL AFTER description,
  ADD COLUMN memory LONGTEXT NULL AFTER instructions,
  ADD COLUMN default_model_slug VARCHAR(120) NULL AFTER color,
  ADD COLUMN default_preset_id BIGINT UNSIGNED NULL AFTER default_model_slug;

CREATE TABLE IF NOT EXISTS project_memory (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  content LONGTEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_project_memory(project_id),
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE presets ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id;
ALTER TABLE presets ADD INDEX idx_presets_project (project_id);
ALTER TABLE presets ADD CONSTRAINT fk_presets_project FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE SET NULL;

ALTER TABLE documents ADD COLUMN is_knowledge TINYINT(1) NOT NULL DEFAULT 1 AFTER project_id;
ALTER TABLE documents ADD INDEX idx_documents_knowledge (project_id,is_knowledge);

CREATE INDEX idx_threads_title ON chat_threads(user_id,title);
CREATE INDEX idx_projects_name ON projects(user_id,name);
