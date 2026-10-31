-- Evruni V16 performance indexes. Run once in phpMyAdmin.
-- Safe for existing data; duplicate-index errors can be ignored if an index already exists.
ALTER TABLE chat_messages ADD INDEX idx_chat_messages_thread_id_id (thread_id,id);
ALTER TABLE chat_threads ADD INDEX idx_chat_threads_user_project_updated (user_id,project_id,updated_at);
ALTER TABLE documents ADD INDEX idx_documents_user_project_created (user_id,project_id,created_at);
ALTER TABLE generations ADD INDEX idx_generations_user_type_created (user_id,type,created_at);
ALTER TABLE api_usage ADD INDEX idx_api_usage_user_created_cost (user_id,created_at,cost);
ALTER TABLE oauth_accounts ADD INDEX idx_oauth_accounts_user_provider (user_id,provider);
