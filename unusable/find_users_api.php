<?php
session_start();
require_once '../src/config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'РќРµРѕР±С…РѕРґРёРјР° Р°РІС‚РѕСЂРёР·Р°С†РёСЏ']);
    exit;
}

$userId = $_SESSION['user_id'];
$query = $_GET['query'] ?? '';

try {
    // Р‘Р°Р·РѕРІС‹Р№ Р·Р°РїСЂРѕСЃ РґР»СЏ РїРѕРёСЃРєР° РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№
    $sql = "
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            CONCAT(u.first_name, ' ', u.last_name) as name,
            u.avatar,
            u.city,
            c.name as city_name,
            (u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as is_online,
            EXISTS(
                SELECT 1 FROM user_blocks 
                WHERE blocker_id = ? AND blocked_id = u.id
            ) as is_blocked_by_me,
            EXISTS(
                SELECT 1 FROM user_blocks 
                WHERE blocker_id = u.id AND blocked_id = ?
            ) as is_blocked_by_other
        FROM users u
        LEFT JOIN cities c ON u.city = c.id
        WHERE u.id != ?
            AND u.is_active = TRUE
            AND NOT EXISTS(
                SELECT 1 FROM user_blocks 
                WHERE (blocker_id = ? AND blocked_id = u.id) 
                   OR (blocker_id = u.id AND blocked_id = ?)
            )
    ";
    
    $params = [$userId, $userId, $userId, $userId, $userId];
    
    // Р”РѕР±Р°РІР»СЏРµРј РїРѕРёСЃРє РїРѕ РёРјРµРЅРё, РµСЃР»Рё РµСЃС‚СЊ Р·Р°РїСЂРѕСЃ
    if (!empty($query)) {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
        $searchTerm = "%{$query}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " ORDER BY u.first_name, u.last_name LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($users);
    
} catch (PDOException $e) {
    error_log("Find users API error: " . $e->getMessage());
    echo json_encode(['error' => 'РћС€РёР±РєР° Р±Р°Р·С‹ РґР°РЅРЅС‹С…']);
}
?>
