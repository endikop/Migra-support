<?php
/**
 * Система запрещенных слов (бан-ворды)
 */

// Функция для проверки и цензурирования текста
function censorText($text, $pdo = null) {
    if (empty($text)) {
        return $text;
    }
    
    // Получаем список активных запрещенных слов
    $bannedWords = getBannedWords($pdo);
    
    $originalText = $text;
    $censored = false;
    $foundWords = [];
    
    foreach ($bannedWords as $wordData) {
        $word = $wordData['word'];
        $replacement = $wordData['replacement'];
        
        // Используем регулярное выражение для поиска слова с учетом регистра
        $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
        
        if (preg_match($pattern, $text)) {
            // Заменяем слово
            $text = preg_replace($pattern, $replacement, $text);
            $foundWords[] = [
                'word' => $word,
                'replacement' => $replacement,
                'severity' => $wordData['severity']
            ];
            $censored = true;
        }
    }
    
    return [
        'text' => $text,
        'censored' => $censored,
        'found_words' => $foundWords,
        'original_text' => $originalText
    ];
}

// Функция для получения списка запрещенных слов
function getBannedWords($pdo = null) {
    static $cachedWords = null;
    
    // Если слова уже закэшированы, возвращаем их
    if ($cachedWords !== null) {
        return $cachedWords;
    }
    
    if (!$pdo) {
        global $pdo;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, word, replacement, severity 
            FROM banned_words 
            WHERE is_active = TRUE 
            ORDER BY severity DESC, word ASC
        ");
        $stmt->execute();
        $cachedWords = $stmt->fetchAll();
        return $cachedWords;
    } catch (PDOException $e) {
        // В случае ошибки возвращаем пустой массив
        return [];
    }
}

// Функция для добавления запрещенного слова
function addBannedWord($word, $replacement = '***', $severity = 'medium', $createdBy = null, $pdo = null) {
    if (!$pdo) {
        global $pdo;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO banned_words (word, replacement, severity, created_by) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                replacement = VALUES(replacement),
                severity = VALUES(severity),
                is_active = TRUE,
                updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$word, $replacement, $severity, $createdBy]);
    } catch (PDOException $e) {
        return false;
    }
}

// Функция для удаления/деактивации запрещенного слова
function removeBannedWord($wordId, $pdo = null) {
    if (!$pdo) {
        global $pdo;
    }
    
    try {
        // Деактивируем слово вместо удаления
        $stmt = $pdo->prepare("UPDATE banned_words SET is_active = FALSE WHERE id = ?");
        return $stmt->execute([$wordId]);
    } catch (PDOException $e) {
        return false;
    }
}

// Функция для логирования цензуры
function logCensorship($userId, $messageId, $originalText, $censoredText, $bannedWordId, $action = 'censored', $pdo = null) {
    if (!$pdo) {
        global $pdo;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO censorship_logs 
            (user_id, message_id, original_text, censored_text, banned_word_id, action_taken) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $messageId, $originalText, $censoredText, $bannedWordId, $action]);
    } catch (PDOException $e) {
        return false;
    }
}

// Функция для проверки текста перед отправкой (использовать в чатах)
function checkAndCensorMessage($text, $userId = null, $messageId = null, $pdo = null) {
    $result = censorText($text, $pdo);
    
    // Логируем если текст был подвергнут цензуре
    if ($result['censored'] && $userId && $pdo) {
        foreach ($result['found_words'] as $wordInfo) {
            // Находим ID запрещенного слова
            $wordStmt = $pdo->prepare("SELECT id FROM banned_words WHERE word = ? AND is_active = TRUE");
            $wordStmt->execute([$wordInfo['word']]);
            $bannedWord = $wordStmt->fetch();
            
            if ($bannedWord) {
                logCensorship($userId, $messageId, $result['original_text'], 
                            $result['text'], $bannedWord['id'], 'censored', $pdo);
            }
        }
    }
    
    return $result;
}
?>