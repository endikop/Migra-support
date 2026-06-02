    <?php
    session_start();
    require_once 'config.php';

    // Подключаем файл с данными аватара
    require_once 'include_avatar.php';

    // Подключаем файл со списком стран
    require_once 'countries.php';

    // Проверка авторизации и прав администратора
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    // Если пользователь не админ - перенаправляем на главную
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }

    $pageTitle = 'Добавить мигранта | Панель администратора';

    // Данные по городам для выпадающего списка
    $cities = [
        'minsk' => 'Минск',
        'grodno' => 'Гродно',
        'brest' => 'Брест',
        'vitebsk' => 'Витебск',
        'gomel' => 'Гомель',
        'mogilev' => 'Могилёв'
    ];

    // Статусы пользователей
    $statuses = [
        'pending' => 'Ожидание',
        'active' => 'Активен',
        'inactive' => 'Неактивен',
        'blocked' => 'Заблокирован'
    ];

    // Обработка формы добавления мигранта
    $errors = [];
    $success = false;
    $formData = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Получаем данные из формы
        $formData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'country_of_origin' => trim($_POST['country_of_origin'] ?? ''),
            'passport_number' => trim($_POST['passport_number'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'status' => trim($_POST['status'] ?? 'pending')
        ];
        
        // Валидация данных
        if (empty($formData['username'])) {
            $errors[] = 'Введите имя пользователя';
        } elseif (strlen($formData['username']) < 3) {
            $errors[] = 'Имя пользователя должно быть не менее 3 символов';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $formData['username'])) {
            $errors[] = 'Имя пользователя может содержать только буквы, цифры и подчеркивания';
        }
        
        if (empty($formData['email'])) {
            $errors[] = 'Введите email';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Введите корректный email';
        }
        
        if (empty($formData['password'])) {
            $errors[] = 'Введите пароль';
        } elseif (strlen($formData['password']) < 8) {
            $errors[] = 'Пароль должен быть не менее 8 символов';
        } elseif ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = 'Пароли не совпадают';
        }
        
        if (empty($formData['first_name'])) {
            $errors[] = 'Введите имя';
        }
        
        if (empty($formData['last_name'])) {
            $errors[] = 'Введите фамилию';
        }
        
        if (empty($formData['country_of_origin'])) {
            $errors[] = 'Выберите страну происхождения';
        } elseif (!in_array($formData['country_of_origin'], $countries)) {
            $errors[] = 'Выбрана недопустимая страна';
        }
        
        if (empty($formData['passport_number'])) {
            $errors[] = 'Введите номер паспорта';
        }
        
        if (empty($formData['city'])) {
            $errors[] = 'Выберите город проживания';
        }
        
        // Проверка уникальности имени пользователя и email
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$formData['username'], $formData['email']]);
                $existing_user = $stmt->fetch();
                
                if ($existing_user) {
                    $errors[] = 'Пользователь с таким именем или email уже существует';
                }
            } catch (PDOException $e) {
                $errors[] = 'Ошибка при проверке данных: ' . $e->getMessage();
            }
        }
        
        // Если ошибок нет, создаем пользователя
        if (empty($errors)) {
            try {
                // Начинаем транзакцию
                $pdo->beginTransaction();
                
                // Хешируем пароль
                $password_hash = password_hash($formData['password'], PASSWORD_DEFAULT);
                
                // Создаем пользователя
                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        username, email, password, first_name, last_name, 
                        phone, country_of_origin, passport_number, city, 
                        user_type, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'migrant', ?, NOW())
                ");
                
                $stmt->execute([
                    $formData['username'],
                    $formData['email'],
                    $password_hash,
                    $formData['first_name'],
                    $formData['last_name'],
                    $formData['phone'],
                    $formData['country_of_origin'],
                    $formData['passport_number'],
                    $formData['city'],
                    $formData['status']
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                // Создаем запись миграционных данных
                $stmt = $pdo->prepare("
                    INSERT INTO migration_data (user_id, updated_at) 
                    VALUES (?, NOW())
                ");
                $stmt->execute([$user_id]);
                
                // Подтверждаем транзакцию
                $pdo->commit();
                
                $success = true;
                $_SESSION['success'] = 'Мигрант успешно добавлен!';
                
                // Очищаем форму после успешного добавления
                $formData = [];
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Ошибка при создании пользователя: ' . $e->getMessage();
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $pageTitle; ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- Универсальные анимации -->
        <?php include_once 'include_animations.php'; ?>
        
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

            /* Форма добавления мигранта */
            .add-migrant-container {
                max-width: 1000px;
                margin: 0 auto;
                animation: fadeIn 0.5s ease;
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .add-migrant-header {
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                color: white;
                padding: 30px;
                border-radius: 15px 15px 0 0;
                margin-bottom: 0;
                position: relative;
                overflow: hidden;
            }

            .add-migrant-header::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -20%;
                width: 300px;
                height: 300px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
            }

            .add-migrant-header-content {
                position: relative;
                z-index: 1;
            }

            .add-migrant-header h2 {
                font-size: 1.8rem;
                margin-bottom: 10px;
            }

            .add-migrant-header p {
                opacity: 0.9;
            }

            .add-migrant-form {
                background-color: white;
                padding: 40px;
                border-radius: 0 0 15px 15px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                border: 1px solid var(--border-color);
                border-top: none;
            }

            /* Секции формы */
            .form-section {
                margin-bottom: 40px;
                padding-bottom: 30px;
                border-bottom: 1px solid var(--border-color);
            }

            .form-section:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }

            .section-title {
                color: var(--primary-color);
                font-size: 1.3rem;
                margin-bottom: 25px;
                padding-bottom: 10px;
                border-bottom: 2px solid var(--primary-color);
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .section-title i {
                color: var(--warning-color);
            }

            /* Сетка формы */
            .form-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 25px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group.full-width {
                grid-column: 1 / -1;
            }

            .form-label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: var(--dark-color);
                font-size: 0.95rem;
            }

            .form-label .required {
                color: var(--warning-color);
                margin-left: 4px;
            }

            .form-help {
                display: block;
                margin-top: 5px;
                font-size: 0.85rem;
                color: var(--gray-color);
            }

            .form-input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid var(--border-color);
                border-radius: 8px;
                font-size: 0.95rem;
                transition: all 0.3s;
                background: white;
                color: var(--dark-color);
            }

            .form-input:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            }

            .form-input.error {
                border-color: var(--warning-color);
                box-shadow: 0 0 0 3px rgba(247, 37, 133, 0.2);
            }

            select.form-input {
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 16px center;
                background-size: 16px;
                padding-right: 45px;
            }

            /* Индикатор силы пароля */
            .password-strength-container {
                margin-top: 15px;
            }

            .strength-bar {
                height: 6px;
                background: var(--border-color);
                border-radius: 3px;
                overflow: hidden;
                margin-bottom: 8px;
            }

            .strength-fill {
                height: 100%;
                width: 0%;
                transition: width 0.3s;
                border-radius: 3px;
            }

            .strength-fill.weak { width: 25%; background: var(--warning-color); }
            .strength-fill.medium { width: 50%; background: #ff9e00; }
            .strength-fill.strong { width: 75%; background: #38b000; }
            .strength-fill.very-strong { width: 100%; background: var(--success-color); }

            .strength-text {
                font-size: 0.85rem;
                color: var(--gray-color);
            }

            /* Кнопки формы */
            .form-actions {
                display: flex;
                gap: 15px;
                margin-top: 30px;
                padding-top: 25px;
                border-top: 1px solid var(--border-color);
            }

            .btn {
                padding: 14px 28px;
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
                background-color: var(--gray-color);
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

            .btn i {
                font-size: 1rem;
            }

            /* Сообщения об ошибках и успехе */
            .error-list {
                background: rgba(247, 37, 133, 0.1);
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 30px;
                border-left: 4px solid var(--warning-color);
                animation: fadeIn 0.3s ease;
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
                padding: 5px 0;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .error-list li:before {
                content: '•';
                color: var(--warning-color);
                font-weight: bold;
            }

            .success-message {
                text-align: center;
                padding: 50px;
                background: rgba(76, 201, 240, 0.1);
                border-radius: 12px;
                border-left: 4px solid var(--success-color);
                animation: fadeIn 0.5s ease;
                margin-bottom: 30px;
            }

            .success-message i {
                font-size: 3rem;
                color: var(--success-color);
                margin-bottom: 20px;
            }

            .success-message h3 {
                color: var(--dark-color);
                margin-bottom: 15px;
                font-size: 1.5rem;
            }

            .success-message p {
                color: var(--gray-color);
                margin-bottom: 25px;
                line-height: 1.6;
            }

            /* Информация о пароле */
            .password-info {
                background: rgba(76, 201, 240, 0.05);
                border-radius: 8px;
                padding: 15px;
                margin-top: 10px;
                border-left: 3px solid var(--success-color);
                font-size: 0.85rem;
                color: var(--gray-color);
            }

            .password-info ul {
                margin: 10px 0 0 20px;
            }

            .password-info li {
                margin-bottom: 5px;
            }

            /* Responsive */
            @media (max-width: 1200px) {
                .sidebar {
                    width: 70px;
                }
                
                .sidebar .logo h2 span,
                .sidebar .nav-menu a span {
                    display: none;
                }
                
                .sidebar .logo h2 {
                    justify-content: center;
                }
                
                .nav-menu a {
                    justify-content: center;
                    padding: 15px 10px;
                }
                
                .main-content {
                    margin-left: 70px;
                }
            }

            @media (max-width: 768px) {
                .add-migrant-form {
                    padding: 25px 20px;
                }
                
                .form-grid {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
                
                .form-section {
                    padding-bottom: 25px;
                    margin-bottom: 30px;
                }
                
                .form-actions {
                    flex-direction: column;
                }
                
                .btn {
                    width: 100%;
                }
                
                .header {
                    flex-direction: column;
                    gap: 15px;
                    align-items: flex-start;
                }
            }

            @media (max-width: 480px) {
                .main-content {
                    padding: 15px;
                }
                
                .add-migrant-header {
                    padding: 20px;
                }
                
                .add-migrant-header h2 {
                    font-size: 1.5rem;
                }
                
                .form-input {
                    padding: 10px 14px;
                    font-size: 0.9rem;
                }
                
                .btn {
                    padding: 12px 20px;
                    font-size: 0.9rem;
                }
            }
        </style>
    </head>
    <body>
        <!-- Подключаем единую навигацию -->
        <?php include_once 'admin_navigation.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1><i class="fas fa-user-plus"></i> Добавить нового мигранта</h1>
                <?php 
                // Подключаем user-info из admin_navigation.php
                $userAvatar = getAdminUserAvatar();
                $userName = getAdminUserName();
                ?>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if ($userAvatar): ?>
                            <img src="<?php echo htmlspecialchars($userAvatar); ?>" 
                                alt="<?php echo htmlspecialchars($userName); ?>"
                                style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: 
                            echo strtoupper(substr($userName, 0, 1));
                        endif; ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($userName); ?></div>
                        <div style="font-size: 0.9rem; color: var(--gray-color);"><?php echo date('d.m.Y H:i'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Контейнер формы -->
            <div class="add-migrant-container">
                <?php if ($success): ?>
                    <!-- Сообщение об успешном добавлении -->
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <h3>Мигрант успешно добавлен!</h3>
                        <p>Новый мигрант был добавлен в систему. Вы можете просмотреть его данные в списке мигрантов или добавить еще одного.</p>
                        <div class="form-actions" style="justify-content: center; border-top: none; margin-top: 0; padding-top: 0;">
                            <a href="migrants.php" class="btn btn-primary">
                                <i class="fas fa-users"></i> Перейти к списку мигрантов
                            </a>
                            <a href="add_migrant.php" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Добавить еще одного
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Заголовок формы -->
                    <div class="add-migrant-header">
                        <div class="add-migrant-header-content">
                            <h2><i class="fas fa-user-plus"></i> Создание аккаунта мигранта</h2>
                            <p>Заполните все обязательные поля для добавления нового мигранта в систему</p>
                        </div>
                    </div>

                    <!-- Форма добавления мигранта -->
                    <form method="POST" action="" class="add-migrant-form" id="addMigrantForm">
                        <?php if (!empty($errors)): ?>
                            <div class="error-list">
                                <h4><i class="fas fa-exclamation-circle"></i> Ошибки при заполнении формы:</h4>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Секция учетных данных -->
                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="fas fa-user-lock"></i> Учетные данные
                            </h2>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        Имя пользователя
                                        <span class="required">*</span>
                                    </label>
                                    <input type="text" 
                                        name="username" 
                                        class="form-input <?php echo isset($formData['username']) && empty($formData['username']) ? 'error' : ''; ?>" 
                                        value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>"
                                        required
                                        placeholder="Например: ivanov_i"
                                        oninput="checkUsernameAvailability(this.value)">
                                    <span class="form-help">От 3 до 20 символов. Можно использовать буквы, цифры и подчеркивания</span>
                                    <div id="username-availability" style="font-size: 0.85rem; margin-top: 5px;"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        Email
                                        <span class="required">*</span>
                                    </label>
                                    <input type="email" 
                                        name="email" 
                                        class="form-input <?php echo isset($formData['email']) && (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL) || empty($formData['email'])) ? 'error' : ''; ?>" 
                                        value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                        required
                                        placeholder="example@email.com"
                                        oninput="checkEmailAvailability(this.value)">
                                    <span class="form-help">Email должен быть уникальным для каждого пользователя</span>
                                    <div id="email-availability" style="font-size: 0.85rem; margin-top: 5px;"></div>
                                </div>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        Пароль
                                        <span class="required">*</span>
                                    </label>
                                    <input type="password" 
                                        name="password" 
                                        class="form-input" 
                                        id="password"
                                        required
                                        oninput="checkPasswordStrength(this.value)"
                                        placeholder="Минимум 8 символов">
                                    <div class="password-strength-container">
                                        <div class="strength-bar">
                                            <div class="strength-fill" id="password-strength-fill"></div>
                                        </div>
                                        <div class="strength-text" id="password-strength-text">Надежность пароля</div>
                                    </div>
                                    <div class="password-info">
                                        <strong>Пароль должен содержать:</strong>
                                        <ul>
                                            <li>Не менее 8 символов</li>
                                            <li>Заглавные и строчные буквы</li>
                                            <li>Цифры</li>
                                            <li>Специальные символы</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        Подтверждение пароля
                                        <span class="required">*</span>
                                    </label>
                                    <input type="password" 
                                        name="confirm_password" 
                                        class="form-input" 
                                        id="confirm_password"
                                        required
                                        oninput="checkPasswordMatch()"
                                        placeholder="Повторите пароль">
                                    <div id="password-match" style="font-size: 0.85rem; margin-top: 5px; margin-bottom: 10px;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Секция личной информации -->
                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="fas fa-user-circle"></i> Личная информация
                            </h2>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        Имя
                                        <span class="required">*</span>
                                    </label>
                                    <input type="text" 
                                        name="first_name" 
                                        class="form-input <?php echo isset($formData['first_name']) && empty($formData['first_name']) ? 'error' : ''; ?>" 
                                        value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>"
                                        required
                                        placeholder="Иван">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        Фамилия
                                        <span class="required">*</span>
                                    </label>
                                    <input type="text" 
                                        name="last_name" 
                                        class="form-input <?php echo isset($formData['last_name']) && empty($formData['last_name']) ? 'error' : ''; ?>" 
                                        value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>"
                                        required
                                        placeholder="Иванов">
                                </div>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        Телефон
                                    </label>
                                    <input type="tel" 
                                        name="phone" 
                                        class="form-input" 
                                        value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>"
                                        placeholder="+375 (__) ___ __ __"
                                        oninput="formatPhoneNumber(this)"
                                        maxlength="19">
                                    <span class="form-help">Формат: +375 (XX) XXX-XX-XX</span>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        Страна происхождения
                                        <span class="required">*</span>
                                    </label>
                                    <select name="country_of_origin" 
                                        class="form-input <?php echo isset($formData['country_of_origin']) && empty($formData['country_of_origin']) ? 'error' : ''; ?>" 
                                        required>
                                        <option value="">Выберите страну</option>
                                        <?php foreach ($countries as $country): ?>
                                            <option value="<?php echo htmlspecialchars($country); ?>" 
                                                <?php echo isset($formData['country_of_origin']) && $formData['country_of_origin'] === $country ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($country); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                                        class="form-input <?php echo isset($formData['passport_number']) && empty($formData['passport_number']) ? 'error' : ''; ?>" 
                                        value="<?php echo htmlspecialchars($formData['passport_number'] ?? ''); ?>"
                                        required
                                        placeholder="AB1234567">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        Город проживания в Беларуси
                                        <span class="required">*</span>
                                    </label>
                                    <select name="city" 
                                            class="form-input <?php echo isset($formData['city']) && empty($formData['city']) ? 'error' : ''; ?>"
                                            required>
                                        <option value="">Выберите город</option>
                                        <?php foreach ($cities as $code => $name): ?>
                                            <option value="<?php echo $code; ?>" <?php echo ($formData['city'] ?? '') === $code ? 'selected' : ''; ?>>
                                                <?php echo $name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Секция дополнительной информации -->
                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="fas fa-cog"></i> Дополнительные настройки
                            </h2>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        Статус аккаунта
                                        <span class="required">*</span>
                                    </label>
                                    <select name="status" 
                                            class="form-input"
                                            required>
                                        <?php foreach ($statuses as $code => $name): ?>
                                            <option value="<?php echo $code; ?>" <?php echo ($formData['status'] ?? 'pending') === $code ? 'selected' : ''; ?>>
                                                <?php echo $name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="form-help">
                                        <strong>Ожидание</strong> - требуется подтверждение<br>
                                        <strong>Активен</strong> - полный доступ к системе<br>
                                        <strong>Неактивен</strong> - доступ ограничен<br>
                                        <strong>Заблокирован</strong> - вход запрещен
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Кнопки формы -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="submitButton">
                                <i class="fas fa-user-plus"></i>
                                <span id="buttonText">Добавить мигранта</span>
                                <div class="loading" id="submitLoading" style="display: none; margin-left: 10px;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </button>
                            
                            <a href="migrants.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Отмена
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // Проверка доступности имени пользователя
            function checkUsernameAvailability(username) {
                if (username.length < 3) {
                    document.getElementById('username-availability').innerHTML = '';
                    return;
                }
                
                const usernameRegex = /^[a-zA-Z0-9_]+$/;
                if (!usernameRegex.test(username)) {
                    document.getElementById('username-availability').innerHTML = 
                        '<i class="fas fa-times-circle" style="color: #f72585;"></i> Можно использовать только буквы, цифры и подчеркивания';
                    return;
                }
                
                fetch(`check_availability.php?field=username&value=${encodeURIComponent(username)}`)
                    .then(response => response.json())
                    .then(data => {
                        const availabilityEl = document.getElementById('username-availability');
                        if (data.available) {
                            availabilityEl.innerHTML = '<i class="fas fa-check-circle" style="color: #4cc9f0;"></i> Имя пользователя доступно';
                        } else {
                            availabilityEl.innerHTML = '<i class="fas fa-times-circle" style="color: #f72585;"></i> Имя пользователя уже занято';
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка проверки имени пользователя:', error);
                    });
            }

            // Проверка доступности email
            function checkEmailAvailability(email) {
                if (!email.includes('@')) {
                    document.getElementById('email-availability').innerHTML = '';
                    return;
                }
                
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    document.getElementById('email-availability').innerHTML = 
                        '<i class="fas fa-times-circle" style="color: #f72585;"></i> Введите корректный email';
                    return;
                }
                
                fetch(`check_availability.php?field=email&value=${encodeURIComponent(email)}`)
                    .then(response => response.json())
                    .then(data => {
                        const availabilityEl = document.getElementById('email-availability');
                        if (data.available) {
                            availabilityEl.innerHTML = '<i class="fas fa-check-circle" style="color: #4cc9f0;"></i> Email доступен';
                        } else {
                            availabilityEl.innerHTML = '<i class="fas fa-times-circle" style="color: #f72585;"></i> Email уже используется';
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка проверки email:', error);
                    });
            }

            // Форматирование номера телефона
            function formatPhoneNumber(input) {
                let value = input.value.replace(/\D/g, '');
                
                // Если начинается с 375 (Беларусь)
                if (value.startsWith('375')) {
                    value = '+' + value;
                } 
                // Если начинается с 80 (белорусский мобильный без кода страны)
                else if (value.startsWith('80')) {
                    value = '+375' + value.substring(2);
                }
                // Если начинается с цифры, но не с кода страны
                else if (value.length > 0 && !value.startsWith('+')) {
                    value = '+375' + value;
                }
                
                // Форматирование: +375 (XX) XXX-XX-XX
                if (value.length > 4) {
                    value = value.substring(0, 4) + ' (' + value.substring(4);
                }
                if (value.length > 8) {
                    value = value.substring(0, 8) + ') ' + value.substring(8);
                }
                if (value.length > 13) {
                    value = value.substring(0, 13) + '-' + value.substring(13);
                }
                if (value.length > 16) {
                    value = value.substring(0, 16) + '-' + value.substring(16);
                }
                
                // Ограничиваем длину (макс: +375 (XX) XXX-XX-XX = 19 символов)
                input.value = value.substring(0, 19);
            }

            // Проверка надежности пароля
            function checkPasswordStrength(password) {
                const fill = document.getElementById('password-strength-fill');
                const text = document.getElementById('password-strength-text');
                
                // Считаем силу пароля
                let strength = 0;
                
                // Длина
                if (password.length >= 8) strength += 25;
                else if (password.length >= 6) strength += 15;
                else if (password.length >= 4) strength += 5;
                
                // Строчные буквы
                if (/[a-z]/.test(password)) strength += 20;
                
                // Заглавные буквы
                if (/[A-Z]/.test(password)) strength += 20;
                
                // Цифры
                if (/[0-9]/.test(password)) strength += 20;
                
                // Специальные символы
                if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
                
                // Обновляем индикатор
                if (strength < 40) {
                    fill.className = 'strength-fill weak';
                    text.textContent = 'Слабый';
                    text.style.color = '#f72585';
                } else if (strength < 60) {
                    fill.className = 'strength-fill medium';
                    text.textContent = 'Средний';
                    text.style.color = '#ff9e00';
                } else if (strength < 80) {
                    fill.className = 'strength-fill strong';
                    text.textContent = 'Сильный';
                    text.style.color = '#38b000';
                } else {
                    fill.className = 'strength-fill very-strong';
                    text.textContent = 'Очень сильный';
                    text.style.color = '#4cc9f0';
                }
                
                fill.style.width = Math.min(strength, 100) + '%';
                
                checkPasswordMatch();
            }

            // Проверка совпадения паролей
            function checkPasswordMatch() {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const matchEl = document.getElementById('password-match');
                
                if (!confirmPassword) {
                    matchEl.innerHTML = '';
                    return;
                }
                
                if (password === confirmPassword) {
                    matchEl.innerHTML = '<i class="fas fa-check-circle" style="color: #4cc9f0;"></i> Пароли совпадают';
                } else {
                    matchEl.innerHTML = '<i class="fas fa-times-circle" style="color: #f72585;"></i> Пароли не совпадают';
                }
            }

            // Обработка отправки формы
            document.getElementById('addMigrantForm')?.addEventListener('submit', function(e) {
                const submitButton = document.getElementById('submitButton');
                const loadingSpinner = document.getElementById('submitLoading');
                const buttonText = document.getElementById('buttonText');
                
                if (submitButton && loadingSpinner && buttonText) {
                    // Проверяем пароли
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Пароли не совпадают');
                        return;
                    }
                    
                    // Проверяем длину пароля
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('Пароль должен быть не менее 8 символов');
                        return;
                    }
                    
                    // Показываем индикатор загрузки
                    buttonText.style.display = 'none';
                    loadingSpinner.style.display = 'block';
                    submitButton.disabled = true;
                    submitButton.style.opacity = '0.7';
                }
            });

            // Инициализация при загрузке
            document.addEventListener('DOMContentLoaded', function() {
                // Проверяем поля при загрузке
                const username = document.querySelector('input[name="username"]');
                const email = document.querySelector('input[name="email"]');
                const password = document.getElementById('password');
                
                if (username && username.value) {
                    checkUsernameAvailability(username.value);
                }
                
                if (email && email.value) {
                    checkEmailAvailability(email.value);
                }
                
                if (password && password.value) {
                    checkPasswordStrength(password.value);
                }
            });
        </script>
    </body>
    </html>