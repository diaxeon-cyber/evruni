-- Evruni unified USD wallet
-- Run after migration_v4 and before using the unified wallet in production.
-- Existing wallet rows are canonicalized by the application with the current FX table;
-- this migration only enforces the canonical USD row shape without changing user-facing data.
CREATE TABLE IF NOT EXISTS wallet_balances(
  user_id BIGINT UNSIGNED NOT NULL,
  currency VARCHAR(10) NOT NULL,
  balance DECIMAL(18,6) NOT NULL DEFAULT 0,
  PRIMARY KEY(user_id,currency),
  CONSTRAINT fk_wallet_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallet_ledger(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  currency VARCHAR(10) NOT NULL,
  amount DECIMAL(18,6) NOT NULL,
  balance_after DECIMAL(18,6) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  reference_type VARCHAR(80) NULL,
  reference_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id,created_at),
  CONSTRAINT fk_wallet_ledger_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
