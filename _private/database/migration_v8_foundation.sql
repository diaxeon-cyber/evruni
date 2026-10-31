-- Evruni V8 Foundation migration. Run once in phpMyAdmin.
ALTER TABLE chat_threads ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id;
ALTER TABLE chat_threads ADD COLUMN is_pinned TINYINT(1) NOT NULL DEFAULT 0 AFTER system_prompt;
ALTER TABLE chat_threads ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER is_pinned;
ALTER TABLE chat_threads ADD INDEX idx_chat_project (project_id, updated_at);
ALTER TABLE chat_threads ADD CONSTRAINT fk_chat_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL;
ALTER TABLE projects ADD COLUMN color VARCHAR(20) NOT NULL DEFAULT 'purple' AFTER description;
CREATE INDEX idx_projects_user_updated ON projects(user_id, updated_at);
