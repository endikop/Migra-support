<?php
/**
 * Простая система цензуры для чата
 */

/**
 * Цензурирует текст с использованием запрещенных слов
 * @param string $text Исходный текст
 * @return string Текст с цензурой
 */
function censorText($text) {
    global $pdo;
    
    if (empty($text)) {
        return $text;
    }
    
    try {
        // Проверяем, существует ли таблица banned_words
        $stmt = $pdo->query("SHOW TABLES LIKE 'banned_words'");
        if ($stmt->rowCount() == 0) {
            // Таблица не существует, возвращаем исходный текст
            return $text;
        }
        
        // Получаем список активных запрещенных слов
        $stmt = $pdo->prepare("SELECT word, replacement FROM banned_words WHERE is_active = 1");
        $stmt->execute();
        $bannedWords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Если ошибка, возвращаем исходный текст
        error_log("Ошибка при получении запрещенных слов: " . $e->getMessage());
        return $text;
    }
    
    if (empty($bannedWords)) {
        return $text;
    }
    
    $censoredText = $text;
    
    foreach ($bannedWords as $wordData) {
        $word = trim($wordData['word']);
        $replacement = !empty($wordData['replacement']) ? $wordData['replacement'] : '***';
        
        if (empty($word)) {
            continue;
        }
        
        // Для UTF-8 используем модификатор u
        // Заменяем слово как отдельное или как часть другого слова
        $pattern = '/' . preg_quote($word, '/') . '/iu';
        $censoredText = preg_replace($pattern, $replacement, $censoredText);
    }
    
    return $censoredText;
}

/**
 * Проверяет текст на наличие запрещенных слов
 * @param string $text Текст для проверки
 * @return bool true если содержит запрещенные слова, false если нет
 */
function containsBannedWords($text) {
    global $pdo;
    
    if (empty($text)) {
        return false;
    }
    
    try {
        // Проверяем, существует ли таблица banned_words
        $stmt = $pdo->query("SHOW TABLES LIKE 'banned_words'");
        if ($stmt->rowCount() == 0) {
            // Таблица не существует, считаем что запрещенных слов нет
            return false;
        }
        
        // Получаем список активных запрещенных слов
        $stmt = $pdo->prepare("SELECT word FROM banned_words WHERE is_active = 1");
        $stmt->execute();
        $bannedWords = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Если ошибка, считаем что запрещенных слов нет
        error_log("Ошибка при проверке запрещенных слов: " . $e->getMessage());
        return false;
    }
    
    if (empty($bannedWords)) {
        return false;
    }
    
    foreach ($bannedWords as $word) {
        $word = trim($word);
        if (empty($word)) {
            continue;
        }
        
        // Для UTF-8 используем модификатор u
        // Ищем слово как отдельное или как часть другого слова
        $pattern = '/' . preg_quote($word, '/') . '/iu';
        if (preg_match($pattern, $text)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Логирует операцию цензуры
 * @param int $userId ID пользователя
 * @param string $originalText Оригинальный текст
 * @param string $censoredText Цензурированный текст
 * @return bool Успех операции
 */
function logCensorship($userId, $originalText, $censoredText) {
    global $pdo;
    
    try {
        // Проверяем, существует ли таблица censorship_logs
        $stmt = $pdo->query("SHOW TABLES LIKE 'censorship_logs'");
        if ($stmt->rowCount() == 0) {
            // Таблица не существует, не логируем
            return true;
        }
        
        // Пытаемся вставить запись без message_id
        $stmt = $pdo->prepare("INSERT INTO censorship_logs (user_id, original_text, censored_text, action_taken) VALUES (?, ?, ?, 'censored')");
        return $stmt->execute([$userId, $originalText, $censoredText]);
        
    } catch (PDOException $e) {
        // Если ошибка из-за отсутствия поля message_id, пробуем другой запрос
        if (strpos($e->getMessage(), 'message_id') !== false) {
            try {
                $stmt = $pdo->prepare("INSERT INTO censorship_logs (user_id, original_text, censored_text, action_taken) VALUES (?, ?, ?, 'censored')");
                return $stmt->execute([$userId, $originalText, $censoredText]);
            } catch (PDOException $e2) {
                // В случае ошибки просто возвращаем false, не прерывая выполнение
                error_log("Ошибка логирования цензуры (вторая попытка): " . $e2->getMessage());
                return false;
            }
        }
        
        // В случае другой ошибки просто возвращаем false, не прерывая выполнение
        error_log("Ошибка логирования цензуры: " . $e->getMessage());
        return false;
    }
}
?>