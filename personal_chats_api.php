<?php
// Включаем буферизацию вывода ДО любого кода
if (ob_get_level() == 0) {
    ob_start();
}

/**
 * API для личных чатов - обработка AJAX запросов
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'toggle_block':
            // Блокировка/разблокировка пользователя
            $blockedId = $_POST['user_id'] ?? 0;
            
            if ($blockedId == $userId) {
                echo json_encode(['success' => false, 'error' => 'Нельзя заблокировать самого себя']);
                exit;
            }
            
            // Проверяем, существует ли уже блокировка
            $stmt = $pdo->prepare("
                SELECT id FROM user_blocks 
                WHERE blocker_id = ? AND blocked_id = ?
            ");
            $stmt->execute([$userId, $blockedId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Разблокируем пользователя
                $stmt = $pdo->prepare("
                    DELETE FROM user_blocks 
                    WHERE blocker_id = ? AND blocked_id = ?
                ");
                $stmt->execute([$userId, $blockedId]);
                echo json_encode(['success' => true, 'is_blocked' => false]);
            } else {
                // Блокируем пользователя
                $stmt = $pdo->prepare("
                    INSERT INTO user_blocks (blocker_id, blocked_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$userId, $blockedId]);
                echo json_encode(['success' => true, 'is_blocked' => true]);
            }
            break;
            
        case 'check_block_status':
            // Проверка статуса блокировки
            $otherUserId = $_GET['user_id'] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT 
                    EXISTS(
                        SELECT 1 FROM user_blocks 
                        WHERE blocker_id = ? AND blocked_id = ?
                    ) as is_blocked_by_me,
                    EXISTS(
                        SELECT 1 FROM user_blocks 
                        WHERE blocker_id = ? AND blocked_id = ?
                    ) as is_blocked_by_other
            ");
            $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'status' => $status]);
            break;
            
        case 'create_chat':
            // Создание нового чата
            $otherUserId = $_POST['other_user_id'] ?? 0;
            
            if ($otherUserId == $userId) {
                echo json_encode(['success' => false, 'error' => 'Нельзя создать чат с самим собой']);
                exit;
            }
            
            // Проверяем блокировку
            $stmt = $pdo->prepare("
                SELECT 1 FROM user_blocks 
                WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)
            ");
            $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Нельзя создать чат с заблокированным пользователем']);
                exit;
            }
            
            // Проверяем, существует ли уже чат
            $user1 = min($userId, $otherUserId);
            $user2 = max($userId, $otherUserId);
            
            $stmt = $pdo->prepare("
                SELECT id FROM personal_chats 
                WHERE user1_id = ? AND user2_id = ?
            ");
            $stmt->execute([$user1, $user2]);
            $existingChat = $stmt->fetch();
            
            if ($existingChat) {
                echo json_encode(['success' => true, 'chat_id' => $existingChat['id'], 'existing' => true]);
                exit;
            }
            
            // Создаем новый чат
            $stmt = $pdo->prepare("
                INSERT INTO personal_chats (user1_id, user2_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$user1, $user2]);
            
            echo json_encode(['success' => true, 'chat_id' => $pdo->lastInsertId(), 'existing' => false]);
            break;
            
        case 'get_messages':
            // Получение сообщений чата
            $chatId = $_GET['chat_id'] ?? 0;
            
            // Проверяем доступ к чату
            $stmt = $pdo->prepare("
                SELECT 1 FROM personal_chats 
                WHERE id = ? AND (user1_id = ? OR user2_id = ?)
            ");
            $stmt->execute([$chatId, $userId, $userId]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['error' => 'Доступ запрещен']);
                exit;
            }
            
            // Получаем сообщения
            $stmt = $pdo->prepare("
                SELECT 
                    pm.id,
                    pm.sender_id,
                    pm.receiver_id,
                    pm.message_text,
                    pm.is_read,
                    pm.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as sender_name,
                    u.avatar as sender_avatar
                FROM personal_messages pm
                JOIN users u ON pm.sender_id = u.id
                WHERE pm.chat_id = ? AND pm.is_deleted = FALSE
                ORDER BY pm.created_at ASC
                LIMIT 100
            ");
            $stmt->execute([$chatId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($messages);
            break;
            
        case 'send_message':
            // Отправка сообщения
            $chatId = $_POST['chat_id'] ?? 0;
            $message = trim($_POST['message'] ?? '');
            
            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Сообщение не может быть пустым']);
                exit;
            }
            
            if (strlen($message) > 1000) {
                echo json_encode(['success' => false, 'error' => 'Сообщение слишком длинное']);
                exit;
            }
            
            // Проверяем доступ к чату и получаем ID получателя
            $stmt = $pdo->prepare("
                SELECT user1_id, user2_id 
                FROM personal_chats 
                WHERE id = ? AND (user1_id = ? OR user2_id = ?)
            ");
            $stmt->execute([$chatId, $userId, $userId]);
            $chat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$chat) {
                echo json_encode(['success' => false, 'error' => 'Чат не найден']);
                exit;
            }
            
            $receiverId = ($chat['user1_id'] == $userId) ? $chat['user2_id'] : $chat['user1_id'];
            
            // Проверяем блокировку
            $stmt = $pdo->prepare("
                SELECT 1 FROM user_blocks 
                WHERE blocker_id = ? AND blocked_id = ?
            ");
            $stmt->execute([$receiverId, $userId]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Вы заблокированы этим пользователем']);
                exit;
            }
            
            // Экранируем HTML
            $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            
            // Вставляем сообщение
            $stmt = $pdo->prepare("
                INSERT INTO personal_messages (chat_id, sender_id, receiver_id, message_text)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$chatId, $userId, $receiverId, $message]);
            
            // Обновляем последнее сообщение в чате
            $stmt = $pdo->prepare("
                UPDATE personal_chats 
                SET last_message_text = ?, last_message_time = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$message, $chatId]);
            
            // Увеличиваем счетчик непрочитанных для получателя
            if ($chat['user1_id'] == $receiverId) {
                $stmt = $pdo->prepare("UPDATE personal_chats SET unread_count_user1 = unread_count_user1 + 1 WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE personal_chats SET unread_count_user2 = unread_count_user2 + 1 WHERE id = ?");
            }
            $stmt->execute([$chatId]);
            
            echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
            break;
            
        case 'mark_read':
            // Отметить сообщения как прочитанные
            $chatId = $_POST['chat_id'] ?? 0;
            
            // Обновляем непрочитанные сообщения
            $stmt = $pdo->prepare("
                UPDATE personal_messages 
                SET is_read = TRUE, read_at = NOW()
                WHERE chat_id = ? AND receiver_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$chatId, $userId]);
            
            // Сбрасываем счетчик непрочитанных
            $stmt = $pdo->prepare("
                UPDATE personal_chats 
                SET unread_count_user1 = CASE WHEN user1_id = ? THEN 0 ELSE unread_count_user1 END,
                    unread_count_user2 = CASE WHEN user2_id = ? THEN 0 ELSE unread_count_user2 END
                WHERE id = ?
            ");
            $stmt->execute([$userId, $userId, $chatId]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get_chats':
            // Получение списка чатов пользователя
            $stmt = $pdo->prepare("
                SELECT 
                    pc.id as chat_id,
                    CASE 
                        WHEN pc.user1_id = ? THEN pc.user2_id
                        ELSE pc.user1_id
                    END as other_user_id,
                    CASE 
                        WHEN pc.user1_id = ? THEN CONCAT(u2.first_name, ' ', u2.last_name)
                        ELSE CONCAT(u1.first_name, ' ', u1.last_name)
                    END as other_user_name,
                    CASE 
                        WHEN pc.user1_id = ? THEN u2.first_name
                        ELSE u1.first_name
                    END as other_user_first_name,
                    CASE 
                        WHEN pc.user1_id = ? THEN u2.avatar
                        ELSE u1.avatar
                    END as other_user_avatar,
                    pc.last_message_text,
                    pc.last_message_time,
                    CASE 
                        WHEN pc.user1_id = ? THEN pc.unread_count_user1
                        ELSE pc.unread_count_user2
                    END as unread_count,
                    CASE 
                        WHEN pc.user1_id = ? THEN (u2.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE))
                        ELSE (u1.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE))
                    END as is_online,
                    EXISTS(
                        SELECT 1 FROM favorite_chats 
                        WHERE user_id = ? AND chat_id = pc.id
                    ) as is_favorite
                FROM personal_chats pc
                LEFT JOIN users u1 ON pc.user1_id = u1.id
                LEFT JOIN users u2 ON pc.user2_id = u2.id
                WHERE (pc.user1_id = ? OR pc.user2_id = ?)
                    AND pc.is_active = 1
                ORDER BY 
                    is_favorite DESC,
                    pc.last_message_time DESC,
                    pc.updated_at DESC
            ");
            $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
            $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($chats);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие: ' . $action]);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Personal chats API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Personal chats API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()]);
}
?>