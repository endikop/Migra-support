-- Таблица для хранения запрещенных слов
CREATE TABLE IF NOT EXISTS banned_words (
    id INT AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(100) NOT NULL,
    replacement VARCHAR(100) DEFAULT '***',
    severity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_word (word)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавим внешний ключ на пользователей
ALTER TABLE banned_words 
ADD CONSTRAINT fk_banned_words_created_by 
FOREIGN KEY (created_by) 
REFERENCES users(id) 
ON DELETE SET NULL;

-- Вставим несколько начальных запрещенных слов
INSERT INTO banned_words (word, replacement, severity, created_by) VALUES
('мат1', '***', 'high', 1),
('мат2', '***', 'high', 1),
('оскорбление1', '***', 'high', 1),
('оскорбление2', '***', 'high', 1),
('спам', '[реклама]', 'medium', 1),
('мошенничество', '[мошенничество]', 'high', 1);

-- Создадим таблицу для логов цензуры
CREATE TABLE IF NOT EXISTS censorship_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message_id INT,
    original_text TEXT,
    censored_text TEXT,
    banned_word_id INT,
    action_taken ENUM('censored', 'blocked', 'warned') DEFAULT 'censored',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_message_id (message_id),
    INDEX idx_banned_word_id (banned_word_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Внешние ключи для логов
ALTER TABLE censorship_logs 
ADD CONSTRAINT fk_censorship_logs_user_id 
FOREIGN KEY (user_id) 
REFERENCES users(id) 
ON DELETE SET NULL;

ALTER TABLE censorship_logs 
ADD CONSTRAINT fk_censorship_logs_banned_word_id 
FOREIGN KEY (banned_word_id) 
REFERENCES banned_words(id) 
ON DELETE SET NULL;