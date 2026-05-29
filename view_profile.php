<?php
/**
 * Страница просмотра профиля пользователя
 */

session_start();
require_once 'config.php';


// Получаем ID пользователя из параметра
$userId = $_GET['id'] ?? null;

if (!$userId) {
    header('Location: index.php');
    exit;
}

// Получаем данные пользователя
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, city, avatar, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: index.php');
    exit;
}

// Проверяем авторизацию (функция isLoggedIn() уже есть в config.php)
$isLoggedIn = isLoggedIn();
$currentUserId = $_SESSION['user_id'] ?? null;

// Поддерживаемые языки
$supportedLanguages = ['ru', 'en', 'pt', 'fr', 'de'];
$lang = $_COOKIE['lang'] ?? 'ru';

// Функция перевода
function t($ru, $en, $pt = '', $fr = '', $de = '') {
    global $lang;
    switch ($lang) {
        case 'en': return $en;
        case 'pt': return !empty($pt) ? $pt : $en;
        case 'fr': return !empty($fr) ? $fr : $en;
        case 'de': return !empty($de) ? $de : $en;
        default: return $ru;
    }
}

$translations = [
    'profile' => t('Профиль пользователя', 'User Profile', 'Perfil do Usuário', 'Profil de l\'Utilisateur', 'Benutzerprofil'),
    'name' => t('Имя', 'Name', 'Nome', 'Nom', 'Name'),
    'email' => t('Email', 'Email', 'Email', 'Email', 'Email'),
    'city' => t('Город', 'City', 'Cidade', 'Ville', 'Stadt'),
    'member_since' => t('Участник с', 'Member since', 'Membro desde', 'Membre depuis', 'Mitglied seit'),
    'back' => t('Назад', 'Back', 'Voltar', 'Retour', 'Zurück'),
    'send_message' => t('Отправить сообщение', 'Send Message', 'Enviar Mensagem', 'Envoyer un Message', 'Nachricht senden'),
    'login_to_message' => t('Войдите, чтобы отправить сообщение', 'Login to send a message', 'Faça login para enviar uma mensagem', 'Connectez-vous pour envoyer un message', 'Melden Sie sich an, um eine Nachricht zu senden'),
    'edit_profile' => t('Редактировать профиль', 'Edit Profile', 'Editar Perfil', 'Modifier le Profil', 'Profil bearbeiten'),
    'messages' => t('Сообщений', 'Messages', 'Mensagens', 'Messages', 'Nachrichten'),
    'connections' => t('Контакты', 'Connections', 'Conexões', 'Connexions', 'Verbindungen'),
    'activity' => t('Активность', 'Activity', 'Atividade', 'Activité', 'Aktivität'),
    'block' => t('Заблокировать', 'Block', 'Bloquear', 'Bloquer', 'Blockieren'),
    'unblock' => t('Разблокировать', 'Unblock', 'Desbloquear', 'Débloquer', 'Entblocken'),
    'you_are_blocked' => t('Вы заблокированы', 'You are blocked', 'Você está bloqueado', 'Vous êtes bloqué', 'Sie sind blockiert')
];

$cityNames = [
    'minsk' => t('Минск', 'Minsk', 'Minsk', 'Minsk', 'Minsk'),
    'grodno' => t('Гродно', 'Grodno', 'Grodno', 'Grodno', 'Grodno'),
    'brest' => t('Брест', 'Brest', 'Brest', 'Brest', 'Brest'),
    'vitebsk' => t('Витебск', 'Vitebsk', 'Vitebsk', 'Vitebsk', 'Vitebsk'),
    'gomel' => t('Гомель', 'Gomel', 'Gomel', 'Gomel', 'Gomel'),
    'mogilev' => t('Могилёв', 'Mogilev', 'Mogilev', 'Mogilev', 'Mogilev')
];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translations['profile'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #3a86ff;
            --primary-dark: #2667cc;
            --primary-light: #5a9cff;
            --secondary: #8338ec;
            --accent: #ff006e;
            --success: #38b000;
            --danger: #ff0054;
            --warning: #ff9e00;
            --dark: #1a1a2e;
            --dark-light: #2d2d44;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --gradient-primary: linear-gradient(135deg, #3a86ff 0%, #8338ec 100%);
            --gradient-secondary: linear-gradient(135deg, #ff006e 0%, #ff9e00 100%);
            --gradient-dark: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 30px 60px rgba(0, 0, 0, 0.2);
            --radius: 16px;
            --radius-lg: 24px;
            --radius-xl: 32px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gradient-dark);
            color: var(--light);
            line-height: 1.7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(58, 134, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(131, 56, 236, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(255, 0, 110, 0.08) 0%, transparent 60%);
            z-index: -1;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(58, 134, 255, 0.4);
            }
            50% {
                box-shadow: 0 0 0 15px rgba(58, 134, 255, 0);
            }
        }

        .profile-container {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: 0;
            max-width: 700px;
            width: 100%;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeInUp 0.6s ease;
            overflow: hidden;
            position: relative;
        }

        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: var(--gradient-primary);
            opacity: 0.15;
            z-index: 0;
        }

        .profile-header {
            text-align: center;
            padding: 50px 40px 30px;
            position: relative;
            z-index: 1;
        }

        .profile-avatar-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 25px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .profile-avatar {
            width: 140px;
            height: 140px;
            background: var(--gradient-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 3.5rem;
            box-shadow: 0 15px 40px rgba(255, 0, 110, 0.4);
            border: 5px solid rgba(26, 26, 46, 0.95);
            position: relative;
            transition: var(--transition);
        }

        .profile-avatar:hover {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 20px 50px rgba(255, 0, 110, 0.5);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-avatar::after {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 50%;
            background: var(--gradient-secondary);
            z-index: -1;
            opacity: 0.3;
            animation: pulse 2s ease infinite;
        }

        .profile-name {
            font-size: 2.2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.8s ease 0.3s both;
        }

        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(58, 134, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--primary-light);
            border: 1px solid rgba(58, 134, 255, 0.3);
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .profile-badge i {
            font-size: 0.9rem;
        }

        .profile-body {
            padding: 30px 40px 40px;
            position: relative;
            z-index: 1;
        }

        .profile-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            padding: 0;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
            animation: slideInLeft 0.8s ease 0.5s both;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: var(--transition);
            position: relative;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row:hover {
            background: rgba(255, 255, 255, 0.05);
            padding-left: 30px;
        }

        .info-row::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: var(--transition);
        }

        .info-row:hover::before {
            opacity: 1;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--gray-light);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .info-label i {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(58, 134, 255, 0.15);
            border-radius: 8px;
            color: var(--primary-light);
            font-size: 0.9rem;
        }

        .info-value {
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            animation: fadeInUp 0.8s ease 0.6s both;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn i,
        .btn span {
            position: relative;
            z-index: 1;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 8px 20px rgba(58, 134, 255, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(58, 134, 255, 0.4);
        }

        .btn-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .btn-danger:hover:not(:disabled) {
            background: rgba(220, 53, 69, 0.3);
            transform: translateY(-2px);
        }

        .btn-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover:not(:disabled) {
            background: rgba(40, 167, 69, 0.3);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
            animation: fadeInUp 0.8s ease 0.5s both;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            padding: 20px 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: var(--transition);
        }

        .stat-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            margin: 0 auto 10px;
            background: var(--gradient-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--gray-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 576px) {
            .profile-container {
                border-radius: var(--radius-lg);
            }

            .profile-header {
                padding: 40px 25px 25px;
            }

            .profile-body {
                padding: 25px 25px 30px;
            }

            .profile-name {
                font-size: 1.8rem;
            }

            .profile-avatar {
                width: 120px;
                height: 120px;
                font-size: 3rem;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 15px 20px;
            }

            .info-row:hover {
                padding-left: 25px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar">
                    <?php if ($user['avatar']): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php else: ?>
                        <?php echo htmlspecialchars(substr($user['first_name'], 0, 1), ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="profile-badge">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($translations['member_since'], ENT_QUOTES, 'UTF-8'); ?> <?php echo date('Y', strtotime($user['created_at'])); ?></span>
            </div>
        </div>

        <div class="profile-body">
            <!-- Статистика пользователя -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-value" id="messagesCount">0</div>
                    <div class="stat-label"><?php echo htmlspecialchars(t('Сообщений', 'Messages', 'Mensagens', 'Messages', 'Nachrichten'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value" id="connectionsCount">0</div>
                    <div class="stat-label"><?php echo htmlspecialchars(t('Контакты', 'Connections', 'Conexões', 'Connexions', 'Verbindungen'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-value" id="activityScore">0</div>
                    <div class="stat-label"><?php echo htmlspecialchars(t('Активность', 'Activity', 'Atividade', 'Activité', 'Aktivität'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <div class="profile-info">
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($translations['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="info-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($translations['city'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="info-value"><?php echo htmlspecialchars($cityNames[$user['city']] ?? $user['city'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo htmlspecialchars($translations['member_since'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="info-value"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
                </div>
                <?php if ($isLoggedIn && $currentUserId == $userId): ?>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($translations['email'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="profile-actions">
                <?php if ($isLoggedIn && $currentUserId != $userId): 
                    // Проверяем статус блокировки
                    $stmt = $pdo->prepare("
                        SELECT 
                            EXISTS(
                                SELECT 1 FROM user_blocks 
                                WHERE blocker_id = ? AND blocked_id = ?
                            ) as is_blocked_by_me,
                            EXISTS(
                                SELECT 1 FROM user_blocks 
                                WHERE blocker_id = ? AND blocked_id = ?
                            ) as is_blocked_by_other
                    ");
                    $stmt->execute([$currentUserId, $userId, $userId, $currentUserId]);
                    $blockStatus = $stmt->fetch(PDO::FETCH_ASSOC);
                    $isBlockedByMe = $blockStatus['is_blocked_by_me'] ?? false;
                    $isBlockedByOther = $blockStatus['is_blocked_by_other'] ?? false;
                ?>
                    <a href="personal_chats.php?start_chat=<?php echo $user['id']; ?>" 
                       class="btn btn-primary" 
                       <?php echo $isBlockedByOther ? 'disabled title="' . htmlspecialchars($translations['you_are_blocked'], ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
                        <i class="fas fa-paper-plane"></i>
                        <span><?php echo htmlspecialchars($translations['send_message'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                    <button class="btn <?php echo $isBlockedByMe ? 'btn-success' : 'btn-danger'; ?>" onclick="toggleBlock(<?php echo $user['id']; ?>, this)">
                        <i class="fas <?php echo $isBlockedByMe ? 'fa-unlock' : 'fa-ban'; ?>"></i>
                        <span><?php echo $isBlockedByMe ? htmlspecialchars($translations['unblock'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($translations['block'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                <?php elseif (!$isLoggedIn): ?>
                    <button class="btn btn-primary" disabled title="<?php echo htmlspecialchars($translations['login_to_message'], ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fas fa-lock"></i>
                        <span><?php echo htmlspecialchars($translations['login_to_message'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                <?php endif; ?>

                <?php if ($isLoggedIn && $currentUserId == $userId): ?>
                    <a href="profile.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        <span><?php echo htmlspecialchars(t('Редактировать профиль', 'Edit Profile', 'Editar Perfil', 'Modifier le Profil', 'Profil bearbeiten'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endif; ?>

                <button onclick="window.history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span><?php echo htmlspecialchars($translations['back'], ENT_QUOTES, 'UTF-8'); ?></span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Блокировка/разблокировка пользователя
        async function toggleBlock(userId, button) {
            const isBlocked = button.classList.contains('btn-success');
            const message = isBlocked ? 'Вы уверены, что хотите разблокировать этого пользователя?' : 'Вы уверены, что хотите заблокировать этого пользователя?';
            
            if (!confirm(message)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_block');
                formData.append('user_id', userId);
                
                const response = await fetch('personal_chats_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const isBlockedNow = result.is_blocked;
                    
                    // Обновляем кнопку
                    button.className = `btn ${isBlockedNow ? 'btn-success' : 'btn-danger'}`;
                    button.innerHTML = `
                        <i class="fas ${isBlockedNow ? 'fa-unlock' : 'fa-ban'}"></i>
                        <span>${isBlockedNow ? 'Разблокировать' : 'Заблокировать'}</span>
                    `;
                    
                    // Обновляем кнопку отправки сообщения
                    const messageBtn = document.querySelector('.btn-primary');
                    if (messageBtn) {
                        messageBtn.disabled = isBlockedNow;
                        messageBtn.title = isBlockedNow ? 'Вы заблокированы' : '';
                    }
                    
                    alert(isBlockedNow ? 'Пользователь заблокирован' : 'Пользователь разблокирован');
                } else {
                    alert(result.error || 'Ошибка');
                }
            } catch (error) {
                console.error('Error toggling block:', error);
                alert('Ошибка при выполнении операции');
            }
        }

        // Анимация счетчика
        function animateCounter(element, targetValue, duration = 2000) {
            const startValue = 0;
            const increment = targetValue / (duration / 16);
            let currentValue = startValue;
            
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= targetValue) {
                    currentValue = targetValue;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(currentValue);
            }, 16);
        }

        // Загрузка статистики пользователя
        async function loadUserStats() {
            try {
                // Используем случайные значения для демонстрации
                const messagesCount = Math.floor(Math.random() * 50) + 10;
                const connectionsCount = Math.floor(Math.random() * 20) + 5;
                const activityScore = Math.floor(Math.random() * 100) + 30;
                
                // Анимируем счетчики
                setTimeout(() => {
                    animateCounter(document.getElementById('messagesCount'), messagesCount);
                }, 100);
                
                setTimeout(() => {
                    animateCounter(document.getElementById('connectionsCount'), connectionsCount);
                }, 300);
                
                setTimeout(() => {
                    animateCounter(document.getElementById('activityScore'), activityScore);
                }, 500);
                
            } catch (error) {
                console.error('Error loading user stats:', error);
            }
        }

        // Интерактивные эффекты
        function setupInteractiveEffects() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    const icon = card.querySelector('.stat-icon');
                    if (icon) icon.style.transform = 'scale(1.1) rotate(5deg)';
                });
                
                card.addEventListener('mouseleave', () => {
                    const icon = card.querySelector('.stat-icon');
                    if (icon) icon.style.transform = 'scale(1) rotate(0deg)';
                });
            });

            const infoRows = document.querySelectorAll('.info-row');
            infoRows.forEach(row => {
                row.addEventListener('mouseenter', () => {
                    const icon = row.querySelector('.info-label i');
                    if (icon) {
                        icon.style.transform = 'scale(1.2)';
                        icon.style.color = 'var(--primary-light)';
                    }
                });
                
                row.addEventListener('mouseleave', () => {
                    const icon = row.querySelector('.info-label i');
                    if (icon) {
                        icon.style.transform = 'scale(1)';
                        icon.style.color = '';
                    }
                });
            });
        }

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadUserStats();
            setupInteractiveEffects();
        });

        // Обработка клавиши Escape для возврата назад
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.history.back();
            }
        });
    </script>
</body>
</html>