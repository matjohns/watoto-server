PRAGMA journal_mode=WAL;

CREATE TABLE ping (
  uuid TEXT PRIMARY KEY,
  time_received INTEGER NOT NULL,
  time_sent INTEGER NOT NULL,
  time_lastPing INTEGER NOT NULL,
  time_lastMessage INTEGER NOT NULL,
  app_version TEXT NOT NULL,
  app_source TEXT NOT NULL,
  app_time INTEGER NOT NULL,
  device_id TEXT NOT NULL,
  device_manufacturer TEXT NOT NULL,
  device_model TEXT NOT NULL,
  device_country TEXT NOT NULL,
  device_screen_width INTEGER NOT NULL,
  device_screen_height INTEGER NOT NULL,
  device_screen_scale REAL NOT NULL,
  device_platform_os TEXT NOT NULL,
  device_platform_version TEXT NOT NULL,
  device_emulator BOOLEAN NOT NULL DEFAULT 0,
  network_ip TEXT NOT NULL,
  network_country TEXT NOT NULL,
  other TEXT
);
CREATE INDEX ping_app_version ON ping(app_version);
CREATE INDEX ping_device_country ON ping(device_country);
CREATE INDEX ping_device_id ON ping(device_id);
CREATE INDEX ping_device_model ON ping(device_manufacturer, device_model);
CREATE INDEX ping_device_os ON ping(device_platform_os, device_platform_version);
CREATE INDEX ping_network_country ON ping(network_country);
CREATE INDEX ping_time_lastPing ON ping(time_lastPing);
CREATE INDEX ping_time_received ON ping(time_received);
CREATE INDEX ping_time_sent ON ping(time_sent);

CREATE TABLE "visited" (
  uuid TEXT NOT NULL,
  time_sent INTEGER NOT NULL,
  time_lastPing INTEGER NOT NULL,
  time_day INTEGER NOT NULL DEFAULT 0,
  type TEXT NOT NULL,
  what TEXT NOT NULL,
  num INTEGER NOT NULL
);
CREATE INDEX visited_ping ON visited(uuid);
CREATE INDEX visited_time_day ON visited(time_day);
CREATE INDEX visited_time_lastPing ON visited(time_lastPing);
CREATE INDEX visited_time_sent ON visited(time_sent);
CREATE INDEX visited_type_what ON visited(type, what);

CREATE TABLE "delivered" (
  ping TEXT NOT NULL,
  message TEXT NOT NULL
);
CREATE INDEX delivered_ping ON delivered(ping);
CREATE INDEX delivered_message ON delivered(message);
