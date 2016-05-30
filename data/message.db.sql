PRAGMA journal_mode=WAL;

CREATE TABLE message (
  uuid TEXT PRIMARY KEY,
  time_start INTEGER NOT NULL,
  time_end INTEGER,
  title TEXT NOT NULL,
  message TEXT NOT NULL,
  is_disabled BOOLEAN NOT NULL DEFAULT 0,
  is_update BOOLEAN NOT NULL DEFAULT 0,
  app_version TEXT,
  device_id TEXT,
  device_platform_os TEXT,
  device_platform_version TEXT,
  device_country TEXT,
  network_country TEXT
);
CREATE INDEX message_time ON message(time_start, time_end);
