<?php
session_start();
require_once 'config.php';

// Подключаем файл с данными аватара
require_once 'include_avatar.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$chat_id = $_GET['id'] ?? 0;

// Получаем информацию о чате
$stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.email, u.phone, u.city, u.country_of_origin,
           a.first_name as admin_first_name, a.last_name as admin_last_name
    FROM admin_chats c 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN users a ON c.admin_id = a.id 
    WHERE c.id = ?
");
$stmt->execute([$chat_id]);
$chat = $stmt->fetch();

if (!$chat) {
    die('Чат не найден');
}

// Помечаем сообщения как прочитанные
$stmt = $pdo->prepare("UPDATE admin_chat_messages SET is_read = 1 WHERE chat_id = ? AND sender_id != ?");
$stmt->execute([$chat_id, $_SESSION['user_id']]);

// Получаем сообщения
$stmt = $pdo->prepare("
    SELECT m.*, u.first_name, u.last_name, u.user_type,
           DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') as created_at,
           CASE 
                WHEN m.sender_id = ? THEN 'admin'
                ELSE 'user'
           END as message_type
    FROM admin_chat_messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.chat_id = ? 
    ORDER BY m.created_at ASC
");
$stmt->execute([$_SESSION['user_id'], $chat_id]);
$messages = $stmt->fetchAll();

// Обработка отправки сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO admin_chat_messages (chat_id, sender_id, message_text) VALUES (?, ?, ?)");
        $stmt->execute([$chat_id, $_SESSION['user_id'], $message]);
        
        // Обновляем время чата
        $stmt = $pdo->prepare("UPDATE admin_chats SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$chat_id]);
        
        $_SESSION['success'] = 'Сообщение отправлено!';
        header("Location: chat_admin.php?id=$chat_id");
        exit;
    }
}

// Обработка закрытия чата
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_chat'])) {
    $stmt = $pdo->prepare("UPDATE admin_chats SET status = 'closed' WHERE id = ?");
    $stmt->execute([$chat_id]);
    
    $_SESSION['success'] = 'Чат закрыт!';
    header('Location: chats.php');
    exit;
}

// Получаем имя пользователя для отображения
$userName = isset($userName) ? $userName : 'Администратор';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат с пользователем | Админ-панель</title>
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
            --admin-message-bg: #e3f2fd;
            --user-message-bg: #f5f5f5;
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
            display: flex;
            flex-direction: column;
            height: 100vh;
            padding: 0;
        }

        /* Chat Header */
        .chat-header {
            background-color: white;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .user-details h1 {
            font-size: 1.3rem;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .user-details .user-meta {
            display: flex;
            gap: 15px;
            color: var(--gray-color);
            font-size: 0.9rem;
        }

        .chat-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge.open {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
        }

        .status-badge.closed {
            background-color: rgba(114, 9, 183, 0.2);
            color: var(--danger-color);
        }

        /* Chat Container */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Chat Messages Area */
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            max-width: 65%;
            display: flex;
            flex-direction: column;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.admin {
            align-self: flex-end;
        }

        .message.user {
            align-self: flex-start;
        }

        .message-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .message.admin .message-bubble {
            background-color: var(--admin-message-bg);
            border-bottom-right-radius: 4px;
        }

        .message.user .message-bubble {
            background-color: var(--user-message-bg);
            border-bottom-left-radius: 4px;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.8rem;
            color: var(--gray-color);
        }

        .sender-name {
            font-weight: 600;
            color: var(--primary-color);
        }

        .message-time {
            color: #999;
        }

        .message-text {
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message.admin .message-text {
            color: var(--dark-color);
        }

        .message.user .message-text {
            color: var(--dark-color);
        }

        /* Chat Input Area */
        .chat-input-area {
            background-color: white;
            padding: 20px;
            border-top: 1px solid var(--border-color);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .input-wrapper {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            font-size: 0.95rem;
            resize: none;
            max-height: 120px;
            min-height: 50px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .chat-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        .chat-input:disabled {
            background-color: var(--light-color);
            cursor: not-allowed;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 24px;
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
            height: 50px;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #5a088f;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* Success Message */
        .success {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a8ea8;
            padding: 15px;
            border-radius: 5px;
            margin: 20px;
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

        /* User Info Modal */
        .user-info-modal {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            margin: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--gray-color);
            font-weight: 500;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        /* Empty Chat */
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray-color);
            text-align: center;
            padding: 40px;
        }

        .empty-chat i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ddd;
        }

        /* Scrollbar Styling */
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
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
            .message {
                max-width: 85%;
            }
            
            .chat-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .user-details .user-meta {
                flex-direction: column;
                gap: 5px;
            }
            
            .input-wrapper {
                flex-direction: column;
            }
            
            .btn-primary {
                width: 100%;
                justify-content: center;
            }
            
            .user-info-modal {
                grid-template-columns: 1fr;
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
            <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Главная</span></a></li>
            <li><a href="migrants.php"><i class="fas fa-users"></i> <span>Мигранты</span></a></li>
            <li><a href="add_migrant.php"><i class="fas fa-user-plus"></i> <span>Добавить мигранта</span></a></li>
            <li><a href="city_chat_admin.php"><i class="fas fa-city"></i> <span>Городские чаты</span></a></li>
            <li><a href="chats.php" class="active"><i class="fas fa-comments"></i> <span>Личные чаты</span></a></li>
            <li><a href="migration_data.php"><i class="fas fa-database"></i> <span>Миграционные данные</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Выход</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($chat['first_name'], 0, 1) . substr($chat['last_name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h1><?php echo htmlspecialchars($chat['first_name'] . ' ' . $chat['last_name']); ?></h1>
                    <div class="user-meta">
                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($chat['email']); ?></span>
                        <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($chat['phone']); ?></span>
                        <span><i class="fas fa-city"></i> <?php echo htmlspecialchars($chat['city']); ?></span>
                    </div>
                </div>
            </div>
            <div class="chat-status">
                <span class="status-badge <?php echo $chat['status']; ?>">
                    <?php echo $chat['status'] == 'open' ? 'Открыт' : 'Закрыт'; ?>
                </span>
                <div class="action-buttons">
                    <?php if ($chat['status'] == 'open'): ?>
                        <button onclick="showCloseConfirm()" class="btn btn-danger btn-sm">
                            <i class="fas fa-times"></i> Закрыть чат
                        </button>
                    <?php endif; ?>
                    <a href="chats.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад
                    </a>
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div class="user-info-modal">
            <div class="info-item">
                <span class="info-label">Тема чата</span>
                <span class="info-value"><?php echo htmlspecialchars($chat['subject']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Страна происхождения</span>
                <span class="info-value"><?php echo htmlspecialchars($chat['country_of_origin']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Ответственный</span>
                <span class="info-value">
                    <?php if ($chat['admin_first_name']): ?>
                        <?php echo htmlspecialchars($chat['admin_first_name'] . ' ' . $chat['admin_last_name']); ?>
                    <?php else: ?>
                        <span style="color: var(--gray-color);">Не назначен</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Дата создания</span>
                <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($chat['created_at'])); ?></span>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="chat-container">
            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                <?php if (empty($messages)): ?>
                    <div class="empty-chat">
                        <i class="fas fa-comment-slash"></i>
                        <h3>Чат пуст</h3>
                        <p>Начните общение с пользователем</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['message_type']; ?>">
                            <div class="message-header">
                                <span class="sender-name">
                                    <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                                    <?php if ($message['user_type'] == 'admin'): ?>
                                        <i class="fas fa-user-shield" style="margin-left: 5px;"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="message-time"><?php echo $message['created_at']; ?></span>
                            </div>
                            <div class="message-bubble">
                                <div class="message-text"><?php echo nl2br(htmlspecialchars($message['message_text'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Input Area -->
            <?php if ($chat['status'] == 'open'): ?>
            <form method="POST" class="chat-input-area" id="messageForm">
                <div class="input-wrapper">
                    <textarea name="message" id="messageInput" class="chat-input" 
                              placeholder="Введите ваше сообщение..." rows="1" required></textarea>
                    <button type="submit" name="send_message" class="btn btn-primary" id="sendButton">
                        <i class="fas fa-paper-plane"></i> Отправить
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div class="chat-input-area">
                <div class="input-wrapper">
                    <textarea class="chat-input" disabled placeholder="Чат закрыт. Новые сообщения не принимаются."></textarea>
                    <button class="btn btn-primary" disabled>
                        <i class="fas fa-paper-plane"></i> Отправить
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Close Chat Modal -->
    <div id="closeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 400px; text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--danger-color); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 15px;">Закрыть чат?</h3>
            <p style="color: var(--gray-color); margin-bottom: 25px;">После закрытия чата новые сообщения не смогут быть отправлены.</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="close_chat" class="btn btn-danger">
                        <i class="fas fa-times"></i> Да, закрыть
                    </button>
                </form>
                <button onclick="hideCloseConfirm()" class="btn btn-secondary">Отмена</button>
            </div>
        </div>
    </div>

    <script>
        // Прокрутка к последнему сообщению
        function scrollToBottom() {
            const container = document.getElementById('chatMessages');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Автоматическое увеличение высоты textarea
        const textarea = document.getElementById('messageInput');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }

        // Отправка сообщения по Enter (без Shift)
        const messageForm = document.getElementById('messageForm');
        if (messageForm) {
            messageForm.addEventListener('keydown', function(e) {
                const textarea = this.querySelector('textarea[name="message"]');
                if (textarea && e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    
                    if (textarea.value.trim() !== '') {
                        const sendButton = document.getElementById('sendButton');
                        const originalText = sendButton.innerHTML;
                        sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
                        sendButton.disabled = true;
                        
                        setTimeout(() => {
                            this.submit();
                        }, 100);
                    }
                }
            });
            
            if (textarea) {
                textarea.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        const form = document.getElementById('messageForm');
                        if (form && this.value.trim() !== '') {
                            form.submit();
                        }
                    }
                });
            }
        }

        // Подтверждение закрытия чата
        function showCloseConfirm() {
            document.getElementById('closeModal').style.display = 'flex';
        }

        function hideCloseConfirm() {
            document.getElementById('closeModal').style.display = 'none';
        }

        // Автообновление чата
        let isAutoRefresh = true;
        
        function refreshChat() {
            if (!isAutoRefresh) return;
            
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newMessages = doc.getElementById('chatMessages');
                    if (newMessages) {
                        const currentMessages = document.getElementById('chatMessages').innerHTML;
                        if (currentMessages !== newMessages.innerHTML) {
                            document.getElementById('chatMessages').innerHTML = newMessages.innerHTML;
                            scrollToBottom();
                        }
                    }
                })
                .catch(error => console.error('Ошибка обновления чата:', error));
        }

        // Обновление каждые 3 секунды
        setInterval(refreshChat, 3000);

        // При фокусе на поле ввода останавливаем автообновление
        if (textarea) {
            textarea.addEventListener('focus', () => {
                isAutoRefresh = false;
            });
            
            textarea.addEventListener('blur', () => {
                isAutoRefresh = true;
            });
        }

        // Прокрутка при загрузке
        scrollToBottom();
        
        // Фокус на поле ввода при загрузке
        if (textarea) {
            setTimeout(() => {
                textarea.focus();
            }, 100);
        }
        
        // Автоматическая высота textarea при загрузке
        if (textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }
    </script>
</body>
</html>