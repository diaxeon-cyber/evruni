-- Evruni V9.81 project files. Run once in phpMyAdmin.
ALTER TABLE documents ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER user_id;
ALTER TABLE documents ADD INDEX idx_documents_project (project_id, created_at);
ALTER TABLE documents ADD CONSTRAINT fk_documents_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL;
