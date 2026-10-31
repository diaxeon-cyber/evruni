-- Evruni security hardening v14
-- Run once after the existing migrations.
-- These indexes reduce the cost of security/audit/rate-limit lookups under load.

ALTER TABLE audit_log
  ADD INDEX idx_audit_ip_created (ip, created_at),
  ADD INDEX idx_audit_action_created (action, created_at);

ALTER TABLE rate_limits
  ADD INDEX idx_rate_limits_reset_at (reset_at);
