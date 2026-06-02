<?php
// Включаем буферизацию вывода ДО любого кода
if (ob_get_level() == 0) {
    ob_start();
}

session_start();

require_once 'config.php';

// Получаем ID новости
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($newsId <= 0) {
    header('Location: index.php');
    exit;
}

// Получаем данные новости
try {
    $stmt = $pdo->prepare("SELECT n.*, u.first_name, u.last_name FROM news n LEFT JOIN users u ON n.author_id = u.id WHERE n.id = ? AND n.is_active = 1");
    $stmt->execute([$newsId]);
    $news = $stmt->fetch();
    
    if (!$news) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: index.php');
    exit;
}

// Проверяем авторизацию
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : '';
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';

// Получаем город пользователя из БД если авторизован
$userCity = 'minsk';
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT city FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch();
        $userCity = $userData['city'] ?? 'minsk';
        $_SESSION['city'] = $userCity;
    } catch (PDOException $e) {
        $userCity = 'minsk';
    }
}

// Поддерживаемые языки
$supportedLanguages = ['ru', 'en', 'pt', 'fr', 'de'];
$languageNames = [
    'ru' => 'Русский',
    'en' => 'English',
    'pt' => 'Português',
    'fr' => 'Français',
    'de' => 'Deutsch'
];

// Язык по умолчанию
$lang = $_COOKIE['lang'] ?? 'ru';
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLanguages)) {
    $lang = $_GET['lang'];
    setcookie('lang', $lang, time() + (86400 * 30), "/");
}

// Функция перевода с поддержкой всех языков
function t($ru, $en, $pt = '', $fr = '', $de = '') {
    global $lang;
    
    switch ($lang) {
        case 'en':
            return $en;
        case 'pt':
            return !empty($pt) ? $pt : $en;
        case 'fr':
            return !empty($fr) ? $fr : $en;
        case 'de':
            return !empty($de) ? $de : $en;
        default:
            return $ru;
    }
}

// Тексты для перевода
$translations = [
    'back_to_home' => t('На главную', 'Back to Home', 'Voltar ao Início', 'Retour à l\'accueil', 'Zurück zur Startseite'),
    'all_news' => t('Все новости', 'All News', 'Todas as Notícias', 'Toutes les actualités', 'Alle Nachrichten'),
    'published' => t('Опубликовано', 'Published', 'Publicado', 'Publié', 'Veröffentlicht'),
    'author' => t('Автор', 'Author', 'Autor', 'Auteur', 'Autor'),
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?> | MigraSupport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #3a86ff;
            --primary-dark: #2667cc;
            --primary-light: #5a9cff;
            --secondary: #8338ec;
            --secondary-dark: #6726cc;
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
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
            --radius: 16px;
            --radius-lg: 24px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #f8f9fa;
            line-height: 1.7;
            min-height: 100vh;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header */
        header {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }

        .logo:hover {
            transform: translateY(-2px);
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3);
        }

        .logo-text {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }

        .language-selector {
            display: flex;
            gap: 5px;
        }

        .lang-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .lang-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .lang-btn.active {
            background: var(--gradient-primary);
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(58, 134, 255, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: rgba(58, 134, 255, 0.1);
            transform: translateY(-2px);
        }

        /* News Detail */
        .news-detail {
            padding: 60px 0;
        }

        .breadcrumb {
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .breadcrumb a {
            color: var(--primary-light);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary);
        }

        .breadcrumb span {
            color: rgba(255, 255, 255, 0.6);
        }

        .news-card {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .news-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: white;
            line-height: 1.3;
        }

        .news-meta {
            display: flex;
            gap: 25px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-wrap: wrap;
        }

        .news-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .news-meta i {
            color: var(--accent);
        }

        .news-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: var(--radius);
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .news-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.9);
        }

        .news-content p {
            margin-bottom: 20px;
        }

        .back-button {
            margin-top: 30px;
            text-align: center;
        }

        /* Footer */
        footer {
            background: rgba(13, 13, 23, 0.95);
            backdrop-filter: blur(20px);
            color: white;
            padding: 30px 0;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 60px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .news-title {
                font-size: 2rem;
            }
            
            .news-card {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .news-title {
                font-size: 1.6rem;
            }
            
            .news-content {
                font-size: 1rem;
            }
            
            .news-meta {
                gap: 15px;
            }
            
            .language-selector {
                gap: 3px;
            }
            
            .lang-btn {
                padding: 5px 8px;
                font-size: 0.75rem;
            }
        }
    
        /* ===== UNIFIED MOBILE RESPONSIVE (all pages) ===== */

        /* Header base - ensure flex layout */
        .header-wrapper { display: flex; flex-direction: column; }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.85rem 0;
            flex-wrap: nowrap;
            gap: 10px;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .language-selector {
            display: flex;
            gap: 4px;
            align-items: center;
            flex-wrap: nowrap;
        }
        .lang-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 8px;
            padding: 7px 11px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.82rem;
            min-width: 42px;
            text-align: center;
            white-space: nowrap;
        }
        .lang-btn:hover { background: rgba(255,255,255,0.2); transform: translateY(-1px); }
        .lang-btn.active { background: linear-gradient(135deg,#3a86ff,#8338ec); box-shadow: 0 4px 12px rgba(58,134,255,0.3); }

        /* Burger always hidden on desktop */
        .burger-menu { display: none; }

        /* Mobile nav always hidden on desktop */
        .mobile-nav { display: none; }

        /* === 992px breakpoint === */
        @media (max-width: 992px) {
            .header-nav { display: none !important; }
            .burger-menu {
                display: flex !important;
                flex-direction: column;
                cursor: pointer;
                padding: 9px;
                gap: 5px;
                background: rgba(255,255,255,0.1);
                border-radius: 8px;
                transition: all 0.3s ease;
                flex-shrink: 0;
            }
            .burger-menu:hover { background: rgba(255,255,255,0.15); }
            .burger-line { width: 22px; height: 3px; background: white; transition: all 0.3s ease; border-radius: 2px; }
            .burger-menu.active .burger-line:nth-child(1) { transform: rotate(45deg) translate(6px,6px); background: #ff006e; }
            .burger-menu.active .burger-line:nth-child(2) { opacity: 0; }
            .burger-menu.active .burger-line:nth-child(3) { transform: rotate(-45deg) translate(6px,-6px); background: #ff006e; }

            .mobile-nav {
                display: block !important;
                position: fixed;
                top: 62px;
                left: 0; right: 0;
                background: rgba(26,26,46,0.98);
                backdrop-filter: blur(20px);
                border-radius: 0 0 16px 16px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                z-index: 999;
                overflow: hidden;
                max-height: 0;
                transition: max-height 0.3s ease;
            }
            .mobile-nav.active { max-height: 520px; }
            .mobile-nav-tabs { display: flex; flex-direction: column; list-style: none; padding: 12px; }
            .mobile-nav-tab {
                padding: 13px 16px;
                font-weight: 500;
                color: rgba(248,249,250,0.8);
                display: flex;
                align-items: center;
                gap: 10px;
                border-radius: 8px;
                margin-bottom: 4px;
                font-size: 0.92rem;
                transition: all 0.3s ease;
            }
            .mobile-nav-tab:hover { color: white; background: rgba(255,255,255,0.06); }
            .mobile-nav-tab.active { color: white; background: rgba(255,255,255,0.09); border-left: 3px solid #ff006e; }
            .mobile-nav-tab i { font-size: 1rem; width: 20px; }
            .mobile-nav-link { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px; width: 100%; }

            main { margin-top: 60px !important; }

            .lang-btn { padding: 6px 9px; font-size: 0.78rem; min-width: 38px; }
            .header-right { gap: 8px; }
            .logo { font-size: 1.25rem !important; }
            .logo-icon { width: 36px !important; height: 36px !important; font-size: 1.05rem !important; }
        }

        /* === 768px breakpoint === */
        @media (max-width: 768px) {
            .container { padding: 0 14px; }
            .header-top { padding: 0.7rem 0; }

            .lang-btn { padding: 5px 8px; font-size: 0.74rem; min-width: 34px; }
            .header-right { gap: 6px; }

            .logo-text { display: inline !important; }
            .logo { font-size: 1.15rem !important; }

            .user-avatar, .user-avatar-header {
                width: 34px !important;
                height: 34px !important;
                font-size: 0.85rem !important;
            }

            .btn { padding: 7px 13px; font-size: 0.8rem; }

            .footer-content { grid-template-columns: 1fr !important; text-align: center; }
            .footer-section h3::after { left: 50% !important; transform: translateX(-50%) !important; }
            .social-links { justify-content: center !important; }

            .hero-section { padding: 45px 18px !important; }
            .hero-title { font-size: 1.85rem !important; }
            .hero-subtitle { font-size: 1rem !important; }
            .hero-buttons { flex-direction: column; align-items: center; }
            .hero-buttons .btn { width: 100%; max-width: 280px; justify-content: center; }

            .mission-title, .section-title { font-size: 1.7rem !important; }
        }

        /* === 576px breakpoint === */
        @media (max-width: 576px) {
            .container { padding: 0 12px; }
            .lang-btn { padding: 4px 6px; font-size: 0.7rem; min-width: 31px; }
            .language-selector { gap: 3px; }

            .hero-title { font-size: 1.55rem !important; }
            .hero-subtitle { font-size: 0.9rem !important; }

            .cities-grid { grid-template-columns: repeat(2, 1fr) !important; }
            .city-header { grid-template-columns: 1fr !important; }
            .city-image { height: 190px !important; }

            .card { padding: 18px !important; }
            .city-selector { padding: 18px !important; }

            .emergency-help { flex-direction: column !important; text-align: center !important; gap: 14px !important; }
            .emergency-icon { width: 58px !important; height: 58px !important; font-size: 1.4rem !important; }
        }

        /* === 400px breakpoint === */
        @media (max-width: 400px) {
            .lang-btn { padding: 3px 5px; font-size: 0.65rem; min-width: 28px; }
            .language-selector { gap: 2px; }
            .logo-text { display: none !important; }
            .header-right { gap: 5px; }
        }
        /* ===== END UNIFIED MOBILE RESPONSIVE ===== */
</style>
</head>
<body>
    <header>
        <div class="container header-wrapper">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <span class="logo-text">MigraSupport</span>
            </a>
            
            <div class="language-selector">
                <button class="lang-btn <?php echo $lang === 'ru' ? 'active' : ''; ?>" onclick="changeLanguage('ru')">RU</button>
                <button class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" onclick="changeLanguage('en')">EN</button>
                <button class="lang-btn <?php echo $lang === 'pt' ? 'active' : ''; ?>" onclick="changeLanguage('pt')">PT</button>
                <button class="lang-btn <?php echo $lang === 'fr' ? 'active' : ''; ?>" onclick="changeLanguage('fr')">FR</button>
                <button class="lang-btn <?php echo $lang === 'de' ? 'active' : ''; ?>" onclick="changeLanguage('de')">DE</button>
            </div>
        </div>
    </header>

    <main class="news-detail">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> <?php echo $translations['back_to_home']; ?></a>
                <span><i class="fas fa-chevron-right"></i></span>
                <span><?php echo htmlspecialchars($news['title']); ?></span>
            </div>

            <div class="news-card">
                <h1 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h1>
                
                <div class="news-meta">
                    <span><i class="fas fa-calendar-alt"></i> <?php echo date('d.m.Y H:i', strtotime($news['created_at'])); ?></span>
                    <?php if (!empty($news['first_name'])): ?>
                    <span><i class="fas fa-user"></i> <?php echo $translations['author']; ?>: <?php echo htmlspecialchars($news['first_name'] . ' ' . $news['last_name']); ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($news['image'])): ?>
                <img src="<?php echo htmlspecialchars($news['image']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-image">
                <?php endif; ?>

                <div class="news-content">
                    <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                </div>

                <div class="back-button">
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> <?php echo $translations['back_to_home']; ?>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2023-2026 MigraSupport. Все права защищены.</p>
        </div>
    </footer>

    <script>
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>