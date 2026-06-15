<?php
session_start();
require_once 'config.php';

// Подключаем файл с данными аватара
require_once 'include_avatar.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Миграционные данные | Админ-панель';

// Получаем ID мигранта из GET параметра
$migrant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Если ID не указан, показываем список мигрантов
$show_list = ($migrant_id == 0);

// Типы виз
$visa_types = [
    '' => 'Не указано',
    'tourist' => 'Туристическая',
    'business' => 'Бизнес',
    'work' => 'Рабочая',
    'student' => 'Студенческая',
    'family' => 'Семейная',
    'transit' => 'Транзитная',
    'diplomatic' => 'Дипломатическая',
    'service' => 'Служебная',
    'humanitarian' => 'Гуманитарная'
];

$migrant = null;
$errors = [];
$success = false;

// Если ID указан, получаем данные мигранта
if (!$show_list) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.username, 
                   u.country_of_origin, u.passport_number, u.status, u.phone, u.city,
                   md.* 
            FROM users u 
            LEFT JOIN migration_data md ON u.id = md.user_id 
            WHERE u.id = ? AND u.user_type = 'migrant'
        ");
        $stmt->execute([$migrant_id]);
        $migrant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$migrant) {
            $_SESSION['error'] = 'Мигрант не найден';
            header('Location: migration_data.php');
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
    }
}

// Обработка формы редактирования миграционных данных
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$show_list) {
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
    
    // Валидация дат
    if (!empty($visa_issue_date) && !empty($visa_expiry_date) && strtotime($visa_issue_date) > strtotime($visa_expiry_date)) {
        $errors[] = 'Дата выдачи визы не может быть позже даты окончания срока действия';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Проверяем, есть ли уже запись в migration_data
            $stmt = $pdo->prepare("SELECT id FROM migration_data WHERE user_id = ?");
            $stmt->execute([$migrant_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
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
                        registration_date = ?,
                        updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $visa_type, $visa_number, $visa_issue_date, $arrival_date,
                    $visa_expiry_date, $purpose_of_stay, $employer_name,
                    $work_permit_number, $residential_address, $registration_date,
                    $migrant_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO migration_data (
                        user_id, visa_type, visa_number, visa_issue_date,
                        arrival_date, visa_expiry_date, purpose_of_stay,
                        employer_name, work_permit_number, residential_address,
                        registration_date, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $migrant_id, $visa_type, $visa_number, $visa_issue_date,
                    $arrival_date, $visa_expiry_date, $purpose_of_stay,
                    $employer_name, $work_permit_number, $residential_address,
                    $registration_date
                ]);
            }
            
            $pdo->commit();
            $success = true;
            $_SESSION['success'] = 'Миграционные данные успешно обновлены';
            
            // Обновляем данные для отображения
            $stmt = $pdo->prepare("
                SELECT u.*, md.* 
                FROM users u 
                LEFT JOIN migration_data md ON u.id = md.user_id 
                WHERE u.id = ?
            ");
            $stmt->execute([$migrant_id]);
            $migrant = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Ошибка при сохранении: ' . $e->getMessage();
        }
    }
}

// Получение списка мигрантов для отображения (если ID не указан)
$migrants_list = [];
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

if ($show_list) {
    try {
        $sql = "
            SELECT u.id, u.first_name, u.last_name, u.email, u.passport_number, 
                   u.country_of_origin, u.status, u.created_at, u.phone,
                   md.visa_type, md.visa_expiry_date, md.visa_number
            FROM users u
            LEFT JOIN migration_data md ON u.id = md.user_id
            WHERE u.user_type = 'migrant'
        ";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.passport_number LIKE ?)";
            $search_term = "%$search%";
            $params = [$search_term, $search_term, $search_term, $search_term];
        }
        
        if (!empty($status_filter)) {
            $sql .= " AND u.status = ?";
            $params[] = $status_filter;
        }
        
        $sql .= " ORDER BY u.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $migrants_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $errors[] = 'Ошибка при загрузке списка: ' . $e->getMessage();
    }
}

// Функции для форматирования
function formatDate($date) {
    if (empty($date) || $date == '0000-00-00') {
        return '';
    }
    return date('Y-m-d', strtotime($date));
}

function formatDateDisplay($date) {
    if (empty($date) || $date == '0000-00-00') {
        return '—';
    }
    return date('d.m.Y', strtotime($date));
}

function getVisaStatus($expiryDate) {
    if (empty($expiryDate)) return 'not_set';
    
    $today = strtotime(date('Y-m-d'));
    $expiry = strtotime($expiryDate);
    $daysLeft = floor(($expiry - $today) / (60 * 60 * 24));
    
    if ($expiry < $today) {
        return 'expired';
    } elseif ($daysLeft <= 30) {
        return 'expiring_soon';
    } else {
        return 'valid';
    }
}

function getVisaStatusClass($status) {
    $classes = [
        'valid' => 'visa-valid',
        'expiring_soon' => 'visa-expiring',
        'expired' => 'visa-expired',
        'not_set' => 'visa-not-set'
    ];
    return $classes[$status] ?? 'visa-not-set';
}

function getVisaStatusText($status) {
    $texts = [
        'valid' => 'Действительна',
        'expiring_soon' => 'Истекает скоро',
        'expired' => 'Просрочена',
        'not_set' => 'Не указана'
    ];
    return $texts[$status] ?? 'Не указана';
}

// Подсчет статистики
$active_count = 0;
$pending_count = 0;
$inactive_count = 0;
$has_visa_count = 0;
$expiring_visa_count = 0;

foreach ($migrants_list as $m) {
    switch ($m['status']) {
        case 'active': $active_count++; break;
        case 'pending': $pending_count++; break;
        case 'inactive': $inactive_count++; break;
    }
    if (!empty($m['visa_type'])) $has_visa_count++;
    $visaStatus = getVisaStatus($m['visa_expiry_date'] ?? '');
    if ($visaStatus == 'expiring_soon') $expiring_visa_count++;
}

// Получаем имя и аватар пользователя
$userName = isset($userName) ? $userName : 'Администратор';
$userAvatar = isset($userAvatar) ? $userAvatar : null;
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
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Grid и карточки */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--dark-color);
        }

        .stat-label {
            color: var(--gray-color);
            font-size: 0.85rem;
            margin-top: 5px;
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
            vertical-align: middle;
        }

        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Статусы */
        .status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
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

        /* Visa Status */
        .visa-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .visa-valid {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
        }

        .visa-expiring {
            background-color: rgba(255, 158, 0, 0.2);
            color: #cc7e00;
        }

        .visa-expired {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--warning-color);
        }

        .visa-not-set {
            background-color: rgba(108, 117, 125, 0.2);
            color: var(--gray-color);
        }

        /* Badge ID */
        .id-badge {
            background-color: var(--light-color);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            color: var(--gray-color);
            font-weight: normal;
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

        .migrant-info-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .migrant-info-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .migrant-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .form-section {
            margin-bottom: 30px;
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

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 40px;
        }

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

        .alert-success {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid var(--success-color);
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
        }

        .error-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .error-list li {
            color: var(--warning-color);
            padding: 5px 0;
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
                grid-template-columns: 1fr 1fr;
            }
            .filter-form {
                flex-direction: column;
            }
            .data-table {
                display: block;
                overflow-x: auto;
            }
            .migrant-info-card {
                flex-direction: column;
                text-align: center;
            }
            .migrant-info-left {
                flex-direction: column;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar - единая панель навигации -->
    <!-- Подключаем единую навигацию -->
    <?php include_once 'admin_navigation.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-comments"></i> Управление чатами</h1>
            <?php 
            // Получаем данные администратора аналогично dashboard и migrants
            $userAvatar = function_exists('getAdminUserAvatar') ? getAdminUserAvatar() : (isset($userAvatar) ? $userAvatar : null);
            $userName = function_exists('getAdminUserName') ? getAdminUserName() : (isset($userName) ? $userName : 'Администратор');
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

        <?php if ($show_list): ?>
            <!-- ==================== РЕЖИМ СПИСКА ==================== -->
            
            <!-- Статистика -->
            <div class="grid">
                <div class="card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo count($migrants_list); ?></div>
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
                    <div class="stat-icon"><i class="fas fa-passport"></i></div>
                    <div class="stat-value"><?php echo $has_visa_count; ?></div>
                    <div class="stat-label">С визой</div>
                </div>
                <?php if ($expiring_visa_count > 0): ?>
                <div class="card">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-value"><?php echo $expiring_visa_count; ?></div>
                    <div class="stat-label">Виза истекает скоро</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Фильтры -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="filter-form">
                    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
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
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Найти</button>
                            <a href="migration_data.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Сбросить</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Список мигрантов -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2><i class="fas fa-list"></i> Список мигрантов (<?php echo count($migrants_list); ?>)</h2>
                    <a href="add_migrant.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Добавить мигранта</a>
                </div>
                
                <?php if (empty($migrants_list)): ?>
                    <div class="no-data">
                        <i class="fas fa-user-slash"></i>
                        <div>Мигранты не найдены</div>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>Email / Телефон</th>
                                <th>Паспорт</th>
                                <th>Тип визы</th>
                                <th>Номер визы</th>
                                <th>Срок действия</th>
                                <th>Статус визы</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($migrants_list as $m): 
                                $visaStatus = getVisaStatus($m['visa_expiry_date'] ?? '');
                            ?>
                                <tr>
                                    <td><span class="id-badge">#<?php echo $m['id']; ?></span></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($m['email']); ?><br>
                                        <small><?php echo htmlspecialchars($m['phone'] ?? '—'); ?></small>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($m['passport_number']); ?></code><br><small><?php echo htmlspecialchars($m['country_of_origin']); ?></small></td>
                                    <td><?php echo $visa_types[$m['visa_type']] ?? '—'; ?></td>
                                    <td><?php echo htmlspecialchars($m['visa_number'] ?? '—'); ?></td>
                                    <td><?php echo formatDateDisplay($m['visa_expiry_date'] ?? ''); ?></td>
                                    <td>
                                        <span class="visa-badge <?php echo getVisaStatusClass($visaStatus); ?>">
                                            <?php echo getVisaStatusText($visaStatus); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $m['status']; ?>">
                                            <?php 
                                            $status_texts = ['active' => 'Активен', 'pending' => 'Ожидание', 'inactive' => 'Неактивен'];
                                            echo $status_texts[$m['status']] ?? $m['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="edit_migrant.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-primary" title="Редактировать"><i class="fas fa-user-edit"></i></a>
                                            <a href="migration_data.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-primary" title="Миграционные данные" style="background-color: var(--success-color);"><i class="fas fa-passport"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- ==================== РЕЖИМ РЕДАКТИРОВАНИЯ ==================== -->
            
            <div class="back-button">
                <a href="migration_data.php">
                    <i class="fas fa-arrow-left"></i> Назад к списку мигрантов
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <h4><i class="fas fa-exclamation-circle"></i> Ошибки при сохранении</h4>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="edit-form-container">
                <!-- Информация о мигранте с ID -->
                <div class="migrant-info-card">
                    <div class="migrant-info-left">
                        <div class="migrant-avatar">
                            <?php echo strtoupper(substr($migrant['first_name'] ?? '', 0, 1) . substr($migrant['last_name'] ?? '', 0, 1)); ?>
                        </div>
                        <div>
                            <h3><?php echo htmlspecialchars(($migrant['first_name'] ?? '') . ' ' . ($migrant['last_name'] ?? '')); ?></h3>
                            <p><i class="fas fa-id-card"></i> ID: <strong>#<?php echo $migrant['id']; ?></strong></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($migrant['email'] ?? ''); ?></p>
                            <p><i class="fas fa-passport"></i> Паспорт: <?php echo htmlspecialchars($migrant['passport_number'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div>
                        <span class="status <?php echo $migrant['status']; ?>">
                            <?php 
                            $status_texts = ['active' => 'Активен', 'pending' => 'Ожидание', 'inactive' => 'Неактивен'];
                            echo $status_texts[$migrant['status']] ?? $migrant['status'];
                            ?>
                        </span>
                    </div>
                </div>

                <form method="POST" action="">
                    <!-- Визовая информация -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-passport"></i> Визовая информация
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Тип визы</label>
                                <select name="visa_type" class="form-control">
                                    <?php foreach ($visa_types as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" 
                                            <?php echo ($migrant['visa_type'] ?? '') === $value ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Номер визы</label>
                                <input type="text" 
                                       name="visa_number" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($migrant['visa_number'] ?? ''); ?>"
                                       placeholder="Например: V12345678">
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Дата выдачи визы</label>
                                <input type="date" 
                                       name="visa_issue_date" 
                                       class="form-control" 
                                       value="<?php echo formatDate($migrant['visa_issue_date'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Срок действия до</label>
                                <input type="date" 
                                       name="visa_expiry_date" 
                                       class="form-control" 
                                       value="<?php echo formatDate($migrant['visa_expiry_date'] ?? ''); ?>">
                                <small style="color: var(--gray-color);">Дата, до которой действительна виза</small>
                            </div>
                        </div>
                    </div>

                    <!-- Информация о пребывании -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-plane-arrival"></i> Информация о пребывании
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Дата прибытия</label>
                                <input type="date" 
                                       name="arrival_date" 
                                       class="form-control" 
                                       value="<?php echo formatDate($migrant['arrival_date'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Дата регистрации</label>
                                <input type="date" 
                                       name="registration_date" 
                                       class="form-control" 
                                       value="<?php echo formatDate($migrant['registration_date'] ?? ''); ?>">
                                <small style="color: var(--gray-color);">Дата постановки на миграционный учет</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Цель пребывания</label>
                            <textarea name="purpose_of_stay" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Например: Трудоустройство, Обучение, Воссоединение с семьей..."><?php echo htmlspecialchars($migrant['purpose_of_stay'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Адрес проживания в Беларуси</label>
                            <textarea name="residential_address" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Полный адрес места проживания"><?php echo htmlspecialchars($migrant['residential_address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Трудовая деятельность -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-briefcase"></i> Трудовая деятельность
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Работодатель</label>
                                <input type="text" 
                                       name="employer_name" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($migrant['employer_name'] ?? ''); ?>"
                                       placeholder="Название организации-нанимателя">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Номер разрешения на работу</label>
                                <input type="text" 
                                       name="work_permit_number" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($migrant['work_permit_number'] ?? ''); ?>"
                                       placeholder="Номер специального разрешения">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="migration_data.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Отмена
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить миграционные данные
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>