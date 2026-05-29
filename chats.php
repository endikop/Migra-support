<?php
session_start();
require_once 'config.php';


// Подключаем файл с данными аватара
require_once 'include_avatar.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Получение списка чатов
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT c.*, u.first_name, u.last_name, u.email, u.phone, u.city,
               (SELECT COUNT(*) FROM admin_chat_messages WHERE chat_id = c.id AND is_read = 0 AND sender_id != c.admin_id) as unread_count
        FROM admin_chats c 
        JOIN users u ON c.user_id = u.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.subject LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if (!empty($status_filter)) {
    $sql .= " AND c.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY c.updated_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$chats = $stmt->fetchAll();

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_chat'])) {
        $chat_id = $_POST['chat_id'];
        
        $stmt = $pdo->prepare("UPDATE admin_chats SET admin_id = ?, status = 'open' WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $chat_id]);
        
        $_SESSION['success'] = 'Чат успешно назначен вам!';
        header('Location: chats.php');
        exit;
    }
    
    if (isset($_POST['close_chat'])) {
        $chat_id = $_POST['chat_id'];
        
        $stmt = $pdo->prepare("UPDATE admin_chats SET status = 'closed' WHERE id = ?");
        $stmt->execute([$chat_id]);
        
        $_SESSION['success'] = 'Чат закрыт!';
        header('Location: chats.php');
        exit;
    }
    
    if (isset($_POST['send_message'])) {
        $chat_id = $_POST['chat_id'];
        $message = trim($_POST['message']);
        
        if (!empty($message)) {
            $stmt = $pdo->prepare("INSERT INTO admin_chat_messages (chat_id, sender_id, message_text) VALUES (?, ?, ?)");
            $stmt->execute([$chat_id, $_SESSION['user_id'], $message]);
            
            // Обновляем время чата
            $stmt = $pdo->prepare("UPDATE admin_chats SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$chat_id]);
            
            $_SESSION['success'] = 'Сообщение отправлено!';
        }
        
        header("Location: chat_admin.php?id=$chat_id");
        exit;
    }
}

// Получаем имя и аватар пользователя
// Стало (использует существующие переменные):
$userName = isset($userName) ? $userName : 'Администратор';
$userAvatar = isset($userAvatar) ? $userAvatar : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление чатами | Админ-панель</title>
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
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-card.open {
            border-left-color: var(--success-color);
        }

        .stat-card.pending {
            border-left-color: #ff9e00;
        }

        .stat-card.closed {
            border-left-color: var(--danger-color);
        }

        .stat-card.total {
            border-left-color: var(--primary-color);
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark-color);
        }

        .stat-card .stat-label {
            color: var(--gray-color);
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .stat-card .stat-icon {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        /* Filters */
        .filters {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .filter-form input, .filter-form select {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            flex: 1;
            min-width: 200px;
        }

        .filter-form input:focus, .filter-form select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #0a8ea8;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* Table */
        .chats-list {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .table-header h2 {
            color: var(--dark-color);
            font-size: 1.3rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border-bottom: 1px solid var(--border-color);
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status.open {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
        }

        .status.pending {
            background-color: rgba(255, 158, 0, 0.2);
            color: #cc7e00;
        }

        .status.closed {
            background-color: rgba(114, 9, 183, 0.2);
            color: var(--danger-color);
        }

        .unread-badge {
            background-color: var(--warning-color);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 5px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Success Message */
        .success {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid var(--success-color);
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray-color);
            font-style: italic;
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
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-form input, .filter-form select {
                min-width: auto;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .action-buttons form {
                width: 100%;
            }
            
            .action-buttons select, .action-buttons .btn {
                width: 100%;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
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
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> <span>Главная</span></a>
            </li>
            <li><a href="migrants.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'migrants.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> <span>Мигранты</span></a>
            </li>
            <li><a href="add_migrant.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'add_migrant.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> <span>Добавить мигранта</span></a>
            </li>
            <li><a href="city_chat_admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'city_chat_admin.php' ? 'active' : ''; ?>">
                <i class="fas fa-city"></i> <span>Городские чаты</span></a>
            </li>
            <li><a href="chats.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'chats.php' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> <span>Личные чаты</span></a>
            </li>
            <li><a href="migration_data.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'migration_data.php' ? 'active' : ''; ?>">
                <i class="fas fa-database"></i> <span>Миграционные данные</span></a>
            </li>
            <li><a href="censorship_admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'censorship_admin.php' ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i> <span>Цензура</span></a>
            </li>
            <li><a href="news_admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'news_admin.php' ? 'active' : ''; ?>">
                <i class="fas fa-newspaper"></i> <span>Новости</span></a>
            </li>
            <li><a href="index.php" target="_blank">
                <i class="fas fa-external-link-alt"></i> <span>На сайт</span></a>
            </li>
            <li><a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Выход</span></a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-comments"></i> Управление чатами</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php if ($userAvatar): ?>
                        <img src="<?php echo htmlspecialchars($userAvatar); ?>" 
                             alt="<?php echo htmlspecialchars($userName); ?>"
                             style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <span><?php echo htmlspecialchars($userName); ?></span>
            </div>
        </div>

        <!-- Stats Cards -->
        <?php
        // Подсчет чатов по статусам для статистики
        $open_count = 0;
        $pending_count = 0;
        $closed_count = 0;
        
        foreach ($chats as $chat) {
            switch ($chat['status']) {
                case 'open':
                    $open_count++;
                    break;
                case 'pending':
                    $pending_count++;
                    break;
                case 'closed':
                    $closed_count++;
                    break;
            }
        }
        ?>
        <div class="stats-cards">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <div class="stat-value"><?php echo count($chats); ?></div>
                <div class="stat-label">Всего чатов</div>
            </div>
            <div class="stat-card open">
                <div class="stat-icon"><i class="fas fa-comment-dots"></i></div>
                <div class="stat-value"><?php echo $open_count; ?></div>
                <div class="stat-label">Открытые</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $pending_count; ?></div>
                <div class="stat-label">Ожидающие</div>
            </div>
            <div class="stat-card closed">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-value"><?php echo $closed_count; ?></div>
                <div class="stat-label">Закрытые</div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Поиск по имени, email, теме..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status">
                    <option value="">Все статусы</option>
                    <option value="open" <?php echo $status_filter == 'open' ? 'selected' : ''; ?>>Открытые</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Ожидающие</option>
                    <option value="closed" <?php echo $status_filter == 'closed' ? 'selected' : ''; ?>>Закрытые</option>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Фильтровать</button>
                <a href="chats.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Сбросить</a>
            </form>
        </div>

        <!-- Chats List -->
        <div class="chats-list">
            <div class="table-header">
                <h2>Список чатов (<?php echo count($chats); ?>)</h2>
                <div class="user-info">
                    <i class="fas fa-sync-alt" style="cursor: pointer; color: var(--primary-color);" 
                       onclick="window.location.reload()" title="Обновить"></i>
                    <small style="color: var(--gray-color);">Автообновление через <span id="timer">30</span> сек</small>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Пользователь</th>
                        <th>Тема</th>
                        <th>Город</th>
                        <th>Статус</th>
                        <th>Новых сообщений</th>
                        <th>Последнее обновление</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($chats)): ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                <i class="fas fa-comment-slash" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <div>Чаты не найдены</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($chats as $chat): ?>
                        <tr>
                            <td><?php echo $chat['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($chat['first_name'] . ' ' . $chat['last_name']); ?></strong><br>
                                <small style="color: var(--gray-color);"><?php echo htmlspecialchars($chat['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($chat['subject']); ?></td>
                            <td><?php echo htmlspecialchars($chat['city']); ?></td>
                            <td>
                                <span class="status <?php echo $chat['status']; ?>">
                                    <?php 
                                    $status_text = '';
                                    switch($chat['status']) {
                                        case 'open': $status_text = 'Открыт'; break;
                                        case 'pending': $status_text = 'Ожидает'; break;
                                        case 'closed': $status_text = 'Закрыт'; break;
                                        default: $status_text = $chat['status'];
                                    }
                                    echo $status_text;
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($chat['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $chat['unread_count']; ?></span>
                                <?php else: ?>
                                    <span style="color: var(--gray-color);">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($chat['updated_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="chat_admin.php?id=<?php echo $chat['id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Открыть чат">
                                        <i class="fas fa-comments"></i> Чат
                                    </a>
                                    
                                    <?php if (!$chat['admin_id'] && $chat['status'] == 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="chat_id" value="<?php echo $chat['id']; ?>">
                                            <button type="submit" name="assign_chat" 
                                                    class="btn btn-sm btn-success"
                                                    title="Взять чат в работу">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($chat['status'] == 'open'): ?>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Вы уверены, что хотите закрыть этот чат?')">
                                            <input type="hidden" name="chat_id" value="<?php echo $chat['id']; ?>">
                                            <button type="submit" name="close_chat" 
                                                    class="btn btn-sm btn-danger"
                                                    title="Закрыть чат">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Таймер автообновления
        let timeLeft = 30;
        const timerElement = document.getElementById('timer');
        
        if (timerElement) {
            const countdown = setInterval(() => {
                timeLeft--;
                timerElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    window.location.reload();
                }
            }, 1000);
        }
    </script>
</body>
</html>