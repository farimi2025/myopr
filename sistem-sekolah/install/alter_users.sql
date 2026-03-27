-- Tambah kolum token_reset_tamat ke jadual users (jika belum ada)
ALTER TABLE users ADD COLUMN IF NOT EXISTS token_reset_tamat DATETIME NULL AFTER token_reset;
