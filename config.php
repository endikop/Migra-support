<?php
// Убираем session_start() отсюда
// $host = 'localhost';
// $dbname = 'migrant_system';
// $username = 'root';
// $password = '';
// $port = 3307;

$host = getenv('MYSQL_HOST');
$dbname = getenv('MYSQL_DB');
$username = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');
$port = 3306;

date_default_timezone_set('Europe/Moscow');

// Инициализируем переменные
$db_error = false;
$pdo = null;

try {
    // Опции подключения должны передаваться сразу в конструктор PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_CA       => '',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password, 
        $options
    );
} catch(PDOException $e) {
    // Логируем ошибку и выводим общее сообщение
    error_log("Ошибка подключения к БД: " . $e->getMessage());
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['db_error'] = "Ошибка подключения к базе данных. Пожалуйста, попробуйте позже.";
    }
    // Не используем die() с выводом, чтобы не нарушать заголовки
    // Вместо этого устанавливаем флаг ошибки
    $db_error = true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Функция для проверки, что пользователь активен (не заблокирован и не неактивен)
function isUserActive($pdo = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Если передан объект PDO, проверяем статус в базе данных
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $status = $stmt->fetchColumn();
            
            // Обновляем статус в сессии для быстрого доступа
            $_SESSION['status'] = $status;
            
            // Пользователь активен только если статус 'active'
            return $status === 'active';
        } catch (PDOException $e) {
            // В случае ошибки считаем пользователя неактивным
            return false;
        }
    }
    
    // Если PDO не передан, проверяем статус в сессии
    return isset($_SESSION['status']) && $_SESSION['status'] === 'active';
}

// Функция для проверки, может ли пользователь выполнять действия (авторизован И активен)
function canUserPerformActions($pdo = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Если PDO не передан, пытаемся использовать глобальную переменную
    if ($pdo === null) {
        global $pdo;
    }
    
    return isUserActive($pdo);
}



// Функция для получения аватара пользователя
function getUserAvatar($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        return $userData['avatar'] ?? null;
    } catch (PDOException $e) {
        return null;
    }
}

// Функция для отображения аватара в HTML
function displayUserAvatar($pdo, $userId, $userName, $size = 'small') {
    $avatar = getUserAvatar($pdo, $userId);
    $sizeClass = $size === 'large' ? 'user-avatar-large' : 'user-avatar';
    
    if ($avatar) {
        return '<div class="' . $sizeClass . '" id="profileAvatar" title="' . htmlspecialchars($userName) . '">
                    <img src="' . htmlspecialchars($avatar) . '" 
                         alt="' . htmlspecialchars($userName) . '"
                         style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                </div>';
    } else {
        $initials = substr($userName, 0, 1);
        return '<div class="' . $sizeClass . '" id="profileAvatar" title="' . htmlspecialchars($userName) . '">
                    ' . htmlspecialchars($initials) . '
                </div>';
    }
}