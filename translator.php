<?php
ob_start();
session_start();
require_once 'config.php';

// Проверяем авторизацию
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : '';
$userType = $isLoggedIn ? ($_SESSION['user_type'] ?? '') : '';

// Получаем аватар пользователя
$userAvatar = null;
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch();
        $userAvatar = $userData['avatar'] ?? null;
    } catch (PDOException $e) {
        $userAvatar = null;
    }
}

// Подключаем файл с данными аватара
require_once 'include_avatar.php';

// Поддерживаемые языки - 5 ЯЗЫКОВ
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

// Функция перевода с поддержкой 5 языков
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

// Получаем историю переводов для пользователя
$translationHistory = [];
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM translation_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$_SESSION['user_id']]);
        $translationHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching translation history: " . $e->getMessage());
    }
}

// ==================== ПОЛНЫЙ МАССИВ ПЕРЕВОДОВ ДЛЯ 5 ЯЗЫКОВ ====================
$translations = [
    // Основные элементы интерфейса
    'main_title' => t(
        'Онлайн Переводчик - MigraSupport',
        'Online Translator - MigraSupport',
        'Tradutor Online - MigraSupport',
        'Traducteur en Ligne - MigraSupport',
        'Online-Übersetzer - MigraSupport'
    ),
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
    
    // Заголовки и описания переводчика
    'translator_title' => t(
        'Онлайн Переводчик',
        'Online Translator',
        'Tradutor Online',
        'Traducteur en Ligne',
        'Online-Übersetzer'
    ),
    'translator_desc' => t(
        'Быстрый и точный перевод с помощью современного API. Поддерживаются основные языки.',
        'Fast and accurate translation using modern API. Major languages supported.',
        'Tradução rápida e precisa usando API moderna. Idiomas principais suportados.',
        'Traduction rapide et précise grâce à une API moderne. Langues principales prises en charge.',
        'Schnelle und genaue Übersetzung mit moderner API. Hauptsprachen werden unterstützt.'
    ),
    'translator_main' => t(
        'Переводчик MigraSupport',
        'MigraSupport Translator',
        'Tradutor MigraSupport',
        'Traducteur MigraSupport',
        'MigraSupport Übersetzer'
    ),
    'translator_sub' => t(
        'Переводите тексты, документы и общайтесь без языковых барьеров',
        'Translate texts, documents and communicate without language barriers',
        'Traduza textos, documentos e comunique-se sem barreiras linguísticas',
        'Traduisez des textes, des documents et communiquez sans barrières linguistiques',
        'Übersetzen Sie Texte, Dokumente und kommunizieren Sie ohne Sprachbarrieren'
    ),
    
    // Языки и элементы управления переводом
    'source_lang' => t('Исходный язык:', 'Source language:', 'Idioma de origem:', 'Langue source:', 'Ausgangssprache:'),
    'target_lang' => t('Целевой язык:', 'Target language:', 'Idioma de destino:', 'Langue cible:', 'Zielsprache:'),
    'translate_text' => t('Текст для перевода:', 'Text to translate:', 'Texto para traduzir:', 'Texte à traduire:', 'Zu übersetzender Text:'),
    'translation' => t('Перевод:', 'Translation:', 'Tradução:', 'Traduction:', 'Übersetzung:'),
    'translate' => t('Перевести', 'Translate', 'Traduzir', 'Traduire', 'Übersetzen'),
    'clear' => t('Очистить', 'Clear', 'Limpar', 'Effacer', 'Löschen'),
    'swap_languages' => t('Поменять местами', 'Swap languages', 'Trocar idiomas', 'Échanger les langues', 'Sprachen tauschen'),
    'autodetect' => t('Автоопределение', 'Auto-detect', 'Detecção automática', 'Détection automatique', 'Automatisch erkennen'),
    
    // Названия языков
    'russian' => t('Русский', 'Russian', 'Russo', 'Russe', 'Russisch'),
    'english' => t('Английский', 'English', 'Inglês', 'Anglais', 'Englisch'),
    'spanish' => t('Испанский', 'Spanish', 'Espanhol', 'Espagnol', 'Spanisch'),
    'french' => t('Французский', 'French', 'Francês', 'Français', 'Französisch'),
    'german' => t('Немецкий', 'German', 'Alemão', 'Allemand', 'Deutsch'),
    'italian' => t('Итальянский', 'Italian', 'Italiano', 'Italien', 'Italienisch'),
    'japanese' => t('Японский', 'Japanese', 'Japonês', 'Japonais', 'Japanisch'),
    'chinese' => t('Китайский', 'Chinese', 'Chinês', 'Chinois', 'Chinesisch'),
    'arabic' => t('Арабский', 'Arabic', 'Árabe', 'Arabe', 'Arabisch'),
    'polish' => t('Польский', 'Polish', 'Polonês', 'Polonais', 'Polnisch'),
    'turkish' => t('Турецкий', 'Turkish', 'Turco', 'Turc', 'Türkisch'),
    'portuguese' => t('Португальский', 'Portuguese', 'Português', 'Portugais', 'Portugiesisch'),
    
    // Интерфейсные тексты переводчика
    'characters' => t('символов', 'characters', 'caracteres', 'caractères', 'Zeichen'),
    'enter_text' => t('Введите текст для перевода...', 'Enter text to translate...', 'Digite o texto para traduzir...', 'Entrez le texte à traduire...', 'Text zum Übersetzen eingeben...'),
    'translation_appears' => t('Перевод появится здесь...', 'Translation will appear here...', 'A tradução aparecerá aqui...', 'La traduction apparaîtra ici...', 'Übersetzung erscheint hier...'),
    'detecting_language' => t('Определение языка...', 'Detecting language...', 'Detectando idioma...', 'Détection de la langue...', 'Sprache erkennen...'),
    'translating' => t('Перевод...', 'Translating...', 'Traduzindo...', 'Traduction...', 'Übersetzen...'),
    'error' => t('Ошибка', 'Error', 'Erro', 'Erreur', 'Fehler'),
    'enter_text_to_translate' => t('Пожалуйста, введите текст для перевода', 'Please enter text to translate', 'Por favor, digite o texto para traduzir', 'Veuillez entrer le texte à traduire', 'Bitte geben Sie den zu übersetzenden Text ein'),
    'text_too_long' => t('Текст слишком длинный (максимум 5000 символов)', 'Text too long (maximum 5000 characters)', 'Texto muito longo (máximo 5000 caracteres)', 'Texte trop long (maximum 5000 caractères)', 'Text zu lang (maximal 5000 Zeichen)'),
    'all_translation_services_unavailable' => t('Все сервисы перевода недоступны', 'All translation services unavailable', 'Todos os serviços de tradução indisponíveis', 'Tous les services de traduction indisponibles', 'Alle Übersetzungsdienste nicht verfügbar'),
    'translation_services_unavailable' => t('Сервисы перевода временно недоступны. Используется демо-режим.', 'Translation services temporarily unavailable. Using demo mode.', 'Serviços de tradução temporariamente indisponíveis. Usando modo de demonstração.', 'Services de traduction temporairement indisponibles. Utilisation du mode démo.', 'Übersetzungsdienste vorübergehend nicht verfügbar. Demo-Modus wird verwendet.'),
    'demo_translation' => t('[ДЕМО-ПЕРЕВОД]', '[DEMO TRANSLATION]', '[TRADUÇÃO DEMO]', '[TRADUCTION DÉMO]', '[DEMO-ÜBERSETZUNG]'),
    
    // Советы по использованию
    'usage_tips' => t('Советы по использованию', 'Usage Tips', 'Dicas de uso', 'Conseils d\'utilisation', 'Nutzungstipps'),
    'clear_formulations' => t('Четкие формулировки', 'Clear Formulations', 'Formulações claras', 'Formulations claires', 'Klare Formulierungen'),
    'clear_formulations_desc' => t(
        'Для лучшего перевода используйте простые и понятные формулировки. Избегайте сленга и сложных конструкций.',
        'For better translation use simple and clear formulations. Avoid slang and complex constructions.',
        'Para uma melhor tradução, use formulações simples e claras. Evite gírias e construções complexas.',
        'Pour une meilleure traduction, utilisez des formulations simples et claires. Évitez l\'argot et les constructions complexes.',
        'Für eine bessere Übersetzung verwenden Sie einfache und klare Formulierungen. Vermeiden Sie Slang und komplexe Konstruktionen.'
    ),
    'official_docs' => t('Официальные документы', 'Official Documents', 'Documentos oficiais', 'Documents officiels', 'Offizielle Dokumente'),
    'official_docs_desc' => t(
        'При переводе официальных документов рекомендуется дополнительная проверка у профессионального переводчика.',
        'When translating official documents, additional verification by a professional translator is recommended.',
        'Ao traduzir documentos oficiais, recomenda-se verificação adicional por um tradutor profissional.',
        'Lors de la traduction de documents officiels, une vérification supplémentaire par un traducteur professionnel est recommandée.',
        'Bei der Übersetzung offizieller Dokumente wird eine zusätzliche Überprüfung durch einen professionellen Übersetzer empfohlen.'
    ),
    'communication' => t('Общение', 'Communication', 'Comunicação', 'Communication', 'Kommunikation'),
    'communication_desc' => t(
        'Используйте переводчик для преодоления языкового барьера в повседневном общении и официальных ситуациях.',
        'Use the translator to overcome language barriers in everyday communication and official situations.',
        'Use o tradutor para superar barreiras linguísticas na comunicação cotidiana e em situações oficiais.',
        'Utilisez le traducteur pour surmonter les barrières linguistiques dans la communication quotidienne et les situations officielles.',
        'Nutzen Sie den Übersetzer, um Sprachbarrieren in der alltäglichen Kommunikation und in offiziellen Situationen zu überwinden.'
    ),
    
    // История переводов
    'translation_history' => t('История переводов', 'Translation History', 'Histórico de traduções', 'Historique des traductions', 'Übersetzungsverlauf'),
    'no_history' => t('История переводов пуста', 'Translation history is empty', 'Histórico de traduções está vazio', 'L\'historique des traductions est vide', 'Der Übersetzungsverlauf ist leer'),
    'clear_history' => t('Очистить историю', 'Clear History', 'Limpar histórico', 'Effacer l\'historique', 'Verlauf löschen'),
    'copy_translation' => t('Копировать перевод', 'Copy Translation', 'Copiar tradução', 'Copier la traduction', 'Übersetzung kopieren'),
    'translation_copied' => t('Перевод скопирован в буфер обмена', 'Translation copied to clipboard', 'Tradução copiada para a área de transferência', 'Traduction copiée dans le presse-papiers', 'Übersetzung in die Zwischenablage kopiert'),
    'speak_text' => t('Озвучить текст', 'Speak Text', 'Falar texto', 'Lire le texte', 'Text vorlesen'),
    'stop_speech' => t('Остановить озвучивание', 'Stop Speech', 'Parar fala', 'Arrêter la lecture', 'Vorlesen stoppen'),
    'text_copied' => t('Текст скопирован в буфер обмена', 'Text copied to clipboard', 'Texto copiado para a área de transferência', 'Texte copié dans le presse-papiers', 'Text in die Zwischenablage kopiert'),
    'copy_text' => t('Копировать текст', 'Copy Text', 'Copiar texto', 'Copier le texte', 'Text kopieren'),
    'use_translation' => t('Использовать этот перевод', 'Use this translation', 'Usar esta tradução', 'Utiliser cette traduction', 'Diese Übersetzung verwenden'),
    'delete_from_history' => t('Удалить из истории', 'Delete from history', 'Excluir do histórico', 'Supprimer de l\'historique', 'Aus Verlauf löschen'),
    'history_cleared' => t('История переводов очищена', 'Translation history cleared', 'Histórico de traduções limpo', 'Historique des traductions effacé', 'Übersetzungsverlauf gelöscht'),
    'failed_to_copy' => t('Не удалось скопировать текст', 'Failed to copy text', 'Falha ao copiar texto', 'Échec de la copie du texte', 'Fehler beim Kopieren des Textes'),
    'confirm_clear_history' => t('Вы уверены, что хотите очистить всю историю переводов?', 'Are you sure you want to clear all translation history?', 'Tem certeza de que deseja limpar todo o histórico de traduções?', 'Êtes-vous sûr de vouloir effacer tout l\'historique des traductions?', 'Sind Sie sicher, dass Sie den gesamten Übersetzungsverlauf löschen möchten?'),
    'translation_deleted' => t('Перевод удален из истории', 'Translation deleted from history', 'Tradução excluída do histórico', 'Traduction supprimée de l\'historique', 'Übersetzung aus Verlauf gelöscht'),
    'translation_loaded' => t('Перевод загружен из истории', 'Translation loaded from history', 'Tradução carregada do histórico', 'Traduction chargée depuis l\'historique', 'Übersetzung aus Verlauf geladen'),
    'login_to_history' => t(
        'Для просмотра истории переводов необходимо войти в систему',
        'You need to login to view translation history',
        'Você precisa fazer login para ver o histórico de traduções',
        'Vous devez vous connecter pour voir l\'historique des traductions',
        'Sie müssen sich anmelden, um den Übersetzungsverlauf anzuzeigen'
    ),
    'go_to_profile' => t(
        'Перейти в профиль',
        'Go to Profile',
        'Ir para o Perfil',
        'Aller au Profil',
        'Zum Profil gehen'
    ),
    
    // Футер
    'footer_title' => t('MigraSupport', 'MigraSupport', 'MigraSupport', 'MigraSupport', 'MigraSupport'),
    'footer_desc' => t(
        'Комплексная система поддержки мигрантов в Беларуси. Мы помогаем с адаптацией, документами и интеграцией.',
        'Comprehensive migrant support system in Belarus. We help with adaptation, documents and integration.',
        'Sistema abrangente de apoio a migrantes na Bielorrússia. Ajudamos com adaptação, documentos e integração.',
        'Système complet de soutien aux migrants en Biélorussie. Nous aidons avec l\'adaptation, les documents et l\'intégration.',
        'Umfassendes Migrantenunterstützungssystem in Belarus. Wir helfen bei der Anpassung, Dokumenten und Integration.'
    ),
    'quick_links' => t('Быстрые ссылки', 'Quick Links', 'Links rápidos', 'Liens rapides', 'Schnelllinks'),
    'contacts' => t('Контакты', 'Contacts', 'Contatos', 'Contacts', 'Kontakte'),
    'support_247' => t('Поддержка 24/7', '24/7 Support', 'Suporte 24/7', 'Support 24/7', '24/7 Unterstützung'),
    'copyright' => t('Все права защищены.', 'All rights reserved.', 'Todos os direitos reservados.', 'Tous droits réservés.', 'Alle Rechte vorbehalten.'),
    'minsk_belarus' => t(
        'Минск, Беларусь',
        'Minsk, Belarus',
        'Minsk, Bielorrússia',
        'Minsk, Biélorussie',
        'Minsk, Belarus'
    )
];

// Функция для безопасного вывода строк в JavaScript
function js_string($str) {
    return str_replace(['\\', "'", '"', "\n", "\r"], ['\\\\', "\\'", '\\"', '\\n', '\\r'], $str);
}

// Подготавливаем переводы для JavaScript
$js_translations = [];
foreach ($translations as $key => $value) {
    $js_translations[$key] = js_string($value);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['main_title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Универсальные анимации -->
    <?php include_once 'include_animations.php'; ?>
    
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

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Анимации */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ========== HEADER ========== */
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
        }

        .logo-text {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        /* ========== ПРАВАЯ ЧАСТЬ ХЕДЕРА ========== */
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Language Selector для 5 языков */
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 0.85rem;
            min-width: 50px;
            text-align: center;
            flex: 0 0 auto;
        }

        .lang-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .lang-btn.active {
            background: var(--gradient-primary);
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        }

        /* User Info with Profile Dropdown */
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.5s ease;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--gradient-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 6px 15px rgba(255, 0, 110, 0.3);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .user-avatar::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: var(--gradient-secondary);
            border-radius: 50%;
            z-index: -1;
            animation: pulse 2s infinite;
        }

        .user-avatar:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 25px rgba(255, 0, 110, 0.4);
        }

        .profile-dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius);
            min-width: 180px;
            box-shadow: var(--shadow-lg);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: var(--transition);
            z-index: 1000;
            overflow: hidden;
        }

        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1);
            padding-left: 25px;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        .dropdown-item.logout {
            color: var(--danger);
        }

        .dropdown-item.logout:hover {
            background: rgba(255, 0, 84, 0.1);
        }

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
            letter-spacing: 0.3px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
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

        .btn-secondary {
            background: var(--gradient-secondary);
            color: white;
            box-shadow: 0 6px 15px rgba(255, 0, 110, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 25px rgba(255, 0, 110, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            backdrop-filter: blur(10px);
        }

        .btn-outline:hover {
            background: rgba(58, 134, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(58, 134, 255, 0.2);
        }

        .btn-danger-outline {
            background: transparent;
            border: 2px solid var(--danger);
            color: var(--danger);
            backdrop-filter: blur(10px);
        }

        .btn-danger-outline:hover {
            background: rgba(255, 0, 84, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 0, 84, 0.2);
            border-color: var(--danger);
        }

        /* ========== ОСНОВНАЯ НАВИГАЦИЯ ========== */
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

        /* ========== BURGER MENU ========== */
        .burger-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 10px;
            gap: 5px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: var(--transition);
        }

        .burger-menu:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.05);
        }

        .burger-line {
            width: 24px;
            height: 3px;
            background: white;
            transition: var(--transition);
            border-radius: 2px;
        }

        .burger-menu.active .burger-line:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
            background: var(--accent);
        }

        .burger-menu.active .burger-line:nth-child(2) {
            opacity: 0;
        }

        .burger-menu.active .burger-line:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
            background: var(--accent);
        }

        /* ========== МОБИЛЬНАЯ НАВИГАЦИЯ ========== */
        .mobile-nav {
            display: none;
            position: fixed;
            top: 72px;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 0 0 var(--radius) var(--radius);
            box-shadow: var(--shadow-xl);
            z-index: 1000;
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.3s ease;
        }
        
        .mobile-nav.active {
            max-height: 500px;
        }
        
        .mobile-nav-tabs {
            display: flex;
            flex-direction: column;
            list-style: none;
            padding: 15px;
        }
        
        .mobile-nav-tab {
            padding: 14px 18px;
            transition: var(--transition);
            font-weight: 500;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .mobile-nav-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        
        .mobile-nav-tab:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .mobile-nav-tab.active {
            color: white;
            background: rgba(255, 255, 255, 0.08);
            border-left: 3px solid var(--accent);
        }
        
        .mobile-nav-tab i {
            font-size: 1rem;
            width: 22px;
        }

        /* ========== MAIN CONTENT ========== */
        main {
            padding: 40px 0;
            margin-top: 120px;
            animation: fadeInUp 0.6s ease;
        }

        /* Hero Section */
        .hero-section {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 80px 40px;
            margin-bottom: 60px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
            background-image: url('https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-blend-mode: overlay;
            background-color: rgba(26, 26, 46, 0.8);
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .hero-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(58, 134, 255, 0.2) 0%, rgba(131, 56, 236, 0.2) 100%);
            z-index: -1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            color: white;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.8s ease;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            animation: fadeInUp 1s ease;
        }

        /* ========== CARDS ========== */
        .card {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.6s ease;
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            color: var(--accent);
            font-size: 1.4rem;
            animation: float 3s ease-in-out infinite;
        }

        /* ========== TRANSLATOR CONTAINER ========== */
        .translator-container {
            background: rgba(26, 26, 46, 0.7);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .translator-header {
            background: var(--gradient-primary);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .translator-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/always-grey.png');
            opacity: 0.1;
        }

        .translator-header h2 {
            font-size: 1.6rem;
            margin-bottom: 8px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .translator-header p {
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }

        .translator-body {
            padding: 30px;
        }

        .language-selectors {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .language-selectors {
                flex-direction: column;
                gap: 15px;
            }
        }

        .language-selector-item {
            flex: 1;
        }

        .language-selector-item label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }

        .translator-select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            transition: var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 16px;
            padding-right: 45px;
        }

        .translator-select option {
            background-color: var(--dark);
            color: white;
        }

        .translator-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .swap-button {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3);
            font-size: 1.1rem;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .swap-button {
                margin: 8px auto;
                transform: rotate(90deg);
            }
        }

        .swap-button:hover {
            transform: rotate(180deg) scale(1.1);
            box-shadow: 0 10px 20px rgba(58, 134, 255, 0.4);
        }

        .text-areas {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .text-areas {
                flex-direction: column;
                gap: 15px;
            }
        }

        .text-area-container {
            flex: 1;
            position: relative;
        }

        .text-area-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }

        .translator-textarea {
            width: 100%;
            height: 180px;
            padding: 18px 50px 18px 18px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            resize: none;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
        }

        .translator-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .translator-textarea::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .translator-textarea[readonly] {
            background: rgba(0, 0, 0, 0.2);
            cursor: not-allowed;
        }

        .text-area-controls {
            position: absolute;
            top: 40px;
            right: 10px;
            display: flex;
            gap: 8px;
        }

        .text-area-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .text-area-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .text-area-btn.active {
            background: var(--primary);
            color: white;
        }

        .char-count {
            text-align: right;
            margin-top: 6px;
            font-size: 0.8rem;
            color: var(--gray-light);
        }

        .translator-buttons {
            display: flex;
            gap: 12px;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .translator-buttons {
                flex-direction: column;
            }
        }

        .translate-button {
            flex: 1;
            padding: 16px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .translate-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(58, 134, 255, 0.4);
        }

        .translate-button:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .translator-clear-button {
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .translator-clear-button:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }

        .translator-error {
            color: var(--danger);
            text-align: center;
            margin-top: 12px;
            padding: 12px;
            background-color: rgba(255, 0, 84, 0.1);
            border-radius: 8px;
            display: none;
            border-left: 4px solid var(--danger);
            animation: fadeIn 0.3s ease;
            font-size: 0.9rem;
        }

        .auto-detect-indicator {
            font-size: 0.8rem;
            color: var(--success);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            opacity: 0.8;
        }

        .language-badge {
            display: inline-block;
            padding: 3px 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            font-size: 0.75rem;
            color: var(--gray-light);
            margin-left: 8px;
        }

        .loading {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        /* ========== TRANSLATION HISTORY ========== */
        .history-container {
            margin-top: 20px;
        }

        .history-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            padding: 15px;
            margin-bottom: 10px;
            border-left: 3px solid var(--primary);
            transition: var(--transition);
        }

        .history-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(5px);
        }

        .history-text {
            color: var(--gray-light);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .history-translation {
            color: white;
            margin-top: 8px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .history-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.8rem;
            color: var(--gray);
        }

        .history-actions {
            display: flex;
            gap: 8px;
        }

        .history-action-btn {
            background: none;
            border: none;
            color: var(--gray-light);
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .history-action-btn:hover {
            color: var(--primary);
        }

        /* ========== SERVICES GRID ========== */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 35px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            animation: fadeInUp 0.6s ease;
            animation-fill-mode: both;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .service-card:nth-child(2) { animation-delay: 0.1s; }
        .service-card:nth-child(3) { animation-delay: 0.2s; }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 25px rgba(58, 134, 255, 0.3);
        }

        .service-card h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.4rem;
            font-weight: 600;
            line-height: 1.3;
        }

        .service-card p {
            color: var(--gray-light);
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 25px;
            flex-grow: 1;
        }

        /* ========== NOTIFICATIONS ========== */
        .notification {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin: 15px 0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInUp 0.5s ease;
            border-left: 4px solid;
            backdrop-filter: blur(10px);
            font-size: 0.9rem;
        }

        .notification.success {
            background: rgba(56, 176, 0, 0.15);
            color: #c8ffb0;
            border-left-color: var(--success);
        }

        .notification.error {
            background: rgba(255, 0, 84, 0.15);
            color: #ffb8d0;
            border-left-color: var(--danger);
        }

        .notification.info {
            background: rgba(58, 134, 255, 0.15);
            color: #b8d6ff;
            border-left-color: var(--primary);
        }

        .notification.warning {
            background: rgba(255, 158, 0, 0.15);
            color: #ffe4b8;
            border-left-color: var(--warning);
        }

        /* ========== FOOTER ========== */
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

        /* ========== ДЕКОРАТИВНЫЕ ЭЛЕМЕНТЫ ========== */
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), transparent);
            filter: blur(40px);
            opacity: 0.3;
            z-index: -1;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            width: 250px;
            height: 250px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 180px;
            height: 180px;
            bottom: 20%;
            right: 10%;
            animation-delay: 2s;
            background: linear-gradient(135deg, var(--secondary), transparent);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .header-nav {
                display: none;
            }
            
            .burger-menu {
                display: flex;
            }
            
            .header-right {
                gap: 10px;
            }
            
            main {
                margin-top: 60px;
            }
            
            .mobile-nav {
                top: 60px;
                display: block;
            }
            
            .services-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 50px 20px;
            }

            .hero-title {
                font-size: 2.2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .card {
                padding: 20px;
            }

            .translator-body {
                padding: 20px;
            }
            
            .translator-buttons {
                flex-direction: column;
            }
            
            .header-top {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .logo {
                font-size: 1.3rem;
            }
            
            .logo-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
            
            .user-avatar {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 8px 15px;
                font-size: 0.8rem;
            }
            
            .service-card {
                padding: 25px;
            }
            
            .service-icon {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
            
            .language-selector {
                flex-wrap: nowrap;
                justify-content: center;
                width: 100%;
                max-width: 280px;
            }
            
            .lang-btn {
                min-width: 45px;
                padding: 6px 8px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0 15px;
            }

            .hero-title {
                font-size: 1.9rem;
            }

            .hero-subtitle {
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
            
            .language-selectors {
                flex-direction: column;
            }
            
            .swap-button {
                margin: 8px auto;
                transform: rotate(90deg);
            }
            
            .language-selector {
                max-width: 250px;
            }
            
            .lang-btn {
                min-width: 40px;
                padding: 5px 6px;
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 400px) {
            .language-selector {
                max-width: 220px;
            }
            
            .lang-btn {
                min-width: 35px;
                padding: 4px 5px;
                font-size: 0.7rem;
            }
            
            .header-right {
                gap: 5px;
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
                
                <!-- Правая часть хедера -->
                <div class="header-right">
                    <!-- Language Selector с 5 языками -->
                    <div class="language-selector">
                        <button class="lang-btn <?php echo $lang === 'ru' ? 'active' : ''; ?>" onclick="changeLanguage('ru')">
                            RU
                        </button>
                        <button class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" onclick="changeLanguage('en')">
                            EN
                        </button>
                        <button class="lang-btn <?php echo $lang === 'pt' ? 'active' : ''; ?>" onclick="changeLanguage('pt')">
                            PT
                        </button>
                        <button class="lang-btn <?php echo $lang === 'fr' ? 'active' : ''; ?>" onclick="changeLanguage('fr')">
                            FR
                        </button>
                        <button class="lang-btn <?php echo $lang === 'de' ? 'active' : ''; ?>" onclick="changeLanguage('de')">
                            DE
                        </button>
                    </div>
                    
                    <div class="user-info" <?php if (!$isLoggedIn) echo 'style="display: none;"'; ?>>
                        <?php if ($userType === 'admin'): ?>
                            <a href="dashboard.php" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.8rem;">
                                <i class="fas fa-cog"></i> <?php echo $translations['admin_panel']; ?>
                            </a>
                        <?php endif; ?>
                        <div class="profile-dropdown">
                            <div class="user-avatar" id="profileAvatar" title="<?php echo $translations['go_to_profile']; ?>">
                                <?php if ($userAvatar): ?>
                                    <img src="<?php echo htmlspecialchars($userAvatar); ?>" 
                                         alt="<?php echo htmlspecialchars($userName); ?>"
                                         style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo substr($userName, 0, 1); ?>
                                <?php endif; ?>
                            </div>
                            <div class="dropdown-menu" id="profileDropdown">
                                <a href="profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i> <?php echo $translations['profile']; ?>
                                </a>
                                <a href="logout.php" class="dropdown-item logout">
                                    <i class="fas fa-sign-out-alt"></i> <?php echo $translations['logout']; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Основная навигация -->
            <nav class="header-nav">
                <ul class="nav-tabs">
                    <li class="nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-home"></i> <?php echo $translations['home']; ?>
                        </a>
                    </li>
                    <li class="nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'information.php' ? 'active' : ''; ?>">
                        <a href="information.php" class="nav-link">
                            <i class="fas fa-info-circle"></i> <?php echo $translations['information']; ?>
                        </a>
                    </li>
                    <li class="nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'map.php' ? 'active' : ''; ?>">
                        <a href="map.php" class="nav-link">
                            <i class="fas fa-map-marked-alt"></i> <?php echo $translations['map_services']; ?>
                        </a>
                    </li>
                    <li class="nav-tab active">
                        <a href="translator.php" class="nav-link">
                            <i class="fas fa-language"></i> <?php echo $translations['translator']; ?>
                        </a>
                    </li>
                    <li class="nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'converter.php' ? 'active' : ''; ?>">
                        <a href="converter.php" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?>
                        </a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
                            <a href="chat.php" class="nav-link">
                                <i class="fas fa-comments"></i> <?php echo $translations['city_chat']; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">
                            <a href="login.php" class="nav-link">
                                <i class="fas fa-sign-in-alt"></i> <?php echo $translations['login_nav']; ?>
                            </a>
                        </li>
                        <li class="nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>">
                            <a href="register.php" class="nav-link">
                                <i class="fas fa-user-plus"></i> <?php echo $translations['register_nav']; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <!-- Мобильная навигация -->
            <div class="mobile-nav" id="mobileNav">
                <ul class="mobile-nav-tabs">
                    <li class="mobile-nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <a href="index.php" class="mobile-nav-link">
                            <i class="fas fa-home"></i> <?php echo $translations['home']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'information.php' ? 'active' : ''; ?>">
                        <a href="information.php" class="mobile-nav-link">
                            <i class="fas fa-info-circle"></i> <?php echo $translations['information']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'map.php' ? 'active' : ''; ?>">
                        <a href="map.php" class="mobile-nav-link">
                            <i class="fas fa-map-marked-alt"></i> <?php echo $translations['map_services']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab active">
                        <a href="translator.php" class="mobile-nav-link">
                            <i class="fas fa-language"></i> <?php echo $translations['translator']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'converter.php' ? 'active' : ''; ?>">
                        <a href="converter.php" class="mobile-nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?>
                        </a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="mobile-nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
                            <a href="chat.php" class="mobile-nav-link">
                                <i class="fas fa-comments"></i> <?php echo $translations['city_chat']; ?>
                            </a>
                        </li>
                        <li class="mobile-nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                            <a href="profile.php" class="mobile-nav-link">
                                <i class="fas fa-user"></i> <?php echo $translations['profile']; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="mobile-nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">
                            <a href="login.php" class="mobile-nav-link">
                                <i class="fas fa-sign-in-alt"></i> <?php echo $translations['login_nav']; ?>
                            </a>
                        </li>
                        <li class="mobile-nav-tab <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>">
                            <a href="register.php" class="mobile-nav-link">
                                <i class="fas fa-user-plus"></i> <?php echo $translations['register_nav']; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="hero-title"><?php echo $translations['translator_title']; ?></h1>
            <p class="hero-subtitle"><?php echo $translations['translator_desc']; ?></p>
        </div>

        <!-- Translator Container -->
        <div class="translator-container">
            <div class="translator-header">
                <h2><i class="fas fa-language"></i> <?php echo $translations['translator_main']; ?></h2>
                <p><?php echo $translations['translator_sub']; ?></p>
            </div>
            
            <div class="translator-body">
                <div class="language-selectors">
                    <div class="language-selector-item">
                        <label for="source-language"><?php echo $translations['source_lang']; ?></label>
                        <select id="source-language" class="translator-select smooth-input">
                            <option value="auto"><?php echo $translations['autodetect']; ?></option>
                            <option value="ru"><?php echo $translations['russian']; ?></option>
                            <option value="en"><?php echo $translations['english']; ?></option>
                            <option value="es"><?php echo $translations['spanish']; ?></option>
                            <option value="fr"><?php echo $translations['french']; ?></option>
                            <option value="de"><?php echo $translations['german']; ?></option>
                            <option value="it"><?php echo $translations['italian']; ?></option>
                            <option value="ja"><?php echo $translations['japanese']; ?></option>
                            <option value="zh"><?php echo $translations['chinese']; ?></option>
                            <option value="ar"><?php echo $translations['arabic']; ?></option>
                            <option value="pl"><?php echo $translations['polish']; ?></option>
                            <option value="tr"><?php echo $translations['turkish']; ?></option>
                            <option value="pt"><?php echo $translations['portuguese']; ?></option>
                        </select>
                        <div class="auto-detect-indicator" id="auto-detect-indicator" style="display: none;">
                            <i class="fas fa-bolt"></i>
                            <span><?php echo $translations['detecting_language']; ?></span>
                        </div>
                    </div>
                    
                    <button class="swap-button" id="swap-languages" title="<?php echo $translations['swap_languages']; ?>">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    
                    <div class="language-selector-item">
                        <label for="target-language"><?php echo $translations['target_lang']; ?></label>
                        <select id="target-language" class="translator-select smooth-input">
                            <option value="en"><?php echo $translations['english']; ?></option>
                            <option value="ru"><?php echo $translations['russian']; ?></option>
                            <option value="es"><?php echo $translations['spanish']; ?></option>
                            <option value="fr"><?php echo $translations['french']; ?></option>
                            <option value="de"><?php echo $translations['german']; ?></option>
                            <option value="it"><?php echo $translations['italian']; ?></option>
                            <option value="ja"><?php echo $translations['japanese']; ?></option>
                            <option value="zh"><?php echo $translations['chinese']; ?></option>
                            <option value="ar"><?php echo $translations['arabic']; ?></option>
                            <option value="pl"><?php echo $translations['polish']; ?></option>
                            <option value="tr"><?php echo $translations['turkish']; ?></option>
                            <option value="pt"><?php echo $translations['portuguese']; ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="text-areas">
                    <div class="text-area-container">
                        <label for="source-text">
                            <span id="source-text-label"><?php echo $translations['translate_text']; ?></span>
                            <span class="language-badge" id="detected-language"></span>
                        </label>
                        <textarea id="source-text" class="translator-textarea smooth-input" placeholder="<?php echo $translations['enter_text']; ?>" rows="6"></textarea>
                        <div class="text-area-controls">
                            <button class="text-area-btn" id="speak-source" title="<?php echo $translations['speak_text']; ?>">
                                <i class="fas fa-volume-up"></i>
                            </button>
                            <button class="text-area-btn" id="copy-source" title="<?php echo $translations['copy_text']; ?>">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="char-count" id="source-char-count">0 <?php echo $translations['characters']; ?></div>
                    </div>
                    
                    <div class="text-area-container">
                        <label for="translated-text">
                            <span id="translated-text-label"><?php echo $translations['translation']; ?></span>
                        </label>
                        <textarea id="translated-text" class="translator-textarea" placeholder="<?php echo $translations['translation_appears']; ?>" readonly rows="6"></textarea>
                        <div class="text-area-controls">
                            <button class="text-area-btn" id="speak-translation" title="<?php echo $translations['speak_text']; ?>">
                                <i class="fas fa-volume-up"></i>
                            </button>
                            <button class="text-area-btn" id="copy-translation" title="<?php echo $translations['copy_translation']; ?>">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="char-count" id="translated-char-count">0 <?php echo $translations['characters']; ?></div>
                    </div>
                </div>
                
                <div class="translator-error" id="error-message">
                    <?php echo $translations['error']; ?>
                </div>
                
                <div class="translator-buttons">
                    <button class="translator-clear-button" id="clear-translator">
                        <i class="fas fa-trash"></i> <?php echo $translations['clear']; ?>
                    </button>
                    <button class="translate-button" id="translate-button">
                        <i class="fas fa-language"></i>
                        <span class="button-text"><?php echo $translations['translate']; ?></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Translation History -->
        <div class="card" id="history-section">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-history"></i> <?php echo $translations['translation_history']; ?>
                </h2>
                <div style="display: flex; gap: 10px;">
                    <?php if ($isLoggedIn && !empty($translationHistory)): ?>
                        <button class="btn btn-danger-outline" onclick="clearHistory()" style="padding: 8px 15px; font-size: 0.85rem;">
                            <i class="fas fa-trash"></i> <?php echo $translations['clear_history']; ?>
                        </button>
                    <?php elseif ($isLoggedIn): ?>
                        <button class="btn btn-danger-outline" onclick="clearHistory()" style="padding: 8px 15px; font-size: 0.85rem; display: none;">
                            <i class="fas fa-trash"></i> <?php echo $translations['clear_history']; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="history-container" id="history-container">
                <?php if (empty($translationHistory)): ?>
                    <div class="notification info">
                        <i class="fas fa-info-circle"></i> 
                        <?php if ($isLoggedIn): ?>
                            <?php echo $translations['no_history']; ?>
                        <?php else: ?>
                            <?php echo $translations['login_to_history']; ?>
                            <a href="login.php" style="color: var(--primary); margin-left: 10px; text-decoration: none;">
                                <i class="fas fa-sign-in-alt"></i> <?php echo $translations['login_nav']; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($translationHistory as $item): ?>
                        <div class="history-item" data-id="<?php echo $item['id']; ?>">
                            <div class="history-text">
                                <strong><?php echo strtoupper($item['source_lang']); ?> → <?php echo strtoupper($item['target_lang']); ?></strong><br>
                                <?php echo htmlspecialchars($item['source_text']); ?>
                            </div>
                            <div class="history-translation">
                                <?php echo htmlspecialchars($item['translated_text']); ?>
                            </div>
                            <div class="history-meta">
                                <span><?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?></span>
                                <div class="history-actions">
                                    <button class="history-action-btn" onclick="useHistoryItem(<?php echo $item['id']; ?>)" title="<?php echo $translations['use_translation']; ?>">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <button class="history-action-btn" onclick="deleteHistoryItem(<?php echo $item['id']; ?>)" title="<?php echo $translations['delete_from_history']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Usage Tips -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-lightbulb"></i> <?php echo $translations['usage_tips']; ?>
            </h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon" style="background: var(--gradient-primary);">
                        <i class="fas fa-text-height"></i>
                    </div>
                    <h3><?php echo $translations['clear_formulations']; ?></h3>
                    <p><?php echo $translations['clear_formulations_desc']; ?></p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon" style="background: var(--gradient-success);">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3><?php echo $translations['official_docs']; ?></h3>
                    <p><?php echo $translations['official_docs_desc']; ?></p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon" style="background: var(--gradient-secondary);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3><?php echo $translations['communication']; ?></h3>
                    <p><?php echo $translations['communication_desc']; ?></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>MigraSupport</h3>
                    <p><?php echo t(
                        'Комплексная система поддержки мигрантов в Беларуси. Мы помогаем с адаптацией, документами и интеграцией.',
                        'Comprehensive migrant support system in Belarus. We help with adaptation, documents and integration.',
                        'Sistema abrangente de apoio a migrantes na Bielorrússia. Ajudamos com adaptação, documentos e integração.',
                        'Système complet de soutien aux migrants en Biélorussie. Nous aidons à l\'adaptation, aux documents et à l\'intégration.',
                        'Umfassendes Migrantenunterstützungssystem in Belarus. Wir helfen bei Anpassung, Dokumenten und Integration.'
                    ); ?></p>
                </div>
                
                <div class="footer-section">
                    <h3><?php echo $translations['quick_links']; ?></h3>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-home"></i> <?php echo $translations['home']; ?></a></li>
                        <li><a href="converter.php"><i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?></a></li>
                        <li><a href="translator.php"><i class="fas fa-language"></i> <?php echo $translations['translator']; ?></a></li>
                        <li><a href="map.php"><i class="fas fa-map-marked-alt"></i> <?php echo $translations['map_services']; ?></a></li>
                        <li><a href="information.php"><i class="fas fa-info-circle"></i> <?php echo $translations['information']; ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3><?php echo $translations['contacts']; ?></h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> info@migrasupport.by</li>
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo $translations['minsk_belarus']; ?></li>
                        <li><i class="fas fa-clock"></i> <?php echo t('Поддержка 24/7', '24/7 Support', 'Suporte 24/7', 'Support 24/7', '24/7 Support'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 MigraSupport. <?php echo $translations['all_rights_reserved']; ?></p>
            </div>
        </div>
    </footer>
    
    <script>
        // ИСПРАВЛЕНО: Безопасные переводы для JavaScript
        const langTranslations = {
            <?php foreach ($js_translations as $key => $value): ?>
            '<?php echo $key; ?>': '<?php echo $value; ?>',
            <?php endforeach; ?>
        };

        // Функция смены языка
        window.changeLanguage = function(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        };

        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            initializeTranslator();
            initializeBurgerMenu();
            initializeProfileDropdown();
            
            // Автофокус на поле ввода
            const sourceText = document.getElementById('source-text');
            if (sourceText) {
                sourceText.focus();
            }
        });

        // Инициализация профиля и dropdown
        function initializeProfileDropdown() {
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
                
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        }

        // ИСПРАВЛЕНО: Бургер-меню - правильная инициализация
        function initializeBurgerMenu() {
            const burgerMenu = document.getElementById('burgerMenu');
            const mobileNav = document.getElementById('mobileNav');
            
            if (burgerMenu && mobileNav) {
                // Функция закрытия меню
                function closeMobileMenu() {
                    burgerMenu.classList.remove('active');
                    mobileNav.classList.remove('active');
                }
                
                // Функция открытия/закрытия меню
                function toggleMobileMenu() {
                    burgerMenu.classList.toggle('active');
                    mobileNav.classList.toggle('active');
                }
                
                // Обработчик клика по бургер-меню
                burgerMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleMobileMenu();
                });
                
                // Закрытие при клике вне меню
                document.addEventListener('click', function(event) {
                    if (!burgerMenu.contains(event.target) && !mobileNav.contains(event.target)) {
                        closeMobileMenu();
                    }
                });
                
                // Закрытие при клике на ссылку в меню
                document.querySelectorAll('.mobile-nav-link').forEach(link => {
                    link.addEventListener('click', function() {
                        closeMobileMenu();
                    });
                });
            }
        }

        // Функция для обновления текста меток на основе выбранного языка
        function updateUILabels() {
            const sourceTextLabel = document.getElementById('source-text-label');
            const translatedTextLabel = document.getElementById('translated-text-label');
            
            if (sourceTextLabel) {
                sourceTextLabel.textContent = langTranslations['translate_text'] || 'Текст для перевода:';
            }
            if (translatedTextLabel) {
                translatedTextLabel.textContent = langTranslations['translation'] || 'Перевод:';
            }
        }

        // Переменные для управления переводчиком
        let speechSynthesis = window.speechSynthesis;
        let currentSpeech = null;
        let currentController = null;

        // Быстрые API переводчиков (в порядке приоритета)
        const translationApis = [
            {
                name: 'Lingva.ml',
                url: (text, sourceLang, targetLang) => `https://lingva.ml/api/v1/${sourceLang}/${targetLang}/${encodeURIComponent(text)}`,
                method: 'GET',
                parse: async (response) => {
                    const data = await response.json();
                    return data.translation;
                },
                timeout: 3000
            },
            {
                name: 'LibreTranslate',
                url: () => 'https://translate.argosopentech.com/translate',
                method: 'POST',
                body: (text, sourceLang, targetLang) => JSON.stringify({
                    q: text,
                    source: sourceLang,
                    target: targetLang,
                    format: 'text'
                }),
                headers: {
                    'Content-Type': 'application/json'
                },
                parse: async (response) => {
                    const data = await response.json();
                    return data.translatedText;
                },
                timeout: 4000
            },
            {
                name: 'SimpleTranslate',
                url: (text, sourceLang, targetLang) => `https://simplytranslate.org/api/translate?engine=google&from=${sourceLang}&to=${targetLang}&text=${encodeURIComponent(text)}`,
                method: 'GET',
                parse: async (response) => {
                    const data = await response.json();
                    return data.translated_text;
                },
                timeout: 3000
            },
            {
                name: 'GoogleTranslate',
                url: (text, sourceLang, targetLang) => `https://translate.googleapis.com/translate_a/single?client=gtx&sl=${sourceLang}&tl=${targetLang}&dt=t&q=${encodeURIComponent(text)}`,
                method: 'GET',
                parse: async (response) => {
                    const data = await response.json();
                    let translated = '';
                    if (data[0]) {
                        data[0].forEach(item => {
                            if (item[0]) {
                                translated += item[0];
                            }
                        });
                    }
                    return translated;
                },
                timeout: 5000
            }
        ];

        // Кэш быстрых переводов с поддержкой всех языков
        const commonPhrasesCache = {
            'ru-en': { 'здравствуйте': 'hello', 'привет': 'hi', 'спасибо': 'thank you', 'пожалуйста': 'please', 'извините': 'excuse me', 'да': 'yes', 'нет': 'no', 'помогите': 'help', 'врач': 'doctor', 'полиция': 'police', 'больница': 'hospital', 'адрес': 'address', 'документы': 'documents', 'паспорт': 'passport', 'виза': 'visa', 'работа': 'work', 'жилье': 'housing', 'миграция': 'migration', 'поддержка': 'support' },
            'en-ru': { 'hello': 'здравствуйте', 'hi': 'привет', 'thank you': 'спасибо', 'please': 'пожалуйста', 'excuse me': 'извините', 'yes': 'да', 'no': 'нет', 'help': 'помогите', 'doctor': 'врач', 'police': 'полиция', 'hospital': 'больница', 'address': 'адрес', 'documents': 'документы', 'passport': 'паспорт', 'visa': 'виза', 'work': 'работа', 'housing': 'жилье', 'migration': 'миграция', 'support': 'поддержка' },
            'ru-fr': { 'здравствуйте': 'bonjour', 'спасибо': 'merci', 'пожалуйста': 's\'il vous plaît', 'да': 'oui', 'нет': 'non', 'помогите': 'aidez-moi' },
            'fr-ru': { 'bonjour': 'здравствуйте', 'merci': 'спасибо', 's\'il vous plaît': 'пожалуйста', 'oui': 'да', 'non': 'нет', 'aidez-moi': 'помогите' },
            'ru-de': { 'здравствуйте': 'hallo', 'спасибо': 'danke', 'пожалуйста': 'bitte', 'да': 'ja', 'нет': 'nein', 'помогите': 'hilfe' },
            'de-ru': { 'hallo': 'здравствуйте', 'danke': 'спасибо', 'bitte': 'пожалуйста', 'ja': 'да', 'nein': 'нет', 'hilfe': 'помогите' },
            'ru-pt': { 'здравствуйте': 'olá', 'спасибо': 'obrigado', 'пожалуйста': 'por favor', 'да': 'sim', 'нет': 'não', 'помогите': 'ajuda' },
            'pt-ru': { 'olá': 'здравствуйте', 'obrigado': 'спасибо', 'por favor': 'пожалуйста', 'sim': 'да', 'não': 'нет', 'ajuda': 'помогите' },
            'en-fr': { 'hello': 'bonjour', 'thank you': 'merci', 'please': 's\'il vous plaît', 'yes': 'oui', 'no': 'non', 'help': 'aidez-moi' },
            'fr-en': { 'bonjour': 'hello', 'merci': 'thank you', 's\'il vous plaît': 'please', 'oui': 'yes', 'non': 'no', 'aidez-moi': 'help' },
            'en-de': { 'hello': 'hallo', 'thank you': 'danke', 'please': 'bitte', 'yes': 'ja', 'no': 'nein', 'help': 'hilfe' },
            'de-en': { 'hallo': 'hello', 'danke': 'thank you', 'bitte': 'please', 'ja': 'yes', 'nein': 'no', 'hilfe': 'help' },
            'en-pt': { 'hello': 'olá', 'thank you': 'obrigado', 'please': 'por favor', 'yes': 'sim', 'no': 'não', 'help': 'ajuda' },
            'pt-en': { 'olá': 'hello', 'obrigado': 'thank you', 'por favor': 'please', 'sim': 'yes', 'não': 'no', 'ajuda': 'help' }
        };

        // ИСПРАВЛЕНО: Инициализация современного переводчика
        function initializeTranslator() {
            const sourceLanguageSelect = document.getElementById('source-language');
            const targetLanguageSelect = document.getElementById('target-language');
            const swapButton = document.getElementById('swap-languages');
            const sourceText = document.getElementById('source-text');
            const translatedText = document.getElementById('translated-text');
            const translateButton = document.getElementById('translate-button');
            const clearButton = document.getElementById('clear-translator');
            const buttonText = translateButton ? translateButton.querySelector('.button-text') : null;
            const sourceCharCount = document.getElementById('source-char-count');
            const translatedCharCount = document.getElementById('translated-char-count');
            const errorMessage = document.getElementById('error-message');
            const detectedLanguageBadge = document.getElementById('detected-language');
            const autoDetectIndicator = document.getElementById('auto-detect-indicator');
            const speakSourceBtn = document.getElementById('speak-source');
            const speakTranslationBtn = document.getElementById('speak-translation');
            const copySourceBtn = document.getElementById('copy-source');
            const copyTranslationBtn = document.getElementById('copy-translation');

            if (!sourceText || !translatedText) return;

            // Обновление счетчика символов
            function updateCharCount() {
                const sourceLength = sourceText.value.length;
                const translatedLength = translatedText.value.length;
                
                if (sourceCharCount) {
                    sourceCharCount.textContent = `${sourceLength} ${langTranslations['characters'] || 'символов'}`;
                }
                if (translatedCharCount) {
                    translatedCharCount.textContent = `${translatedLength} ${langTranslations['characters'] || 'символов'}`;
                }
            }
            
            updateCharCount();
            sourceText.addEventListener('input', updateCharCount);
            sourceText.addEventListener('input', function() {
                if (translatedText) translatedText.value = '';
                updateCharCount();
            });

            // ИСПРАВЛЕНО: Быстрая детекция языка с поддержкой 5 языков
            async function detectLanguage(text) {
                if (!text.trim()) return 'en';
                
                if (autoDetectIndicator) autoDetectIndicator.style.display = 'flex';
                
                try {
                    const textLower = text.toLowerCase().trim();
                    
                    // Простая эвристика для определения языка с поддержкой 5 языков
                    if (textLower.match(/[а-яё]/i)) {
                        updateLanguageBadge('ru');
                        return 'ru';
                    } else if (textLower.match(/[a-z]/i)) {
                        updateLanguageBadge('en');
                        return 'en';
                    } else if (textLower.match(/[áéíóúâêîôûãõç]/i)) {
                        updateLanguageBadge('pt');
                        return 'pt';
                    } else if (textLower.match(/[àâäçéèêëîïôöùûüÿœ]/i)) {
                        updateLanguageBadge('fr');
                        return 'fr';
                    } else if (textLower.match(/[äöüß]/i)) {
                        updateLanguageBadge('de');
                        return 'de';
                    } else if (textLower.match(/[áéíóúñ]/i)) {
                        updateLanguageBadge('es');
                        return 'es';
                    }
                    
                    updateLanguageBadge('en');
                    return 'en';
                    
                } catch (error) {
                    console.error('Language detection error:', error);
                    updateLanguageBadge('en');
                    return 'en';
                } finally {
                    if (autoDetectIndicator) autoDetectIndicator.style.display = 'none';
                }
            }

            // Обновление бейджа языка
            function updateLanguageBadge(langCode) {
                if (!detectedLanguageBadge) return;
                
                const langNames = {
                    'ru': langTranslations['russian'] || 'Русский',
                    'en': langTranslations['english'] || 'English',
                    'es': langTranslations['spanish'] || 'Español',
                    'fr': langTranslations['french'] || 'Français',
                    'de': langTranslations['german'] || 'Deutsch',
                    'it': langTranslations['italian'] || 'Italiano',
                    'ja': langTranslations['japanese'] || '日本語',
                    'zh': langTranslations['chinese'] || '中文',
                    'ar': langTranslations['arabic'] || 'العربية',
                    'pl': langTranslations['polish'] || 'Polski',
                    'tr': langTranslations['turkish'] || 'Türkçe',
                    'pt': langTranslations['portuguese'] || 'Português'
                };
                
                detectedLanguageBadge.textContent = langNames[langCode] || langCode;
                detectedLanguageBadge.style.display = 'inline-block';
            }

            // Проверка кэша общих фраз
            function getCommonPhraseTranslation(text, sourceLang, targetLang) {
                const phraseKey = text.toLowerCase().trim();
                const cacheKey = `${sourceLang}-${targetLang}`;
                
                if (commonPhrasesCache[cacheKey] && commonPhrasesCache[cacheKey][phraseKey]) {
                    console.log('Using common phrase cache');
                    return commonPhrasesCache[cacheKey][phraseKey];
                }
                
                return null;
            }

            // Получение из локального кэша
            function getCachedTranslation(text, sourceLang, targetLang) {
                try {
                    const cacheKey = `${sourceLang}_${targetLang}_${text.trim().toLowerCase()}`;
                    const cache = JSON.parse(localStorage.getItem('translation_cache') || '{}');
                    
                    if (cache[cacheKey]) {
                        const cacheItem = cache[cacheKey];
                        const now = Date.now();
                        
                        if (now - cacheItem.timestamp < 24 * 60 * 60 * 1000) {
                            console.log('Using local cache');
                            return cacheItem.translation;
                        } else {
                            delete cache[cacheKey];
                            localStorage.setItem('translation_cache', JSON.stringify(cache));
                        }
                    }
                } catch (error) {
                    console.error('Cache error:', error);
                }
                
                return null;
            }

            // Сохранение в локальный кэш
            function saveToCache(text, translation, sourceLang, targetLang) {
                try {
                    const cacheKey = `${sourceLang}_${targetLang}_${text.trim().toLowerCase()}`;
                    const cache = JSON.parse(localStorage.getItem('translation_cache') || '{}');
                    
                    cache[cacheKey] = {
                        translation: translation,
                        timestamp: Date.now(),
                        sourceLang: sourceLang,
                        targetLang: targetLang
                    };
                    
                    const keys = Object.keys(cache);
                    if (keys.length > 100) {
                        const oldestKey = keys.reduce((oldest, current) => {
                            return cache[current].timestamp < cache[oldest].timestamp ? current : oldest;
                        });
                        delete cache[oldestKey];
                    }
                    
                    localStorage.setItem('translation_cache', JSON.stringify(cache));
                } catch (error) {
                    console.error('Error saving to cache:', error);
                }
            }

            // ИСПРАВЛЕНО: Основная функция перевода с корректным обновлением меток
            async function translateText(text, sourceLang, targetLang) {
                if (!text.trim()) {
                    if (translatedText) translatedText.value = '';
                    updateCharCount();
                    return '';
                }
                
                try {
                    if (translateButton) translateButton.disabled = true;
                    if (buttonText) buttonText.innerHTML = `<div class="loading"></div> ${langTranslations['translating'] || 'Перевод...'}`;
                    
                    let actualSourceLang = sourceLang;
                    if (sourceLang === 'auto') {
                        actualSourceLang = await detectLanguage(text);
                    }
                    
                    const commonTranslation = getCommonPhraseTranslation(text, actualSourceLang, targetLang);
                    if (commonTranslation) {
                        await saveTranslationToHistory(text, commonTranslation, actualSourceLang, targetLang);
                        saveToCache(text, commonTranslation, actualSourceLang, targetLang);
                        return commonTranslation;
                    }
                    
                    const cachedTranslation = getCachedTranslation(text, actualSourceLang, targetLang);
                    if (cachedTranslation) {
                        await saveTranslationToHistory(text, cachedTranslation, actualSourceLang, targetLang);
                        return cachedTranslation;
                    }
                    
                    let translated = '';
                    let lastError = '';
                    
                    for (const api of translationApis) {
                        try {
                            console.log(`Trying ${api.name}...`);
                            
                            if (currentController) {
                                currentController.abort();
                            }
                            currentController = new AbortController();
                            
                            const timeoutId = setTimeout(() => {
                                currentController.abort();
                            }, api.timeout || 5000);
                            
                            const response = await fetch(
                                typeof api.url === 'function' ? api.url(text, actualSourceLang, targetLang) : api.url,
                                {
                                    method: api.method || 'GET',
                                    headers: api.headers || {},
                                    body: typeof api.body === 'function' ? api.body(text, actualSourceLang, targetLang) : api.body,
                                    signal: currentController.signal
                                }
                            );
                            
                            clearTimeout(timeoutId);
                            
                            if (!response.ok) {
                                throw new Error(`${api.name} failed: ${response.status}`);
                            }
                            
                            translated = await api.parse(response);
                            
                            if (translated && translated.trim()) {
                                console.log(`Successfully translated with ${api.name}`);
                                await saveTranslationToHistory(text, translated, actualSourceLang, targetLang);
                                saveToCache(text, translated, actualSourceLang, targetLang);
                                return translated;
                            }
                        } catch (apiError) {
                            if (apiError.name !== 'AbortError') {
                                console.warn(`${api.name} error:`, apiError.message);
                                lastError = apiError.message;
                            }
                            continue;
                        }
                    }
                    
                    console.log('Falling back to MyMemory API...');
                    try {
                        const response = await fetch(
                            `https://api.mymemory.translated.net/get?q=${encodeURIComponent(text)}&langpair=${actualSourceLang}|${targetLang}`,
                            {
                                signal: AbortSignal.timeout(3000)
                            }
                        );
                        
                        if (response.ok) {
                            const data = await response.json();
                            if (data.responseData && data.responseData.translatedText) {
                                translated = data.responseData.translatedText;
                                await saveTranslationToHistory(text, translated, actualSourceLang, targetLang);
                                saveToCache(text, translated, actualSourceLang, targetLang);
                                return translated;
                            }
                        }
                    } catch (memoryError) {
                        console.error('MyMemory error:', memoryError);
                    }
                    
                    return performDemoTranslation(text, actualSourceLang, targetLang);
                    
                } catch (error) {
                    console.error('All translation methods failed:', error);
                    throw new Error(langTranslations['all_translation_services_unavailable'] || 'Все сервисы перевода недоступны');
                } finally {
                    if (translateButton) translateButton.disabled = false;
                    if (buttonText) buttonText.textContent = langTranslations['translate'] || 'Перевести';
                    currentController = null;
                }
            }

            // Функция для демо-перевода
            function performDemoTranslation(text, sourceLang, targetLang) {
                const demoTranslations = {
                    'ru-en': { 'здравствуйте': 'hello', 'помощь': 'help', 'документы': 'documents', 'миграция': 'migration', 'врач': 'doctor', 'спасибо': 'thank you', 'где': 'where', 'можно': 'can', 'оформить': 'process' },
                    'en-ru': { 'hello': 'здравствуйте', 'help': 'помощь', 'documents': 'документы', 'migration': 'миграция', 'doctor': 'врач', 'thank you': 'спасибо', 'where': 'где', 'can': 'можно', 'process': 'оформить' },
                    'ru-fr': { 'здравствуйте': 'bonjour', 'спасибо': 'merci', 'помощь': 'aide', 'документы': 'documents' },
                    'fr-ru': { 'bonjour': 'здравствуйте', 'merci': 'спасибо', 'aide': 'помощь', 'documents': 'документы' },
                    'ru-de': { 'здравствуйте': 'hallo', 'спасибо': 'danke', 'помощь': 'hilfe', 'документы': 'dokumente' },
                    'de-ru': { 'hallo': 'здравствуйте', 'danke': 'спасибо', 'hilfe': 'помощь', 'dokumente': 'документы' },
                    'ru-pt': { 'здравствуйте': 'olá', 'спасибо': 'obrigado', 'помощь': 'ajuda', 'документы': 'documentos' },
                    'pt-ru': { 'olá': 'здравствуйте', 'obrigado': 'спасибо', 'ajuda': 'помощь', 'documentos': 'documentos' }
                };
                
                const translationKey = `${sourceLang}-${targetLang}`;
                const translationMap = demoTranslations[translationKey];
                
                if (!translationMap) {
                    return `[${langTranslations['demo_translation'] || 'ДЕМО-ПЕРЕВОД'}] ${text}`;
                }
                
                let translated = text;
                
                Object.entries(translationMap).forEach(([key, value]) => {
                    const regex = new RegExp(`\\b${key}\\b`, 'gi');
                    translated = translated.replace(regex, value);
                });
                
                return `[${langTranslations['demo_translation'] || 'ДЕМО-ПЕРЕВОД'}] ${translated}`;
            }

            // Обработчик кнопки перевода
            if (translateButton) {
                translateButton.addEventListener('click', async function() {
                    const textToTranslate = sourceText.value.trim();
                    
                    if (!textToTranslate) {
                        showTranslatorError(langTranslations['enter_text_to_translate'] || 'Пожалуйста, введите текст для перевода');
                        return;
                    }
                    
                    if (textToTranslate.length > 5000) {
                        showTranslatorError(langTranslations['text_too_long'] || 'Текст слишком длинный');
                        return;
                    }
                    
                    hideTranslatorError();
                    
                    try {
                        const sourceLang = sourceLanguageSelect.value;
                        const targetLang = targetLanguageSelect.value;
                        
                        const translated = await translateText(textToTranslate, sourceLang, targetLang);
                        if (translatedText) {
                            translatedText.value = translated;
                        }
                        
                        updateCharCount();
                        
                    } catch (error) {
                        console.error('Translation failed:', error);
                        showTranslatorError(error.message || langTranslations['all_translation_services_unavailable'] || 'Сервисы перевода недоступны');
                    }
                });
            }
            
            // Обработчик кнопки очистки
            if (clearButton) {
                clearButton.addEventListener('click', function() {
                    sourceText.value = '';
                    if (translatedText) translatedText.value = '';
                    if (detectedLanguageBadge) detectedLanguageBadge.style.display = 'none';
                    updateCharCount();
                    hideTranslatorError();
                    sourceText.focus();
                });
            }
            
            // Обработчик кнопки смены языков
            if (swapButton) {
                swapButton.addEventListener('click', async function() {
                    const sourceLang = sourceLanguageSelect.value;
                    const targetLang = targetLanguageSelect.value;
                    
                    if (targetLang === 'auto') {
                        targetLanguageSelect.value = sourceLang === 'auto' ? 'ru' : sourceLang;
                        sourceLanguageSelect.value = 'auto';
                    } else {
                        sourceLanguageSelect.value = targetLang;
                        targetLanguageSelect.value = sourceLang === 'auto' ? 'ru' : sourceLang;
                    }
                    
                    if (sourceText.value && translatedText.value) {
                        const tempText = sourceText.value;
                        sourceText.value = translatedText.value;
                        translatedText.value = tempText;
                        
                        updateCharCount();
                        
                        if (sourceLanguageSelect.value === 'auto' && sourceText.value.trim()) {
                            const detectedLang = await detectLanguage(sourceText.value);
                            updateLanguageBadge(detectedLang);
                        }
                    }
                });
            }
            
            // Автоматический перевод при вводе
            let translationTimeout;
            sourceText.addEventListener('input', function() {
                clearTimeout(translationTimeout);
                const text = sourceText.value.trim();
                
                if (text && text.length > 2 && text.length <= 200) {
                    translationTimeout = setTimeout(async () => {
                        if (text === sourceText.value.trim()) {
                            if (sourceLanguageSelect.value === 'auto' && text.length > 10) {
                                await detectLanguage(text);
                            }
                            if (text.length <= 100 && translateButton) {
                                translateButton.click();
                            }
                        }
                    }, 1000);
                }
            });
            
            // Озвучивание текста
            if (speakSourceBtn) {
                speakSourceBtn.addEventListener('click', function() {
                    speakText(sourceText.value, sourceLanguageSelect.value === 'auto' ? 'ru' : sourceLanguageSelect.value);
                    this.classList.add('active');
                    setTimeout(() => this.classList.remove('active'), 1000);
                });
            }
            
            if (speakTranslationBtn) {
                speakTranslationBtn.addEventListener('click', function() {
                    speakText(translatedText.value, targetLanguageSelect.value);
                    this.classList.add('active');
                    setTimeout(() => this.classList.remove('active'), 1000);
                });
            }
            
            // Копирование текста
            if (copySourceBtn) {
                copySourceBtn.addEventListener('click', function() {
                    copyToClipboard(sourceText.value, langTranslations['text_copied'] || 'Текст скопирован в буфер обмена');
                    this.classList.add('active');
                    setTimeout(() => this.classList.remove('active'), 1000);
                });
            }
            
            if (copyTranslationBtn) {
                copyTranslationBtn.addEventListener('click', function() {
                    copyToClipboard(translatedText.value, langTranslations['translation_copied'] || 'Перевод скопирован в буфер обмена');
                    this.classList.add('active');
                    setTimeout(() => this.classList.remove('active'), 1000);
                });
            }
            
            function showTranslatorError(message) {
                if (errorMessage) {
                    errorMessage.textContent = message;
                    errorMessage.style.display = 'block';
                    setTimeout(hideTranslatorError, 5000);
                }
            }
            
            function hideTranslatorError() {
                if (errorMessage) {
                    errorMessage.style.display = 'none';
                }
            }
            
            sourceText.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter' && translateButton) {
                    e.preventDefault();
                    translateButton.click();
                }
            });
        }

        // Сохранение перевода в историю
        async function saveTranslationToHistory(sourceText, translatedText, sourceLang, targetLang) {
            <?php if ($isLoggedIn): ?>
            try {
                const formData = new FormData();
                formData.append('source_text', sourceText);
                formData.append('translated_text', translatedText);
                formData.append('source_lang', sourceLang);
                formData.append('target_lang', targetLang);
                formData.append('action', 'save_translation');
                
                const response = await fetch('translator_ajax.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    addHistoryItem(result.translation);
                }
            } catch (error) {
                console.error('Error saving translation history:', error);
            }
            <?php endif; ?>
        }

        // ИСПРАВЛЕНО: Добавление элемента истории на страницу
        function addHistoryItem(item) {
            const historyContainer = document.getElementById('history-container');
            const clearHistoryBtn = document.querySelector('#history-section .btn-danger-outline');
            
            if (!historyContainer) return;
            
            const noHistoryMessage = historyContainer.querySelector('.notification.info');
            if (noHistoryMessage) {
                noHistoryMessage.remove();
            }
            
            if (clearHistoryBtn && clearHistoryBtn.style.display === 'none') {
                clearHistoryBtn.style.display = 'inline-flex';
            }
            
            const historyItem = document.createElement('div');
            historyItem.className = 'history-item';
            historyItem.dataset.id = item.id;
            
            const date = new Date(item.created_at);
            const formattedDate = date.toLocaleDateString(undefined, { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            historyItem.innerHTML = `
                <div class="history-text">
                    <strong>${item.source_lang.toUpperCase()} → ${item.target_lang.toUpperCase()}</strong><br>
                    ${escapeHtml(item.source_text)}
                </div>
                <div class="history-translation">
                    ${escapeHtml(item.translated_text)}
                </div>
                <div class="history-meta">
                    <span>${formattedDate}</span>
                    <div class="history-actions">
                        <button class="history-action-btn" onclick="useHistoryItem(${item.id})" title="${langTranslations['use_translation'] || 'Использовать этот перевод'}">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="history-action-btn" onclick="deleteHistoryItem(${item.id})" title="${langTranslations['delete_from_history'] || 'Удалить из истории'}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            historyContainer.prepend(historyItem);
            
            // Обновляем метки интерфейса при добавлении нового элемента
            updateUILabels();
        }

        // Использование элемента истории
        async function useHistoryItem(itemId) {
            <?php if ($isLoggedIn): ?>
            try {
                const formData = new FormData();
                formData.append('id', itemId);
                formData.append('action', 'get_translation');
                
                const response = await fetch('translator_ajax.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const item = result.translation;
                    
                    const sourceText = document.getElementById('source-text');
                    const sourceLanguageSelect = document.getElementById('source-language');
                    const targetLanguageSelect = document.getElementById('target-language');
                    const translatedText = document.getElementById('translated-text');
                    const sourceCharCount = document.getElementById('source-char-count');
                    const translatedCharCount = document.getElementById('translated-char-count');
                    
                    if (sourceText) sourceText.value = item.source_text;
                    if (sourceLanguageSelect) sourceLanguageSelect.value = item.source_lang;
                    if (targetLanguageSelect) targetLanguageSelect.value = item.target_lang;
                    if (translatedText) translatedText.value = item.translated_text;
                    
                    if (sourceCharCount) {
                        sourceCharCount.textContent = `${item.source_text.length} ${langTranslations['characters'] || 'символов'}`;
                    }
                    if (translatedCharCount) {
                        translatedCharCount.textContent = `${item.translated_text.length} ${langTranslations['characters'] || 'символов'}`;
                    }
                    
                    showNotification(langTranslations['translation_loaded'] || 'Перевод загружен из истории', 'success');
                }
            } catch (error) {
                console.error('Error loading translation:', error);
                showNotification(langTranslations['error'] || 'Ошибка', 'error');
            }
            <?php endif; ?>
        }

        // Удаление элемента истории
        async function deleteHistoryItem(itemId) {
            <?php if ($isLoggedIn): ?>
            try {
                const formData = new FormData();
                formData.append('id', itemId);
                formData.append('action', 'delete_translation');
                
                const response = await fetch('translator_ajax.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const historyItem = document.querySelector(`.history-item[data-id="${itemId}"]`);
                    if (historyItem) {
                        historyItem.remove();
                    }
                    
                    const historyContainer = document.getElementById('history-container');
                    const clearHistoryBtn = document.querySelector('#history-section .btn-danger-outline');
                    
                    if (historyContainer && historyContainer.children.length === 0) {
                        historyContainer.innerHTML = `
                            <div class="notification info">
                                <i class="fas fa-info-circle"></i> ${langTranslations['no_history'] || 'История переводов пуста'}
                            </div>
                        `;
                        
                        if (clearHistoryBtn) {
                            clearHistoryBtn.style.display = 'none';
                        }
                    }
                    
                    showNotification(langTranslations['translation_deleted'] || 'Перевод удален из истории', 'success');
                }
            } catch (error) {
                console.error('Error deleting translation:', error);
                showNotification(langTranslations['error'] || 'Ошибка', 'error');
            }
            <?php endif; ?>
        }

        // Очистка истории
        async function clearHistory() {
            <?php if ($isLoggedIn): ?>
            if (!confirm(langTranslations['confirm_clear_history'] || 'Вы уверены, что хотите очистить всю историю переводов?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'clear_history');
                
                const response = await fetch('translator_ajax.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const historyContainer = document.getElementById('history-container');
                    const clearHistoryBtn = document.querySelector('#history-section .btn-danger-outline');
                    
                    if (historyContainer) {
                        historyContainer.innerHTML = `
                            <div class="notification info">
                                <i class="fas fa-info-circle"></i> ${langTranslations['no_history'] || 'История переводов пуста'}
                            </div>
                        `;
                    }
                    
                    if (clearHistoryBtn) {
                        clearHistoryBtn.style.display = 'none';
                    }
                    
                    showNotification(langTranslations['history_cleared'] || 'История переводов очищена', 'success');
                }
            } catch (error) {
                console.error('Error clearing history:', error);
                showNotification(langTranslations['error'] || 'Ошибка', 'error');
            }
            <?php else: ?>
            showNotification(langTranslations['login_to_history'] || 'Для просмотра истории переводов необходимо войти в систему', 'info');
            <?php endif; ?>
        }

        // Озвучивание текста с поддержкой всех языков
        function speakText(text, lang) {
            if (!text.trim() || !speechSynthesis) return;
            
            stopSpeech();
            
            const utterance = new SpeechSynthesisUtterance(text);
            
            const langMap = {
                'ru': 'ru-RU',
                'en': 'en-US',
                'es': 'es-ES',
                'fr': 'fr-FR',
                'de': 'de-DE',
                'it': 'it-IT',
                'ja': 'ja-JP',
                'zh': 'zh-CN',
                'ar': 'ar-SA',
                'pl': 'pl-PL',
                'tr': 'tr-TR',
                'pt': 'pt-PT'
            };
            
            utterance.lang = langMap[lang] || 'en-US';
            utterance.rate = 0.9;
            utterance.pitch = 1;
            utterance.volume = 1;
            
            currentSpeech = utterance;
            
            utterance.onend = function() {
                currentSpeech = null;
            };
            
            speechSynthesis.speak(utterance);
        }

        // Остановка озвучивания
        function stopSpeech() {
            if (speechSynthesis && speechSynthesis.speaking) {
                speechSynthesis.cancel();
                currentSpeech = null;
            }
        }

        // Копирование в буфер обмена
        function copyToClipboard(text, successMessage) {
            if (!text.trim()) return;
            
            navigator.clipboard.writeText(text).then(() => {
                showNotification(successMessage, 'success');
            }).catch(err => {
                console.error('Copy failed:', err);
                showNotification(langTranslations['failed_to_copy'] || 'Не удалось скопировать текст', 'error');
            });
        }

        // Показать уведомление
        function showNotification(message, type) {
            const main = document.querySelector('main .container');
            if (!main) return;
            
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            let icon = 'exclamation-circle';
            if (type === 'success') icon = 'check-circle';
            if (type === 'info') icon = 'info-circle';
            if (type === 'warning') icon = 'exclamation-triangle';
            
            notification.innerHTML = `
                <i class="fas fa-${icon}"></i>
                ${message}
            `;
            
            main.prepend(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Экранирование HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Глобальные функции для HTML-кнопок
        window.useHistoryItem = useHistoryItem;
        window.deleteHistoryItem = deleteHistoryItem;
        window.clearHistory = clearHistory;
    </script>
</body>
</html>