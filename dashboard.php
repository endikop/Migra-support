<?php
// Самая первая операция - буферизация вывода
ob_start();

session_start();
require_once 'config.php';

// Подключаем файл с данными аватара
require_once 'include_avatar.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Если пользователь не администратор - перенаправляем на главную
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Панель администратора | Управление миграционным центром';

// Получаем статистику
try {
    // Количество мигрантов
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'migrant'");
    $stmt->execute();
    $migrants_count = $stmt->fetchColumn();
    
    // Активные мигранты
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'migrant' AND status = 'active'");
    $stmt->execute();
    $active_migrants = $stmt->fetchColumn();
    
    // Ожидающие подтверждения
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $stmt->execute();
    $pending_users = $stmt->fetchColumn();
    
    // Активные чаты
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_chats WHERE status = 'open'");
    $stmt->execute();
    $active_chats = $stmt->fetchColumn();
    
    // Новые сообщения в чатах (не прочитанные администратором)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_chat_messages WHERE is_read = 0 AND sender_id != ?");
    $stmt->execute([$_SESSION['user_id']]);
    $new_messages = $stmt->fetchColumn();
    
    // Мигранты, добавленные сегодня
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'migrant' AND DATE(created_at) = CURDATE()");
    $stmt->execute();
    $today_migrants = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
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

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-content h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .welcome-content p {
            opacity: 0.9;
            max-width: 600px;
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card.total {
            border-top-color: var(--primary-color);
        }

        .stat-card.active {
            border-top-color: var(--success-color);
        }

        .stat-card.pending {
            border-top-color: #ff9e00;
        }

        .stat-card.chats {
            border-top-color: var(--warning-color);
        }

        .stat-card.messages {
            border-top-color: var(--danger-color);
        }

        .stat-card.today {
            border-top-color: #7209b7;
        }

        .stat-icon {
            font-size: 2.2rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .stat-card.total .stat-icon { color: var(--primary-color); }
        .stat-card.active .stat-icon { color: var(--success-color); }
        .stat-card.pending .stat-icon { color: #ff9e00; }
        .stat-card.chats .stat-icon { color: var(--warning-color); }
        .stat-card.messages .stat-icon { color: var(--danger-color); }
        .stat-card.today .stat-icon { color: #7209b7; }

        .stat-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray-color);
            font-size: 0.95rem;
        }

        /* Admin Guide */
        .admin-guide {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .admin-guide h2 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .guide-steps {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .guide-step {
            padding: 20px;
            background: var(--light-color);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s;
        }

        .guide-step:hover {
            transform: translateX(5px);
        }

        .step-number {
            display: inline-block;
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .guide-step h3 {
            color: var(--dark-color);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .guide-step p {
            color: var(--gray-color);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Quick Actions */
        .quick-actions {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .quick-actions h2 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        /* Recent Users */
        .recent-users {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .recent-users h2 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
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

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border-bottom: 2px solid var(--border-color);
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table tbody tr:hover {
            background-color: #f8f9fa;
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
            background-color: rgba(114, 9, 183, 0.2);
            color: var(--danger-color);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid var(--danger-color);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .guide-steps {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray-color);
            font-style: italic;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ddd;
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
            <h1><i class="fas fa-user-shield"></i> Панель администратора</h1>
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

        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-content">
                <h2>Добро пожаловать, Администратор!</h2>
                <p>Здесь вы можете управлять мигрантами, обрабатывать заявки, вести переписку и контролировать миграционные процессы.</p>
            </div>
        </div>

        <!-- Success & Error Messages -->
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-cards">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?php echo $migrants_count; ?></div>
                <div class="stat-label">Всего мигрантов</div>
            </div>
            <div class="stat-card active">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $active_migrants; ?></div>
                <div class="stat-label">Активные мигранты</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $pending_users; ?></div>
                <div class="stat-label">Ожидают подтверждения</div>
            </div>
            <div class="stat-card chats">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <div class="stat-value"><?php echo $active_chats; ?></div>
                <div class="stat-label">Активные чаты</div>
            </div>
            <?php if ($new_messages > 0): ?>
            <div class="stat-card messages">
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                <div class="stat-value"><?php echo $new_messages; ?></div>
                <div class="stat-label">Новые сообщения</div>
            </div>
            <?php endif; ?>
            <div class="stat-card today">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-value"><?php echo $today_migrants; ?></div>
                <div class="stat-label">Новых сегодня</div>
            </div>
        </div>

        <!-- Admin Guide -->
        <div class="admin-guide">
            <h2><i class="fas fa-graduation-cap"></i> Руководство администратора</h2>
            <div class="guide-steps">
                <div class="guide-step">
                    <div class="step-number">1</div>
                    <h3>Управление мигрантами</h3>
                    <p>Добавляйте новых мигрантов, редактируйте их данные, меняйте статусы и удаляйте неактивных пользователей.</p>
                </div>
                <div class="guide-step">
                    <div class="step-number">2</div>
                    <h3>Обработка заявок</h3>
                    <p>Проверяйте новые заявки в статусе "pending", подтверждайте или отклоняйте их после верификации данных.</p>
                </div>
                <div class="guide-step">
                    <div class="step-number">3</div>
                    <h3>Общение в чатах</h3>
                    <p>Отвечайте на вопросы мигрантов в чатах, назначайте ответственных и закрывайте решенные вопросы.</p>
                </div>
                <div class="guide-step">
                    <div class="step-number">4</div>
                    <h3>Миграционные данные</h3>
                    <p>Ведите учет миграционных документов, сроков пребывания и другой важной информации о мигрантах.</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Быстрые действия</h2>
            <div class="action-buttons">
                <a href="add_migrant.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Добавить мигранта
                </a>
                <a href="migrants.php?status=pending" class="btn btn-warning">
                    <i class="fas fa-clock"></i> Ожидающие заявки
                </a>
                <a href="chats.php" class="btn btn-success">
                    <i class="fas fa-comments"></i> Управление чатами
                </a>
                <a href="migration_data.php" class="btn btn-danger">
                    <i class="fas fa-database"></i> Миграционные данные
                </a>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="recent-users">
            <h2><i class="fas fa-history"></i> Последние регистрации</h2>
            <?php
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_type = 'migrant' ORDER BY created_at DESC LIMIT 10");
                $stmt->execute();
                $recent_users = $stmt->fetchAll();
                
                if ($recent_users): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Имя</th>
                                <th>Фамилия</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Статус</th>
                                <th>Дата регистрации</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['first_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td>
                                    <span class="status <?php echo $user['status']; ?>">
                                        <?php 
                                        $status_text = '';
                                        switch($user['status']) {
                                            case 'active': $status_text = 'Активен'; break;
                                            case 'pending': $status_text = 'Ожидание'; break;
                                            case 'inactive': $status_text = 'Неактивен'; break;
                                            default: $status_text = $user['status'];
                                        }
                                        echo $status_text;
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="edit_migrant.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-warning" 
                                           style="padding: 5px 10px; font-size: 0.8rem;"
                                           title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="migration_data.php?user_id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-success" 
                                           style="padding: 5px 10px; font-size: 0.8rem;"
                                           title="Миграционные данные">
                                            <i class="fas fa-database"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-user-slash"></i>
                        <div>Нет зарегистрированных мигрантов</div>
                    </div>
                <?php endif;
                
            } catch (PDOException $e) {
                echo '<div class="error"><i class="fas fa-exclamation-circle"></i> Ошибка при загрузке данных: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>
    </div>

    <script>
        // Автообновление статистики каждые 30 секунд
        setInterval(() => {
            const statsCards = document.querySelector('.stats-cards');
            if (statsCards) {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newStats = doc.querySelector('.stats-cards');
                        if (newStats && statsCards.innerHTML !== newStats.innerHTML) {
                            statsCards.innerHTML = newStats.innerHTML;
                        }
                    })
                    .catch(error => console.error('Ошибка обновления статистики:', error));
            }
        }, 30000);

        // Анимация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'slideIn 0.5s ease forwards';
                card.style.opacity = '0';
            });
        });
    </script>
</body>
</html>