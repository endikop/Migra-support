<?php
// Самая первая операция - буферизация вывода
ob_start();

session_start();
require_once 'config.php';

// Обновляем статус пользователя в базе данных
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Игнорируем ошибку при выходе
    }
}

// Удаляем токен "запомнить меня", если он есть
if (isset($_COOKIE['remember_token'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
    } catch (PDOException $e) {
        // Игнорируем ошибку
    }
    
    // Удаляем куки
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('user_id', '', time() - 3600, '/');
}

// Уничтожаем сессию
$_SESSION = array();
session_destroy();

// Перенаправляем на главную страницу
header('Location: index.php');
exit();