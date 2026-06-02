<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'save_translation':
            $sourceText = $_POST['source_text'] ?? '';
            $translatedText = $_POST['translated_text'] ?? '';
            $sourceLang = $_POST['source_lang'] ?? '';
            $targetLang = $_POST['target_lang'] ?? '';
            
            if (empty($sourceText) || empty($translatedText)) {
                echo json_encode(['success' => false, 'message' => 'Missing data']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO translation_history (user_id, source_text, translated_text, source_lang, target_lang, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $sourceText, $translatedText, $sourceLang, $targetLang]);
            
            $id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'translation' => [
                    'id' => $id,
                    'source_text' => $sourceText,
                    'translated_text' => $translatedText,
                    'source_lang' => $sourceLang,
                    'target_lang' => $targetLang,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
            break;
            
        case 'get_translation':
            $id = $_POST['id'] ?? 0;
            
            $stmt = $pdo->prepare("SELECT * FROM translation_history WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $translation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($translation) {
                echo json_encode(['success' => true, 'translation' => $translation]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Translation not found']);
            }
            break;
            
        case 'delete_translation':
            $id = $_POST['id'] ?? 0;
            
            $stmt = $pdo->prepare("DELETE FROM translation_history WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'clear_history':
            $stmt = $pdo->prepare("DELETE FROM translation_history WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
