<?php
// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_USER', 'referral_user');
define('DB_PASS', 'Xiveb1717');
define('DB_NAME', 'referral_system');

// Настройки реферальной системы
define('REFERRAL_REWARD', 100); // Базовая награда за реферала
define('SITE_URL', 'https://advanced-dayz.online'); // URL вашего сайта

// Настройки безопасности
define('CSRF_TOKEN_SECRET', 'AdvancedDayzServerTOP2025');
session_start();

// Функция для генерации CSRF токена
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Функция для проверки CSRF токена
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Функция для проверки авторизации
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Функция для получения ID текущего пользователя
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Функция для логирования ошибок
function logError($message, $context = []) {
    error_log(date('[Y-m-d H:i:s] ') . $message . ': ' . json_encode($context) . "\n", 3, __DIR__ . '/logs/error.log');
} 