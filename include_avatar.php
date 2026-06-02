<?php
// Универсальный файл для получения данных аватара пользователя
// Подключайте этот файл после session_start() и require_once '../src/config/config.php'

// Проверяем авторизацию
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : '';
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';
$userStatus = 'inactive'; // По умолчанию неактивен

// Получаем аватар и статус пользователя
$userAvatar = null;
if ($isLoggedIn && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT avatar, status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch();
        $userAvatar = $userData['avatar'] ?? null;
        $userStatus = $userData['status'] ?? 'inactive';
        
        // Обновляем статус в сессии для быстрого доступа
        $_SESSION['status'] = $userStatus;
    } catch (PDOException $e) {
        // В случае ошибки просто игнорируем
        $userAvatar = null;
        $userStatus = 'inactive';
    }
}
?>