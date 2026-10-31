-- Evruni V19: AI-themed user roles
-- Uses user_category as the visible role profile while preserving role=user/admin for access control.
UPDATE users SET user_category='ai_admin' WHERE role='admin';
UPDATE users SET user_category='ai_user' WHERE role='user' AND (user_category IS NULL OR user_category='');
