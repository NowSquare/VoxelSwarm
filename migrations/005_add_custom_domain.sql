-- VoxelSwarm — Custom domain support (Part XVIII)
ALTER TABLE instances ADD COLUMN custom_domain TEXT NULL;
ALTER TABLE instances ADD COLUMN domain_verified_at TEXT NULL;
ALTER TABLE instances ADD COLUMN domain_ssl_at TEXT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS idx_instances_custom_domain ON instances(custom_domain);
