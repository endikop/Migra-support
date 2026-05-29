-- Создание таблицы для личных чатов между пользователями
CREATE TABLE IF NOT EXISTS personal_chats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    -- Уникальный идентификатор чата, чтобы избежать дублирования
    chat_uid VARCHAR(100) GENERATED ALWAYS AS (
        CONCAT(
            LEAST(user1_id, user2_id), 
            '_', 
            GREATEST(user1_id, user2_id)
        )
    ) STORED UNIQUE,
    last_message_id INT DEFAULT NULL,
    last_message_text TEXT,
    last_message_time TIMESTAMP NULL,
    unread_count_user1 INT DEFAULT 0,
    unread_count_user2 INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Внешние ключи
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Индексы для быстрого поиска
    INDEX idx_user1 (user1_id),
    INDEX idx_user2 (user2_id),
    INDEX idx_chat_uid (chat_uid),
    INDEX idx_last_message_time (last_message_time),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы для сообщений в личных чатах
CREATE TABLE IF NOT EXISTS personal_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chat_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_text TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Внешние ключи
    FOREIGN KEY (chat_id) REFERENCES personal_chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Индексы для быстрого поиска
    INDEX idx_chat_id (chat_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_receiver_id (receiver_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read),
    INDEX idx_chat_created (chat_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы для уведомлений о новых сообщениях
CREATE TABLE IF NOT EXISTS message_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    chat_id INT NOT NULL,
    message_id INT NOT NULL,
    is_seen BOOLEAN DEFAULT FALSE,
    seen_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Внешние ключи
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chat_id) REFERENCES personal_chats(id) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES personal_messages(id) ON DELETE CASCADE,
    
    -- Индексы
    INDEX idx_user_id (user_id),
    INDEX idx_is_seen (is_seen),
    INDEX idx_user_seen (user_id, is_seen),
    UNIQUE INDEX idx_user_message (user_id, message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы для избранных чатов
CREATE TABLE IF NOT EXISTS favorite_chats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    chat_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Внешние ключи
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chat_id) REFERENCES personal_chats(id) ON DELETE CASCADE,
    
    -- Уникальный индекс, чтобы пользователь не мог добавить один чат дважды
    UNIQUE INDEX idx_user_chat (user_id, chat_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы для блокировок пользователей
CREATE TABLE IF NOT EXISTS user_blocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Внешние ключи
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Уникальный индекс, чтобы избежать дублирования блокировок
    UNIQUE INDEX idx_blocker_blocked (blocker_id, blocked_id),
    INDEX idx_blocker_id (blocker_id),
    INDEX idx_blocked_id (blocked_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание триггера для обновления счетчика непрочитанных сообщений
DELIMITER //

CREATE TRIGGER after_personal_message_insert
AFTER INSERT ON personal_messages
FOR EACH ROW
BEGIN
    -- Обновляем последнее сообщение в чате
    UPDATE personal_chats 
    SET 
        last_message_id = NEW.id,
        last_message_text = LEFT(NEW.message_text, 100), -- Сохраняем первые 100 символов
        last_message_time = NEW.created_at,
        updated_at = NEW.created_at
    WHERE id = NEW.chat_id;
    
    -- Увеличиваем счетчик непрочитанных для получателя
    UPDATE personal_chats 
    SET 
        unread_count_user2 = unread_count_user2 + 1
    WHERE id = NEW.chat_id AND user2_id = NEW.receiver_id;
    
    UPDATE personal_chats 
    SET 
        unread_count_user1 = unread_count_user1 + 1
    WHERE id = NEW.chat_id AND user1_id = NEW.receiver_id;
    
    -- Создаем уведомление для получателя
    INSERT INTO message_notifications (user_id, chat_id, message_id)
    VALUES (NEW.receiver_id, NEW.chat_id, NEW.id);
END//

DELIMITER ;

-- Создание триггера для обновления счетчика при прочтении сообщения
DELIMITER //

CREATE TRIGGER after_personal_message_read
AFTER UPDATE ON personal_messages
FOR EACH ROW
BEGIN
    IF NEW.is_read = TRUE AND OLD.is_read = FALSE THEN
        -- Уменьшаем счетчик непрочитанных для получателя
        UPDATE personal_chats 
        SET 
            unread_count_user2 = GREATEST(0, unread_count_user2 - 1)
        WHERE id = NEW.chat_id AND user2_id = NEW.receiver_id;
        
        UPDATE personal_chats 
        SET 
            unread_count_user1 = GREATEST(0, unread_count_user1 - 1)
        WHERE id = NEW.chat_id AND user1_id = NEW.receiver_id;
        
        -- Обновляем уведомление как просмотренное
        UPDATE message_notifications 
        SET is_seen = TRUE, seen_at = NOW()
        WHERE user_id = NEW.receiver_id AND message_id = NEW.id;
    END IF;
END//

DELIMITER ;

-- Создание функции для получения или создания чата
DELIMITER //

CREATE FUNCTION get_or_create_chat(
    p_user1_id INT,
    p_user2_id INT
) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_chat_id INT;
    DECLARE v_chat_uid VARCHAR(100);
    
    -- Генерируем уникальный идентификатор чата
    SET v_chat_uid = CONCAT(LEAST(p_user1_id, p_user2_id), '_', GREATEST(p_user1_id, p_user2_id));
    
    -- Пыт��емся найти существующий чат
    SELECT id INTO v_chat_id 
    FROM personal_chats 
    WHERE chat_uid = v_chat_uid;
    
    -- Если чат не найден, создаем новый
    IF v_chat_id IS NULL THEN
        INSERT INTO personal_chats (user1_id, user2_id)
        VALUES (LEAST(p_user1_id, p_user2_id), GREATEST(p_user1_id, p_user2_id));
        
        SET v_chat_id = LAST_INSERT_ID();
    END IF;
    
    RETURN v_chat_id;
END//

DELIMITER ;

-- Создание процедуры для отправки сообщения
DELIMITER //

CREATE PROCEDURE send_personal_message(
    IN p_sender_id INT,
    IN p_receiver_id INT,
    IN p_message_text TEXT
)
BEGIN
    DECLARE v_chat_id INT;
    DECLARE v_blocked BOOLEAN DEFAULT FALSE;
    
    -- Проверяем, не заблокирован ли отправитель получателем
    SELECT COUNT(*) INTO v_blocked
    FROM user_blocks 
    WHERE blocker_id = p_receiver_id AND blocked_id = p_sender_id;
    
    IF v_blocked > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Вы заблокированы этим пользователем';
    END IF;
    
    -- Получаем или создаем чат
    SET v_chat_id = get_or_create_chat(p_sender_id, p_receiver_id);
    
    -- Вставляем сообщение
    INSERT INTO personal_messages (chat_id, sender_id, receiver_id, message_text)
    VALUES (v_chat_id, p_sender_id, p_receiver_id, p_message_text);
    
    -- Возвращаем ID созданного сообщения
    SELECT LAST_INSERT_ID() as message_id, v_chat_id as chat_id;
END//

DELIMITER ;

-- Создание процедуры для получения истории чата
DELIMITER //

CREATE PROCEDURE get_chat_history(
    IN p_chat_id INT,
    IN p_user_id INT,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    -- Проверяем, является ли пользователь участником чата
    IF NOT EXISTS (
        SELECT 1 FROM personal_chats 
        WHERE id = p_chat_id AND (user1_id = p_user_id OR user2_id = p_user_id)
    ) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'У вас нет доступа к этому чату';
    END IF;
    
    -- Получаем историю сообщений
    SELECT 
        pm.id,
        pm.sender_id,
        pm.receiver_id,
        pm.message_text,
        pm.is_read,
        pm.read_at,
        pm.created_at,
        CONCAT(u.first_name, ' ', u.last_name) as sender_name,
        u.avatar as sender_avatar
    FROM personal_messages pm
    JOIN users u ON pm.sender_id = u.id
    WHERE pm.chat_id = p_chat_id 
        AND pm.is_deleted = FALSE
    ORDER BY pm.created_at DESC
    LIMIT p_limit OFFSET p_offset;
END//

DELIMITER ;

-- Создание процедуры для получения списка чатов пользователя
DELIMITER //

CREATE PROCEDURE get_user_chats(
    IN p_user_id INT
)
BEGIN
    SELECT 
        pc.id as chat_id,
        CASE 
            WHEN pc.user1_id = p_user_id THEN pc.user2_id
            ELSE pc.user1_id
        END as other_user_id,
        CASE 
            WHEN pc.user1_id = p_user_id THEN u2.first_name
            ELSE u1.first_name
        END as other_user_first_name,
        CASE 
            WHEN pc.user1_id = p_user_id THEN u2.last_name
            ELSE u1.last_name
        END as other_user_last_name,
        CASE 
            WHEN pc.user1_id = p_user_id THEN u2.avatar
            ELSE u1.avatar
        END as other_user_avatar,
        CONCAT(
            CASE 
                WHEN pc.user1_id = p_user_id THEN u2.first_name
                ELSE u1.first_name
            END,
            ' ',
            CASE 
                WHEN pc.user1_id = p_user_id THEN u2.last_name
                ELSE u1.last_name
            END
        ) as other_user_name,
        pc.last_message_text,
        pc.last_message_time,
        CASE 
            WHEN pc.user1_id = p_user_id THEN pc.unread_count_user1
            ELSE pc.unread_count_user2
        END as unread_count,
        pc.updated_at,
        EXISTS(
            SELECT 1 FROM favorite_chats 
            WHERE user_id = p_user_id AND chat_id = pc.id
        ) as is_favorite
    FROM personal_chats pc
    LEFT JOIN users u1 ON pc.user1_id = u1.id
    LEFT JOIN users u2 ON pc.user2_id = u2.id
    WHERE (pc.user1_id = p_user_id OR pc.user2_id = p_user_id)
        AND pc.is_active = TRUE
    ORDER BY 
        pc.last_message_time DESC,
        pc.updated_at DESC;
END//

DELIMITER ;

-- Добавляем комментарии к таблицам
ALTER TABLE personal_chats COMMENT = 'Таблица личных чатов между пользователями';
ALTER TABLE personal_messages COMMENT = 'Таблица сообщений в личных чатах';
ALTER TABLE message_notifications COMMENT = 'Таблица уведомлений о новых сообщениях';
ALTER TABLE favorite_chats COMMENT = 'Таблица избранных чатов пользователей';
ALTER TABLE user_blocks COMMENT = 'Таблица блокировок пользователей';

-- Выводим информацию о созданных таблицах
SELECT 
    TABLE_NAME as 'Таблица',
    TABLE_COMMENT as 'Описание'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'migrant_system' 
    AND TABLE_NAME IN (
        'personal_chats', 
        'personal_messages', 
        'message_notifications', 
        'favorite_chats', 
        'user_blocks'
    );