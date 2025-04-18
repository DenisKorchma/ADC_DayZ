<?php
require_once 'config.php';
require_once 'ReferralSystem.php';

class ReferralIntegration {
    private $referralSystem;
    
    public function __construct() {
        $this->referralSystem = new ReferralSystem();
    }
    
    // Вызывать этот метод после успешной регистрации пользователя
    public function onUserRegistration($userId, $username, $referralCode = null) {
        try {
            // Регистрируем пользователя в реферальной системе
            $newReferralCode = $this->referralSystem->registerUser($userId, $username);
            
            // Если был указан реферальный код, применяем его
            if ($referralCode) {
                $this->referralSystem->applyReferralCode($userId, $referralCode);
            }
            
            return $newReferralCode;
        } catch (Exception $e) {
            logError('Ошибка при регистрации в реферальной системе', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    // Вызывать этот метод для получения данных реферальной системы
    public function getUserReferralData($userId) {
        try {
            $stats = $this->referralSystem->getDetailedStatistics($userId);
            $referralLink = $this->generateReferralLink($userId);
            
            return [
                'success' => true,
                'stats' => $stats,
                'referral_link' => $referralLink
            ];
        } catch (Exception $e) {
            logError('Ошибка при получении данных реферальной системы', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Ошибка при получении данных'];
        }
    }
    
    // Генерация реферальной ссылки
    private function generateReferralLink($userId) {
        $stats = $this->referralSystem->getUserStatistics($userId);
        return SITE_URL . '/register?ref=' . $stats['referral_code'];
    }
    
    // Проверка и применение реферального кода
    public function processReferralCode($newUserId, $referralCode) {
        try {
            return $this->referralSystem->applyReferralCode($newUserId, $referralCode);
        } catch (Exception $e) {
            logError('Ошибка при применении реферального кода', [
                'userId' => $newUserId,
                'referralCode' => $referralCode,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Ошибка при применении кода'];
        }
    }
} 