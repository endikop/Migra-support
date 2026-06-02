<?php
session_start();
require_once 'config.php';

// Подключаем файл с данными аватара
require_once 'include_avatar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Редактирование мигранта | Админ-панель';

// Получаем ID мигранта из GET параметра
$migrant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$migrant_id) {
    $_SESSION['error'] = 'Не указан ID мигранта';
    header('Location: migrants.php');
    exit;
}

// Получаем данные мигранта
$migrant = null;
$errors = [];
$success = false;

try {
    // Получаем основную информацию о мигранте
    $stmt = $pdo->prepare("
        SELECT u.*, md.* 
        FROM users u 
        LEFT JOIN migration_data md ON u.id = md.user_id 
        WHERE u.id = ? AND u.user_type = 'migrant'
    ");
    $stmt->execute([$migrant_id]);
    $migrant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$migrant) {
        $_SESSION['error'] = 'Мигрант не найден';
        header('Location: migrants.php');
        exit;
    }
    
} catch (PDOException $e) {
    $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $country_of_origin = trim($_POST['country_of_origin'] ?? '');
    $passport_number = trim($_POST['passport_number'] ?? '');
    $city = trim($_POST['city'] ?? 'minsk');
    $status = trim($_POST['status'] ?? 'pending');
    
    // Миграционные данные
    $visa_type = trim($_POST['visa_type'] ?? '');
    $visa_number = trim($_POST['visa_number'] ?? '');
    $visa_issue_date = trim($_POST['visa_issue_date'] ?? '');
    $arrival_date = trim($_POST['arrival_date'] ?? '');
    $visa_expiry_date = trim($_POST['visa_expiry_date'] ?? '');
    $purpose_of_stay = trim($_POST['purpose_of_stay'] ?? '');
    $employer_name = trim($_POST['employer_name'] ?? '');
    $work_permit_number = trim($_POST['work_permit_number'] ?? '');
    $residential_address = trim($_POST['residential_address'] ?? '');
    $registration_date = trim($_POST['registration_date'] ?? '');
    
    // Валидация данных
    if (empty($first_name)) {
        $errors[] = 'Введите имя';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Введите фамилию';
    }
    
    if (empty($email)) {
        $errors[] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email';
    }
    
    if (empty($username)) {
        $errors[] = 'Введите имя пользователя';
    }
    
    // Проверяем уникальность email и username (кроме текущего пользователя)
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
        $stmt->execute([$email, $username, $migrant_id]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            $errors[] = 'Пользователь с таким email или именем пользователя уже существует';
        }
    } catch (PDOException $e) {
        $errors[] = 'Ошибка при проверке данных';
    }
    
    // Если нет ошибок, обновляем данные
    if (empty($errors)) {
        try {
            // Начинаем транзакцию
            $pdo->beginTransaction();
            
            // Обновляем данные пользователя
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    phone = ?,
                    username = ?,
                    country_of_origin = ?,
                    passport_number = ?,
                    city = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $first_name, $last_name, $email, $phone, $username,
                $country_of_origin, $passport_number, $city, $status,
                $migrant_id
            ]);
            
            // Проверяем, есть ли уже запись в migration_data
            $stmt = $pdo->prepare("SELECT id FROM migration_data WHERE user_id = ?");
            $stmt->execute([$migrant_id]);
            $existing_migration_data = $stmt->fetch();
            
            if ($existing_migration_data) {
                // Обновляем существующую запись
                $stmt = $pdo->prepare("
                    UPDATE migration_data SET 
                        visa_type = ?,
                        visa_number = ?,
                        visa_issue_date = ?,
                        arrival_date = ?,
                        visa_expiry_date = ?,
                        purpose_of_stay = ?,
                        employer_name = ?,
                        work_permit_number = ?,
                        residential_address = ?,
                        registration_date = ?
                    WHERE user_id = ?
                ");
                
                $stmt->execute([
                    $visa_type, $visa_number, $visa_issue_date, $arrival_date, 
                    $visa_expiry_date, $purpose_of_stay, $employer_name, 
                    $work_permit_number, $residential_address, $registration_date,
                    $migrant_id
                ]);
            } else {
                // Создаем новую запись
                $stmt = $pdo->prepare("
                    INSERT INTO migration_data (
                        user_id, visa_type, visa_number, visa_issue_date, 
                        arrival_date, visa_expiry_date, purpose_of_stay, 
                        employer_name, work_permit_number, residential_address, 
                        registration_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $migrant_id, $visa_type, $visa_number, $visa_issue_date, 
                    $arrival_date, $visa_expiry_date, $purpose_of_stay, 
                    $employer_name, $work_permit_number, $residential_address, 
                    $registration_date
                ]);
            }
            
            // Фиксируем транзакцию
            $pdo->commit();
            
            // Обновляем данные мигранта для отображения
            $stmt = $pdo->prepare("
                SELECT u.*, md.* 
                FROM users u 
                LEFT JOIN migration_data md ON u.id = md.user_id 
                WHERE u.id = ?
            ");
            $stmt->execute([$migrant_id]);
            $migrant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $success = true;
            $_SESSION['success'] = 'Данные мигранта успешно обновлены';
            
        } catch (PDOException $e) {
            // Откатываем транзакцию при ошибке
            $pdo->rollBack();
            $errors[] = 'Ошибка при обновлении данных: ' . $e->getMessage();
        }
    }
}

// Данные по городам для выпадающего списка
$cities = [
    'minsk' => 'Минск',
    'grodno' => 'Гродно',
    'brest' => 'Брест',
    'vitebsk' => 'Витебск',
    'gomel' => 'Гомель',
    'mogilev' => 'Могилёв'
];

// Статусы пользователя
$statuses = [
    'active' => 'Активен',
    'pending' => 'Ожидание',
    'inactive' => 'Неактивен'
];

// Типы виз
$visa_types = [
    '' => 'Не указано',
    'tourist' => 'Туристическая',
    'business' => 'Бизнес',
    'work' => 'Рабочая',
    'student' => 'Студенческая',
    'family' => 'Семейная',
    'transit' => 'Транзитная'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --danger-color: #7209b7;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --border-color: #dee2e6;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark-color);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .logo {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .logo h2 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo h2 i {
            color: #4cc9f0;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
        }

        .nav-menu li {
            margin-bottom: 5px;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }

        .nav-menu a:hover, .nav-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid #4cc9f0;
        }

        .nav-menu a i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .header h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Back Button */
        .back-button {
            margin-bottom: 20px;
        }

        .back-button a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .back-button a:hover {
            background-color: rgba(67, 97, 238, 0.1);
        }

        /* Edit Form */
        .edit-form-container {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .form-header h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .user-info-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .user-info-header h3 {
            font-size: 1.4rem;
            margin-bottom: 5px;
            color: var(--dark-color);
        }

        .user-info-header p {
            color: var(--gray-color);
            margin-bottom: 10px;
        }

        .user-id {
            font-size: 0.9rem;
            color: var(--gray-color);
            background-color: var(--light-color);
            padding: 3px 10px;
            border-radius: 12px;
            display: inline-block;
        }

        /* Form Layout */
        .form-section {
            margin-bottom: 40px;
        }

        .section-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-label .required {
            color: var(--warning-color);
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .form-control.error {
            border-color: var(--warning-color);
        }

        .form-help {
            display: block;
            margin-top: 5px;
            font-size: 0.85rem;
            color: var(--gray-color);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 40px;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Status Badges */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status.active {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
        }

        .status.pending {
            background-color: rgba(255, 158, 0, 0.2);
            color: #cc7e00;
        }

        .status.inactive {
            background-color: rgba(114, 9, 183, 0.2);
            color: var(--danger-color);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        /* Success & Error Messages */
        .success {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid var(--success-color);
            animation: slideIn 0.5s ease;
        }

        .error {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--warning-color);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid var(--warning-color);
        }

        .error-list {
            background-color: rgba(247, 37, 133, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid var(--warning-color);
        }

        .error-list h4 {
            color: var(--warning-color);
            margin-bottom: 10px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .error-list li {
            color: var(--warning-color);
            padding: 8px 0;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-list li:before {
            content: '•';
            color: var(--warning-color);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 0.95rem;
            justify-content: center;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #0a8ea8;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #c2185b;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #5a088f;
            transform: translateY(-2px);
        }

        .btn i {
            font-size: 1rem;
        }

        /* Loading */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Dates Section */
        .dates-section .form-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        /* Account Info */
        .account-info {
            background-color: var(--light-color);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid var(--success-color);
        }

        .account-info h4 {
            color: var(--success-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .account-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .account-item {
            padding: 10px;
            background-color: white;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        .account-label {
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-bottom: 5px;
        }

        .account-value {
            font-weight: 600;
            color: var(--dark-color);
            word-break: break-all;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h2><i class="fas fa-user-shield"></i> <span>Админ-панель</span></h2>
        </div>
        <ul class="nav-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Главная</span></a></li>
            <li><a href="migrants.php"><i class="fas fa-users"></i> <span>Мигранты</span></a></li>
            <li><a href="add_migrant.php"><i class="fas fa-user-plus"></i> <span>Добавить мигранта</span></a></li>
            <li><a href="migration_data.php"><i class="fas fa-database"></i> <span>Миграционные данные</span></a></li>
            <li><a href="chats.php"><i class="fas fa-comments"></i> <span>Чаты</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Выход</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Редактирование мигранта</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php if (isset($userAvatar) && $userAvatar): ?>
                        <img src="<?php echo htmlspecialchars($userAvatar); ?>" 
                             alt="<?php echo htmlspecialchars($userName); ?>"
                             style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo isset($userName) ? strtoupper(substr($userName, 0, 1)) : 'A'; ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo $_SESSION['user_name'] ?? 'Администратор'; ?></div>
                    <div style="font-size: 0.9rem; color: var(--gray-color);"><?php echo date('d.m.Y H:i'); ?></div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="back-button">
            <a href="migrants.php">
                <i class="fas fa-arrow-left"></i> Назад к списку мигрантов
            </a>
        </div>

        <!-- Success & Error Messages -->
        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span>Данные мигранта успешно обновлены</span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error-list">
                <h4><i class="fas fa-exclamation-circle"></i> Ошибки при обновлении данных</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="edit-form-container">
            <div class="form-header">
                <h2><i class="fas fa-user-edit"></i> Редактирование данных мигранта</h2>
                <div class="user-id">ID: #<?php echo $migrant['id']; ?></div>
            </div>

            <div class="user-info-header">
                <div class="user-avatar-large">
                    <?php echo strtoupper(substr($migrant['first_name'], 0, 1) . substr($migrant['last_name'], 0, 1)); ?>
                </div>
                <h3><?php echo htmlspecialchars($migrant['first_name'] . ' ' . $migrant['last_name']); ?></h3>
                <p><?php echo htmlspecialchars($migrant['email']); ?></p>
                <div class="user-id">
                    Зарегистрирован: <?php echo date('d.m.Y', strtotime($migrant['created_at'])); ?>
                </div>
            </div>

            <!-- Информация об учетной записи (только для просмотра) -->
            <div class="account-info">
                <h4><i class="fas fa-info-circle"></i> Учетная запись</h4>
                <div class="account-details">
                    <div class="account-item">
                        <div class="account-label">Логин</div>
                        <div class="account-value"><?php echo htmlspecialchars($migrant['username']); ?></div>
                    </div>
                    <div class="account-item">
                        <div class="account-label">Email</div>
                        <div class="account-value"><?php echo htmlspecialchars($migrant['email']); ?></div>
                    </div>
                    <div class="account-item">
                        <div class="account-label">Тип пользователя</div>
                        <div class="account-value">Мигрант</div>
                    </div>
                </div>
                <p style="margin-top: 15px; font-size: 0.9rem; color: var(--gray-color);">
                    <i class="fas fa-exclamation-triangle"></i> Примечание: Пароль может быть изменен только самим пользователем в личном кабинете
                </p>
            </div>

            <form method="POST" action="" id="editMigrantForm">
                <!-- Личная информация -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i> Личная информация
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                Имя
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="first_name" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['first_name']); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Фамилия
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="last_name" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['last_name']); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                Имя пользователя
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="username" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['username']); ?>"
                                   required>
                            <span class="form-help">Для входа в систему</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Email
                                <span class="required">*</span>
                            </label>
                            <input type="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['email']); ?>"
                                   required>
                            <span class="form-help">Для уведомлений и связи</span>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Телефон</label>
                            <input type="tel" 
                                   name="phone" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['phone']); ?>"
                                   placeholder="+375 (XX) XXX-XX-XX">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Страна происхождения
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="country_of_origin" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['country_of_origin']); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                Номер паспорта
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="passport_number" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['passport_number']); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Город проживания</label>
                            <select name="city" class="form-control">
                                <?php foreach ($cities as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" 
                                        <?php echo ($migrant['city'] ?? 'minsk') === $code ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Статус</label>
                        <select name="status" class="form-control">
                            <?php foreach ($statuses as $code => $name): ?>
                                <option value="<?php echo $code; ?>" 
                                    <?php echo $migrant['status'] === $code ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-help">
                            <strong>Активен</strong> - полный доступ к системе<br>
                            <strong>Ожидание</strong> - требуется подтверждение<br>
                            <strong>Неактивен</strong> - доступ заблокирован
                        </span>
                    </div>
                </div>

                <!-- Миграционные данные -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-passport"></i> Миграционные данные
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Тип визы</label>
                            <select name="visa_type" class="form-control">
                                <?php foreach ($visa_types as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" 
                                        <?php echo ($migrant['visa_type'] ?? '') === $code ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Номер визы</label>
                            <input type="text" 
                                   name="visa_number" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['visa_number'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-section dates-section">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Дата выдачи визы</label>
                                <input type="date" 
                                       name="visa_issue_date" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($migrant['visa_issue_date'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Дата прибытия</label>
                                <input type="date" 
                                       name="arrival_date" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($migrant['arrival_date'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Срок действия визы</label>
                                <input type="date" 
                                       name="visa_expiry_date" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($migrant['visa_expiry_date'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Дата регистрации</label>
                                <input type="date" 
                                       name="registration_date" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($migrant['registration_date'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Работодатель</label>
                            <input type="text" 
                                   name="employer_name" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['employer_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Номер разрешения на работу</label>
                            <input type="text" 
                                   name="work_permit_number" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($migrant['work_permit_number'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Цель пребывания</label>
                        <textarea name="purpose_of_stay" class="form-control" rows="3"><?php echo htmlspecialchars($migrant['purpose_of_stay'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Адрес проживания</label>
                        <textarea name="residential_address" class="form-control" rows="3"><?php echo htmlspecialchars($migrant['residential_address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="migrants.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Обработка отправки формы
        document.getElementById('editMigrantForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            // Показываем индикатор загрузки
            submitBtn.innerHTML = '<div class="loading"></div>';
            submitBtn.disabled = true;
            
            // Автоматически возвращаем кнопку в исходное состояние через 10 секунд
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });

        // Подсветка полей с ошибками
        const formControls = document.querySelectorAll('.form-control');
        formControls.forEach(control => {
            control.addEventListener('input', function() {
                this.classList.remove('error');
            });
        });

        // Маска для телефона
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.startsWith('80')) {
                    value = '375' + value.substring(2);
                }
                
                value = value.substring(0, 12);
                
                let formatted = '';
                
                if (value.length > 0) {
                    formatted = '+' + value.substring(0, 3);
                }
                if (value.length > 3) {
                    formatted += ' (' + value.substring(3, 5);
                }
                if (value.length > 5) {
                    formatted += ') ' + value.substring(5, 8);
                }
                if (value.length > 8) {
                    formatted += '-' + value.substring(8, 10);
                }
                if (value.length > 10) {
                    formatted += '-' + value.substring(10, 12);
                }
                
                e.target.value = formatted;
            });
        }

        // Подсказки при наведении на статусы
        const statusSelect = document.querySelector('select[name="status"]');
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                const statusHelp = document.querySelector('.form-help');
                if (statusHelp) {
                    const statuses = {
                        'active': '✅ Пользователь активен и имеет полный доступ к системе',
                        'pending': '⏳ Ожидает подтверждения администратора. Доступ ограничен',
                        'inactive': '❌ Доступ заблокирован. Пользователь не может войти в систему'
                    };
                    
                    const selectedStatus = this.value;
                    if (statuses[selectedStatus]) {
                        statusHelp.innerHTML = `<strong>${statuses[selectedStatus]}</strong><br><strong>Активен</strong> - полный доступ к системе<br><strong>Ожидание</strong> - требуется подтверждение<br><strong>Неактивен</strong> - доступ заблокирован`;
                    }
                }
            });
        }
    </script>
</body>
</html>