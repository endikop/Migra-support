-- Добавление поля avatar в таблицу users
-- Выполните этот SQL запрос в вашей базе данных

-- Проверяем, существует ли поле avatar
SELECT COUNT(*) 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'migrant_system' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'avatar';

-- Если поле не существует, добавляем его
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL COMMENT 'Путь к файлу аватара пользователя' AFTER city;

-- Обновляем структуру таблицы
DESCRIBE users;