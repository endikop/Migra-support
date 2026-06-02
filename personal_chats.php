<?php
// Включаем буферизацию вывода ДО любого кода
if (ob_get_level() == 0) {
    ob_start();
}

session_start();
/**
 * Личные чаты - Современный мессенджер с премиум дизайном
 */


require_once 'config.php';

// Проверяем авторизацию
$isLoggedIn = isset($_SESSION['user_id']);

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Получаем информацию о пользователе
$userName = '';
$userAvatar = '';
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if ($user) {
        $userName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8');
        $userAvatar = htmlspecialchars($user['avatar'] ?? '', ENT_QUOTES, 'UTF-8');
    }
} catch (PDOException $e) {
    error_log("Ошибка получения данных пользователя: " . $e->getMessage());
}

// Языки
$supportedLanguages = ['ru', 'en', 'pt', 'fr', 'de'];
$lang = $_COOKIE['lang'] ?? 'ru';
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLanguages)) {
    $lang = $_GET['lang'];
    setcookie('lang', $lang, time() + (86400 * 30), "/");
}

function t($ru, $en, $pt = '', $fr = '', $de = '') {
    global $lang;
    switch ($lang) {
        case 'en': return $en;
        case 'pt': return !empty($pt) ? $pt : $en;
        case 'fr': return !empty($fr) ? $fr : $en;
        case 'de': return !empty($de) ? $de : $en;
        default: return $ru;
    }
}

// ============================================
// ОБРАБОТКА API ЗАПРОСОВ
// ============================================

if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'get_chats':
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
                        ) as is_favorite,
                        EXISTS(
                            SELECT 1 FROM user_blocks 
                            WHERE blocker_id = ? AND blocked_id = CASE 
                                WHEN pc.user1_id = ? THEN pc.user2_id
                                ELSE pc.user1_id
                            END
                        ) as is_blocked_by_me,
                        EXISTS(
                            SELECT 1 FROM user_blocks 
                            WHERE blocker_id = CASE 
                                WHEN pc.user1_id = ? THEN pc.user2_id
                                ELSE pc.user1_id
                            END AND blocked_id = ?
                        ) as is_blocked_by_other
                    FROM personal_chats pc
                    LEFT JOIN users u1 ON pc.user1_id = u1.id
                    LEFT JOIN users u2 ON pc.user2_id = u2.id
                    WHERE (pc.user1_id = ? OR pc.user2_id = ?)
                        AND pc.is_active = TRUE
                    ORDER BY 
                        is_favorite DESC,
                        pc.last_message_time DESC,
                        pc.updated_at DESC
                ");
                $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
                $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($chats);
                break;
                
            case 'search_users':
                $query = trim($_GET['query'] ?? '');
                if (empty($query)) {
                    echo json_encode([]);
                    break;
                }
                $stmt = $pdo->prepare("
                    SELECT 
                        u.id,
                        u.first_name,
                        u.last_name,
                        CONCAT(u.first_name, ' ', u.last_name) as name,
                        u.avatar,
                        (u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as is_online,
                        EXISTS(
                            SELECT 1 FROM personal_chats 
                            WHERE ((user1_id = ? AND user2_id = u.id) OR (user1_id = u.id AND user2_id = ?))
                            AND is_active = TRUE
                        ) as has_chat,
                        EXISTS(
                            SELECT 1 FROM user_blocks 
                            WHERE blocker_id = ? AND blocked_id = u.id
                        ) as is_blocked_by_me,
                        EXISTS(
                            SELECT 1 FROM user_blocks 
                            WHERE blocker_id = u.id AND blocked_id = ?
                        ) as is_blocked_by_other
                    FROM users u
                    WHERE u.id != ?
                        AND u.status = 'active'
                        AND (u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)
                    ORDER BY 
                        has_chat DESC,
                        u.first_name ASC
                    LIMIT 30
                ");
                $searchTerm = "%{$query}%";
                $stmt->execute([$userId, $userId, $userId, $userId, $userId, $searchTerm, $searchTerm, $searchTerm]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($users);
                break;
                
            case 'get_user_info':
                $otherUserId = $_GET['user_id'] ?? 0;
                $stmt = $pdo->prepare("
                    SELECT 
                        id,
                        first_name,
                        last_name,
                        CONCAT(first_name, ' ', last_name) as name,
                        avatar,
                        (last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as is_online
                    FROM users 
                    WHERE id = ? AND status = 'active'
                ");
                $stmt->execute([$otherUserId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($user ?: ['error' => 'Пользователь не найден']);
                break;
                
            case 'get_messages':
                $chatId = $_GET['chat_id'] ?? 0;
                $stmt = $pdo->prepare("
                    SELECT 1 FROM personal_chats 
                    WHERE id = ? AND (user1_id = ? OR user2_id = ?) AND is_active = TRUE
                ");
                $stmt->execute([$chatId, $userId, $userId]);
                if (!$stmt->fetch()) {
                    echo json_encode(['error' => 'Доступ запрещен']);
                    exit;
                }
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
                $stmt = $pdo->prepare("
                    SELECT user1_id, user2_id 
                    FROM personal_chats 
                    WHERE id = ? AND (user1_id = ? OR user2_id = ?) AND is_active = TRUE
                ");
                $stmt->execute([$chatId, $userId, $userId]);
                $chat = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$chat) {
                    echo json_encode(['success' => false, 'error' => 'Чат не найден']);
                    exit;
                }
                $receiverId = ($chat['user1_id'] == $userId) ? $chat['user2_id'] : $chat['user1_id'];
                $stmt = $pdo->prepare("SELECT 1 FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
                $stmt->execute([$receiverId, $userId]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Вы заблокированы этим пользователем']);
                    exit;
                }
                $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                $stmt = $pdo->prepare("
                    INSERT INTO personal_messages (chat_id, sender_id, receiver_id, message_text)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$chatId, $userId, $receiverId, $message]);
                $stmt = $pdo->prepare("
                    UPDATE personal_chats 
                    SET last_message_text = ?, last_message_time = NOW(), updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$message, $chatId]);
                if ($chat['user1_id'] == $receiverId) {
                    $stmt = $pdo->prepare("UPDATE personal_chats SET unread_count_user1 = unread_count_user1 + 1 WHERE id = ?");
                } else {
                    $stmt = $pdo->prepare("UPDATE personal_chats SET unread_count_user2 = unread_count_user2 + 1 WHERE id = ?");
                }
                $stmt->execute([$chatId]);
                echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
                break;
                
            case 'mark_read':
                $chatId = $_POST['chat_id'] ?? 0;
                $stmt = $pdo->prepare("
                    UPDATE personal_messages 
                    SET is_read = TRUE, read_at = NOW()
                    WHERE chat_id = ? AND receiver_id = ? AND is_read = FALSE
                ");
                $stmt->execute([$chatId, $userId]);
                $stmt = $pdo->prepare("
                    UPDATE personal_chats 
                    SET unread_count_user1 = CASE WHEN user1_id = ? THEN 0 ELSE unread_count_user1 END,
                        unread_count_user2 = CASE WHEN user2_id = ? THEN 0 ELSE unread_count_user2 END
                    WHERE id = ?
                ");
                $stmt->execute([$userId, $userId, $chatId]);
                echo json_encode(['success' => true]);
                break;
                
            case 'create_chat':
                $otherUserId = $_POST['other_user_id'] ?? 0;
                if ($otherUserId == $userId) {
                    echo json_encode(['success' => false, 'error' => 'Нельзя создать чат с самим собой']);
                    exit;
                }
                $stmt = $pdo->prepare("
                    SELECT 1 FROM user_blocks 
                    WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)
                ");
                $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Нельзя создать чат с заблокированным пользователем']);
                    exit;
                }
                $user1 = min($userId, $otherUserId);
                $user2 = max($userId, $otherUserId);
                $stmt = $pdo->prepare("SELECT id FROM personal_chats WHERE user1_id = ? AND user2_id = ? AND is_active = TRUE");
                $stmt->execute([$user1, $user2]);
                $existingChat = $stmt->fetch();
                if ($existingChat) {
                    echo json_encode(['success' => true, 'chat_id' => $existingChat['id'], 'existing' => true]);
                    exit;
                }
                $stmt = $pdo->prepare("INSERT INTO personal_chats (user1_id, user2_id) VALUES (?, ?)");
                $stmt->execute([$user1, $user2]);
                echo json_encode(['success' => true, 'chat_id' => $pdo->lastInsertId(), 'existing' => false]);
                break;
                
            case 'toggle_favorite':
                $chatId = $_POST['chat_id'] ?? 0;
                $stmt = $pdo->prepare("SELECT 1 FROM personal_chats WHERE id = ? AND (user1_id = ? OR user2_id = ?) AND is_active = TRUE");
                $stmt->execute([$chatId, $userId, $userId]);
                if (!$stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
                    exit;
                }
                $stmt = $pdo->prepare("SELECT id FROM favorite_chats WHERE user_id = ? AND chat_id = ?");
                $stmt->execute([$userId, $chatId]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $stmt = $pdo->prepare("DELETE FROM favorite_chats WHERE user_id = ? AND chat_id = ?");
                    $stmt->execute([$userId, $chatId]);
                    echo json_encode(['success' => true, 'is_favorite' => false]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO favorite_chats (user_id, chat_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $chatId]);
                    echo json_encode(['success' => true, 'is_favorite' => true]);
                }
                break;
                
            case 'toggle_block':
                $blockedId = $_POST['user_id'] ?? 0;
                if ($blockedId == $userId) {
                    echo json_encode(['success' => false, 'error' => 'Нельзя заблокировать самого себя']);
                    exit;
                }
                $stmt = $pdo->prepare("SELECT id FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
                $stmt->execute([$userId, $blockedId]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $stmt = $pdo->prepare("DELETE FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
                    $stmt->execute([$userId, $blockedId]);
                    echo json_encode(['success' => true, 'is_blocked' => false]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO user_blocks (blocker_id, blocked_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $blockedId]);
                    echo json_encode(['success' => true, 'is_blocked' => true]);
                }
                break;
                
            case 'get_favorites':
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
                            WHEN pc.user1_id = ? THEN (u2.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE))
                            ELSE (u1.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE))
                        END as is_online,
                        fc.created_at as favorite_since
                    FROM favorite_chats fc
                    JOIN personal_chats pc ON fc.chat_id = pc.id
                    LEFT JOIN users u1 ON pc.user1_id = u1.id
                    LEFT JOIN users u2 ON pc.user2_id = u2.id
                    WHERE fc.user_id = ? AND pc.is_active = TRUE
                    ORDER BY fc.created_at DESC
                ");
                $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
                $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($chats);
                break;
                
            case 'get_blocked':
                $stmt = $pdo->prepare("
                    SELECT 
                        u.id,
                        u.first_name,
                        u.last_name,
                        CONCAT(u.first_name, ' ', u.last_name) as name,
                        u.avatar,
                        ub.created_at as blocked_since
                    FROM user_blocks ub
                    JOIN users u ON ub.blocked_id = u.id
                    WHERE ub.blocker_id = ? AND u.status = 'active'
                    ORDER BY ub.created_at DESC
                ");
                $stmt->execute([$userId]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($users);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
                break;
        }
    } catch (PDOException $e) {
        error_log("Personal chats API error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
    }
    exit;
}

$startChatWith = isset($_GET['start_chat']) ? (int)$_GET['start_chat'] : 0;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo t('Личные чаты - MigraSupport', 'Personal Chats - MigraSupport', 'Chats Pessoais - MigraSupport', 'Chats Personnels - MigraSupport', 'Persönliche Chats - MigraSupport'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #ec4899;
            --secondary-dark: #db2777;
            --accent: #f59e0b;
            --success: #10b981;
            --info: #3b82f6;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #0f172a;
            --dark-light: #1e293b;
            --light: #f8fafc;
            --gray: #94a3b8;
            --gray-light: #cbd5e1;
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            --gradient-secondary: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --gradient-message-sent: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --gradient-message-received: linear-gradient(135deg, #334155 0%, #475569 100%);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --radius-sm: 0.5rem;
            --radius-md: 1rem;
            --radius-lg: 1.5rem;
            --radius-xl: 2rem;
            --radius-full: 9999px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gradient-secondary);
            color: var(--light);
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(236, 72, 153, 0.15) 0%, transparent 50%);
            z-index: -1;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }

        /* Header */
        .messenger-header {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: var(--shadow-lg);
            padding: 0 28px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .burger-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 12px;
            gap: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-md);
            transition: var(--transition);
        }

        .burger-menu:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.05);
        }

        .burger-line {
            width: 24px;
            height: 2px;
            background: var(--light);
            transition: var(--transition);
            border-radius: 2px;
        }

        .burger-menu.active .burger-line:nth-child(1) {
            transform: rotate(45deg) translate(8px, 6px);
            background: var(--primary);
        }

        .burger-menu.active .burger-line:nth-child(2) {
            opacity: 0;
            transform: translateX(-10px);
        }

        .burger-menu.active .burger-line:nth-child(3) {
            transform: rotate(-45deg) translate(8px, -6px);
            background: var(--primary);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 1.4rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }

        .logo:hover {
            transform: translateY(-2px);
        }

        .logo-icon {
            width: 44px;
            height: 44px;
            background: var(--gradient-primary);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .logo-icon i {
            font-size: 1.3rem;
        }

        .logo-text {
            background: linear-gradient(135deg, #fff 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .language-selector {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            background: rgba(255, 255, 255, 0.05);
            padding: 6px;
            border-radius: var(--radius-full);
        }

        .lang-btn {
            background: transparent;
            border: none;
            border-radius: var(--radius-full);
            padding: 8px 14px;
            color: var(--gray-light);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.8rem;
            min-width: 48px;
            text-align: center;
        }

        .lang-btn:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .lang-btn.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .profile-dropdown {
            position: relative;
        }

        .user-avatar-header {
            width: 48px;
            height: 48px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .user-avatar-header:hover {
            transform: scale(1.08) rotate(5deg);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.5);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .user-avatar-header img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            min-width: 220px;
            box-shadow: var(--shadow-2xl);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px) scale(0.95);
            transition: var(--transition);
            z-index: 1000;
            overflow: hidden;
        }

        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .dropdown-item {
            padding: 14px 20px;
            color: var(--light);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.08);
            padding-left: 28px;
        }

        .dropdown-item i {
            width: 18px;
            text-align: center;
        }

        .dropdown-item.logout {
            color: var(--danger);
        }

        .dropdown-item.logout:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Avatar clickable cursor */
        .chat-user-avatar-clickable {
            cursor: pointer;
            transition: var(--transition);
        }
        
        .chat-user-avatar-clickable:hover {
            transform: scale(1.05);
            filter: brightness(1.1);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.5);
        }

        /* Messenger Layout */
        .messenger-layout {
            display: flex;
            height: calc(100vh - 80px);
        }

        /* Sidebar */
        .sidebar {
            width: 450px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }

        .sidebar-tabs {
            display: flex;
            padding: 20px 24px;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-tab {
            flex: 1;
            padding: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: none;
            border-radius: var(--radius-md);
            color: var(--gray);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .sidebar-tab:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--light);
            transform: translateY(-1px);
        }

        .sidebar-tab.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .search-box {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .search-box input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-md);
            color: var(--light);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .lists-container {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }

        .list-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 6px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid transparent;
            animation: fadeInUp 0.4s ease backwards;
            position: relative;
            overflow: hidden;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .list-item:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }

        .list-item.active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.1));
            border-color: rgba(99, 102, 241, 0.3);
        }

        .item-avatar {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-full);
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.3rem;
            flex-shrink: 0;
            position: relative;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
        }

        .list-item:hover .item-avatar {
            transform: scale(1.05) rotate(3deg);
        }

        .item-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .online-dot {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 14px;
            height: 14px;
            background: var(--success);
            border: 2px solid var(--dark-light);
            border-radius: 50%;
            animation: onlinePulse 1.5s infinite;
        }

        @keyframes onlinePulse {
            0%, 100% { 
                opacity: 1;
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            50% { 
                opacity: 0.8;
                transform: scale(1.1);
                box-shadow: 0 0 0 4px rgba(16, 185, 129, 0);
            }
        }

        .item-info {
            flex: 1;
            min-width: 0;
        }

        .item-name {
            font-weight: 700;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 1rem;
        }

        .item-preview {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-right {
            text-align: right;
        }

        .item-time {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.4);
            margin-bottom: 6px;
        }

        .unread-badge {
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            padding: 4px 10px;
            font-size: 0.7rem;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }

        .favorite-star {
            color: var(--warning);
            font-size: 0.9rem;
            filter: drop-shadow(0 0 4px rgba(245, 158, 11, 0.5));
        }

        /* Chat Area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
        }

        .chat-area-header {
            padding: 20px 28px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
        }

        .chat-user-info {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .chat-user-avatar {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-full);
            object-fit: cover;
            background: var(--gradient-primary);
            transition: var(--transition);
            border: 2px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .chat-user-avatar:hover {
            transform: scale(1.05);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }

        .chat-user-name {
            font-weight: 800;
            font-size: 1.2rem;
            margin-bottom: 4px;
        }

        .chat-status {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .chat-status.online {
            color: var(--success);
        }

        .chat-status.online::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            display: inline-block;
            animation: statusPulse 1s infinite;
        }

        @keyframes statusPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .chat-actions {
            display: flex;
            gap: 12px;
        }

        .icon-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-md);
            padding: 12px;
            color: var(--light);
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
        }

        .icon-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px) scale(1.05);
        }

        /* Messages Container */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message {
            max-width: 65%;
            padding: 14px 20px;
            border-radius: var(--radius-lg);
            word-wrap: break-word;
            animation: messageAppear 0.3s ease;
            position: relative;
        }

        @keyframes messageAppear {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .message.sent {
            align-self: flex-end;
            background: var(--gradient-message-sent);
            border-bottom-right-radius: 4px;
            box-shadow: var(--shadow-lg);
        }

        .message.received {
            align-self: flex-start;
            background: var(--gradient-message-received);
            border-bottom-left-radius: 4px;
            box-shadow: var(--shadow-md);
        }

        .message-text {
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .message-time {
            font-size: 0.65rem;
            opacity: 0.7;
            margin-top: 8px;
            text-align: right;
        }

        /* Message Input Area */
        .message-input-area {
            padding: 20px 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            gap: 16px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
        }

        .message-input {
            flex: 1;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-full);
            color: var(--light);
            font-family: inherit;
            resize: none;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .message-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .send-btn {
            background: var(--gradient-primary);
            border: none;
            border-radius: var(--radius-full);
            padding: 0 32px;
            color: white;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            color: rgba(255, 255, 255, 0.4);
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 24px;
            opacity: 0.5;
            animation: floatIcon 3s infinite ease-in-out;
        }

        @keyframes floatIcon {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 12px;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--shadow-2xl);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.3rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }

        .modal-search {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-search input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-md);
            color: var(--light);
            font-size: 0.9rem;
        }

        .modal-search input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .modal-users-list {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .modal-user-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 8px;
        }

        .modal-user-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .modal-user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            position: relative;
        }

        .modal-user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .modal-user-info {
            flex: 1;
        }

        .modal-user-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .modal-user-status {
            font-size: 0.75rem;
            color: var(--gray);
        }

        .modal-user-status.online {
            color: var(--success);
        }

        .modal-user-btn {
            padding: 8px 16px;
            background: var(--gradient-primary);
            border: none;
            border-radius: var(--radius-full);
            color: white;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-user-btn:hover {
            transform: scale(1.05);
        }

        .modal-user-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .new-chat-btn {
            background: var(--gradient-primary);
            border: none;
            border-radius: var(--radius-full);
            padding: 10px 18px;
            color: white;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            margin-left: auto;
        }

        .new-chat-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        /* Notification */
        .notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--gradient-primary);
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius-full);
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideInRight 0.3s ease;
            font-size: 0.9rem;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 380px;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                left: -450px;
                height: calc(100vh - 80px);
                z-index: 200;
                width: 340px;
            }

            .sidebar.open {
                left: 0;
                animation: slideInLeft 0.3s ease;
            }

            @keyframes slideInLeft {
                from { left: -450px; }
                to { left: 0; }
            }

            .burger-menu {
                display: flex;
            }

            .message {
                max-width: 85%;
            }
        }

        @media (max-width: 768px) {
            .messenger-header {
                padding: 0 16px;
                height: 70px;
            }

            .sidebar {
                height: calc(100vh - 70px);
            }

            .chat-user-name {
                font-size: 1rem;
            }

            .chat-user-avatar {
                width: 44px;
                height: 44px;
            }

            .message {
                max-width: 90%;
            }

            .language-selector {
                display: none;
            }
            
            .logo-text {
                display: none;
            }

            .chat-area-header {
                padding: 16px;
            }

            .messages-container {
                padding: 16px;
            }

            .message-input-area {
                padding: 16px;
            }
        }

        @media (max-width: 576px) {
            .logo-icon {
                width: 38px;
                height: 38px;
            }
            
            .user-avatar-header {
                width: 40px;
                height: 40px;
            }
            
            .sidebar-tabs {
                padding: 16px;
            }
            
            .sidebar-tab span {
                display: none;
            }
            
            .sidebar-tab {
                padding: 10px;
            }
            
            .sidebar-tab i {
                font-size: 1.2rem;
                margin: 0;
            }
            
            .search-box {
                padding: 16px;
            }
            
            .list-item {
                padding: 12px;
            }
            
            .item-avatar {
                width: 48px;
                height: 48px;
                font-size: 1rem;
            }
            
            .send-btn {
                padding: 0 20px;
            }
            
            .send-btn span {
                display: none;
            }
        }
    
        /* ===== UNIFIED MOBILE RESPONSIVE (all pages) ===== */

        /* Header base - ensure flex layout */
        .header-wrapper { display: flex; flex-direction: column; }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.85rem 0;
            flex-wrap: nowrap;
            gap: 10px;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .language-selector {
            display: flex;
            gap: 4px;
            align-items: center;
            flex-wrap: nowrap;
        }
        .lang-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 8px;
            padding: 7px 11px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.82rem;
            min-width: 42px;
            text-align: center;
            white-space: nowrap;
        }
        .lang-btn:hover { background: rgba(255,255,255,0.2); transform: translateY(-1px); }
        .lang-btn.active { background: linear-gradient(135deg,#3a86ff,#8338ec); box-shadow: 0 4px 12px rgba(58,134,255,0.3); }

        /* Burger always hidden on desktop */
        .burger-menu { display: none; }

        /* Mobile nav always hidden on desktop */
        .mobile-nav { display: none; }

        /* === 992px breakpoint === */
        @media (max-width: 992px) {
            .header-nav { display: none !important; }
            .burger-menu {
                display: flex !important;
                flex-direction: column;
                cursor: pointer;
                padding: 9px;
                gap: 5px;
                background: rgba(255,255,255,0.1);
                border-radius: 8px;
                transition: all 0.3s ease;
                flex-shrink: 0;
            }
            .burger-menu:hover { background: rgba(255,255,255,0.15); }
            .burger-line { width: 22px; height: 3px; background: white; transition: all 0.3s ease; border-radius: 2px; }
            .burger-menu.active .burger-line:nth-child(1) { transform: rotate(45deg) translate(6px,6px); background: #ff006e; }
            .burger-menu.active .burger-line:nth-child(2) { opacity: 0; }
            .burger-menu.active .burger-line:nth-child(3) { transform: rotate(-45deg) translate(6px,-6px); background: #ff006e; }

            .mobile-nav {
                display: block !important;
                position: fixed;
                top: 62px;
                left: 0; right: 0;
                background: rgba(26,26,46,0.98);
                backdrop-filter: blur(20px);
                border-radius: 0 0 16px 16px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                z-index: 999;
                overflow: hidden;
                max-height: 0;
                transition: max-height 0.3s ease;
            }
            .mobile-nav.active { max-height: 520px; }
            .mobile-nav-tabs { display: flex; flex-direction: column; list-style: none; padding: 12px; }
            .mobile-nav-tab {
                padding: 13px 16px;
                font-weight: 500;
                color: rgba(248,249,250,0.8);
                display: flex;
                align-items: center;
                gap: 10px;
                border-radius: 8px;
                margin-bottom: 4px;
                font-size: 0.92rem;
                transition: all 0.3s ease;
            }
            .mobile-nav-tab:hover { color: white; background: rgba(255,255,255,0.06); }
            .mobile-nav-tab.active { color: white; background: rgba(255,255,255,0.09); border-left: 3px solid #ff006e; }
            .mobile-nav-tab i { font-size: 1rem; width: 20px; }
            .mobile-nav-link { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px; width: 100%; }

            main { margin-top: 60px !important; }

            .lang-btn { padding: 6px 9px; font-size: 0.78rem; min-width: 38px; }
            .header-right { gap: 8px; }
            .logo { font-size: 1.25rem !important; }
            .logo-icon { width: 36px !important; height: 36px !important; font-size: 1.05rem !important; }
        }

        /* === 768px breakpoint === */
        @media (max-width: 768px) {
            .container { padding: 0 14px; }
            .header-top { padding: 0.7rem 0; }

            .lang-btn { padding: 5px 8px; font-size: 0.74rem; min-width: 34px; }
            .header-right { gap: 6px; }

            .logo-text { display: inline !important; }
            .logo { font-size: 1.15rem !important; }

            .user-avatar, .user-avatar-header {
                width: 34px !important;
                height: 34px !important;
                font-size: 0.85rem !important;
            }

            .btn { padding: 7px 13px; font-size: 0.8rem; }

            .footer-content { grid-template-columns: 1fr !important; text-align: center; }
            .footer-section h3::after { left: 50% !important; transform: translateX(-50%) !important; }
            .social-links { justify-content: center !important; }

            .hero-section { padding: 45px 18px !important; }
            .hero-title { font-size: 1.85rem !important; }
            .hero-subtitle { font-size: 1rem !important; }
            .hero-buttons { flex-direction: column; align-items: center; }
            .hero-buttons .btn { width: 100%; max-width: 280px; justify-content: center; }

            .mission-title, .section-title { font-size: 1.7rem !important; }
        }

        /* === 576px breakpoint === */
        @media (max-width: 576px) {
            .container { padding: 0 12px; }
            .lang-btn { padding: 4px 6px; font-size: 0.7rem; min-width: 31px; }
            .language-selector { gap: 3px; }

            .hero-title { font-size: 1.55rem !important; }
            .hero-subtitle { font-size: 0.9rem !important; }

            .cities-grid { grid-template-columns: repeat(2, 1fr) !important; }
            .city-header { grid-template-columns: 1fr !important; }
            .city-image { height: 190px !important; }

            .card { padding: 18px !important; }
            .city-selector { padding: 18px !important; }

            .emergency-help { flex-direction: column !important; text-align: center !important; gap: 14px !important; }
            .emergency-icon { width: 58px !important; height: 58px !important; font-size: 1.4rem !important; }
        }

        /* === 400px breakpoint === */
        @media (max-width: 400px) {
            .lang-btn { padding: 3px 5px; font-size: 0.65rem; min-width: 28px; }
            .language-selector { gap: 2px; }
            .logo-text { display: none !important; }
            .header-right { gap: 5px; }
        }
        /* ===== END UNIFIED MOBILE RESPONSIVE ===== */
</style>
</head>
<body>
    <div class="messenger-header">
        <div class="header-left">
            <div class="burger-menu" id="burgerMenu">
                <div class="burger-line"></div>
                <div class="burger-line"></div>
                <div class="burger-line"></div>
            </div>
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <span class="logo-text">MigraSupport</span>
            </a>
        </div>
        <div class="header-right">
            <div class="language-selector">
                <button class="lang-btn <?php echo $lang === 'ru' ? 'active' : ''; ?>" data-lang="ru">RU</button>
                <button class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" data-lang="en">EN</button>
                <button class="lang-btn <?php echo $lang === 'pt' ? 'active' : ''; ?>" data-lang="pt">PT</button>
                <button class="lang-btn <?php echo $lang === 'fr' ? 'active' : ''; ?>" data-lang="fr">FR</button>
                <button class="lang-btn <?php echo $lang === 'de' ? 'active' : ''; ?>" data-lang="de">DE</button>
            </div>
            <div class="profile-dropdown">
                <div class="user-avatar-header" id="profileAvatar">
                    <?php if ($userAvatar): ?>
                        <img src="<?php echo $userAvatar; ?>" alt="<?php echo $userName; ?>">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="dropdown-menu" id="profileDropdown">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i> <?php echo t('Профиль', 'Profile', 'Perfil', 'Profil', 'Profil'); ?>
                    </a>
                    <a href="chat.php" class="dropdown-item">
                        <i class="fas fa-users"></i> <?php echo t('Городской чат', 'City Chat', 'Chat da Cidade', 'Chat de la Ville', 'Stadt-Chat'); ?>
                    </a>
                    <a href="logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i> <?php echo t('Выйти', 'Logout', 'Sair', 'Déconnexion', 'Abmelden'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="messenger-layout">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-tabs">
                <button class="sidebar-tab active" data-tab="chats">
                    <i class="fas fa-comment-dots"></i> <span><?php echo t('Чаты', 'Chats', 'Chats', 'Chats', 'Chats'); ?></span>
                </button>
                <button class="sidebar-tab" data-tab="favorites">
                    <i class="fas fa-star"></i> <span><?php echo t('Избранное', 'Favorites', 'Favoritos', 'Favoris', 'Favoriten'); ?></span>
                </button>
                <button class="sidebar-tab" data-tab="blocked">
                    <i class="fas fa-ban"></i> <span><?php echo t('Заблокированные', 'Blocked', 'Bloqueados', 'Bloqués', 'Gesperrt'); ?></span>
                </button>
            </div>

            <div class="search-box" id="searchBox">
                <input type="text" id="searchInput" placeholder="<?php echo t('Поиск чатов...', 'Search chats...', 'Buscar chats...', 'Rechercher des chats...', 'Chats suchen...'); ?>">
            </div>

            <div style="padding: 12px 24px; border-bottom: 1px solid rgba(255,255,255,0.08);">
                <button class="new-chat-btn" id="newChatBtn">
                    <i class="fas fa-plus-circle"></i> <?php echo t('Новый чат', 'New Chat', 'Novo Chat', 'Nouveau Chat', 'Neuer Chat'); ?>
                </button>
            </div>

            <div class="lists-container" id="listsContainer">
                <div class="empty-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p><?php echo t('Загрузка...', 'Loading...', 'Carregando...', 'Chargement...', 'Laden...'); ?></p>
                </div>
            </div>
        </div>

        <div class="chat-area" id="chatArea">
            <div class="empty-state">
                <i class="fas fa-comment-dots"></i>
                <h3><?php echo t('Выберите чат', 'Select a chat', 'Selecione um chat', 'Sélectionnez un chat', 'Wählen Sie einen Chat'); ?></h3>
                <p><?php echo t('Начните общение с другим пользователем', 'Start chatting with another user', 'Comece a conversar com outro usuário', 'Commencez à discuter avec un autre utilisateur', 'Beginnen Sie eine Unterhaltung mit einem anderen Benutzer'); ?></p>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="searchUsersModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> <?php echo t('Новый чат', 'New Chat', 'Novo Chat', 'Nouveau Chat', 'Neuer Chat'); ?></h3>
                <button class="modal-close" id="modalCloseBtn">&times;</button>
            </div>
            <div class="modal-search">
                <input type="text" id="modalSearchInput" placeholder="<?php echo t('Поиск пользователей...', 'Search users...', 'Buscar usuários...', 'Rechercher des utilisateurs...', 'Benutzer suchen...'); ?>" autocomplete="off">
            </div>
            <div class="modal-users-list" id="modalUsersList">
                <div class="empty-state" style="padding: 40px;">
                    <i class="fas fa-search"></i>
                    <p><?php echo t('Введите имя для поиска', 'Enter a name to search', 'Digite um nome para buscar', 'Entrez un nom pour rechercher', 'Geben Sie einen Namen zum Suchen ein'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo $userId; ?>;
        let currentChatId = null;
        let currentOtherUserId = null;
        let currentTab = 'chats';
        let chatsData = [];
        let currentSearchQuery = '';
        let searchUsersTimeout = null;

        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        function showNotification(message, isError = false) {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.style.background = isError ? 'var(--danger)' : 'var(--gradient-primary)';
            notification.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i> ${escapeHtml(message)}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100px)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function openUserProfile(userId) {
            if (userId && userId > 0) {
                window.location.href = `view_profile.php?id=${userId}`;
            }
        }

        async function searchUsers(query) {
            if (!query.trim()) {
                document.getElementById('modalUsersList').innerHTML = `
                    <div class="empty-state" style="padding: 40px;">
                        <i class="fas fa-search"></i>
                        <p><?php echo t('Введите имя для поиска', 'Enter a name to search', 'Digite um nome para buscar', 'Entrez un nom pour rechercher', 'Geben Sie einen Namen zum Suchen ein'); ?></p>
                    </div>
                `;
                return;
            }

            try {
                const response = await fetch(`personal_chats.php?action=search_users&query=${encodeURIComponent(query)}`);
                const users = await response.json();
                
                const container = document.getElementById('modalUsersList');
                
                if (!users || !users.length) {
                    container.innerHTML = `
                        <div class="empty-state" style="padding: 40px;">
                            <i class="fas fa-user-slash"></i>
                            <p><?php echo t('Пользователи не найдены', 'No users found', 'Nenhum usuário encontrado', 'Aucun utilisateur trouvé', 'Keine Benutzer gefunden'); ?></p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = users.map(user => `
                    <div class="modal-user-item">
                        <div class="modal-user-avatar" onclick="openUserProfile(${user.id})" style="cursor: pointer;">
                            ${user.avatar ? 
                                `<img src="${escapeHtml(user.avatar)}" alt="${escapeHtml(user.name)}">` : 
                                `<span>${escapeHtml((user.first_name || user.name || '?').charAt(0))}</span>`
                            }
                            ${user.is_online ? '<div class="online-dot" style="width: 10px; height: 10px;"></div>' : ''}
                        </div>
                        <div class="modal-user-info" onclick="openUserProfile(${user.id})" style="cursor: pointer;">
                            <div class="modal-user-name">${escapeHtml(user.name)}</div>
                            <div class="modal-user-status ${user.is_online ? 'online' : ''}">
                                ${user.is_online ? '🟢 <?php echo t("В сети", "Online", "Online", "En ligne", "Online"); ?>' : '⚫ <?php echo t("Не в сети", "Offline", "Offline", "Hors ligne", "Offline"); ?>'}
                                ${user.has_chat ? ' • <i class="fas fa-comment"></i> <?php echo t("Чат уже существует", "Chat already exists", "Chat já existe", "Chat existe déjà", "Chat existiert bereits"); ?>' : ''}
                            </div>
                        </div>
                        <button class="modal-user-btn" onclick="startChatWithUser(${user.id}, event)" ${user.is_blocked_by_me || user.is_blocked_by_other ? 'disabled' : ''}>
                            ${user.has_chat ? '<i class="fas fa-comment"></i>' : '<i class="fas fa-plus"></i>'} 
                            ${user.has_chat ? '<?php echo t("Открыть", "Open", "Abrir", "Ouvrir", "Öffnen"); ?>' : '<?php echo t("Начать", "Start", "Iniciar", "Démarrer", "Starten"); ?>'}
                        </button>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Error searching users:', error);
                document.getElementById('modalUsersList').innerHTML = `
                    <div class="empty-state" style="padding: 40px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p><?php echo t('Ошибка поиска', 'Search error', 'Erro de busca', 'Erreur de recherche', 'Suchfehler'); ?></p>
                    </div>
                `;
            }
        }

        async function startChatWithUser(otherUserId, event) {
            if (event) event.stopPropagation();
            
            try {
                const formData = new FormData();
                formData.append('action', 'create_chat');
                formData.append('other_user_id', otherUserId);
                
                const response = await fetch('personal_chats.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('searchUsersModal').classList.remove('show');
                    
                    const userResponse = await fetch(`personal_chats.php?action=get_user_info&user_id=${otherUserId}`);
                    const userInfo = await userResponse.json();
                    
                    if (userInfo && !userInfo.error) {
                        selectChat(result.chat_id, otherUserId, userInfo.name, userInfo.avatar || '', userInfo.is_online ? 1 : 0);
                        document.querySelector('.sidebar-tab[data-tab="chats"]').click();
                        loadChats();
                    }
                } else {
                    showNotification(result.error || '<?php echo t("Ошибка создания чата", "Error creating chat", "Erro ao criar chat", "Erreur lors de la création du chat", "Fehler beim Erstellen des Chats"); ?>', true);
                }
            } catch (error) {
                console.error('Error creating chat:', error);
                showNotification('<?php echo t("Ошибка создания чата", "Error creating chat", "Erro ao criar chat", "Erreur lors de la création du chat", "Fehler beim Erstellen des Chats"); ?>', true);
            }
        }

        function openSearchUsersModal() {
            const modal = document.getElementById('searchUsersModal');
            const searchInput = document.getElementById('modalSearchInput');
            
            modal.classList.add('show');
            searchInput.value = '';
            searchInput.focus();
            
            document.getElementById('modalUsersList').innerHTML = `
                <div class="empty-state" style="padding: 40px;">
                    <i class="fas fa-search"></i>
                    <p><?php echo t('Введите имя для поиска', 'Enter a name to search', 'Digite um nome para buscar', 'Entrez un nom pour rechercher', 'Geben Sie einen Namen zum Suchen ein'); ?></p>
                </div>
            `;
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.addEventListener('click', () => changeLanguage(btn.dataset.lang));
            });
            
            const profileAvatar = document.getElementById('profileAvatar');
            const profileDropdown = document.getElementById('profileDropdown');
            
            if (profileAvatar && profileDropdown) {
                profileAvatar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });
                
                document.addEventListener('click', function() {
                    profileDropdown.classList.remove('show');
                });
            }
            
            const burgerMenu = document.getElementById('burgerMenu');
            const sidebar = document.getElementById('sidebar');
            
            if (burgerMenu && sidebar) {
                burgerMenu.addEventListener('click', function() {
                    this.classList.toggle('active');
                    sidebar.classList.toggle('open');
                });
                
                document.addEventListener('click', function(event) {
                    if (!burgerMenu.contains(event.target) && !sidebar.contains(event.target)) {
                        burgerMenu.classList.remove('active');
                        sidebar.classList.remove('open');
                    }
                });
            }
            
            const newChatBtn = document.getElementById('newChatBtn');
            if (newChatBtn) {
                newChatBtn.addEventListener('click', openSearchUsersModal);
            }
            
            const modalCloseBtn = document.getElementById('modalCloseBtn');
            if (modalCloseBtn) {
                modalCloseBtn.addEventListener('click', () => {
                    document.getElementById('searchUsersModal').classList.remove('show');
                });
            }
            
            const modal = document.getElementById('searchUsersModal');
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.classList.remove('show');
                    }
                });
            }
            
            const modalSearchInput = document.getElementById('modalSearchInput');
            if (modalSearchInput) {
                modalSearchInput.addEventListener('input', (e) => {
                    clearTimeout(searchUsersTimeout);
                    searchUsersTimeout = setTimeout(() => {
                        searchUsers(e.target.value);
                    }, 300);
                });
                
                modalSearchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        clearTimeout(searchUsersTimeout);
                        searchUsers(e.target.value);
                    }
                });
            }
            
            initTabs();
            loadChats();
            
            const startChatWith = <?php echo $startChatWith; ?>;
            if (startChatWith) {
                setTimeout(() => createAndOpenChat(startChatWith), 500);
            }
        });

        function initTabs() {
            const tabs = document.querySelectorAll('.sidebar-tab');
            const searchBox = document.getElementById('searchBox');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    currentTab = tab.dataset.tab;
                    currentSearchQuery = '';
                    
                    if (currentTab === 'chats') {
                        searchBox.style.display = 'block';
                        document.getElementById('searchInput').value = '';
                        filterChats('');
                    } else if (currentTab === 'favorites') {
                        searchBox.style.display = 'none';
                        loadFavorites();
                    } else if (currentTab === 'blocked') {
                        searchBox.style.display = 'none';
                        loadBlocked();
                    }
                });
            });
            
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (currentTab === 'chats') {
                        currentSearchQuery = searchInput.value;
                        filterChats(currentSearchQuery);
                    }
                }, 300);
            });
        }

        async function loadChats() {
            try {
                const response = await fetch('personal_chats.php?action=get_chats');
                const data = await response.json();
                if (Array.isArray(data)) {
                    chatsData = data;
                    if (currentTab === 'chats') {
                        filterChats(currentSearchQuery);
                    }
                }
            } catch (error) {
                console.error('Error loading chats:', error);
            }
        }

        function filterChats(query) {
            if (!query.trim()) {
                displayChats(chatsData);
                return;
            }
            const filtered = chatsData.filter(chat => 
                chat.other_user_name.toLowerCase().includes(query.toLowerCase())
            );
            displayChats(filtered);
        }

        function displayChats(chats) {
            const container = document.getElementById('listsContainer');
            
            if (!chats || chats.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3><?php echo t('Нет активных чатов', 'No active chats', 'Nenhum chat ativo', 'Aucun chat actif', 'Keine aktiven Chats'); ?></h3>
                        <p><?php echo t('Нажмите "Новый чат" чтобы начать общение', 'Click "New Chat" to start chatting', 'Clique em "Novo Chat" para começar', 'Cliquez sur "Nouveau Chat" pour commencer', 'Klicken Sie auf "Neuer Chat", um zu beginnen'); ?></p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = chats.map(chat => `
                <div class="list-item ${currentChatId == chat.chat_id ? 'active' : ''}" onclick="selectChat(${chat.chat_id}, ${chat.other_user_id}, '${escapeHtml(chat.other_user_name)}', '${escapeHtml(chat.other_user_avatar || '')}', ${chat.is_online ? 1 : 0})">
                    <div class="item-avatar">
                        ${chat.other_user_avatar ? 
                            `<img src="${escapeHtml(chat.other_user_avatar)}" alt="${escapeHtml(chat.other_user_name)}">` : 
                            `<span>${escapeHtml(chat.other_user_first_name?.charAt(0) || '?')}</span>`
                        }
                        ${chat.is_online ? '<div class="online-dot"></div>' : ''}
                    </div>
                    <div class="item-info">
                        <div class="item-name">
                            ${escapeHtml(chat.other_user_name)}
                            ${chat.is_favorite ? '<i class="fas fa-star favorite-star"></i>' : ''}
                            ${chat.is_blocked_by_me ? '<i class="fas fa-ban" style="color: #ef4444; font-size: 0.7rem;"></i>' : ''}
                        </div>
                        <div class="item-preview">${escapeHtml(chat.last_message_text || '<?php echo t("Нет сообщений", "No messages", "Sem mensagens", "Aucun message", "Keine Nachrichten"); ?>')}</div>
                    </div>
                    <div class="item-right">
                        <div class="item-time">${formatTime(chat.last_message_time)}</div>
                        ${chat.unread_count > 0 ? `<div class="unread-badge">${chat.unread_count}</div>` : ''}
                    </div>
                </div>
            `).join('');
        }

        async function loadFavorites() {
            try {
                const response = await fetch('personal_chats.php?action=get_favorites');
                const chats = await response.json();
                const container = document.getElementById('listsContainer');
                
                if (!chats || !chats.length) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-star"></i>
                            <h3><?php echo t('Нет избранных чатов', 'No favorite chats', 'Nenhum chat favorito', 'Aucun chat favori', 'Keine Favoriten-Chats'); ?></h3>
                            <p><?php echo t('Добавьте чаты в избранное, нажав на звездочку', 'Add chats to favorites by clicking the star', 'Adicione chats aos favoritos clicando na estrela', 'Ajoutez des chats aux favoris en cliquant sur l\'étoile', 'Fügen Sie Chats zu Favoriten hinzu, indem Sie auf den Stern klicken'); ?></p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = chats.map(chat => `
                    <div class="list-item" onclick="selectChat(${chat.chat_id}, ${chat.other_user_id}, '${escapeHtml(chat.other_user_name)}', '${escapeHtml(chat.other_user_avatar || '')}', ${chat.is_online ? 1 : 0})">
                        <div class="item-avatar">
                            ${chat.other_user_avatar ? 
                                `<img src="${escapeHtml(chat.other_user_avatar)}" alt="${escapeHtml(chat.other_user_name)}">` : 
                                `<span>${escapeHtml(chat.other_user_first_name?.charAt(0) || '?')}</span>`
                            }
                            ${chat.is_online ? '<div class="online-dot"></div>' : ''}
                        </div>
                        <div class="item-info">
                            <div class="item-name">
                                ${escapeHtml(chat.other_user_name)}
                                <i class="fas fa-star favorite-star"></i>
                            </div>
                            <div class="item-preview">${escapeHtml(chat.last_message_text || '<?php echo t("Нет сообщений", "No messages", "Sem mensagens", "Aucun message", "Keine Nachrichten"); ?>')}</div>
                        </div>
                        <div class="item-right">
                            <div class="item-time">${formatDate(chat.favorite_since)}</div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Error loading favorites:', error);
            }
        }

        async function loadBlocked() {
            try {
                const response = await fetch('personal_chats.php?action=get_blocked');
                const users = await response.json();
                const container = document.getElementById('listsContainer');
                
                if (!users || !users.length) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-ban"></i>
                            <h3><?php echo t('Нет заблокированных пользователей', 'No blocked users', 'Nenhum usuário bloqueado', 'Aucun utilisateur bloqué', 'Keine blockierten Benutzer'); ?></h3>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = users.map(user => `
                    <div class="list-item">
                        <div class="item-avatar" onclick="openUserProfile(${user.id})" style="cursor: pointer;">
                            ${user.avatar ? 
                                `<img src="${escapeHtml(user.avatar)}" alt="${escapeHtml(user.name)}">` : 
                                `<span>${escapeHtml((user.first_name || user.name || '?').charAt(0))}</span>`
                            }
                        </div>
                        <div class="item-info" onclick="openUserProfile(${user.id})" style="cursor: pointer;">
                            <div class="item-name">${escapeHtml(user.name)}</div>
                            <div class="item-preview"><?php echo t('Заблокирован', 'Blocked', 'Bloqueado', 'Bloqué', 'Gesperrt'); ?>: ${formatDate(user.blocked_since)}</div>
                        </div>
                        <div class="item-right">
                            <button class="icon-btn" onclick="unblockUser(${user.id}, event)" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border-color: rgba(16, 185, 129, 0.3);">
                                <i class="fas fa-unlock-alt"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Error loading blocked users:', error);
            }
        }

        async function createAndOpenChat(otherUserId, event) {
            if (event) event.stopPropagation();
            
            try {
                const formData = new FormData();
                formData.append('action', 'create_chat');
                formData.append('other_user_id', otherUserId);
                
                const response = await fetch('personal_chats.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const userResponse = await fetch(`personal_chats.php?action=get_user_info&user_id=${otherUserId}`);
                    const userInfo = await userResponse.json();
                    
                    if (userInfo && !userInfo.error) {
                        selectChat(result.chat_id, otherUserId, userInfo.name, userInfo.avatar || '', userInfo.is_online ? 1 : 0);
                        document.querySelector('.sidebar-tab[data-tab="chats"]').click();
                        loadChats();
                    }
                } else {
                    showNotification(result.error || '<?php echo t("Ошибка создания чата", "Error creating chat", "Erro ao criar chat", "Erreur lors de la création du chat", "Fehler beim Erstellen des Chats"); ?>', true);
                }
            } catch (error) {
                console.error('Error creating chat:', error);
                showNotification('<?php echo t("Ошибка создания чата", "Error creating chat", "Erro ao criar chat", "Erreur lors de la création du chat", "Fehler beim Erstellen des Chats"); ?>', true);
            }
        }

        async function selectChat(chatId, otherUserId, otherUserName, otherUserAvatar, isOnline) {
            currentChatId = chatId;
            currentOtherUserId = otherUserId;
            
            document.querySelectorAll('.list-item').forEach(item => {
                item.classList.remove('active');
            });
            
            const chatArea = document.getElementById('chatArea');
            chatArea.style.display = 'flex';
            chatArea.innerHTML = `
                <div class="chat-area-header">
                    <div class="chat-user-info">
                        <img class="chat-user-avatar" src="${escapeHtml(otherUserAvatar) || 'https://ui-avatars.com/api/?background=6366f1&color=fff&name=' + encodeURIComponent(otherUserName)}" alt="${escapeHtml(otherUserName)}" onclick="openUserProfile(${otherUserId})">
                        <div>
                            <div class="chat-user-name">${escapeHtml(otherUserName)}</div>
                            <div class="chat-status ${isOnline ? 'online' : ''}">${isOnline ? '🟢 <?php echo t("В сети", "Online", "Online", "En ligne", "Online"); ?>' : '⚫ <?php echo t("Не в сети", "Offline", "Offline", "Hors ligne", "Offline"); ?>'}</div>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <button class="icon-btn" id="favoriteChatBtn" onclick="toggleFavoriteChat()" title="<?php echo t('В избранное', 'Add to favorites', 'Adicionar aos favoritos', 'Ajouter aux favoris', 'Zu Favoriten hinzufügen'); ?>">
                            <i class="far fa-star"></i>
                        </button>
                        <button class="icon-btn" id="blockChatBtn" onclick="toggleBlockUser()" title="<?php echo t('Заблокировать', 'Block', 'Bloquear', 'Bloquer', 'Sperren'); ?>">
                            <i class="fas fa-ban"></i>
                        </button>
                    </div>
                </div>
                <div class="messages-container" id="messagesContainer">
                    <div class="empty-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p><?php echo t('Загрузка сообщений...', 'Loading messages...', 'Carregando mensagens...', 'Chargement des messages...', 'Nachrichten werden geladen...'); ?></p>
                    </div>
                </div>
                <div class="message-input-area">
                    <textarea class="message-input" id="messageInput" placeholder="<?php echo t('Введите сообщение...', 'Type a message...', 'Digite uma mensagem...', 'Tapez un message...', 'Nachricht eingeben...'); ?>" rows="2" maxlength="1000"></textarea>
                    <button class="send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i> <span><?php echo t('Отправить', 'Send', 'Enviar', 'Envoyer', 'Senden'); ?></span>
                    </button>
                </div>
            `;
            
            await updateFavoriteButtonStatus();
            await markMessagesAsRead(chatId);
            await loadMessages(chatId);
            
            const sendBtn = document.getElementById('sendBtn');
            const messageInput = document.getElementById('messageInput');
            
            if (sendBtn && messageInput) {
                const newSendBtn = sendBtn.cloneNode(true);
                sendBtn.parentNode.replaceChild(newSendBtn, sendBtn);
                newSendBtn.addEventListener('click', () => sendMessage());
                
                messageInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
            
            if (window.innerWidth <= 992) {
                document.getElementById('sidebar').classList.remove('open');
                document.getElementById('burgerMenu').classList.remove('active');
            }
        }

        async function updateFavoriteButtonStatus() {
            try {
                const response = await fetch('personal_chats.php?action=get_chats');
                const chats = await response.json();
                const chat = chats.find(c => c.chat_id == currentChatId);
                const favoriteBtn = document.getElementById('favoriteChatBtn');
                if (favoriteBtn) {
                    if (chat?.is_favorite) {
                        favoriteBtn.innerHTML = '<i class="fas fa-star" style="color: #f59e0b;"></i>';
                        favoriteBtn.title = '<?php echo t("Удалить из избранного", "Remove from favorites", "Remover dos favoritos", "Retirer des favoris", "Aus Favoriten entfernen"); ?>';
                    } else {
                        favoriteBtn.innerHTML = '<i class="far fa-star"></i>';
                        favoriteBtn.title = '<?php echo t("В избранное", "Add to favorites", "Adicionar aos favoritos", "Ajouter aux favoris", "Zu Favoriten hinzufügen"); ?>';
                    }
                }
            } catch (error) {
                console.error('Error updating favorite button:', error);
            }
        }

        async function toggleFavoriteChat() {
            if (!currentChatId) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_favorite');
                formData.append('chat_id', currentChatId);
                
                const response = await fetch('personal_chats.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    await updateFavoriteButtonStatus();
                    await loadChats();
                    
                    if (currentTab === 'chats') {
                        filterChats(currentSearchQuery);
                    } else if (currentTab === 'favorites') {
                        await loadFavorites();
                    }
                    
                    if (result.is_favorite) {
                        showNotification('<?php echo t("Чат добавлен в избранное", "Chat added to favorites", "Chat adicionado aos favoritos", "Chat ajouté aux favoris", "Chat zu Favoriten hinzugefügt"); ?>');
                    } else {
                        showNotification('<?php echo t("Чат удален из избранного", "Chat removed from favorites", "Chat removido dos favoritos", "Chat retiré des favoris", "Chat aus Favoriten entfernt"); ?>');
                    }
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
                showNotification('Ошибка', true);
            }
        }

        async function toggleBlockUser() {
            if (!currentOtherUserId) return;
            
            if (!confirm('<?php echo t("Вы уверены, что хотите заблокировать этого пользователя?", "Are you sure you want to block this user?", "Tem certeza de que deseja bloquear este usuário?", "Êtes-vous sûr de vouloir bloquer cet utilisateur?", "Sind Sie sicher, dass Sie diesen Benutzer blockieren möchten?"); ?>')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_block');
                formData.append('user_id', currentOtherUserId);
                
                const response = await fetch('personal_chats.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.is_blocked) {
                        showNotification('<?php echo t("Пользователь заблокирован", "User blocked", "Usuário bloqueado", "Utilisateur bloqué", "Benutzer gesperrt"); ?>');
                    } else {
                        showNotification('<?php echo t("Пользователь разблокирован", "User unblocked", "Usuário desbloqueado", "Utilisateur débloqué", "Benutzer entsperrt"); ?>');
                    }
                    await loadChats();
                    if (currentTab === 'chats') {
                        filterChats(currentSearchQuery);
                    } else if (currentTab === 'blocked') {
                        await loadBlocked();
                    }
                } else {
                    showNotification(result.error || 'Ошибка', true);
                }
            } catch (error) {
                console.error('Error toggling block:', error);
                showNotification('Ошибка', true);
            }
        }

        async function unblockUser(userId, event) {
            if (event) event.stopPropagation();
            
            if (!confirm('<?php echo t("Разблокировать пользователя?", "Unblock user?", "Desbloquear usuário?", "Débloquer l\'utilisateur?", "Benutzer entsperren?"); ?>')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_block');
                formData.append('user_id', userId);
                
                const response = await fetch('personal_chats.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('<?php echo t("Пользователь разблокирован", "User unblocked", "Usuário desbloqueado", "Utilisateur débloqué", "Benutzer entsperrt"); ?>');
                    await loadChats();
                    if (currentTab === 'blocked') {
                        await loadBlocked();
                    } else if (currentTab === 'chats') {
                        filterChats(currentSearchQuery);
                    }
                } else {
                    showNotification(result.error || 'Ошибка', true);
                }
            } catch (error) {
                console.error('Error unblocking user:', error);
                showNotification('Ошибка', true);
            }
        }

        async function markMessagesAsRead(chatId) {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('chat_id', chatId);
                
                await fetch('personal_chats.php', {
                    method: 'POST',
                    body: formData
                });
                
                await loadChats();
                if (currentTab === 'chats') {
                    filterChats(currentSearchQuery);
                }
            } catch (error) {
                console.error('Error marking messages as read:', error);
            }
        }

        async function loadMessages(chatId) {
            try {
                const response = await fetch(`personal_chats.php?action=get_messages&chat_id=${chatId}`);
                const messages = await response.json();
                
                if (Array.isArray(messages)) {
                    displayMessages(messages);
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        function displayMessages(messages) {
            const container = document.getElementById('messagesContainer');
            
            if (!messages || messages.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-comment-dots"></i>
                        <h3><?php echo t('Нет сообщений', 'No messages', 'Sem mensagens', 'Aucun message', 'Keine Nachrichten'); ?></h3>
                        <p><?php echo t('Начните диалог!', 'Start the conversation!', 'Comece a conversa!', 'Commencez la conversation!', 'Beginnen Sie das Gespräch!'); ?></p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.sender_id == currentUserId ? 'sent' : 'received'}">
                    <div class="message-text">${escapeHtml(msg.message_text)}</div>
                    <div class="message-time">${new Date(msg.created_at).toLocaleString()}</div>
                </div>
            `).join('');
            
            container.scrollTop = container.scrollHeight;
        }

        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message || !currentChatId) return;
            
            input.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('chat_id', currentChatId);
                formData.append('message', message);
                
                const response = await fetch('personal_chats.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    input.value = '';
                    await loadMessages(currentChatId);
                    await loadChats();
                    if (currentTab === 'chats') {
                        filterChats(currentSearchQuery);
                    }
                } else {
                    showNotification(result.error || 'Ошибка отправки', true);
                }
            } catch (error) {
                console.error('Error sending message:', error);
                showNotification('Ошибка отправки сообщения', true);
            } finally {
                input.disabled = false;
                input.focus();
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            const now = new Date();
            if (date.toDateString() === now.toDateString()) {
                return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }
            return date.toLocaleDateString([], {day:'2-digit', month:'2-digit'});
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString([], {day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit'});
        }
    </script>
</body>
</html>