<?php
// Включаем буферизацию вывода ДО любого кода
if (ob_get_level() == 0) {
    ob_start();
}

session_start();
require_once '../src/config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'РќРµРѕР±С…РѕРґРёРјР° Р°РІС‚РѕСЂРёР·Р°С†РёСЏ']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_favorites':
            // РџРѕР»СѓС‡РµРЅРёРµ СЃРїРёСЃРєР° РёР·Р±СЂР°РЅРЅС‹С… С‡Р°С‚РѕРІ
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
                WHERE fc.user_id = ?
                    AND pc.is_active = TRUE
                ORDER BY fc.created_at DESC
            ");
            $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
            $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($chats);
            break;

        default:
            echo json_encode(['error' => 'РќРµРёР·РІРµСЃС‚РЅРѕРµ РґРµР№СЃС‚РІРёРµ']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Favorite chats API error: " . $e->getMessage());
    echo json_encode(['error' => 'РћС€РёР±РєР° Р±Р°Р·С‹ РґР°РЅРЅС‹С…']);
}
?>
