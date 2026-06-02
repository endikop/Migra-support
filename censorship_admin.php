<?php
// Включаем буферизацию вывода ДО любого кода
if (ob_get_level() == 0) {
    ob_start();
}

session_start();
require_once 'config.php';
require_once 'banned_words.php';

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

$pageTitle = 'Управление цензурой | Панель администратора';

// Обработка действий
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Добавление нового запрещенного слова
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_word'])) {
    $word = trim($_POST['word'] ?? '');
    $replacement = trim($_POST['replacement'] ?? '***');
    $severity = $_POST['severity'] ?? 'medium';
    
    if (empty($word)) {
        $error = 'Введите запрещенное слово';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO banned_words (word, replacement, severity, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$word, $replacement, $severity, $_SESSION['user_id']]);
            $message = 'Запрещенное слово успешно добавлено';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Такое слово уже существует в базе данных';
            } else {
                $error = 'Ошибка при добавлении слова: ' . $e->getMessage();
            }
        }
    }
}

// Редактирование слова
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_word'])) {
    $id = $_POST['id'] ?? 0;
    $word = trim($_POST['word'] ?? '');
    $replacement = trim($_POST['replacement'] ?? '***');
    $severity = $_POST['severity'] ?? 'medium';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($word)) {
        $error = 'Введите запрещенное слово';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE banned_words SET word = ?, replacement = ?, severity = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$word, $replacement, $severity, $is_active, $id]);
            $message = 'Запрещенное слово успешно обновлено';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Такое слово уже существует в базе данных';
            } else {
                $error = 'Ошибка при обновлении слова: ' . $e->getMessage();
            }
        }
    }
}

// Удаление слова
if ($action === 'delete' && $id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM banned_words WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Запрещенное слово успешно удалено';
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении слова: ' . $e->getMessage();
    }
}

// Включение/выключение слова
if ($action === 'toggle' && $id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE banned_words SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Статус слова успешно изменен';
    } catch (PDOException $e) {
        $error = 'Ошибка при изменении статуса: ' . $e->getMessage();
    }
}

// Получение списка запрещенных слов
try {
    $stmt = $pdo->query("SELECT bw.*, u.username as creator FROM banned_words bw LEFT JOIN users u ON bw.created_by = u.id ORDER BY bw.created_at DESC");
    $bannedWords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bannedWords = [];
    $error = 'Ошибка при получении списка слов: ' . $e->getMessage();
}

// Получение логов цензуры
try {
    $stmt = $pdo->query("SELECT cl.*, u.username, u.first_name, u.last_name FROM censorship_logs cl LEFT JOIN users u ON cl.user_id = u.id ORDER BY cl.created_at DESC LIMIT 50");
    $censorshipLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $censorshipLogs = [];
}

// Получение статистики
try {
    // Общая статистика
    $stmt = $pdo->query("SELECT COUNT(*) as total_words, SUM(is_active) as active_words FROM banned_words");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Статистика по логам
    $stmt = $pdo->query("SELECT COUNT(*) as total_logs, action_taken FROM censorship_logs GROUP BY action_taken");
    $logStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total_words' => 0, 'active_words' => 0];
    $logStats = [];
}

// Получение слова для редактирования
$editWord = null;
if ($action === 'edit' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM banned_words WHERE id = ?");
        $stmt->execute([$id]);
        $editWord = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Ошибка при получении слова: ' . $e->getMessage();
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

        /* Карточки и статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray-color);
            margin-top: 5px;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .card-header h2, .card-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .badge {
            background: #e9ecef;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #495057;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .form-actions {
            padding: 0 20px 20px 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3a86ff, #8338ec);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(58, 134, 255, 0.3);
        }
        
        .btn-secondary {
            background: rgba(108, 117, 125, 0.2);
            color: #6c757d;
            border: 1px solid rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(108, 117, 125, 0.3);
        }
        
        .btn-warning {
            background: rgba(255, 158, 0, 0.2);
            color: #ff9e00;
            border: 1px solid rgba(255, 158, 0, 0.3);
        }
        
        .btn-success {
            background: rgba(56, 176, 0, 0.2);
            color: #38b000;
            border: 1px solid rgba(56, 176, 0, 0.3);
        }
        
        .btn-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .severity-badge, .status-badge, .action-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .severity-low {
            background: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
        }
        
        .severity-medium {
            background: rgba(255, 158, 0, 0.2);
            color: #cc7a00;
        }
        
        .severity-high {
            background: rgba(247, 37, 133, 0.2);
            color: #c2185b;
        }
        
        .status-active {
            background: rgba(56, 176, 0, 0.2);
            color: #2e7d32;
        }
        
        .status-inactive {
            background: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }
        
        .action-censored {
            background: rgba(67, 97, 238, 0.2);
            color: #4361ee;
        }
        
        .action-blocked {
            background: rgba(247, 37, 133, 0.2);
            color: #f72585;
        }
        
        .action-warned {
            background: rgba(255, 158, 0, 0.2);
            color: #ff9e00;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .success {
            background: rgba(56, 176, 0, 0.1);
            border-left: 4px solid #38b000;
            padding: 12px 20px;
            margin: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2e7d32;
        }
        
        .error {
            background: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            padding: 12px 20px;
            margin: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #c82333;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-color);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--border-color);
            margin-bottom: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .text-truncate {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
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
    </style>
</head>
<body>
    <!-- Подключаем единую навигацию -->
    <?php include_once 'admin_navigation.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Управление цензурой</h1>
            <?php 
            // Получаем данные пользователя для отображения
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

        <?php if ($message): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3a86ff, #8338ec);">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['total_words'] ?? 0; ?></div>
                    <div class="stat-label">Всего запрещенных слов</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #38b000, #3a86ff);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['active_words'] ?? 0; ?></div>
                    <div class="stat-label">Активных слов</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ff006e, #ff9e00);">
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo count($censorshipLogs); ?></div>
                    <div class="stat-label">Логов цензуры</div>
                </div>
            </div>
        </div>

        <!-- Форма добавления/редактирования слова -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> <?php echo $editWord ? 'Редактирование слова' : 'Добавление нового слова'; ?></h3>
            </div>
            
            <form method="POST">
                <?php if ($editWord): ?>
                    <input type="hidden" name="id" value="<?php echo $editWord['id']; ?>">
                    <input type="hidden" name="edit_word" value="1">
                <?php else: ?>
                    <input type="hidden" name="add_word" value="1">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="word"><i class="fas fa-font"></i> Запрещенное слово *</label>
                        <input type="text" id="word" name="word" class="form-control" 
                               value="<?php echo htmlspecialchars($editWord['word'] ?? ''); ?>" 
                               required
                               placeholder="Введите слово или фразу">
                        <small>Слово будет проверяться без учета регистра</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="replacement"><i class="fas fa-exchange-alt"></i> Замена</label>
                        <input type="text" id="replacement" name="replacement" class="form-control" 
                               value="<?php echo htmlspecialchars($editWord['replacement'] ?? '***'); ?>"
                               placeholder="***">
                        <small>На что заменить запрещенное слово</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="severity"><i class="fas fa-exclamation-triangle"></i> Серьезность</label>
                        <select id="severity" name="severity" class="form-control">
                            <option value="low" <?php echo ($editWord['severity'] ?? '') == 'low' ? 'selected' : ''; ?>>Низкая</option>
                            <option value="medium" <?php echo ($editWord['severity'] ?? 'medium') == 'medium' ? 'selected' : ''; ?>>Средняя</option>
                            <option value="high" <?php echo ($editWord['severity'] ?? '') == 'high' ? 'selected' : ''; ?>>Высокая</option>
                        </select>
                    </div>
                    
                    <?php if ($editWord): ?>
                        <div class="form-group">
                            <label for="is_active"><i class="fas fa-power-off"></i> Статус</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" value="1" 
                                       <?php echo $editWord['is_active'] ? 'checked' : ''; ?>>
                                <label for="is_active">Активно</label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editWord ? 'Сохранить изменения' : 'Добавить слово'; ?>
                    </button>
                    
                    <?php if ($editWord): ?>
                        <a href="censorship_admin.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Отмена
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Список запрещенных слов -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Список запрещенных слов</h3>
                <div class="header-actions">
                    <span class="badge">Всего: <?php echo count($bannedWords); ?></span>
                </div>
            </div>
            
            <?php if (empty($bannedWords)): ?>
                <div class="empty-state">
                    <i class="fas fa-ban"></i>
                    <h4>Нет запрещенных слов</h4>
                    <p>Добавьте первое запрещенное слово, используя форму выше</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Слово</th>
                                <th>Замена</th>
                                <th>Серьезность</th>
                                <th>Статус</th>
                                <th>Создал</th>
                                <th>Дата создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bannedWords as $word): ?>
                                <tr>
                                    <td><?php echo $word['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($word['word']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($word['replacement']); ?></td>
                                    <td>
                                        <span class="severity-badge severity-<?php echo $word['severity']; ?>">
                                            <?php 
                                            $severityLabels = [
                                                'low' => 'Низкая',
                                                'medium' => 'Средняя',
                                                'high' => 'Высокая'
                                            ];
                                            echo $severityLabels[$word['severity']] ?? $word['severity'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $word['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $word['is_active'] ? 'Активно' : 'Неактивно'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($word['creator'] ?? 'Система'); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($word['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="censorship_admin.php?action=edit&id=<?php echo $word['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="censorship_admin.php?action=toggle&id=<?php echo $word['id']; ?>" 
                                               class="btn btn-sm btn-<?php echo $word['is_active'] ? 'warning' : 'success'; ?>" 
                                               title="<?php echo $word['is_active'] ? 'Выключить' : 'Включить'; ?>">
                                                <i class="fas fa-power-off"></i>
                                            </a>
                                            <a href="censorship_admin.php?action=delete&id=<?php echo $word['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Вы уверены, что хотите удалить это слово?')"
                                               title="Удалить">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Логи цензуры -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Логи цензуры</h3>
                <div class="header-actions">
                    <span class="badge">Последние 50 записей</span>
                </div>
            </div>
            
            <?php if (empty($censorshipLogs)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h4>Нет логов цензуры</h4>
                    <p>Логи будут появляться при использовании цензуры в чате</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Пользователь</th>
                                <th>Оригинальный текст</th>
                                <th>Отцензурированный текст</th>
                                <th>Действие</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($censorshipLogs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td>
                                        <?php if ($log['username']): ?>
                                            <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                            <br><small>@<?php echo htmlspecialchars($log['username']); ?></small>
                                        <?php else: ?>
                                            Неизвестный пользователь
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-truncate" title="<?php echo htmlspecialchars($log['original_text']); ?>">
                                        <?php echo htmlspecialchars(mb_strimwidth($log['original_text'], 0, 50, '...')); ?>
                                    </td>
                                    <td class="text-truncate" title="<?php echo htmlspecialchars($log['censored_text']); ?>">
                                        <?php echo htmlspecialchars(mb_strimwidth($log['censored_text'], 0, 50, '...')); ?>
                                    </td>
                                    <td>
                                        <span class="action-badge action-<?php echo $log['action_taken']; ?>">
                                            <?php 
                                            $actionLabels = [
                                                'censored' => 'Отцензурировано',
                                                'blocked' => 'Заблокировано',
                                                'warned' => 'Предупреждение'
                                            ];
                                            echo $actionLabels[$log['action_taken']] ?? $log['action_taken'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>