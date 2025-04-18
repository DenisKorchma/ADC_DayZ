<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');
define('REFERRAL_REWARD', 100); // Награда за реферала в донатной валюте

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        $this->connection->set_charset("utf8mb4");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}

// ReferralSystem.php
class ReferralSystem {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Генерация уникального реферального кода
    public function generateReferralCode($length = 8) {
        do {
            $code = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, $length);
            $exists = $this->db->query("SELECT referral_code FROM users WHERE referral_code = '$code'")->num_rows;
        } while ($exists > 0);
        return $code;
    }

    // Регистрация нового пользователя
    public function registerUser($userId, $username) {
        $referralCode = $this->generateReferralCode();
        $stmt = $this->db->prepare("INSERT INTO users (user_id, username, referral_code) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $userId, $username, $referralCode);
        
        if ($stmt->execute()) {
            $stmt->close();
            // Создаем запись в статистике
            $this->db->query("INSERT INTO referral_statistics (user_id) VALUES ('$userId')");
            return $referralCode;
        }
        $stmt->close();
        return false;
    }

    // Применение реферального кода
    public function applyReferralCode($newUserId, $referrerCode) {
        // Находим пользователя по реферальному коду
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE referral_code = ?");
        $stmt->bind_param("s", $referrerCode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ["success" => false, "message" => "Неверный реферальный код"];
        }

        $referrer = $result->fetch_assoc();
        $referrerId = $referrer['user_id'];

        // Проверяем, не использовал ли уже этот пользователь реферальный код
        $checkStmt = $this->db->prepare("SELECT referral_id FROM referrals WHERE referred_id = ?");
        $checkStmt->bind_param("s", $newUserId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return ["success" => false, "message" => "Вы уже использовали реферальный код"];
        }

        // Добавляем запись о реферале
        $insertStmt = $this->db->prepare("INSERT INTO referrals (referrer_id, referred_id) VALUES (?, ?)");
        $insertStmt->bind_param("ss", $referrerId, $newUserId);
        
        if ($insertStmt->execute()) {
            // Обновляем статистику
            $this->updateReferralStatistics($referrerId);
            // Начисляем награду
            $this->addReward($referrerId);
            return ["success" => true, "message" => "Реферальный код успешно применен"];
        }

        return ["success" => false, "message" => "Ошибка при применении кода"];
    }

    // Получение уровня пользователя
    public function getUserLevel($userId) {
        $stats = $this->getUserStatistics($userId);
        $totalReferrals = $stats['total_referrals'];
        
        $stmt = $this->db->prepare("
            SELECT * FROM referral_levels 
            WHERE min_referrals <= ? 
            ORDER BY min_referrals DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $totalReferrals);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Обновление наград с учетом уровня
    private function addReward($userId) {
        $userLevel = $this->getUserLevel($userId);
        $baseReward = REFERRAL_REWARD;
        $finalReward = $baseReward * $userLevel['reward_multiplier'];
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET donation_currency = donation_currency + ? 
            WHERE user_id = ?
        ");
        $stmt->bind_param("ds", $finalReward, $userId);
        $stmt->execute();
        
        // Записываем награду в историю
        $stmt = $this->db->prepare("
            INSERT INTO referral_rewards (user_id, amount, reward_type, description)
            VALUES (?, ?, 'referral', 'Награда за нового реферала')
        ");
        $stmt->bind_param("sd", $userId, $finalReward);
        $stmt->execute();
        
        // Обновляем статистику
        $this->db->query("
            UPDATE referral_statistics 
            SET total_earned_currency = total_earned_currency + $finalReward 
            WHERE user_id = '$userId'
        ");
        
        // Создаем уведомление
        $this->createNotification($userId, 'reward', "Вы получили {$finalReward} монет за нового реферала!");
    }

    // Создание уведомления
    public function createNotification($userId, $type, $message) {
        $stmt = $this->db->prepare("
            INSERT INTO referral_notifications (user_id, type, message)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $userId, $type, $message);
        $stmt->execute();
    }

    // Получение уведомлений пользователя
    public function getUserNotifications($userId, $unreadOnly = false) {
        $query = "
            SELECT * FROM referral_notifications 
            WHERE user_id = ?
        ";
        if ($unreadOnly) {
            $query .= " AND is_read = FALSE";
        }
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Отметить уведомления как прочитанные
    public function markNotificationsAsRead($userId) {
        $stmt = $this->db->prepare("
            UPDATE referral_notifications 
            SET is_read = TRUE 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->bind_param("s", $userId);
        return $stmt->execute();
    }

    // Получение подробной статистики по уровням рефералов
    public function getDetailedStatistics($userId) {
        $stats = $this->getUserStatistics($userId);
        $level = $this->getUserLevel($userId);
        $notifications = $this->getUserNotifications($userId, true);
        $rewards = $this->getUserRewards($userId);
        
        return [
            'user_stats' => $stats,
            'current_level' => $level,
            'unread_notifications' => count($notifications),
            'rewards_history' => $rewards
        ];
    }

    // Получение истории наград
    public function getUserRewards($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM referral_rewards 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Проверка возможности повышения уровня
    public function checkLevelUp($userId) {
        $currentLevel = $this->getUserLevel($userId);
        $stats = $this->getUserStatistics($userId);
        
        $stmt = $this->db->prepare("
            SELECT * FROM referral_levels 
            WHERE min_referrals <= ? AND level_id > ?
            ORDER BY min_referrals DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ii", $stats['total_referrals'], $currentLevel['level_id']);
        $stmt->execute();
        $newLevel = $stmt->get_result()->fetch_assoc();
        
        if ($newLevel) {
            $this->createNotification(
                $userId,
                'level_up',
                "Поздравляем! Вы достигли уровня '{$newLevel['name']}'!"
            );
            return $newLevel;
        }
        
        return false;
    }

    // Получение статистики пользователя
    public function getUserStatistics($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                u.username,
                u.referral_code,
                rs.total_referrals,
                rs.total_earned_currency,
                u.donation_currency
            FROM users u
            JOIN referral_statistics rs ON u.user_id = rs.user_id
            WHERE u.user_id = ?
        ");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Получение списка рефералов пользователя
    public function getUserReferrals($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                u.username as referred_username,
                r.date_joined
            FROM referrals r
            JOIN users u ON r.referred_id = u.user_id
            WHERE r.referrer_id = ?
            ORDER BY r.date_joined DESC
        ");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// api.php
require_once 'config.php';
require_once 'ReferralSystem.php';

header('Content-Type: application/json');

$referralSystem = new ReferralSystem();

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

switch ($action) {
    case 'register':
        $userId = $_POST['user_id'] ?? '';
        $username = $_POST['username'] ?? '';
        if ($userId && $username) {
            $referralCode = $referralSystem->registerUser($userId, $username);
            if ($referralCode) {
                $response = ['success' => true, 'referral_code' => $referralCode];
            } else {
                $response = ['success' => false, 'message' => 'Registration failed'];
            }
        }
        break;

    case 'apply_referral':
        $userId = $_POST['user_id'] ?? '';
        $referralCode = $_POST['referral_code'] ?? '';
        if ($userId && $referralCode) {
            $response = $referralSystem->applyReferralCode($userId, $referralCode);
        }
        break;

    case 'get_statistics':
        $userId = $_POST['user_id'] ?? '';
        if ($userId) {
            $stats = $referralSystem->getUserStatistics($userId);
            $referrals = $referralSystem->getUserReferrals($userId);
            $response = [
                'success' => true,
                'statistics' => $stats,
                'referrals' => $referrals
            ];
        }
        break;

    case 'get_notifications':
        $userId = $_POST['user_id'] ?? '';
        if ($userId) {
            $notifications = $referralSystem->getUserNotifications($userId);
            $response = ['success' => true, 'notifications' => $notifications];
        }
        break;
        
    case 'mark_notifications_read':
        $userId = $_POST['user_id'] ?? '';
        if ($userId) {
            $success = $referralSystem->markNotificationsAsRead($userId);
            $response = ['success' => $success];
        }
        break;
        
    case 'get_detailed_stats':
        $userId = $_POST['user_id'] ?? '';
        if ($userId) {
            $detailedStats = $referralSystem->getDetailedStatistics($userId);
            $response = ['success' => true, 'data' => $detailedStats];
        }
        break;
}

echo json_encode($response);
?>