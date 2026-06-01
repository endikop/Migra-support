<?php
// Отключаем вывод ошибок для чистого JSON ответа
error_reporting(0);
ini_set('display_errors', 0);

// Включаем буферизацию вывода для чистого JSON ответа
ob_start();


require_once 'config.php';

// Подключаем систему цензуры
if (file_exists('censorship_simple.php')) {
    require_once 'censorship_simple.php';
}

// ============================================
// ПЕРВОЕ: ОБРАБОТКА AJAX ЗАПРОСОВ
// ============================================

// Функция для обработки AJAX запросов
function handleAjaxRequest() {
    global $pdo;
    
    // Очищаем буфер вывода перед установкой заголовков
    ob_clean();
    
    // Устанавливаем заголовок JSON
    header('Content-Type: application/json');
    
    // Получаем город пользователя для AJAX запросов
    $userCity = 'minsk';
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT city FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userData = $stmt->fetch();
            $userCity = $userData['city'] ?? 'minsk';
        } catch (PDOException $e) {
            $userCity = 'minsk';
        }
    }
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'send_message':
            handleSendMessage($userCity);
            break;
            
        case 'get_messages':
            handleGetMessages($userCity);
            break;
            
        case 'get_online_users':
            handleGetOnlineUsers($userCity);
            break;
        
        case 'test_censorship':
            handleTestCensorship();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
    }
}

function handleSendMessage($city) {
    global $pdo;
    
    // Проверяем авторизацию
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Введите сообщение']);
        return;
    }
    
    if (strlen($message) > 1000) {
        echo json_encode(['success' => false, 'error' => 'Сообщение слишком длинное (максимум 1000 символов)']);
        return;
    }
    
    try {
        // Применяем цензуру к сообщению
        $censoredMessage = $message;
        $wasCensored = false;
        
        if (function_exists('containsBannedWords') && containsBannedWords($message)) {
            $wasCensored = true;
            // Цензурируем сообщение
            if (function_exists('censorText')) {
                $censoredMessage = censorText($message);
            }
            
            // Логируем цензуру
            if (function_exists('logCensorship')) {
                @logCensorship($userId, $message, $censoredMessage);
            }
        }
        
        // Экранируем HTML-спецсимволы для безопасного хранения
        $censoredMessage = htmlspecialchars($censoredMessage, ENT_QUOTES, 'UTF-8');
        
        // Вставляем сообщение в чат
        $stmt = $pdo->prepare("INSERT INTO city_chat_messages (sender_id, city, message_text) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $city, $censoredMessage]);
        
        // Обновляем время активности пользователя
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId(), 'censored' => $wasCensored]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()]);
    }
}

function handleGetMessages($city) {
    global $pdo;
    
    $lastMessageId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    try {
        $stmt = $pdo->prepare("
            SELECT cm.*, u.first_name, u.last_name, u.avatar 
            FROM city_chat_messages cm 
            JOIN users u ON cm.sender_id = u.id 
            WHERE cm.city = ? AND cm.id > ? 
            ORDER BY cm.created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$city, $lastMessageId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Форматируем сообщения
        foreach ($messages as &$message) {
            $timestamp = strtotime($message['created_at']);
            $message['created_at_time'] = date('H:i', $timestamp);
            $message['created_at_date'] = date('Y-m-d', $timestamp);
            $message['created_at_timestamp'] = $timestamp;
            $message['sender_name'] = htmlspecialchars($message['first_name'] . ' ' . $message['last_name'], ENT_QUOTES, 'UTF-8');
            // Сообщение уже экранировано и отцензурировано при сохранении
            $message['message_text'] = htmlspecialchars($message['message_text'], ENT_QUOTES, 'UTF-8');
            if ($message['avatar']) {
                $message['avatar'] = htmlspecialchars($message['avatar'], ENT_QUOTES, 'UTF-8');
            }
        }
        
        echo json_encode(['success' => true, 'messages' => array_reverse($messages)], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleGetOnlineUsers($city) {
    global $pdo;
    
    try {
        // Обновляем время активности текущего пользователя
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
        
        // Получаем количество онлайн пользователей
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE city = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute([$city]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'count' => (int)$result['count']]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleTestCensorship() {
    global $pdo;
    
    $text = $_POST['text'] ?? '';
    
    if (empty($text)) {
        echo json_encode(['success' => false, 'error' => 'Введите текст для тестирования']);
        return;
    }
    
    $containsBanned = function_exists('containsBannedWords') ? containsBannedWords($text) : false;
    $censoredText = function_exists('censorText') ? censorText($text) : $text;
    
    echo json_encode([
        'success' => true,
        'original' => $text,
        'censored' => $censoredText,
        'contains_banned' => $containsBanned
    ], JSON_UNESCAPED_UNICODE);
}

// Если это AJAX запрос, обрабатываем его и завершаем выполнение
if (isset($_GET['action']) || isset($_POST['action'])) {
    handleAjaxRequest();
    ob_end_flush();
    exit;
}

// ============================================
// ВТОРОЕ: ПОДГОТОВКА ДАННЫХ ДЛЯ HTML
// ============================================

// Подключаем файл с данными аватара
if (file_exists('include_avatar.php')) {
    require_once 'include_avatar.php';
}

// Проверяем авторизацию
$isLoggedIn = isset($_SESSION['user_id']);

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

// Функция перевода
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

// Получаем данные пользователя
$userName = '';
$userAvatar = '';
$userCity = 'minsk';

if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name, city, avatar FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch();
        if ($userData) {
            $userName = htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name'], ENT_QUOTES, 'UTF-8');
            $userAvatar = htmlspecialchars($userData['avatar'] ?? '', ENT_QUOTES, 'UTF-8');
            $userCity = $userData['city'] ?? 'minsk';
            $_SESSION['city'] = $userCity;
        }
    } catch (PDOException $e) {
        error_log("Ошибка получения данных пользователя: " . $e->getMessage());
        $userCity = 'minsk';
    }
}

// Тексты для перевода
$translations = [
    'main_title' => t(
        'Чат города - MigraSupport',
        'City Chat - MigraSupport',
        'Chat da Cidade - MigraSupport',
        'Chat de la Ville - MigraSupport',
        'Stadt-Chat - MigraSupport'
    ),
    'home' => t(
        'Главная',
        'Home',
        'Início',
        'Accueil',
        'Startseite'
    ),
    'information' => t(
        'Информация',
        'Information',
        'Informação',
        'Information',
        'Informationen'
    ),
    'map_services' => t(
        'Карта служб',
        'Services Map',
        'Mapa de Serviços',
        'Carte des Services',
        'Dienstleistungskarte'
    ),
    'translator' => t(
        'Переводчик',
        'Translator',
        'Tradutor',
        'Traducteur',
        'Übersetzer'
    ),
    'currency_converter' => t(
        'Конвертер валют',
        'Currency Converter',
        'Conversor de Moeda',
        'Convertisseur de Devises',
        'Währungsrechner'
    ),
    'city_chat' => t(
        'Чат города',
        'City Chat',
        'Chat da Cidade',
        'Chat de la Ville',
        'Stadt-Chat'
    ),
    'profile' => t(
        'Профиль',
        'Profile',
        'Perfil',
        'Profil',
        'Profil'
    ),
    'login_nav' => t(
        'Вход',
        'Login',
        'Entrar',
        'Connexion',
        'Anmelden'
    ),
    'register_nav' => t(
        'Регистрация',
        'Register',
        'Registrar',
        'Inscription',
        'Registrieren'
    ),
    'admin_panel' => t(
        'Админ',
        'Admin',
        'Admin',
        'Admin',
        'Admin'
    ),
    'logout' => t(
        'Выйти',
        'Logout',
        'Sair',
        'Déconnexion',
        'Abmelden'
    ),
    'city_chat_title' => t(
        'Чаты',
        'Chats',
        'Chats',
        'Chats',
        'Chats'
    ),
    'city_chat_desc' => t(
        'Общайтесь с другими мигрантами в вашем городе. Обменивайтесь опытом, задавайте вопросы и находите новых друзей.',
        'Chat with other migrants in your city. Share experiences, ask questions and make new friends.',
        'Converse com outros migrantes na sua cidade. Compartilhe experiências, faça perguntas e faça novos amigos.',
        'Discutez avec d\'autres migrants dans votre ville. Partagez des expériences, posez des questions et faites de nouveaux amis.',
        'Chatten Sie mit anderen Migranten in Ihrer Stadt. Teilen Sie Erfahrungen, stellen Sie Fragen und finden Sie neue Freunde.'
    ),
    'city_chat_of' => t(
        'Чат города',
        'City Chat of',
        'Chat da Cidade de',
        'Chat de la Ville de',
        'Stadt-Chat von'
    ),
    'chat_rules' => t(
        'Правила чата',
        'Chat Rules',
        'Regras do Chat',
        'Règles du Chat',
        'Chat-Regeln'
    ),
    'rules_list' => [
        t('Уважайте других участников', 'Respect other participants', 'Respeite outros participantes', 'Respectez les autres participants', 'Respektieren Sie andere Teilnehmer'),
        t('Не используйте нецензурную лексику', 'Do not use profanity', 'Não use linguagem obscena', 'N\'utilisez pas de grossièretés', 'Verwenden Sie keine Obszönitäten'),
        t('Не распространяйте спам', 'Do not spread spam', 'Não espalhe spam', 'Ne diffusez pas de spam', 'Verbreiten Sie keinen Spam'),
        t('Запрещены оскорбления и дискриминация', 'Insults and discrimination are prohibited', 'Insultos e discriminação são proibidos', 'Les insultes et la discrimination sont interdites', 'Beleidigungen und Diskriminierung sind verboten'),
        t('Соблюдайте тематику чата', 'Follow the chat topic', 'Siga o tópico do chat', 'Suivez le sujet du chat', 'Bleiben Sie beim Chat-Thema')
    ],
    'type_message' => t(
        'Введите ваше сообщение...',
        'Type your message...',
        'Digite sua mensagem...',
        'Tapez votre message...',
        'Geben Sie Ihre Nachricht ein...'
    ),
    'send' => t(
        'Отправить',
        'Send',
        'Enviar',
        'Envoyer',
        'Senden'
    ),
    'loading_messages' => t(
        'Загрузка сообщений...',
        'Loading messages...',
        'Carregando mensagens...',
        'Chargement des messages...',
        'Nachrichten werden geladen...'
    ),
    'no_messages' => t(
        'Пока нет сообщений. Будьте первым!',
        'No messages yet. Be the first!',
        'Ainda não há mensagens. Seja o primeiro!',
        'Pas encore de messages. Soyez le premier!',
        'Noch keine Nachrichten. Seien Sie der Erste!'
    ),
    'join_chat' => t(
        'Присоединиться к чату',
        'Join Chat',
        'Entrar no Chat',
        'Rejoindre le Chat',
        'Chat beitreten'
    ),
    'login_to_chat' => t(
        'Войдите в систему, чтобы присоединиться к чату',
        'Login to join the chat',
        'Faça login para entrar no chat',
        'Connectez-vous pour rejoindre le chat',
        'Melden Sie sich an, um dem Chat beizutreten'
    ),
    'chat_help_title' => t(
        'Полезные советы',
        'Useful Tips',
        'Dicas Úteis',
        'Conseils Utiles',
        'Nützliche Tipps'
    ),
    'chat_tip1' => t(
        'Чат города - это отличный способ найти новых друзей и получить практические советы от тех, кто уже прошел через процесс адаптации.',
        'City chat is a great way to make new friends and get practical advice from those who have already gone through the adaptation process.',
        'O chat da cidade é uma ótima maneira de fazer novos amigos e obter conselhos práticos de quem já passou pelo processo de adaptação.',
        'Le chat de la ville est un excellent moyen de se faire de nouveaux amis et d\'obtenir des conseils pratiques de ceux qui ont déjà vécu le processus d\'adaptation.',
        'Der Stadt-Chat ist eine großartige Möglichkeit, neue Freunde zu finden und praktische Ratschläge von denen zu erhalten, die den Anpassungsprozess bereits durchlaufen haben.'
    ),
    'chat_tip2' => t(
        'Не стесняйтесь задавать вопросы о жизни в Беларуси, документах, работе и других важных темах.',
        'Do not hesitate to ask questions about life in Belarus, documents, work and other important topics.',
        'Não hesite em fazer perguntas sobre a vida na Bielorrússia, documentos, trabalho e outros tópicos importantes.',
        'N\'hésitez pas à poser des questions sur la vie en Biélorussie, les documents, le travail et d\'autres sujets importants.',
        'Zögern Sie nicht, Fragen zum Leben in Belarus, Dokumenten, Arbeit und anderen wichtigen Themen zu stellen.'
    ),
    'chat_tip3' => t(
        'Делитесь своим опытом - ваши знания могут быть очень полезны для новых мигрантов.',
        'Share your experience - your knowledge can be very useful for new migrants.',
        'Compartilhe sua experiência - seu conhecimento pode ser muito útil para novos migrantes.',
        'Partagez votre expérience - vos connaissances peuvent être très utiles pour les nouveaux migrants.',
        'Teilen Sie Ihre Erfahrungen - Ihr Wissen kann für neue Migranten sehr nützlich sein.'
    ),
    'message_too_long' => t(
        'Сообщение слишком длинное (максимум 1000 символов)',
        'Message too long (maximum 1000 characters)',
        'Mensagem muito longa (máximo 1000 caracteres)',
        'Message trop long (maximum 1000 caractères)',
        'Nachricht zu lang (maximal 1000 Zeichen)'
    ),
    'enter_message' => t(
        'Пожалуйста, введите сообщение',
        'Please enter a message',
        'Por favor, digite uma mensagem',
        'Veuillez entrer un message',
        'Bitte geben Sie eine Nachricht ein'
    ),
    'error_sending' => t(
        'Ошибка отправки',
        'Error sending',
        'Erro ao enviar',
        'Erreur d\'envoi',
        'Fehler beim Senden'
    ),
    'error' => t(
        'Ошибка',
        'Error',
        'Erro',
        'Erreur',
        'Fehler'
    ),
    'sending' => t(
        'Отправка...',
        'Sending...',
        'Enviando...',
        'Envoi en cours...',
        'Senden...'
    ),
    'quick_links' => t(
        'Быстрые ссылки',
        'Quick Links',
        'Links Rápidos',
        'Liens Rapides',
        'Schnelllinks'
    ),
    'contacts' => t(
        'Контакты',
        'Contacts',
        'Contatos',
        'Contacts',
        'Kontakte'
    ),
    'online_users' => t(
        'Пользователей онлайн',
        'Users online',
        'Usuários online',
        'Utilisateurs en ligne',
        'Benutzer online'
    ),
    'go_to_profile' => t(
        'Перейти в профиль',
        'Go to Profile',
        'Ir para o Perfil',
        'Aller au Profil',
        'Zum Profil gehen'
    ),
    'footer_title' => t(
        'Комплексная система поддержки мигрантов в Беларуси.',
        'Comprehensive migrant support system in Belarus.',
        'Sistema abrangente de apoio a migrantes na Bielorrússia.',
        'Système complet de soutien aux migrants en Biélorussie.',
        'Umfassendes Migrantenunterstützungssystem in Belarus.'
    ),
    'all_rights_reserved' => t(
        'Все права защищены.',
        'All rights reserved.',
        'Todos os direitos reservados.',
        'Tous droits réservés.',
        'Alle Rechte vorbehalten.'
    ),
    'minsk_belarus' => t(
        'Минск, Беларусь',
        'Minsk, Belarus',
        'Minsk, Bielorrússia',
        'Minsk, Biélorussie',
        'Minsk, Belarus'
    ),
    'personal_chats' => t(
        'Личные чаты',
        'Personal Chats',
        'Chats Pessoais',
        'Chats Personnels',
        'Persönliche Chats'
    ),
    'today' => t(
        'Сегодня',
        'Today',
        'Hoje',
        'Aujourd\'hui',
        'Heute'
    ),
    'yesterday' => t(
        'Вчера',
        'Yesterday',
        'Ontem',
        'Hier',
        'Gestern'
    ),
    'message_censored' => t(
        '⚠️ Ваше сообщение содержало запрещенные слова и было отцензурировано',
        '⚠️ Your message contained banned words and has been censored',
        '⚠️ Sua mensagem continha palavras proibidas e foi censurada',
        '⚠️ Votre message contenait des mots interdits et a été censuré',
        '⚠️ Ihre Nachricht enthielt verbotene Wörter und wurde zensiert'
    )
];

// Названия городов для отображения
$cityNames = [
    'minsk' => t('Минск', 'Minsk', 'Minsk', 'Minsk', 'Minsk'),
    'grodno' => t('Гродно', 'Grodno', 'Grodno', 'Grodno', 'Grodno'),
    'brest' => t('Брест', 'Brest', 'Brest', 'Brest', 'Brest'),
    'vitebsk' => t('Витебск', 'Vitebsk', 'Vitebsk', 'Vitebsk', 'Vitebsk'),
    'gomel' => t('Гомель', 'Gomel', 'Gomel', 'Gomel', 'Gomel'),
    'mogilev' => t('Могилёв', 'Mogilev', 'Mogilev', 'Mogilev', 'Mogilev')
];

// Массивы названий месяцев для разных языков
$monthNames = [
    'ru' => [
        '01' => 'Января', '02' => 'Февраля', '03' => 'Марта', '04' => 'Апреля',
        '05' => 'Мая', '06' => 'Июня', '07' => 'Июля', '08' => 'Августа',
        '09' => 'Сентября', '10' => 'Октября', '11' => 'Ноября', '12' => 'Декабря'
    ],
    'en' => [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
    ],
    'pt' => [
        '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
        '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
        '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
    ],
    'fr' => [
        '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
        '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
        '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
    ],
    'de' => [
        '01' => 'Januar', '02' => 'Februar', '03' => 'März', '04' => 'April',
        '05' => 'Mai', '06' => 'Juni', '07' => 'Juli', '08' => 'August',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Dezember'
    ]
];

// Проверяем и добавляем поле last_activity если его нет
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
    $column = $stmt->fetch();
    
    if (!$column) {
        $pdo->query("ALTER TABLE users ADD COLUMN last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        error_log("Добавлено поле last_activity в таблицу users");
    }
} catch (PDOException $e) {
    error_log("Ошибка проверки поля last_activity: " . $e->getMessage());
}

// Функция для безопасного JSON
function safeJsonEncode($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translations['main_title'], ENT_QUOTES, 'UTF-8'); ?></title>
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
            letter-spacing: -0.3px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Language Selector */
        .language-selector {
            display: flex;
            gap: 5px;
            flex-wrap: nowrap;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
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

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

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

        .header-nav {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            white-space: nowrap;
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
        }

        .nav-tab i {
            font-size: 1.1rem;
        }

        .nav-tab.active i {
            color: var(--accent);
        }

        main {
            padding: 40px 0;
            margin-top: 20px;
        }

        /* Hero Section */
        .hero-section {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 60px 40px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            background-image: url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-blend-mode: overlay;
            background-color: rgba(26, 26, 46, 0.8);
        }

        .hero-title {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: white;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Cards */
        .card {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .card-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: white;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            color: var(--accent);
            font-size: 1.4rem;
        }

        /* Chat Container */
        .chat-container {
            background: rgba(26, 26, 46, 0.7);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            height: 500px;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-header {
            background: var(--gradient-secondary);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }

        .online-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: rgba(0, 0, 0, 0.2);
        }

        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .message {
            max-width: 75%;
            padding: 14px 18px;
            border-radius: 18px;
            position: relative;
            animation: fadeInUp 0.3s ease;
            word-wrap: break-word;
        }

        .message.own {
            align-self: flex-end;
            background: var(--gradient-primary);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message.other {
            align-self: flex-start;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-bottom-left-radius: 5px;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .message-sender {
            font-weight: 600;
            cursor: pointer;
        }

        .message-sender:hover {
            text-decoration: underline;
        }

        .message-time {
            font-size: 0.7rem;
        }

        .date-divider {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }

        .date-divider::before,
        .date-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }

        .date-divider span {
            padding: 5px 15px;
            background: rgba(58, 134, 255, 0.2);
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--primary-light);
            border: 1px solid rgba(58, 134, 255, 0.3);
        }

        .chat-input-area {
            padding: 20px;
            background: rgba(26, 26, 46, 0.9);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .chat-input {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            resize: none;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 0.95rem;
            min-height: 55px;
            max-height: 110px;
        }

        .chat-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .chat-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        /* Rules Card */
        .rules-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: var(--radius);
        }

        .rules-card ul {
            padding-left: 20px;
            color: var(--gray-light);
        }

        .rules-card li {
            margin-bottom: 8px;
            position: relative;
        }



        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px;
            border-radius: var(--radius);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
        }

        .service-card h4 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .service-card p {
            color: var(--gray-light);
        }

        /* Notification */
        .notification {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInUp 0.5s ease;
            border-left: 4px solid;
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
            color: #ffd9b8;
            border-left-color: var(--warning);
        }

        /* Mobile Navigation */
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

        /* Footer */
        footer {
            background: rgba(13, 13, 23, 0.95);
            backdrop-filter: blur(20px);
            color: white;
            padding: 50px 0 25px;
            margin-top: 70px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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

        /* Responsive */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .header-nav {
                display: none;
            }
            
            .burger-menu {
                display: flex;
            }
            
            .mobile-nav {
                display: block;
            }
            
            main {
                margin-top: 60px;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 40px 20px;
            }

            .hero-title {
                font-size: 1.9rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .chat-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .message {
                max-width: 90%;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0 15px;
            }

            .hero-title {
                font-size: 1.7rem;
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
        }

        /* Censorship indicator */
        .censorship-notice {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 5px;
        }
    </style>
</head>
<body>
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
                    <!-- Language Selector -->
                    <div class="language-selector">
                        <button class="lang-btn <?php echo $lang === 'ru' ? 'active' : ''; ?>" onclick="changeLanguage('ru')">RU</button>
                        <button class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" onclick="changeLanguage('en')">EN</button>
                        <button class="lang-btn <?php echo $lang === 'pt' ? 'active' : ''; ?>" onclick="changeLanguage('pt')">PT</button>
                        <button class="lang-btn <?php echo $lang === 'fr' ? 'active' : ''; ?>" onclick="changeLanguage('fr')">FR</button>
                        <button class="lang-btn <?php echo $lang === 'de' ? 'active' : ''; ?>" onclick="changeLanguage('de')">DE</button>
                    </div>
                    
                    <div class="user-info" <?php if (!$isLoggedIn) echo 'style="display: none;"'; ?>>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                            <a href="dashboard.php" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.8rem;">
                                <i class="fas fa-cog"></i> <?php echo htmlspecialchars($translations['admin_panel'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php endif; ?>
                        <div class="profile-dropdown">
                            <div class="user-avatar" id="profileAvatar" title="<?php echo htmlspecialchars($translations['go_to_profile'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if ($userAvatar): ?>
                                    <img src="<?php echo htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>"
                                         style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo htmlspecialchars(substr($userName, 0, 1), ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="dropdown-menu" id="profileDropdown">
                                <a href="profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($translations['profile'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                                <a href="logout.php" class="dropdown-item logout">
                                    <i class="fas fa-sign-out-alt"></i> <?php echo htmlspecialchars($translations['logout'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!$isLoggedIn): ?>
                        <div class="auth-buttons">
                            <a href="login.php" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.8rem;">
                                <i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars($translations['login_nav'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Desktop Navigation -->
            <nav class="header-nav">
                <ul class="nav-tabs">
                    <li class="nav-tab"><a href="index.php" class="nav-link"><i class="fas fa-home"></i> <?php echo htmlspecialchars($translations['home'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="nav-tab"><a href="information.php" class="nav-link"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($translations['information'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="nav-tab"><a href="map.php" class="nav-link"><i class="fas fa-map-marked-alt"></i> <?php echo htmlspecialchars($translations['map_services'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="nav-tab"><a href="translator.php" class="nav-link"><i class="fas fa-language"></i> <?php echo htmlspecialchars($translations['translator'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="nav-tab"><a href="converter.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($translations['currency_converter'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-tab active"><a href="chat.php" class="nav-link"><i class="fas fa-comments"></i> <?php echo htmlspecialchars($translations['city_chat'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php else: ?>
                        <li class="nav-tab"><a href="login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars($translations['login_nav'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li class="nav-tab"><a href="register.php" class="nav-link"><i class="fas fa-user-plus"></i> <?php echo htmlspecialchars($translations['register_nav'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <!-- Mobile Navigation -->
            <div class="mobile-nav" id="mobileNav">
                <ul class="mobile-nav-tabs">
                    <li class="mobile-nav-tab"><a href="index.php" class="mobile-nav-link"><i class="fas fa-home"></i> <?php echo htmlspecialchars($translations['home'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="mobile-nav-tab"><a href="information.php" class="mobile-nav-link"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($translations['information'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="mobile-nav-tab"><a href="map.php" class="mobile-nav-link"><i class="fas fa-map-marked-alt"></i> <?php echo htmlspecialchars($translations['map_services'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="mobile-nav-tab"><a href="translator.php" class="mobile-nav-link"><i class="fas fa-language"></i> <?php echo htmlspecialchars($translations['translator'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li class="mobile-nav-tab"><a href="converter.php" class="mobile-nav-link"><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($translations['currency_converter'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php if ($isLoggedIn): ?>
                        <li class="mobile-nav-tab active"><a href="chat.php" class="mobile-nav-link"><i class="fas fa-comments"></i> <?php echo htmlspecialchars($translations['city_chat'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li class="mobile-nav-tab"><a href="profile.php" class="mobile-nav-link"><i class="fas fa-user"></i> <?php echo htmlspecialchars($translations['profile'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php else: ?>
                        <li class="mobile-nav-tab"><a href="login.php" class="mobile-nav-link"><i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars($translations['login_nav'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li class="mobile-nav-tab"><a href="register.php" class="mobile-nav-link"><i class="fas fa-user-plus"></i> <?php echo htmlspecialchars($translations['register_nav'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="hero-title"><?php echo htmlspecialchars($translations['city_chat_title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="hero-subtitle"><?php echo htmlspecialchars($translations['city_chat_desc'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if ($isLoggedIn): ?>
                <div style="margin-top: 25px;">
                    <a href="personal_chats.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; font-size: 1rem;">
                        <i class="fas fa-user-friends"></i>
                        <?php echo htmlspecialchars($translations['personal_chats'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$isLoggedIn): ?>
            <!-- Not logged in message -->
            <div class="card">
                <div class="notification info">
                    <i class="fas fa-info-circle"></i>
                    <?php echo htmlspecialchars($translations['login_to_chat'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars($translations['login_nav'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <a href="register.php" class="btn btn-primary" style="margin-left: 10px;">
                        <i class="fas fa-user-plus"></i> <?php echo htmlspecialchars($translations['register_nav'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Main Chat -->
            <div class="card">
                <div class="chat-container">
                    <div class="chat-header">
                        <h3>
                            <i class="fas fa-comments"></i> 
                            <?php echo htmlspecialchars($translations['city_chat_of'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($cityNames[$userCity] ?? $userCity, ENT_QUOTES, 'UTF-8'); ?>
                        </h3>
                        <div class="online-count" id="onlineUsersCount">
                            <i class="fas fa-users"></i> <span id="onlineCount">0</span> <?php echo htmlspecialchars($translations['online_users'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <div class="notification info">
                            <i class="fas fa-info-circle"></i>
                            <?php echo htmlspecialchars($translations['loading_messages'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                    
                    <div class="chat-input-area">
                        <textarea class="chat-input" id="messageInput" 
                                  placeholder="<?php echo htmlspecialchars($translations['type_message'], ENT_QUOTES, 'UTF-8'); ?>" 
                                  rows="2" maxlength="1000"></textarea>
                        <button class="btn btn-primary" id="sendMessageBtn">
                            <i class="fas fa-paper-plane"></i> <?php echo htmlspecialchars($translations['send'], ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Chat Rules -->
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-gavel"></i> <?php echo htmlspecialchars($translations['chat_rules'], ENT_QUOTES, 'UTF-8'); ?>
                </h2>
                <div class="rules-card">
                    <ul>
                        <?php foreach ($translations['rules_list'] as $rule): ?>
                            <li><?php echo htmlspecialchars($rule, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Useful Tips -->
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-lightbulb"></i> <?php echo htmlspecialchars($translations['chat_help_title'], ENT_QUOTES, 'UTF-8'); ?>
                </h2>
                <div class="services-grid">
                    <div class="service-card">
                        <h4><i class="fas fa-users"></i> <?php echo t('Сообщество', 'Community', 'Comunidade', 'Communauté', 'Gemeinschaft'); ?></h4>
                        <p><?php echo htmlspecialchars($translations['chat_tip1'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    
                    <div class="service-card">
                        <h4><i class="fas fa-question-circle"></i> <?php echo t('Вопросы', 'Questions', 'Perguntas', 'Questions', 'Fragen'); ?></h4>
                        <p><?php echo htmlspecialchars($translations['chat_tip2'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    
                    <div class="service-card">
                        <h4><i class="fas fa-share-alt"></i> <?php echo t('Опыт', 'Experience', 'Experiência', 'Expérience', 'Erfahrung'); ?></h4>
                        <p><?php echo htmlspecialchars($translations['chat_tip3'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>MigraSupport</h3>
                    <p><?php echo htmlspecialchars($translations['footer_title'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-telegram"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3><?php echo htmlspecialchars($translations['quick_links'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-home"></i> <?php echo htmlspecialchars($translations['home'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li><a href="information.php"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($translations['information'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li><a href="map.php"><i class="fas fa-map-marked-alt"></i> <?php echo htmlspecialchars($translations['map_services'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li><a href="translator.php"><i class="fas fa-language"></i> <?php echo htmlspecialchars($translations['translator'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li><a href="converter.php"><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($translations['currency_converter'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li><a href="chat.php"><i class="fas fa-comments"></i> <?php echo htmlspecialchars($translations['city_chat'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3><?php echo htmlspecialchars($translations['contacts'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> info@migrasupport.by</li>
                        <li><i class="fas fa-phone"></i> +375 (17) 555-55-55</li>
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($translations['minsk_belarus'], ENT_QUOTES, 'UTF-8'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2023-2026 MigraSupport. <?php echo htmlspecialchars($translations['all_rights_reserved'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </footer>

    <script>
        // Функция экранирования HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Функция смены языка
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Profile dropdown
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
            
            // Burger menu
            const burgerMenu = document.getElementById('burgerMenu');
            const mobileNav = document.getElementById('mobileNav');
            
            if (burgerMenu && mobileNav) {
                burgerMenu.addEventListener('click', function() {
                    this.classList.toggle('active');
                    mobileNav.classList.toggle('active');
                });
                
                document.addEventListener('click', function(event) {
                    if (!burgerMenu.contains(event.target) && !mobileNav.contains(event.target)) {
                        burgerMenu.classList.remove('active');
                        mobileNav.classList.remove('active');
                    }
                });
                
                document.querySelectorAll('.mobile-nav-link').forEach(link => {
                    link.addEventListener('click', function() {
                        burgerMenu.classList.remove('active');
                        mobileNav.classList.remove('active');
                    });
                });
            }

            <?php if ($isLoggedIn): ?>
                // Инициализация чата
                initializeCityChat();
            <?php endif; ?>
        });

        <?php if ($isLoggedIn): ?>
        // Chat functionality
        let lastMessageId = 0;
        let chatInterval;
        let onlineUsersInterval;
        let currentDisplayedDates = new Set();

        // PHP translations for JavaScript
        const chatTranslations = {
            no_messages: <?php echo safeJsonEncode($translations['no_messages']); ?>,
            loading_messages: <?php echo safeJsonEncode($translations['loading_messages']); ?>,
            enter_message: <?php echo safeJsonEncode($translations['enter_message']); ?>,
            message_too_long: <?php echo safeJsonEncode($translations['message_too_long']); ?>,
            error_sending: <?php echo safeJsonEncode($translations['error_sending']); ?>,
            error: <?php echo safeJsonEncode($translations['error']); ?>,
            sending: <?php echo safeJsonEncode($translations['sending']); ?>,
            today: <?php echo safeJsonEncode($translations['today']); ?>,
            yesterday: <?php echo safeJsonEncode($translations['yesterday']); ?>,
            message_censored: <?php echo safeJsonEncode($translations['message_censored']); ?>
        };

        // Month names for all languages
        const monthNames = <?php echo safeJsonEncode($monthNames); ?>;
        const currentLang = '<?php echo addslashes($lang); ?>';

        function initializeCityChat() {
            const sendBtn = document.getElementById('sendMessageBtn');
            const messageInput = document.getElementById('messageInput');

            if (sendBtn) {
                sendBtn.addEventListener('click', sendMessage);
            }
            
            if (messageInput) {
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }

            // Load initial messages
            loadChatMessages();
            
            // Setup auto-refresh
            startChatAutoRefresh();
            startOnlineUsersRefresh();
        }

        async function loadChatMessages() {
            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) return;
            
            try {
                const response = await fetch(`chat.php?action=get_messages&last_id=${lastMessageId}`);
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('Server returned non-JSON response');
                    return;
                }
                
                const result = await response.json();
                
                if (result.success && result.messages) {
                    displayChatMessages(result.messages);
                    if (result.messages.length > 0) {
                        lastMessageId = Math.max(...result.messages.map(msg => msg.id));
                    }
                }
            } catch (error) {
                console.error('Error loading chat messages:', error);
            }
        }

        function formatChatDate(dateString) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const messageDate = new Date(dateString);
            messageDate.setHours(0, 0, 0, 0);
            
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            
            if (messageDate.getTime() === today.getTime()) {
                return chatTranslations.today;
            } else if (messageDate.getTime() === yesterday.getTime()) {
                return chatTranslations.yesterday;
            } else {
                const day = messageDate.getDate();
                const month = (messageDate.getMonth() + 1).toString().padStart(2, '0');
                const year = messageDate.getFullYear();
                
                if (currentLang === 'ru' || currentLang === 'pt' || currentLang === 'fr' || currentLang === 'de') {
                    return `${day} ${monthNames[currentLang]?.[month] || month}`;
                } else {
                    return `${monthNames[currentLang]?.[month] || month} ${day}`;
                }
            }
        }

        function displayChatMessages(messages) {
            const container = document.getElementById('chatMessages');
            if (!container) return;
            
            const currentUserId = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
            
            if (!messages || messages.length === 0) {
                if (container.children.length === 0 || 
                    (container.children.length === 1 && container.children[0].classList?.contains('notification'))) {
                    container.innerHTML = `<div class="notification info"><i class="fas fa-info-circle"></i> ${escapeHtml(chatTranslations.no_messages)}</div>`;
                }
                return;
            }
            
            const loadingNotification = container.querySelector('.notification.info');
            if (loadingNotification && loadingNotification.textContent.includes(chatTranslations.loading_messages)) {
                loadingNotification.remove();
            }
            
            messages.sort((a, b) => a.created_at_timestamp - b.created_at_timestamp);
            
            let lastDate = null;
            
            messages.forEach(msg => {
                const messageDate = msg.created_at_date;
                const formattedDate = formatChatDate(messageDate);
                
                if (messageDate !== lastDate && !currentDisplayedDates.has(messageDate)) {
                    const dateDivider = document.createElement('div');
                    dateDivider.className = 'date-divider';
                    dateDivider.innerHTML = `<span>${escapeHtml(formattedDate)}</span>`;
                    container.appendChild(dateDivider);
                    currentDisplayedDates.add(messageDate);
                    lastDate = messageDate;
                }
                
                const messageDiv = document.createElement('div');
                const isOwnMessage = msg.sender_id == currentUserId;
                
                const escapedMessageText = escapeHtml(msg.message_text);
                const escapedSenderName = escapeHtml(msg.sender_name);
                const escapedAvatar = escapeHtml(msg.avatar || '');
                
                let avatarHtml = '';
                if (escapedAvatar) {
                    avatarHtml = `<img src="${escapedAvatar}" alt="${escapedSenderName}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; cursor: pointer;" onclick="window.location.href='view_profile.php?id=${msg.sender_id}'">`;
                } else {
                    const initial = escapedSenderName.charAt(0).toUpperCase();
                    avatarHtml = `<div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #ff006e 0%, #ff9e00 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem; cursor: pointer;" onclick="window.location.href='view_profile.php?id=${msg.sender_id}'">${escapeHtml(initial)}</div>`;
                }
                
                messageDiv.className = `message ${isOwnMessage ? 'own' : 'other'}`;
                messageDiv.style.display = 'flex';
                messageDiv.style.gap = '10px';
                messageDiv.style.alignItems = 'flex-start';
                
                if (isOwnMessage) {
                    messageDiv.innerHTML = `
                        <div style="flex: 1;">
                            <div class="message-header">
                                <span class="message-sender" onclick="window.location.href='view_profile.php?id=${msg.sender_id}'">${escapedSenderName}</span>
                                <span class="message-time">${escapeHtml(msg.created_at_time)}</span>
                            </div>
                            <div class="message-text">${escapedMessageText}</div>
                        </div>
                        ${avatarHtml}
                    `;
                } else {
                    messageDiv.innerHTML = `
                        ${avatarHtml}
                        <div style="flex: 1;">
                            <div class="message-header">
                                <span class="message-sender" onclick="window.location.href='view_profile.php?id=${msg.sender_id}'">${escapedSenderName}</span>
                                <span class="message-time">${escapeHtml(msg.created_at_time)}</span>
                            </div>
                            <div class="message-text">${escapedMessageText}</div>
                        </div>
                    `;
                }
                
                container.appendChild(messageDiv);
            });
            
            container.scrollTop = container.scrollHeight;
        }

        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            if (!messageInput) return;
            
            const message = messageInput.value.trim();
            const sendBtn = document.getElementById('sendMessageBtn');
            
            if (!message) {
                showChatError(chatTranslations.enter_message);
                return;
            }
            
            if (message.length > 1000) {
                showChatError(chatTranslations.message_too_long);
                return;
            }
            
            const originalHtml = sendBtn ? sendBtn.innerHTML : '';
            if (sendBtn) {
                sendBtn.disabled = true;
                sendBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${chatTranslations.sending}`;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('message', message);
                
                const response = await fetch('chat.php', {
                    method: 'POST',
                    body: formData
                });
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Сервер вернул некорректный ответ');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    messageInput.value = '';
                    await loadChatMessages();
                    hideChatError();
                    
                    // Показываем уведомление о цензуре, если сообщение было изменено
                    if (result.censored) {
                        showChatWarning(chatTranslations.message_censored);
                    }
                } else {
                    throw new Error(result.error || 'Unknown error');
                }
                
            } catch (error) {
                console.error('Error sending message:', error);
                showChatError(`${chatTranslations.error_sending}: ${error.message}`);
            } finally {
                if (sendBtn) {
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = originalHtml;
                }
            }
        }

        function showChatWarning(message) {
            let warningElement = document.getElementById('chatWarning');
            const chatContainer = document.querySelector('.chat-container');
            
            if (!warningElement && chatContainer && chatContainer.parentNode) {
                warningElement = document.createElement('div');
                warningElement.id = 'chatWarning';
                warningElement.className = 'notification warning';
                warningElement.style.margin = '10px 0';
                chatContainer.parentNode.insertBefore(warningElement, chatContainer.nextSibling);
            }
            
            if (warningElement) {
                warningElement.innerHTML = `<i class="fas fa-shield-alt"></i> ${escapeHtml(message)}`;
                warningElement.style.display = 'block';
                
                setTimeout(() => {
                    if (warningElement) {
                        warningElement.style.display = 'none';
                    }
                }, 3000);
            }
        }

        async function updateOnlineUsers() {
            try {
                const response = await fetch('chat.php?action=get_online_users');
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return;
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const onlineCountSpan = document.getElementById('onlineCount');
                    if (onlineCountSpan) {
                        onlineCountSpan.textContent = result.count;
                    }
                }
            } catch (error) {
                console.error('Error updating online users:', error);
            }
        }

        function startChatAutoRefresh() {
            if (chatInterval) clearInterval(chatInterval);
            chatInterval = setInterval(() => {
                loadChatMessages();
            }, 5000);
        }

        function startOnlineUsersRefresh() {
            updateOnlineUsers();
            if (onlineUsersInterval) clearInterval(onlineUsersInterval);
            onlineUsersInterval = setInterval(updateOnlineUsers, 30000);
        }

        function showChatError(message) {
            let errorElement = document.getElementById('chatError');
            const chatContainer = document.querySelector('.chat-container');
            
            if (!errorElement && chatContainer && chatContainer.parentNode) {
                errorElement = document.createElement('div');
                errorElement.id = 'chatError';
                errorElement.className = 'notification error';
                errorElement.style.margin = '10px 0';
                chatContainer.parentNode.insertBefore(errorElement, chatContainer.nextSibling);
            }
            
            if (errorElement) {
                errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(message)}`;
                errorElement.style.display = 'block';
                
                setTimeout(hideChatError, 5000);
            }
        }

        function hideChatError() {
            const errorElement = document.getElementById('chatError');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }

        window.addEventListener('beforeunload', function() {
            if (chatInterval) {
                clearInterval(chatInterval);
            }
            if (onlineUsersInterval) {
                clearInterval(onlineUsersInterval);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
// Завершаем буферизацию вывода
if (!isset($_GET['action']) && !isset($_POST['action'])) {
    ob_end_flush();
}
?>