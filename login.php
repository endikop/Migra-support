<?php
session_start();
require_once 'config.php';


// Если пользователь уже авторизован, перенаправляем на профиль
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit();
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

// Обработка формы входа
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Валидация
    if (empty($username)) {
        $errors[] = t('Введите имя пользователя или email', 'Enter username or email', 'Digite nome de usuário ou email', 'Entrez le nom d\'utilisateur ou l\'email', 'Geben Sie Benutzernamen oder E-Mail ein');
    }
    
    if (empty($password)) {
        $errors[] = t('Введите пароль', 'Enter password', 'Digite a senha', 'Entrez le mot de passe', 'Passwort eingeben');
    }
    
    if (empty($errors)) {
        try {
            // Ищем пользователя по username или email
            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE (username = ? OR email = ?) 
                AND status != 'deleted'
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Проверяем пароль с помощью password_verify
                if (password_verify($password, $user['password'])) {
                    // Проверяем статус пользователя
                    if ($user['status'] === 'pending') {
                        $errors[] = t('Аккаунт не подтвержден. Проверьте вашу почту.', 'Account not confirmed. Check your email.', 'Conta não confirmada. Verifique seu e-mail.', 'Compte non confirmé. Vérifiez votre email.', 'Konto nicht bestätigt. Überprüfen Sie Ihre E-Mail.');
                    } elseif ($user['status'] === 'banned') {
                        $errors[] = t('Аккаунт заблокирован. Свяжитесь с администрацией.', 'Account banned. Contact administration.', 'Conta banida. Entre em contato com a administração.', 'Compte banni. Contactez l\'administration.', 'Konto gesperrt. Kontaktieren Sie die Administration.');
                    } elseif ($user['status'] === 'suspended') {
                        $errors[] = t('Аккаунт временно приостановлен.', 'Account temporarily suspended.', 'Conta temporariamente suspensa.', 'Compte temporairement suspendu.', 'Konto vorübergehend gesperrt.');
                    } else {
                        // Обновляем last_activity вместо last_login
                        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        
                        // Сохраняем данные в сессии
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['city'] = $user['city'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['status'] = $user['status'];
                        
                        // Перенаправляем на страницу, с которой пришли или на профиль
                        $redirect = $_SESSION['redirect_after_login'] ?? 'profile.php';
                        unset($_SESSION['redirect_after_login']);
                        
                        header("Location: $redirect");
                        exit();
                    }
                } else {
                    $errors[] = t('Неверное имя пользователя или пароль', 'Invalid username or password', 'Nome de usuário ou senha inválidos', 'Nom d\'utilisateur ou mot de passe invalide', 'Ungültiger Benutzername oder Passwort');
                }
            } else {
                $errors[] = t('Неверное имя пользователя или пароль', 'Invalid username or password', 'Nome de usuário ou senha inválidos', 'Nom d\'utilisateur ou mot de passe invalide', 'Ungültiger Benutzername oder Passwort');
            }
        } catch (PDOException $e) {
            $errors[] = t('Ошибка авторизации. Попробуйте позже.', 'Authorization error. Please try again later.', 'Erro de autorização. Tente novamente mais tarde.', 'Erreur d\'autorisation. Veuillez réessayer plus tard.', 'Autorisierungsfehler. Bitte versuchen Sie es später erneut.');
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Сохраняем URL для редиректа после входа
if (!isset($_SESSION['redirect_after_login']) && isset($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    if (isset($referer['host']) && $referer['host'] === $_SERVER['HTTP_HOST']) {
        $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'];
    }
}

// Тексты для перевода
$translations = [
    'main_title' => t('Вход - MigraSupport', 'Login - MigraSupport', 'Entrar - MigraSupport', 'Connexion - MigraSupport', 'Anmelden - MigraSupport'),
    'login_title' => t('Вход в систему', 'Login to System', 'Entrar no Sistema', 'Connexion au Système', 'Anmelden am System'),
    'login_desc' => t('Войдите в свой аккаунт для доступа ко всем функциям.', 'Login to your account to access all features.', 'Entre em sua conta para acessar todos os recursos.', 'Connectez-vous à votre compte pour accéder à toutes les fonctionnalités.', 'Melden Sie sich in Ihrem Konto an, um auf alle Funktionen zuzugreifen.'),
    'home' => t('Главная', 'Home', 'Início', 'Accueil', 'Startseite'),
    'information' => t('Информация', 'Information', 'Informação', 'Information', 'Informationen'),
    'map_services' => t('Карта служб', 'Services Map', 'Mapa de Serviços', 'Carte des Services', 'Dienstleistungskarte'),
    'translator' => t('Переводчик', 'Translator', 'Tradutor', 'Traducteur', 'Übersetzer'),
    'currency_converter' => t('Конвертер валют', 'Currency Converter', 'Conversor de Moeda', 'Convertisseur de Devises', 'Währungsrechner'),
    'city_chat' => t('Чат города', 'City Chat', 'Chat da Cidade', 'Chat de la Ville', 'Stadt-Chat'),
    'profile' => t('Профиль', 'Profile', 'Perfil', 'Profil', 'Profil'),
    'login_nav' => t('Вход', 'Login', 'Entrar', 'Connexion', 'Anmelden'),
    'register_nav' => t('Регистрация', 'Register', 'Registrar', 'Inscription', 'Registrieren'),
    'admin_panel' => t('Админ', 'Admin', 'Admin', 'Admin', 'Admin'),
    'logout' => t('Выйти', 'Logout', 'Sair', 'Déconnexion', 'Abmelden'),
    'username_email' => t('Имя пользователя или Email', 'Username or Email', 'Nome de usuário ou Email', 'Nom d\'utilisateur ou Email', 'Benutzername oder E-Mail'),
    'password' => t('Пароль', 'Password', 'Senha', 'Mot de passe', 'Passwort'),
    'login_btn' => t('Войти в систему', 'Login to System', 'Entrar no Sistema', 'Connexion au Système', 'Anmelden am System'),
    'no_account' => t('Нет аккаунта?', 'No account?', 'Não tem conta?', 'Pas de compte ?', 'Kein Konto?'),
    'register_here' => t('Зарегистрируйтесь здесь', 'Register here', 'Registre-se aqui', 'Inscrivez-vous ici', 'Hier registrieren'),
    'footer_title' => t('Комплексная система поддержки мигрантов в Беларуси.', 'Comprehensive migrant support system in Belarus.', 'Sistema abrangente de apoio a migrantes na Bielorrússia.', 'Système complet de soutien aux migrants en Biélorussie.', 'Umfassendes Migrantenunterstützungssystem in Belarus.'),
    'quick_links' => t('Быстрые ссылки', 'Quick Links', 'Links Rápidos', 'Liens Rapides', 'Schnelllinks'),
    'contacts' => t('Контакты', 'Contacts', 'Contatos', 'Contacts', 'Kontakte'),
    'all_rights_reserved' => t('Все права защищены.', 'All rights reserved.', 'Todos os direitos reservados.', 'Tous droits réservés.', 'Alle Rechte vorbehalten.'),
    'login_error' => t('Ошибка входа', 'Login error', 'Erro de login', 'Erreur de connexion', 'Anmeldefehler'),
    'minsk_belarus' => t('Минск, Беларусь', 'Minsk, Belarus', 'Minsk, Bielorrússia', 'Minsk, Biélorussie', 'Minsk, Belarus')
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $translations['main_title']; ?></title>
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
            --gradient-success: linear-gradient(135deg, #38b000 0%, #3a86ff 100%);
            --gradient-dark: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 30px 60px rgba(0, 0, 0, 0.2);
            --radius: 16px;
            --radius-lg: 24px;
            --radius-xl: 32px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.2s ease;
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
                radial-gradient(circle at 80% 20%, rgba(131, 56, 236, 0.15) 0%, transparent 50%);
            z-index: -1;
        }

        /* Декоративные плавающие элементы */
        .floating-element {
            position: fixed;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle at 30% 30%, rgba(58, 134, 255, 0.2), transparent 70%);
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
            z-index: -1;
            animation: float 20s infinite ease-in-out;
        }

        .floating-element:nth-child(1) {
            top: 10%;
            left: 5%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 0, 110, 0.15), transparent 70%);
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            bottom: 10%;
            right: 5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle at 70% 70%, rgba(131, 56, 236, 0.15), transparent 70%);
            animation-delay: -5s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(2%, 2%) scale(1.05); }
            50% { transform: translate(-1%, 3%) scale(0.95); }
            75% { transform: translate(-2%, -1%) scale(1.02); }
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header */
        header {
            background: rgba(26, 26, 46, 0.95);
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-wrapper {
            display: flex;
            flex-direction: column;
        }

        .header-top {
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
            position: relative;
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
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3);
            animation: pulse 3s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3); }
            50% { box-shadow: 0 10px 25px rgba(58, 134, 255, 0.5); }
        }

        .logo-text {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .language-selector {
            display: flex;
            gap: 5px;
            flex-wrap: nowrap;
            justify-content: flex-end;
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
            flex: 0 0 auto;
            min-width: 50px;
            text-align: center;
        }

        .lang-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .lang-btn.active {
            background: var(--gradient-primary);
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        }

        /* Кнопки */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 25px rgba(58, 134, 255, 0.4);
        }

        /* Бургер-меню - ИСПРАВЛЕНО */
        .burger-menu {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: var(--transition);
            gap: 5px;
        }

        .burger-menu:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .burger-line {
            width: 24px;
            height: 2.5px;
            background: white;
            transition: var(--transition);
            border-radius: 2px;
        }

        .burger-menu.active .burger-line:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }

        .burger-menu.active .burger-line:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }

        .burger-menu.active .burger-line:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }

        /* Основная навигация */
        .header-nav {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            position: relative;
            z-index: 999;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 0;
        }

        .nav-tabs {
            display: flex;
            list-style: none;
            gap: 4px;
            overflow-x: auto;
            padding: 0;
            scrollbar-width: none;
        }

        .nav-tabs::-webkit-scrollbar {
            display: none;
        }

        .nav-tab {
            padding: 16px 20px;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
            font-weight: 500;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            white-space: nowrap;
            border-radius: 8px 8px 0 0;
        }

        .nav-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-tab:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }

        .nav-tab.active {
            color: white;
            border-bottom-color: var(--accent);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .nav-tab i {
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .nav-tab.active i {
            color: var(--accent);
            transform: scale(1.1);
        }

        /* Мобильная навигация - ИСПРАВЛЕНА */
        .mobile-nav {
            display: none;
            position: fixed;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 0 0 var(--radius) var(--radius);
            box-shadow: var(--shadow-xl);
            z-index: 999;
            overflow-y: auto;
            max-height: 0;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .mobile-nav.active {
            max-height: 80vh;
        }
        
        .mobile-nav-tabs {
            display: flex;
            flex-direction: column;
            list-style: none;
            padding: 16px;
            margin: 0;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        
        .mobile-nav.active .mobile-nav-tabs {
            opacity: 1;
            transform: translateY(0);
        }
        
        .mobile-nav-tab {
            padding: 14px 18px;
            transition: var(--transition);
            font-weight: 500;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 12px;
            margin-bottom: 4px;
            font-size: 1rem;
        }
        
        .mobile-nav-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }
        
        .mobile-nav-tab:active {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(0.98);
        }
        
        .mobile-nav-tab.active {
            color: white;
            background: rgba(255, 255, 255, 0.08);
            border-left: 3px solid var(--accent);
        }
        
        .mobile-nav-tab i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        /* Main Content */
        main {
            padding: 40px 0;
            min-height: calc(100vh - 200px);
        }

        .login-container {
            max-width: 500px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease;
        }

        .login-header {
            background: var(--gradient-primary);
            color: white;
            padding: 40px;
            text-align: center;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/diamond-upholstery.png');
            opacity: 0.1;
        }

        .login-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .login-form {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: none;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-family: 'Inter', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-input.error {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(255, 0, 84, 0.2);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .login-button {
            width: 100%;
            padding: 16px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.5px;
            margin: 25px 0;
        }

        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(58, 134, 255, 0.3);
        }

        .login-button:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .register-link {
            text-align: center;
            color: var(--gray-light);
            font-size: 0.9rem;
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .register-link a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .error-list {
            background: rgba(255, 0, 84, 0.1);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--danger);
            animation: fadeIn 0.3s ease;
        }

        .error-list h4 {
            color: #ffb8d0;
            margin-bottom: 10px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .error-list li {
            color: #ffb8d0;
            padding: 8px 0;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-list li:before {
            content: '•';
            color: var(--danger);
        }

        /* Footer */
        footer {
            background: rgba(13, 13, 23, 0.95);
            backdrop-filter: blur(20px);
            color: white;
            padding: 50px 0 25px;
            margin-top: 70px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 35px;
            margin-bottom: 35px;
        }

        .footer-section h3 {
            margin-bottom: 20px;
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .footer-section:hover h3::after {
            width: 80px;
        }

        .footer-section p {
            color: var(--gray-light);
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #b0b0c0;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .footer-links a:hover {
            color: white;
            transform: translateX(5px);
        }

        .footer-links a i {
            font-size: 0.85rem;
            color: var(--primary);
            transition: var(--transition);
        }

        .footer-links a:hover i {
            transform: scale(1.2);
        }

        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .social-links a {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
        }

        .social-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .social-links a:hover::before {
            left: 100%;
        }

        .social-links a:hover {
            background: var(--gradient-primary);
            transform: translateY(-4px) rotate(5deg);
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--gray-light);
            font-size: 0.85rem;
        }

        /* Анимации */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* ===== RESPONSIVE STYLES ===== */

        @media (max-width: 992px) {
            .header-nav {
                display: none;
            }
            
            .burger-menu {
                display: flex;
            }
            
            .header-right {
                gap: 10px;
            }
            
            .mobile-nav {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .login-header {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 2rem;
            }

            .login-header p {
                font-size: 0.95rem;
            }

            .login-form {
                padding: 30px 20px;
            }

            .container {
                padding: 0 16px;
            }
            
            .header-top {
                flex-wrap: nowrap;
                gap: 8px;
                padding: 0.75rem 0;
            }
            
            .logo {
                font-size: 1.2rem;
            }
            
            .logo-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
            
            .language-selector {
                flex-wrap: nowrap;
                gap: 3px;
            }
            
            .lang-btn {
                padding: 6px 8px;
                font-size: 0.72rem;
                min-width: 40px;
            }
        }

        @media (max-width: 576px) {
            .login-header h1 {
                font-size: 1.6rem;
            }

            .login-header p {
                font-size: 0.85rem;
            }

            .form-input {
                padding: 12px 15px;
                font-size: 0.9rem;
            }

            .login-button {
                padding: 14px;
                font-size: 0.95rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
            
            .language-selector {
                gap: 2px;
            }
            
            .lang-btn {
                padding: 5px 6px;
                font-size: 0.68rem;
                min-width: 36px;
            }
        }
        
        @media (max-width: 400px) {
            .logo-text {
                display: none;
            }
            
            .lang-btn {
                padding: 4px 5px;
                font-size: 0.62rem;
                min-width: 32px;
            }
            
            .burger-menu {
                width: 40px;
                height: 40px;
            }
            
            .burger-line {
                width: 20px;
            }
        }

        /* ===== UNIVERSAL MOBILE FIXES ===== */

        /* Предотвращаем горизонтальный скролл */
        html, body { 
            max-width: 100%; 
            overflow-x: hidden; 
        }

        /* Фикс backdrop-filter на старых Android */
        @supports not (backdrop-filter: blur(1px)) {
            header, .header-nav, .mobile-nav, .login-form, footer {
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }
            header { background: rgba(26, 26, 46, 0.98) !important; }
            .mobile-nav { background: rgba(26, 26, 46, 0.99) !important; }
            footer { background: rgba(13, 13, 23, 0.99) !important; }
            .login-form { background: rgba(26, 26, 46, 0.95) !important; }
        }

        /* iOS Safari sticky fix */
        @supports (-webkit-touch-callout: none) {
            header { position: -webkit-sticky; position: sticky; }
        }

        /* Touch: увеличиваем области нажатия на мобильных */
        @media (hover: none) and (pointer: coarse) {
            .mobile-nav-tab {
                min-height: 48px;
            }
            
            .lang-btn {
                min-height: 36px;
            }
            
            .login-button {
                min-height: 52px;
            }
        }
        
        /* Запрещаем масштабирование полей ввода на iOS */
        @media screen and (-webkit-min-device-pixel-ratio: 0) {
            select,
            textarea,
            input {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Декоративные элементы -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>

    <!-- Header -->
    <header>
        <div class="container header-wrapper">
            <div class="header-top">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="burger-menu" id="burgerMenu">
                        <div class="burger-line"></div>
                        <div class="burger-line"></div>
                        <div class="burger-line"></div>
                    </div>
                    
                    <a href="index.php" class="logo">
                        <div class="logo-icon">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <span class="logo-text">MigraSupport</span>
                    </a>
                </div>
                
                <div class="header-right">
                    <div class="language-selector">
                        <button class="lang-btn <?php echo $lang === 'ru' ? 'active' : ''; ?>" onclick="changeLanguage('ru')">RU</button>
                        <button class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" onclick="changeLanguage('en')">EN</button>
                        <button class="lang-btn <?php echo $lang === 'pt' ? 'active' : ''; ?>" onclick="changeLanguage('pt')">PT</button>
                        <button class="lang-btn <?php echo $lang === 'fr' ? 'active' : ''; ?>" onclick="changeLanguage('fr')">FR</button>
                        <button class="lang-btn <?php echo $lang === 'de' ? 'active' : ''; ?>" onclick="changeLanguage('de')">DE</button>
                    </div>
                </div>
            </div>
            
            <!-- Основная навигация -->
            <nav class="header-nav">
                <ul class="nav-tabs">
                    <li class="nav-tab">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-home"></i> <?php echo $translations['home']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="information.php" class="nav-link">
                            <i class="fas fa-info-circle"></i> <?php echo $translations['information']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="map.php" class="nav-link">
                            <i class="fas fa-map-marked-alt"></i> <?php echo $translations['map_services']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="translator.php" class="nav-link">
                            <i class="fas fa-language"></i> <?php echo $translations['translator']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="converter.php" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?>
                        </a>
                    </li>
                    <li class="nav-tab active">
                        <a href="login.php" class="nav-link">
                            <i class="fas fa-sign-in-alt"></i> <?php echo $translations['login_nav']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="register.php" class="nav-link">
                            <i class="fas fa-user-plus"></i> <?php echo $translations['register_nav']; ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Мобильная навигация -->
            <div class="mobile-nav" id="mobileNav">
                <ul class="mobile-nav-tabs">
                    <li class="mobile-nav-tab">
                        <a href="index.php" class="mobile-nav-link">
                            <i class="fas fa-home"></i> <?php echo $translations['home']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="information.php" class="mobile-nav-link">
                            <i class="fas fa-info-circle"></i> <?php echo $translations['information']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="map.php" class="mobile-nav-link">
                            <i class="fas fa-map-marked-alt"></i> <?php echo $translations['map_services']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="translator.php" class="mobile-nav-link">
                            <i class="fas fa-language"></i> <?php echo $translations['translator']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="converter.php" class="mobile-nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab active">
                        <a href="login.php" class="mobile-nav-link">
                            <i class="fas fa-sign-in-alt"></i> <?php echo $translations['login_nav']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="register.php" class="mobile-nav-link">
                            <i class="fas fa-user-plus"></i> <?php echo $translations['register_nav']; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <div class="login-container">
            <div class="login-header">
                <h1><?php echo $translations['login_title']; ?></h1>
                <p><?php echo $translations['login_desc']; ?></p>
            </div>

            <form method="POST" action="" class="login-form" id="loginForm">
                <?php if (!empty($errors)): ?>
                    <div class="error-list">
                        <h4><i class="fas fa-exclamation-circle"></i> <?php echo $translations['login_error']; ?></h4>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label"><?php echo $translations['username_email']; ?></label>
                    <input type="text" 
                           name="username" 
                           class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           required
                           autocomplete="username">
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $translations['password']; ?></label>
                    <input type="password" 
                           name="password" 
                           class="form-input" 
                           required
                           autocomplete="current-password">
                </div>

                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i>
                    <span><?php echo $translations['login_btn']; ?></span>
                </button>

                <div class="register-link">
                    <?php echo $translations['no_account']; ?>
                    <a href="register.php"><?php echo $translations['register_here']; ?></a>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>MigraSupport</h3>
                    <p><?php echo $translations['footer_title']; ?></p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-telegram"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3><?php echo $translations['quick_links']; ?></h3>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-home"></i> <?php echo $translations['home']; ?></a></li>
                        <li><a href="information.php"><i class="fas fa-info-circle"></i> <?php echo $translations['information']; ?></a></li>
                        <li><a href="map.php"><i class="fas fa-map-marked-alt"></i> <?php echo $translations['map_services']; ?></a></li>
                        <li><a href="translator.php"><i class="fas fa-language"></i> <?php echo $translations['translator']; ?></a></li>
                        <li><a href="converter.php"><i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3><?php echo $translations['contacts']; ?></h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> info@migrasupport.by</li>
                        <li><i class="fas fa-phone"></i> +375 (17) 555-55-55</li>
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo $translations['minsk_belarus']; ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2023-2026 MigraSupport. <?php echo $translations['all_rights_reserved']; ?></p>
            </div>
        </div>
    </footer>

    <script>
        // Функция смены языка
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        // Инициализация при загрузке DOM
        document.addEventListener('DOMContentLoaded', function() {
            // Получаем элементы
            const burgerMenu = document.getElementById('burgerMenu');
            const mobileNav = document.getElementById('mobileNav');
            const header = document.querySelector('header');
            
            // Функция обновления позиции мобильного меню
            function updateMobileNavPosition() {
                if (mobileNav && header) {
                    const headerHeight = header.offsetHeight;
                    mobileNav.style.top = headerHeight + 'px';
                }
            }
            
            // Функция закрытия мобильного меню
            function closeMobileMenu() {
                if (burgerMenu && mobileNav) {
                    burgerMenu.classList.remove('active');
                    mobileNav.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
            
            // Функция открытия/закрытия мобильного меню
            function toggleMobileMenu(e) {
                if (e) e.stopPropagation();
                if (burgerMenu && mobileNav) {
                    burgerMenu.classList.toggle('active');
                    mobileNav.classList.toggle('active');
                    
                    // Блокируем/разблокируем прокрутку body
                    if (mobileNav.classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                }
            }
            
            // Инициализация бургер-меню
            if (burgerMenu && mobileNav) {
                // Устанавливаем начальную позицию
                updateMobileNavPosition();
                
                // Обновляем позицию при изменении размера окна
                window.addEventListener('resize', function() {
                    updateMobileNavPosition();
                    // Закрываем меню при изменении ориентации экрана
                    if (window.innerWidth > 992) {
                        closeMobileMenu();
                    }
                });
                
                // Обработчик клика по бургер-меню
                burgerMenu.addEventListener('click', toggleMobileMenu);
                
                // Закрытие при клике вне меню
                document.addEventListener('click', function(event) {
                    if (mobileNav.classList.contains('active')) {
                        // Проверяем, был ли клик не по бургер-меню и не по мобильной навигации
                        if (!burgerMenu.contains(event.target) && !mobileNav.contains(event.target)) {
                            closeMobileMenu();
                        }
                    }
                });
                
                // Закрытие при клике на ссылку в меню
                const mobileLinks = document.querySelectorAll('.mobile-nav-link');
                mobileLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        closeMobileMenu();
                    });
                });
                
                // Предотвращаем всплытие кликов внутри мобильного меню
                mobileNav.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Автофокус на поле username
            const usernameInput = document.querySelector('input[name="username"]');
            if (usernameInput && usernameInput.value === '') {
                usernameInput.focus();
            }
        });
    </script>
</body>
</html>
