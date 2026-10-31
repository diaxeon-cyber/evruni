-- Evruni V18: AI-themed user categories
-- Run once on an existing database.
ALTER TABLE users ADD COLUMN user_category VARCHAR(40) NOT NULL DEFAULT 'ai_user' AFTER role;

UPDATE users SET user_category='ai_admin' WHERE role='admin' AND (user_category IS NULL OR user_category='ai_user');
