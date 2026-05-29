<?php
session_start();
require_once 'config.php';

// Подключаем файл с данными аватара
require_once 'include_avatar.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Получение списка мигрантов
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT * FROM users WHERE user_type = 'migrant'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR passport_number LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$migrants = $stmt->fetchAll();

// Изменение статуса мигранта
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $user_id = $_POST['user_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $user_id]);
        
        // Если обновляем статус текущего пользователя, обновляем его сессию
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
            $_SESSION['status'] = $status;
        }
        
        $_SESSION['success'] = 'Статус обновлен успешно!';
        header('Location: migrants.php');
        exit;
    }
    
    if (isset($_POST['delete_migrant'])) {
        $user_id = $_POST['user_id'];
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'migrant'");
        $stmt->execute([$user_id]);
        
        $_SESSION['success'] = 'Мигрант удален успешно!';
        header('Location: migrants.php');
        exit;
    }
}

$pageTitle = 'Управление мигрантами | Админ-панель';
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
        /* Копируем все стили из dashboard.php сюда */
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

        /* User Info */
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

        /* Grid и карточки */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .card-header h2 {
            font-size: 1.3rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-icon {
            font-size: 2.2rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

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

        /* Формы */
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Кнопки */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
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
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--gray-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #c2185b;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #0a8ea8;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #5a088f;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        /* Таблица */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border-bottom: 2px solid var(--border-color);
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Статусы */
        .status {
            padding: 5px 12px;
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

        /* No data */
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray-color);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #ddd;
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
            .grid {
                grid-template-columns: 1fr;
            }
            .filter-form {
                flex-direction: column;
            }
            .data-table {
                display: block;
                overflow-x: auto;
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
            <h1><i class="fas fa-users"></i> Управление мигрантами</h1>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="card" style="background-color: rgba(76, 201, 240, 0.1); border-left: 4px solid var(--success-color);">
                <i class="fas fa-check-circle" style="color: var(--success-color); margin-right: 10px;"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <?php
        $active_count = 0;
        $pending_count = 0;
        $inactive_count = 0;
        
        foreach ($migrants as $migrant) {
            switch ($migrant['status']) {
                case 'active': $active_count++; break;
                case 'pending': $pending_count++; break;
                case 'inactive': $inactive_count++; break;
            }
        }
        ?>
        
        <div class="grid">
            <div class="card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?php echo count($migrants); ?></div>
                <div class="stat-label">Всего мигрантов</div>
            </div>
            <div class="card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $active_count; ?></div>
                <div class="stat-label">Активные</div>
            </div>
            <div class="card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $pending_count; ?></div>
                <div class="stat-label">Ожидающие</div>
            </div>
            <div class="card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-value"><?php echo $inactive_count; ?></div>
                <div class="stat-label">Неактивные</div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Фильтры</h2>
            </div>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="Поиск по имени, email, паспорту..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <select name="status" class="form-control">
                        <option value="">Все статусы</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Активные</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Ожидающие</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Неактивные</option>
                    </select>
                </div>
                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Фильтровать</button>
                    <a href="migrants.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Сбросить</a>
                </div>
            </form>
        </div>

        <!-- Список мигрантов -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Список мигрантов (<?php echo count($migrants); ?>)</h2>
                <a href="add_migrant.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Добавить мигранта</a>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Фамилия</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Страна</th>
                        <th>Паспорт</th>
                        <th>Статус</th>
                        <th>Дата регистрации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($migrants)): ?>
                        <tr>
                            <td colspan="10" class="no-data">
                                <i class="fas fa-user-slash"></i>
                                <div>Мигранты не найдены</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($migrants as $migrant): ?>
                        <tr>
                            <td><?php echo $migrant['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($migrant['first_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($migrant['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($migrant['email']); ?></td>
                            <td><?php echo htmlspecialchars($migrant['phone']); ?></td>
                            <td><?php echo htmlspecialchars($migrant['country_of_origin']); ?></td>
                            <td><code><?php echo htmlspecialchars($migrant['passport_number']); ?></code></td>
                            <td>
                                <span class="status <?php echo $migrant['status']; ?>">
                                    <?php 
                                    $status_text = '';
                                    switch($migrant['status']) {
                                        case 'active': $status_text = 'Активен'; break;
                                        case 'pending': $status_text = 'Ожидание'; break;
                                        case 'inactive': $status_text = 'Неактивен'; break;
                                        default: $status_text = $migrant['status'];
                                    }
                                    echo $status_text;
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($migrant['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="edit_migrant.php?id=<?php echo $migrant['id']; ?>" class="btn btn-sm btn-warning" title="Редактировать"><i class="fas fa-edit"></i></a>
                                    <a href="migration_data.php?user_id=<?php echo $migrant['id']; ?>" class="btn btn-sm btn-success" title="Миграционные данные"><i class="fas fa-database"></i></a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $migrant['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-control" style="width: auto; padding: 5px 10px; font-size: 0.8rem;" title="Изменить статус">
                                            <option value="pending" <?php echo $migrant['status'] == 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                            <option value="active" <?php echo $migrant['status'] == 'active' ? 'selected' : ''; ?>>Активен</option>
                                            <option value="inactive" <?php echo $migrant['status'] == 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этого мигранта?')">
                                        <input type="hidden" name="user_id" value="<?php echo $migrant['id']; ?>">
                                        <button type="submit" name="delete_migrant" class="btn btn-sm btn-danger" title="Удалить"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>