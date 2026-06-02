<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'РќРµРѕР±С…РѕРґРёРјР° Р°РІС‚РѕСЂРёР·Р°С†РёСЏ']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_blocked':
            // РџРѕР»СѓС‡РµРЅРёРµ СЃРїРёСЃРєР° Р·Р°Р±Р»РѕРєРёСЂРѕРІР°РЅРЅС‹С… РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№
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
                WHERE ub.blocker_id = ?
                ORDER BY ub.created_at DESC
            ");
            $stmt->execute([$userId]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($users);
            break;

        default:
            echo json_encode(['error' => 'РќРµРёР·РІРµСЃС‚РЅРѕРµ РґРµР№СЃС‚РІРёРµ']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Blocked users API error: " . $e->getMessage());
    echo json_encode(['error' => 'РћС€РёР±РєР° Р±Р°Р·С‹ РґР°РЅРЅС‹С…']);
}
?>
