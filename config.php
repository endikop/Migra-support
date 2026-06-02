<?php
// Добавьте ЭТИ строки в САМОЕ НАЧАЛО config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = getenv('MYSQL_HOST');
$dbname = getenv('MYSQL_DB');
$username = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');
$port = 3306;

date_default_timezone_set('Europe/Moscow');

try {
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
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isUserActive($pdo = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $status = $stmt->fetchColumn();
            $_SESSION['status'] = $status;
            return $status === 'active';
        } catch (PDOException $e) {
            return false;
        }
    }
    
    return isset($_SESSION['status']) && $_SESSION['status'] === 'active';
}

function canUserPerformActions($pdo = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if ($pdo === null) {
        global $pdo;
    }
    
    return isUserActive($pdo);
}

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
// ВАЖНО: НЕТ ЗАКРЫВАЮЩЕГО ТЕГА ?> В КОНЦЕ!