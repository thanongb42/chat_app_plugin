-- Web Push Subscriptions
CREATE TABLE IF NOT EXISTS chat_push_subscriptions (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  endpoint   TEXT NOT NULL,
  p256dh     VARCHAR(255) NOT NULL,
  auth       VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT NOW(),
  UNIQUE KEY uq_endpoint (endpoint(191)),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
