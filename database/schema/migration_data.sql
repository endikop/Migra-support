-- Таблица для хранения миграционных данных пользователей
CREATE TABLE IF NOT EXISTS migration_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    visa_type VARCHAR(50),
    visa_number VARCHAR(100),
    visa_issue_date DATE,
    arrival_date DATE,
    visa_expiry_date DATE,
    purpose_of_stay TEXT,
    employer_name VARCHAR(255),
    work_permit_number VARCHAR(100),
    residential_address TEXT,
    registration_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Внешний ключ на таблицу users
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Уникальный индекс на user_id, чтобы у каждого пользователя была только одна запись
    UNIQUE KEY unique_user_migration (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Индексы для быстрого поиска
CREATE INDEX idx_migration_data_user_id ON migration_data(user_id);
CREATE INDEX idx_migration_data_visa_number ON migration_data(visa_number);
CREATE INDEX idx_migration_data_visa_expiry_date ON migration_data(visa_expiry_date);
CREATE INDEX idx_migration_data_employer_name ON migration_data(employer_name);