<?php
session_start();
require_once 'config.php';

// Подключаем файл с данными аватара
require_once 'include_avatar.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Функция для проверки, является ли пользователь администратором
function isAdminUser($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_type = $stmt->fetchColumn();
        return $user_type === 'admin';
    } catch (Exception $e) {
        return false;
    }
}

// Определяем текущую страницу для активного пункта меню
$current_page = basename($_SERVER['PHP_SELF']);

// Получение списка городов
$cities = ['minsk', 'grodno', 'gomel', 'vitebsk', 'mogilev', 'brest'];
$selected_city = $_GET['city'] ?? '';

// Получение статистики по городам
$city_stats = [];
foreach ($cities as $city) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM city_chat_messages WHERE city = ?");
    $stmt->execute([$city]);
    $city_stats[$city]['total_messages'] = $stmt->fetchColumn();
    
    // Последнее сообщение
    $stmt = $pdo->prepare("SELECT created_at FROM city_chat_messages WHERE city = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$city]);
    $last_msg = $stmt->fetchColumn();
    $city_stats[$city]['last_message'] = $last_msg ? date('d.m.Y H:i', strtotime($last_msg)) : 'Нет сообщений';
    
    // Уникальные участники
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT sender_id) FROM city_chat_messages WHERE city = ?");
    $stmt->execute([$city]);
    $city_stats[$city]['unique_users'] = $stmt->fetchColumn();
    
    // Сегодняшние сообщения
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM city_chat_messages WHERE city = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$city]);
    $city_stats[$city]['today_messages'] = $stmt->fetchColumn();
}

// Получение сообщений для выбранного города
$messages = [];
$total_messages = 0;
if ($selected_city) {
    $search = $_GET['search'] ?? '';
    $date_filter = $_GET['date'] ?? '';
    
    $sql = "SELECT cm.*, u.first_name, u.last_name, u.email, u.user_type 
            FROM city_chat_messages cm 
            LEFT JOIN users u ON cm.sender_id = u.id 
            WHERE cm.city = ?";
    $params = [$selected_city];
    
    if (!empty($search)) {
        $sql .= " AND (cm.message_text LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $search_term = "%$search%";
        array_push($params, $search_term, $search_term, $search_term, $search_term);
    }
    
    if (!empty($date_filter)) {
        $sql .= " AND DATE(cm.created_at) = ?";
        $params[] = $date_filter;
    }
    
    $sql .= " ORDER BY cm.created_at DESC";
    
    // Получаем общее количество для пагинации
    $count_sql = str_replace("SELECT cm.*, u.first_name, u.last_name, u.email, u.user_type", "SELECT COUNT(*)", $sql);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_messages = $count_stmt->fetchColumn();
    
    // Ограничиваем количество сообщений для отображения
    $sql .= " LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
}

// Очистка чата
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear_chat'])) {
        $city_to_clear = $_POST['city'];
        $clear_type = $_POST['clear_type'];
        $days_old = $_POST['days_old'] ?? 0;
        
        try {
            $pdo->beginTransaction();
            
            if ($clear_type === 'all') {
                // Удалить все сообщения
                $stmt = $pdo->prepare("DELETE FROM city_chat_messages WHERE city = ?");
                $stmt->execute([$city_to_clear]);
                $deleted_count = $stmt->rowCount();
                
                $_SESSION['success'] = "Чат города $city_to_clear очищен. Удалено сообщений: $deleted_count";
            } 
            elseif ($clear_type === 'old') {
                // Удалить старые сообщения
                $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days_old days"));
                $stmt = $pdo->prepare("DELETE FROM city_chat_messages WHERE city = ? AND created_at < ?");
                $stmt->execute([$city_to_clear, $cutoff_date]);
                $deleted_count = $stmt->rowCount();
                
                $_SESSION['success'] = "Удалены сообщения старше $days_old дней. Удалено: $deleted_count";
            }
            
            $pdo->commit();
            header("Location: city_chat_admin.php?city=$city_to_clear");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Ошибка при очистке чата: " . $e->getMessage();
        }
    }
}

// Удаление конкретного сообщения через GET параметр
if (isset($_GET['delete_message'])) {
    $message_id = $_GET['delete_message'];
    $city_to_clear = $_GET['city'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM city_chat_messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $deleted_count = $stmt->rowCount();
        
        if ($deleted_count > 0) {
            $_SESSION['success'] = "Сообщение успешно удалено";
        } else {
            $_SESSION['error'] = "Сообщение не найдено";
        }
        
        $pdo->commit();
        header("Location: city_chat_admin.php?city=$city_to_clear");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Ошибка при удалении сообщения: " . $e->getMessage();
        header("Location: city_chat_admin.php?city=$city_to_clear");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление городскими чатами | Админ-панель</title>
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
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card.active {
            background-color: #f8f9ff;
            border-top-color: var(--success-color);
        }

        .stat-card .city-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 15px;
            text-transform: capitalize;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-card .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark-color);
            line-height: 1;
            margin: 10px 0;
        }

        .stat-card .stat-label {
            color: var(--gray-color);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }

        .stat-card .last-message {
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-card .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: auto;
        }

        .stat-card .badge.today {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
            border: 1px solid rgba(76, 201, 240, 0.3);
        }

        /* City Selector */
        .city-selector {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .city-selector h2 {
            color: var(--dark-color);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .city-selector h2 span {
            color: var(--primary-color);
            text-transform: capitalize;
        }

        .city-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
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

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .city-btn {
            padding: 15px 25px;
            background-color: white;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--dark-color);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-transform: capitalize;
            flex: 1;
            min-width: 130px;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .city-btn:hover {
            background-color: #f8f9fa;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .city-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        /* Filters */
        .filters {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .filter-form input, .filter-form select {
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            flex: 1;
            min-width: 200px;
            transition: all 0.3s;
            background-color: white;
        }

        .filter-form input:focus, .filter-form select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        /* Chat Messages */
        .chat-container {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px;
            background-color: #f8f9fa;
            border-bottom: 1px solid var(--border-color);
        }

        .chat-header h2 {
            color: var(--dark-color);
            font-size: 1.5rem;
            text-transform: capitalize;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-messages {
            max-height: 600px;
            overflow-y: auto;
            padding: 25px;
            background-color: white;
        }

        .message {
            margin-bottom: 25px;
            padding: 25px;
            border-radius: 10px;
            background-color: white;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            position: relative;
        }

        .message:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .message::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 4px 0 0 4px;
        }

        .message.admin::before {
            background-color: var(--success-color);
        }

        .message.migrant::before {
            background-color: var(--warning-color);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .sender-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sender-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .sender-details {
            flex: 1;
        }

        .sender-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .sender-email {
            color: var(--gray-color);
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .admin-badge {
            background-color: var(--success-color);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .migrant-badge {
            background-color: var(--warning-color);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .message-time {
            color: var(--gray-color);
            font-size: 0.9rem;
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
            flex-shrink: 0;
            border: 1px solid var(--border-color);
        }

        .message-text {
            color: var(--dark-color);
            line-height: 1.7;
            padding: 20px 0;
            font-size: 1.05rem;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .message-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            margin-top: 20px;
        }

        /* Clear Chat Form */
        .clear-form {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 2px solid var(--danger-color);
            background-color: rgba(114, 9, 183, 0.05);
        }

        .clear-form h3 {
            color: var(--danger-color);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1rem;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: white;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(114, 9, 183, 0.1);
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 15px 20px;
            background-color: white;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
            flex: 1;
            min-width: 200px;
        }

        .radio-label:hover {
            background-color: #f8f9fa;
            border-color: var(--border-color);
            transform: translateY(-2px);
        }

        .radio-label input[type="radio"] {
            width: auto;
            margin: 0;
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
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-color);
        }

        .no-data i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        .no-data h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: var(--gray-color);
        }

        .no-data p {
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.7;
            font-size: 1.1rem;
        }

        /* Message Counter */
        .message-counter {
            background-color: var(--primary-color);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 10px;
        }

        /* Scrollbar Styling */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
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

        @media (max-width: 992px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .city-buttons {
                flex-direction: column;
            }
            
            .city-btn {
                min-width: 100%;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-form input, .filter-form select {
                min-width: auto;
                width: 100%;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .radio-label {
                min-width: 100%;
            }
            
            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .sender-info {
                width: 100%;
            }
            
            .message-time {
                align-self: flex-start;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .chat-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 1.8rem;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            
            .message {
                padding: 20px;
            }
        }

        /* Instructions */
        .instructions {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .instructions h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .instructions p {
            color: var(--gray-color);
            line-height: 1.7;
            margin-bottom: 15px;
        }

        .instructions ul {
            color: var(--gray-color);
            padding-left: 20px;
            margin-top: 15px;
        }

        .instructions li {
            margin-bottom: 10px;
            line-height: 1.6;
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
            <h1><i class="fas fa-city"></i> Управление городскими чатами</h1>
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
                <h2>Управление городскими чатами</h2>
                <p>Просматривайте, фильтруйте и управляйте сообщениями в чатах всех городов. Очищайте историю сообщений при необходимости.</p>
            </div>
        </div>

        <!-- Success & Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <?php foreach ($cities as $city): ?>
                <a href="city_chat_admin.php?city=<?php echo $city; ?>" style="text-decoration: none;">
                    <div class="stat-card <?php echo $selected_city == $city ? 'active' : ''; ?>">
                        <div class="city-name">
                            <i class="fas fa-city"></i>
                            <?php echo ucfirst($city); ?>
                            <?php if ($city_stats[$city]['today_messages'] > 0): ?>
                                <span class="badge today">+<?php echo $city_stats[$city]['today_messages']; ?> сегодня</span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-value"><?php echo $city_stats[$city]['total_messages']; ?></div>
                        <div class="stat-label">
                            <i class="fas fa-comment"></i>
                            Сообщений
                        </div>
                        <div class="stat-label">
                            <i class="fas fa-users"></i>
                            Участников: <?php echo $city_stats[$city]['unique_users']; ?>
                        </div>
                        <div class="last-message">
                            <i class="far fa-clock"></i>
                            Последнее: <?php echo $city_stats[$city]['last_message']; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($selected_city): ?>
            <!-- City Selector -->
            <div class="city-selector">
                <h2><i class="fas fa-map-marker-alt"></i> Чат города: <span><?php echo $selected_city; ?></span></h2>
                <div class="city-buttons">
                    <?php foreach ($cities as $city): ?>
                        <a href="city_chat_admin.php?city=<?php echo $city; ?>" 
                           class="city-btn <?php echo $selected_city == $city ? 'active' : ''; ?>">
                            <i class="fas fa-<?php echo $selected_city == $city ? 'check' : 'city'; ?>"></i>
                            <?php echo ucfirst($city); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Instructions -->
            <div class="instructions">
                <h2><i class="fas fa-info-circle"></i> Инструкция по управлению чатом</h2>
                <p>Выбран город <strong><?php echo ucfirst($selected_city); ?></strong>. Всего сообщений: <strong><?php echo $city_stats[$selected_city]['total_messages']; ?></strong>, участников: <strong><?php echo $city_stats[$selected_city]['unique_users']; ?></strong>.</p>
                <ul>
                    <li>Используйте фильтры для поиска конкретных сообщений</li>
                    <li>Для удаления отдельного сообщения нажмите кнопку "Удалить" под ним</li>
                    <li>Для массовой очистки используйте форму ниже</li>
                    <li>Все действия логируются и не могут быть отменены</li>
                </ul>
            </div>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="city" value="<?php echo $selected_city; ?>">
                    <input type="text" name="search" placeholder="🔍 Поиск по сообщениям или отправителю..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <input type="date" name="date" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Найти
                    </button>
                    <a href="city_chat_admin.php?city=<?php echo $selected_city; ?>" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Сбросить
                    </a>
                </form>
            </div>

            <!-- Chat Messages -->
            <div class="chat-container">
                <div class="chat-header">
                    <h2>
                        <i class="fas fa-comments"></i>
                        Сообщения в чате 
                        <span class="message-counter">
                            <i class="fas fa-message"></i>
                            <?php echo count($messages); ?>
                            <?php if ($total_messages > 100): ?>
                                <span style="font-size: 0.8rem; opacity: 0.9;">из <?php echo $total_messages; ?></span>
                            <?php endif; ?>
                        </span>
                    </h2>
                    <div>
                        <span style="color: var(--gray-color); margin-right: 15px; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fas fa-users"></i>
                            Участников: <strong style="color: var(--dark-color); margin-left: 5px;"><?php echo $city_stats[$selected_city]['unique_users']; ?></strong>
                        </span>
                        <a href="city_chat_admin.php?city=<?php echo $selected_city; ?>&export=csv" 
                           class="btn btn-sm btn-success" title="Экспорт в CSV">
                            <i class="fas fa-file-export"></i> Экспорт
                        </a>
                    </div>
                </div>
                
                <div class="chat-messages">
                    <?php if (empty($messages)): ?>
                        <div class="no-data">
                            <i class="far fa-comment-slash"></i>
                            <h3>Сообщений не найдено</h3>
                            <p>В этом чате пока нет сообщений или они не соответствуют критериям поиска</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['user_type'] === 'admin' ? 'admin' : 'migrant'; ?>" id="message-<?php echo $message['id']; ?>">
                                <div class="message-header">
                                    <div class="sender-info">
                                        <div class="sender-avatar">
                                            <?php 
                                            $initials = '';
                                            if ($message['first_name']) {
                                                $initials = strtoupper(substr($message['first_name'], 0, 1));
                                                if ($message['last_name']) {
                                                    $initials .= strtoupper(substr($message['last_name'], 0, 1));
                                                }
                                            } else {
                                                $initials = 'U';
                                            }
                                            echo $initials;
                                            ?>
                                        </div>
                                        <div class="sender-details">
                                            <div class="sender-name">
                                                <?php 
                                                if ($message['first_name'] && $message['last_name']) {
                                                    echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']);
                                                } else {
                                                    echo 'Пользователь #' . $message['sender_id'];
                                                }
                                                ?>
                                                <?php if ($message['user_type'] === 'admin'): ?>
                                                    <span class="admin-badge">
                                                        <i class="fas fa-user-shield"></i> Админ
                                                    </span>
                                                <?php else: ?>
                                                    <span class="migrant-badge">
                                                        <i class="fas fa-user"></i> Мигрант
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($message['email'])): ?>
                                                <div class="sender-email">
                                                    <?php echo htmlspecialchars($message['email']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="message-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('d.m.Y H:i:s', strtotime($message['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="message-text">
                                    <?php 
                                    $text = htmlspecialchars($message['message_text']);
                                    // Преобразуем переносы строк
                                    echo nl2br($text);
                                    ?>
                                </div>
                                
                                <div class="message-actions">
                                    <a href="city_chat_admin.php?city=<?php echo $selected_city; ?>&delete_message=<?php echo $message['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirmDeleteMessage()"
                                       title="Удалить сообщение">
                                        <i class="fas fa-trash"></i> Удалить
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Clear Chat Form -->
            <div class="clear-form">
                <h3><i class="fas fa-trash-alt"></i> Очистка чата</h3>
                <form method="POST" onsubmit="return confirmClearChat()">
                    <input type="hidden" name="city" value="<?php echo $selected_city; ?>">
                    
                    <div class="form-group">
                        <label><i class="fas fa-cog"></i> Тип очистки:</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="clear_type" value="all" required>
                                <i class="fas fa-broom"></i> Удалить все сообщения
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="clear_type" value="old">
                                <i class="fas fa-history"></i> Удалить старые сообщения
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group" id="days-group" style="display: none;">
                        <label><i class="fas fa-calendar-day"></i> Удалить сообщения старше (дней):</label>
                        <input type="number" name="days_old" min="1" max="365" value="30">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="clear_chat" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Очистить чат
                        </button>
                        <span style="margin-left: 20px; color: var(--gray-color); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-chart-bar"></i>
                            Всего сообщений: <strong style="color: var(--dark-color);"><?php echo $city_stats[$selected_city]['total_messages']; ?></strong>
                        </span>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <!-- Instructions -->
            <div class="instructions">
                <h2><i class="fas fa-city"></i> Выберите город для управления чатом</h2>
                <p>Для просмотра и управления сообщениями в городских чатах выберите город из карточек статистики выше.</p>
                <p>В каждом городе вы сможете:</p>
                <ul>
                    <li>Просматривать все сообщения в чате</li>
                    <li>Фильтровать сообщения по дате и содержанию</li>
                    <li>Удалять отдельные сообщения или очищать весь чат</li>
                    <li>Экспортировать данные чата</li>
                </ul>
                <p>Все действия логируются и могут быть отслежены в истории администратора.</p>
            </div>
        <?php endif; ?>

        <!-- Info Panel -->
        <div class="chat-container">
            <div class="chat-header">
                <h2><i class="fas fa-info-circle"></i> Информация о системе</h2>
            </div>
            <div style="padding: 25px;">
                <p style="color: var(--gray-color); margin-bottom: 10px; line-height: 1.7;">
                    <i class="fas fa-history"></i> Система городских чатов позволяет мигрантам общаться в рамках своего города.
                </p>
                <p style="color: var(--gray-color); font-size: 0.95rem; line-height: 1.6;">
                    Администраторы могут модерировать сообщения, удалять несоответствующие правилам сообщения и очищать чаты при необходимости.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Показать/скрыть поле для дней при выборе очистки старых сообщений
        document.querySelectorAll('input[name="clear_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const daysGroup = document.getElementById('days-group');
                if (this.value === 'old') {
                    daysGroup.style.display = 'block';
                } else {
                    daysGroup.style.display = 'none';
                }
            });
            
            // Инициализируем состояние при загрузке
            if (radio.value === 'old' && radio.checked) {
                document.getElementById('days-group').style.display = 'block';
            }
        });

        // Подтверждение очистки чата
        function confirmClearChat() {
            const clearType = document.querySelector('input[name="clear_type"]:checked');
            if (!clearType) {
                alert('Пожалуйста, выберите тип очистки');
                return false;
            }
            
            const cityName = '<?php echo ucfirst($selected_city); ?>';
            
            if (clearType.value === 'all') {
                return confirm(`ВНИМАНИЕ!\n\nВы собираетесь удалить ВСЕ сообщения в чате города ${cityName}.\n\nЭто действие нельзя отменить!\n\nПродолжить?`);
            }
            
            if (clearType.value === 'old') {
                const days = document.querySelector('input[name="days_old"]').value;
                return confirm(`Вы собираетесь удалить все сообщения старше ${days} дней в чате города ${cityName}.\n\nПродолжить?`);
            }
            
            return true;
        }

        // Подтверждение удаления конкретного сообщения
        function confirmDeleteMessage() {
            return confirm('Вы уверены, что хотите удалить это сообщение?\n\nЭто действие нельзя отменить.');
        }

        // Анимация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'slideIn 0.5s ease forwards';
                card.style.opacity = '0';
            });
            
            // Плавная прокрутка к сообщениям при наличии хеша
            if (window.location.hash && window.location.hash.startsWith('#message-')) {
                setTimeout(() => {
                    const messageElement = document.querySelector(window.location.hash);
                    if (messageElement) {
                        messageElement.scrollIntoView({ 
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }, 300);
            }
        });
    </script>
</body>
</html>