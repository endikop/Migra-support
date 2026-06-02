<?php
session_start();
require_once '../src/config/config.php';

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // РџРѕР»СѓС‡Р°РµРј РїРѕСЃР»РµРґРЅРёР№ Р°РєС‚РёРІРЅС‹Р№ С‡Р°С‚ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
    $stmt = $pdo->prepare("
        SELECT id FROM chats 
        WHERE migrant_id = ? 
        ORDER BY updated_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $chat = $stmt->fetch();
    
    $messages = [];
    
    if ($chat) {
        $chat_id = $chat['id'];
        
        // РџРѕР»СѓС‡Р°РµРј РІСЃРµ СЃРѕРѕР±С‰РµРЅРёСЏ С‡Р°С‚Р°
        $stmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name,
                   CONCAT(u.first_name, ' ', u.last_name) as sender_name,
                   DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') as created_at
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE m.chat_id = ? 
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$chat_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($messages);
    
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
