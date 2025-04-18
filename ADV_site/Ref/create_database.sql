-- Создание базы данных
CREATE DATABASE IF NOT EXISTS referral_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Создание пользователя и назначение прав
CREATE USER IF NOT EXISTS 'referral_user'@'localhost' IDENTIFIED BY 'ваш_сложный_пароль';
GRANT ALL PRIVILEGES ON referral_system.* TO 'referral_user'@'localhost';
FLUSH PRIVILEGES;

-- Выбор базы данных
USE referral_system; 