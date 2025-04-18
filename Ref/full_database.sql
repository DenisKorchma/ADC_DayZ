-- Создание базы данных
CREATE DATABASE IF NOT EXISTS referral_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Использование базы данных
USE referral_system;

-- Создание таблицы пользователей
CREATE TABLE users (
    user_id VARCHAR(255) PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    referral_code VARCHAR(8) UNIQUE NOT NULL,
    donation_currency INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы рефералов
CREATE TABLE referrals (
    referral_id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id VARCHAR(255) NOT NULL,
    referred_id VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    date_joined TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(user_id),
    FOREIGN KEY (referred_id) REFERENCES users(user_id),
    UNIQUE KEY unique_referral (referred_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы статистики рефералов
CREATE TABLE referral_statistics (
    stat_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) UNIQUE NOT NULL,
    total_referrals INT DEFAULT 0,
    total_earned_currency INT DEFAULT 0,
    level_1_referrals INT DEFAULT 0,
    level_2_referrals INT DEFAULT 0,
    level_3_referrals INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы уровней реферальной программы
CREATE TABLE referral_levels (
    level_id INT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    min_referrals INT NOT NULL,
    reward_multiplier DECIMAL(3,2) DEFAULT 1.00,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы наград за рефералов
CREATE TABLE referral_rewards (
    reward_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    amount INT NOT NULL,
    reward_type ENUM('referral', 'level_up', 'bonus') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы уведомлений
CREATE TABLE referral_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    type ENUM('new_referral', 'reward', 'level_up', 'system') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка базовых уровней реферальной программы
INSERT INTO referral_levels (level_id, name, min_referrals, reward_multiplier, description) VALUES
(1, 'Начинающий', 0, 1.00, 'Базовый уровень реферальной программы'),
(2, 'Продвинутый', 5, 1.25, 'Увеличенные награды за рефералов'),
(3, 'Эксперт', 15, 1.50, 'Значительно увеличенные награды за рефералов'),
(4, 'Мастер', 30, 2.00, 'Максимальные награды за рефералов');

-- Создание индексов для оптимизации
CREATE INDEX idx_referrals_referrer ON referrals(referrer_id);
CREATE INDEX idx_referrals_date ON referrals(date_joined);
CREATE INDEX idx_notifications_user ON referral_notifications(user_id, is_read); 