-- Добавление поля avatar в таблицу users
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER city;

-- Комментарий к полю
ALTER TABLE users MODIFY COLUMN avatar VARCHAR(255) DEFAULT NULL COMMENT 'Путь к файлу аватара пользователя';