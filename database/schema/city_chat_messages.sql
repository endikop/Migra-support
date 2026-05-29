-- Таблица для сообщений городского чата
CREATE TABLE IF NOT EXISTS city_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    city VARCHAR(50) NOT NULL,
    message_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sender_id (sender_id),
    INDEX idx_city (city),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Проверяем и добавляем поле last_activity если его нет в таблице users
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Создаем таблицу для онлайн пользователей если ее нет
CREATE TABLE IF NOT EXISTS user_online_status (
    user_id INT PRIMARY KEY,
    city VARCHAR(50) NOT NULL,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставляем тестовые данные для проверки
INSERT INTO city_chat_messages (sender_id, city, message_text) VALUES
(1, 'minsk', 'Добро пожаловать в чат Минска!'),
(1, 'minsk', 'Здесь вы можете общаться с другими мигрантами'),
(1, 'grodno', 'Добро пожаловать в чат Гродно!'),
(1, 'brest', 'Добро пожаловать в чат Бреста!');

-- Обновляем статус активности для тестового пользователя
UPDATE users SET last_activity = NOW() WHERE id = 1;