<?php
// РћР±С‰РёР№ С„Р°Р№Р» РґР»СЏ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ С€Р°РїРєРё Рё РЅР°РІРёРіР°С†РёРё СЃ Р°РІР°С‚Р°СЂРѕРј
// Р­С‚РѕС‚ С„Р°Р№Р» РґРѕР»Р¶РµРЅ РїРѕРґРєР»СЋС‡Р°С‚СЊСЃСЏ РїРѕСЃР»Рµ session_start() Рё require_once '../src/config/config.php'

// РџСЂРѕРІРµСЂСЏРµРј, Р°РІС‚РѕСЂРёР·РѕРІР°РЅ Р»Рё РїРѕР»СЊР·РѕРІР°С‚РµР»СЊ
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : '';
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';

// Р—Р°РіСЂСѓР¶Р°РµРј РґР°РЅРЅС‹Рµ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ РґР»СЏ Р°РІР°С‚Р°СЂР°
$userAvatar = null;
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch();
        $userAvatar = $userData['avatar'] ?? null;
    } catch (PDOException $e) {
        // Р’ СЃР»СѓС‡Р°Рµ РѕС€РёР±РєРё РїСЂРѕСЃС‚Рѕ РёРіРЅРѕСЂРёСЂСѓРµРј
        $userAvatar = null;
    }
}

// Р¤СѓРЅРєС†РёСЏ РїРµСЂРµРІРѕРґР° (РµСЃР»Рё РЅРµ РѕРїСЂРµРґРµР»РµРЅР°)
if (!function_exists('t')) {
    function t($ru, $en, $pt = '', $fr = '', $de = '') {
        global $lang;
        $lang = $_COOKIE['lang'] ?? 'ru';
        
        switch ($lang) {
            case 'en': return $en;
            case 'pt': return !empty($pt) ? $pt : $en;
            case 'fr': return !empty($fr) ? $fr : $en;
            case 'de': return !empty($de) ? $de : $en;
            default: return $ru;
        }
    }
}

// Р‘Р°Р·РѕРІС‹Рµ РїРµСЂРµРІРѕРґС‹ РґР»СЏ РЅР°РІРёРіР°С†РёРё
$navTranslations = [
    'home' => t('Р“Р»Р°РІРЅР°СЏ', 'Home', 'InГ­cio', 'Accueil', 'Startseite'),
    'information' => t('РРЅС„РѕСЂРјР°С†РёСЏ', 'Information', 'InformaГ§ГЈo', 'Information', 'Informationen'),
    'map_services' => t('РљР°СЂС‚Р° СЃР»СѓР¶Р±', 'Services Map', 'Mapa de ServiГ§os', 'Carte des Services', 'Dienstleistungskarte'),
    'translator' => t('РџРµСЂРµРІРѕРґС‡РёРє', 'Translator', 'Tradutor', 'Traducteur', 'Гњbersetzer'),
    'currency_converter' => t('РљРѕРЅРІРµСЂС‚РµСЂ РІР°Р»СЋС‚', 'Currency Converter', 'Conversor de Moeda', 'Convertisseur de Devises', 'WГ¤hrungsrechner'),
    'city_chat' => t('Р§Р°С‚ РіРѕСЂРѕРґР°', 'City Chat', 'Chat da Cidade', 'Chat de la Ville', 'Stadt-Chat'),
    'profile' => t('РџСЂРѕС„РёР»СЊ', 'Profile', 'Perfil', 'Profil', 'Profil'),
    'admin_panel' => t('РђРґРјРёРЅ', 'Admin', 'Admin', 'Admin', 'Admin'),
    'login_nav' => t('Р’С…РѕРґ', 'Login', 'Entrar', 'Connexion', 'Anmelden'),
    'register_nav' => t('Р РµРіРёСЃС‚СЂР°С†РёСЏ', 'Register', 'Registrar', 'Inscription', 'Registrieren'),
    'logout' => t('Р’С‹Р№С‚Рё', 'Logout', 'Sair', 'DГ©connexion', 'Abmelden'),
    'go_to_profile' => t('РџРµСЂРµР№С‚Рё РІ РїСЂРѕС„РёР»СЊ', 'Go to Profile', 'Ir para o Perfil', 'Aller au Profil', 'Zum Profil gehen')
];
?>

<!-- РЁР°РїРєР° СЃР°Р№С‚Р° -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <!-- Р›РѕРіРѕС‚РёРї -->
            <div class="logo">
                <a href="index.php?lang=<?php echo $lang ?? 'ru'; ?>">
                    <div class="logo-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-title">MigraSupport</span>
                        <span class="logo-subtitle"><?php echo t('РњРёРіСЂР°С†РёРѕРЅРЅС‹Р№ С†РµРЅС‚СЂ', 'Migration Center', 'Centro de MigraГ§ГЈo', 'Centre de Migration', 'Migrationszentrum'); ?></span>
                    </div>
                </a>
            </div>

            <!-- РћСЃРЅРѕРІРЅР°СЏ РЅР°РІРёРіР°С†РёСЏ -->
            <nav class="main-nav">
                <ul class="nav-list">
                    <li class="nav-tab">
                        <a href="index.php?lang=<?php echo $lang ?? 'ru'; ?>" class="nav-link">
                            <i class="fas fa-home"></i> <?php echo $navTranslations['home']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="information.php?lang=<?php echo $lang ?? 'ru'; ?>" class="nav-link">
                            <i class="fas fa-info-circle"></i> <?php echo $navTranslations['information']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="map.php?lang=<?php echo $lang ?? 'ru'; ?>" class="nav-link">
                            <i class="fas fa-map-marked-alt"></i> <?php echo $navTranslations['map_services']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="translator.php?lang=<?php echo $lang ?? 'ru'; ?>" class="nav-link">
                            <i class="fas fa-language"></i> <?php echo $navTranslations['translator']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="converter.php?lang=<?php echo $lang ?? 'ru'; ?>" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $navTranslations['currency_converter']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="chat.php?lang=<?php echo $lang ?? 'ru'; ?>" class="nav-link">
                            <i class="fas fa-comments"></i> <?php echo $navTranslations['city_chat']; ?>
                        </a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-tab active">
                            <a href="profile.php?lang=<?php echo $lang ?? 'ru'; ?>" class="nav-link">
                                <i class="fas fa-user"></i> <?php echo $navTranslations['profile']; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- РџСЂР°РІР°СЏ С‡Р°СЃС‚СЊ С€Р°РїРєРё -->
            <div class="header-right">
                <!-- Р’С‹Р±РѕСЂ СЏР·С‹РєР° -->
                <div class="language-switcher">
                    <button class="language-btn" id="languageBtn">
                        <i class="fas fa-globe"></i>
                        <span class="language-code"><?php echo strtoupper($lang ?? 'ru'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="language-dropdown" id="languageDropdown">
                        <a href="?lang=ru" class="language-option <?php echo ($lang ?? 'ru') == 'ru' ? 'active' : ''; ?>">
                            <span class="flag-icon">рџ‡·рџ‡є</span> Р СѓСЃСЃРєРёР№
                        </a>
                        <a href="?lang=en" class="language-option <?php echo ($lang ?? 'ru') == 'en' ? 'active' : ''; ?>">
                            <span class="flag-icon">рџ‡єрџ‡ё</span> English
                        </a>
                        <a href="?lang=pt" class="language-option <?php echo ($lang ?? 'ru') == 'pt' ? 'active' : ''; ?>">
                            <span class="flag-icon">рџ‡µрџ‡№</span> PortuguГЄs
                        </a>
                        <a href="?lang=fr" class="language-option <?php echo ($lang ?? 'ru') == 'fr' ? 'active' : ''; ?>">
                            <span class="flag-icon">рџ‡«рџ‡·</span> FranГ§ais
                        </a>
                        <a href="?lang=de" class="language-option <?php echo ($lang ?? 'ru') == 'de' ? 'active' : ''; ?>">
                            <span class="flag-icon">рџ‡©рџ‡Є</span> Deutsch
                        </a>
                    </div>
                </div>

                <!-- РљРЅРѕРїРєРё Р°РІС‚РѕСЂРёР·Р°С†РёРё / РїСЂРѕС„РёР»СЊ -->
                <?php if ($isLoggedIn): ?>
                    <?php if ($userType === 'admin'): ?>
                        <a href="dashboard.php?lang=<?php echo $lang ?? 'ru'; ?>" class="btn btn-primary admin-btn">
                            <i class="fas fa-cog"></i> <?php echo $navTranslations['admin_panel']; ?>
                        </a>
                    <?php endif; ?>
                    
                    <div class="profile-dropdown">
                        <div class="user-avatar" id="profileAvatar" title="<?php echo $navTranslations['go_to_profile']; ?>">
                            <?php if ($userAvatar): ?>
                                <img src="<?php echo htmlspecialchars($userAvatar); ?>" 
                                     alt="<?php echo htmlspecialchars($userName); ?>"
                                     style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <?php echo substr($userName, 0, 1); ?>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-menu" id="profileDropdown">
                            <a href="profile.php?lang=<?php echo $lang ?? 'ru'; ?>" class="dropdown-item">
                                <i class="fas fa-user"></i> <?php echo $navTranslations['profile']; ?>
                            </a>
                            <a href="logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i> <?php echo $navTranslations['logout']; ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php?lang=<?php echo $lang ?? 'ru'; ?>" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i> <?php echo $navTranslations['login_nav']; ?>
                        </a>
                        <a href="register.php?lang=<?php echo $lang ?? 'ru'; ?>" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> <?php echo $navTranslations['register_nav']; ?>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Р‘СѓСЂРіРµСЂ-РјРµРЅСЋ РґР»СЏ РјРѕР±РёР»СЊРЅС‹С… -->
                <button class="burger-menu" id="burgerMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>

    <!-- РњРѕР±РёР»СЊРЅР°СЏ РЅР°РІРёРіР°С†РёСЏ -->
    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-content">
            <ul class="mobile-nav-list">
                <li class="mobile-nav-tab">
                    <a href="index.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                        <i class="fas fa-home"></i> <?php echo $navTranslations['home']; ?>
                    </a>
                </li>
                <li class="mobile-nav-tab">
                    <a href="information.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                        <i class="fas fa-info-circle"></i> <?php echo $navTranslations['information']; ?>
                    </a>
                </li>
                <li class="mobile-nav-tab">
                    <a href="map.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                        <i class="fas fa-map-marked-alt"></i> <?php echo $navTranslations['map_services']; ?>
                    </a>
                </li>
                <li class="mobile-nav-tab">
                    <a href="translator.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                        <i class="fas fa-language"></i> <?php echo $navTranslations['translator']; ?>
                    </a>
                </li>
                <li class="mobile-nav-tab">
                    <a href="converter.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                        <i class="fas fa-money-bill-wave"></i> <?php echo $navTranslations['currency_converter']; ?>
                    </a>
                </li>
                <li class="mobile-nav-tab">
                    <a href="chat.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                        <i class="fas fa-comments"></i> <?php echo $navTranslations['city_chat']; ?>
                    </a>
                </li>
                <?php if ($isLoggedIn): ?>
                    <li class="mobile-nav-tab">
                        <a href="profile.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                            <i class="fas fa-user"></i> <?php echo $navTranslations['profile']; ?>
                        </a>
                    </li>
                    <?php if ($userType === 'admin'): ?>
                        <li class="mobile-nav-tab">
                            <a href="dashboard.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                                <i class="fas fa-cog"></i> <?php echo $navTranslations['admin_panel']; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="mobile-nav-tab">
                        <a href="logout.php" class="mobile-nav-link">
                            <i class="fas fa-sign-out-alt"></i> <?php echo $navTranslations['logout']; ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="mobile-nav-tab">
                        <a href="login.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                            <i class="fas fa-sign-in-alt"></i> <?php echo $navTranslations['login_nav']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="register.php?lang=<?php echo $lang ?? 'ru'; ?>" class="mobile-nav-link">
                            <i class="fas fa-user-plus"></i> <?php echo $navTranslations['register_nav']; ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>

<!-- JavaScript РґР»СЏ РЅР°РІРёРіР°С†РёРё -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // РџРµСЂРµРєР»СЋС‡РµРЅРёРµ СЏР·С‹РєР°
    const languageBtn = document.getElementById('languageBtn');
    const languageDropdown = document.getElementById('languageDropdown');
    
    if (languageBtn && languageDropdown) {
        languageBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            languageDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!languageBtn.contains(e.target) && !languageDropdown.contains(e.target)) {
                languageDropdown.classList.remove('show');
            }
        });
    }
    
    // РџСЂРѕС„РёР»СЊ dropdown
    const profileAvatar = document.getElementById('profileAvatar');
    const dropdownMenu = document.getElementById('profileDropdown');
    
    if (profileAvatar && dropdownMenu) {
        profileAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!profileAvatar.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
    
    // Р‘СѓСЂРіРµСЂ РјРµРЅСЋ
    const burgerMenu = document.getElementById('burgerMenu');
    const mobileNav = document.getElementById('mobileNav');
    
    if (burgerMenu && mobileNav) {
        burgerMenu.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileNav.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (mobileNav.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
        
        document.addEventListener('click', function(event) {
            if (!burgerMenu.contains(event.target) && !mobileNav.contains(event.target)) {
                burgerMenu.classList.remove('active');
                mobileNav.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        document.querySelectorAll('.mobile-nav-link').forEach(link => {
            link.addEventListener('click', function() {
                burgerMenu.classList.remove('active');
                mobileNav.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }
});
</script>

<!-- РЎС‚РёР»Рё РґР»СЏ С€Р°РїРєРё -->
<style>
/* Р‘Р°Р·РѕРІС‹Рµ СЃС‚РёР»Рё РґР»СЏ С€Р°РїРєРё */
.header {
    background: linear-gradient(135deg, #1a237e 0%, #311b92 100%);
    color: white;
    padding: 15px 0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;
}

/* Р›РѕРіРѕС‚РёРї */
.logo a {
    display: flex;
    align-items: center;
    gap: 15px;
    text-decoration: none;
    color: white;
}

.logo-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #4a90e2 0%, #357ae8 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.logo-text {
    display: flex;
    flex-direction: column;
}

.logo-title {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
}

.logo-subtitle {
    font-size: 0.85rem;
    opacity: 0.9;
    font-weight: 400;
}

/* РћСЃРЅРѕРІРЅР°СЏ РЅР°РІРёРіР°С†РёСЏ */
.main-nav {
    flex: 1;
}

.nav-list {
    display: flex;
    gap: 5px;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-tab {
    position: relative;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 0.95rem;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    transform: translateY(-2px);
}

.nav-tab.active .nav-link {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* РџСЂР°РІР°СЏ С‡Р°СЃС‚СЊ С€Р°РїРєРё */
.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

/* РџРµСЂРµРєР»СЋС‡Р°С‚РµР»СЊ СЏР·С‹РєР° */
.language-switcher {
    position: relative;
}

.language-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.language-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}

.language-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 10px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    min-width: 180px;
    display: none;
    z-index: 1001;
}

.language-dropdown.show {
    display: block;
}

.language-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f0f0;
}

.language-option:last-child {
    border-bottom: none;
}

.language-option:hover {
    background: #f5f5f5;
}

.language-option.active {
    background: #4a90e2;
    color: white;
}

.flag-icon {
    font-size: 1.2rem;
}

/* РљРЅРѕРїРєРё Р°РІС‚РѕСЂРёР·Р°С†РёРё */
.auth-buttons {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
}

.btn-primary {
    background: linear-gradient(135deg, #4a90e2 0%, #357ae8 100%);
    color: white;
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 144, 226, 0.4);
}

.btn-outline {
    background: transparent;
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
    transform: translateY(-2px);
}

.admin-btn {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
}

/* РђРІР°С‚Р°СЂ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ */
.profile-dropdown {
    position: relative;
}

.user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4a90e2 0%, #357ae8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
}

.user-avatar:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(74, 144, 226, 0.4);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 10px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    min-width: 200px;
    display: none;
    z-index: 1001;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f0f0;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background: #f5f5f5;
}

.dropdown-item.logout {
    color: #ff6b6b;
}

/* Р‘СѓСЂРіРµСЂ РјРµРЅСЋ */
.burger-menu {
    display: none;
    flex-direction: column;
    justify-content: space-between;
    width: 30px;
    height: 21px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
}

.burger-menu span {
    display: block;
    height: 3px;
    width: 100%;
    background: white;
    border-radius: 3px;
    transition: all 0.3s ease;
}

.burger-menu.active span:nth-child(1) {
    transform: rotate(45deg) translate(6px, 6px);
}

.burger-menu.active span:nth-child(2) {
    opacity: 0;
}

.burger-menu.active span:nth-child(3) {
    transform: rotate(-45deg) translate(6px, -6px);
}

/* РњРѕР±РёР»СЊРЅР°СЏ РЅР°РІРёРіР°С†РёСЏ */
.mobile-nav {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(26, 35, 126, 0.95);
    z-index: 999;
    display: none;
    overflow-y: auto;
}

.mobile-nav.active {
    display: block;
}

.mobile-nav-content {
    padding: 100px 20px 40px;
}

.mobile-nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.mobile-nav-tab {
    margin-bottom: 10px;
}

.mobile-nav-link {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    color: white;
    text-decoration: none;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.mobile-nav-link:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(5px);
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 1200px) {
    .main-nav {
        display: none;
    }
    
    .burger-menu {
        display: flex;
    }
    
    .header-content {
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .logo-title {
        font-size: 1.2rem;
    }
    
    .logo-subtitle {
        font-size: 0.75rem;
    }
    
    .language-btn .language-code {
        display: none;
    }
    
    .auth-buttons .btn span {
        display: none;
    }
    
    .auth-buttons .btn {
        padding: 10px;
    }
}

@media (max-width: 480px) {
    .logo-text {
        display: none;
    }
    
    .header-right {
        gap: 10px;
    }
}
</style>
