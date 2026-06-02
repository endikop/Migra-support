<?php
session_start();
require_once '../src/config/config.php';

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$city = $_GET['city'] ?? 'minsk';

try {
    $stmt = $pdo->prepare("
        SELECT ccm.*, u.first_name, u.last_name, u.avatar,
               CONCAT(u.first_name, ' ', u.last_name) as sender_name,
               DATE_FORMAT(ccm.created_at, '%d.%m.%Y %H:%i') as created_at
        FROM city_chat_messages ccm 
        JOIN users u ON ccm.sender_id = u.id 
        WHERE ccm.city = ? 
        ORDER BY ccm.created_at ASC
        LIMIT 100
    ");
    $stmt->execute([$city]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($messages);
    
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
