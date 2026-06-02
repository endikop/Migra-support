<?php
/**
 * Единая навигация для админ-панели
 * Подключается во всех страницах админ-панели
 */

// Определяем текущую страницу для активного пункта меню
$current_page = basename($_SERVER['PHP_SELF']);

// Функция для проверки активного пункта меню
function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}

// Функция для получения аватара пользователя (используем существующую из config.php)
function getAdminUserAvatar() {
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userData = $stmt->fetch();
            return $userData['avatar'] ?? null;
        } catch (PDOException $e) {
            return null;
        }
    }
    return null;
}

// Функция для получения имени пользователя
function getAdminUserName() {
    return $_SESSION['user_name'] ?? 'Администратор';
}
?>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <div class="logo">
        <h2><i class="fas fa-user-shield"></i> <span>Админ-панель</span></h2>
    </div>
    <ul class="nav-menu">
        <li><a href="dashboard.php" class="<?php echo isActive('dashboard.php'); ?>">
            <i class="fas fa-home"></i> <span>Главная</span></a>
        </li>
        <li><a href="migrants.php" class="<?php echo isActive('migrants.php'); ?>">
            <i class="fas fa-users"></i> <span>Мигранты</span></a>
        </li>
        <li><a href="add_migrant.php" class="<?php echo isActive('add_migrant.php'); ?>">
            <i class="fas fa-user-plus"></i> <span>Добавить мигранта</span></a>
        </li>
        <li><a href="city_chat_admin.php" class="<?php echo isActive('city_chat_admin.php'); ?>">
            <i class="fas fa-city"></i> <span>Городские чаты</span></a>
        </li>
        <li><a href="chats.php" class="<?php echo isActive('chats.php'); ?>">
            <i class="fas fa-comments"></i> <span>Личные чаты</span></a>
        </li>
        <li><a href="migration_data.php" class="<?php echo isActive('migration_data.php'); ?>">
            <i class="fas fa-database"></i> <span>Миграционные данные</span></a>
        </li>
        <li><a href="censorship_admin.php" class="<?php echo isActive('censorship_admin.php'); ?>">
            <i class="fas fa-shield-alt"></i> <span>Цензура</span></a>
        </li>
        <li><a href="news_admin.php" class="<?php echo isActive('news_admin.php'); ?>">
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

<!-- User Info Header -->
<div class="user-info">
    <div class="user-avatar">
        <?php 
        $avatar = getAdminUserAvatar();
        $userName = getAdminUserName();
        if ($avatar): ?>
            <img src="<?php echo htmlspecialchars($avatar); ?>" 
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