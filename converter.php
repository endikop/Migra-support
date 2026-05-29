<?php
session_start();
require_once 'config.php';

// Подключаем файл с данными аватара
require_once 'include_avatar.php';

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

// Получение курсов валют (кешируем на 5 минут для более частого обновления)
$exchangeRates = [];
$cacheFile = 'exchange_rates_cache.json';

function getExchangeRates() {
    global $cacheFile;
    
    // Проверяем кэш (обновляем каждые 5 минут = 300 секунд)
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        if ($cacheData && time() - $cacheData['timestamp'] < 300) {
            return $cacheData['rates'];
        }
    }
    
    // Получаем актуальные курсы
    $url = 'https://api.exchangerate-api.com/v4/latest/USD';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['rates'])) {
            // Сохраняем в кэш
            $cacheData = [
                'timestamp' => time(),
                'rates' => $data['rates']
            ];
            file_put_contents($cacheFile, json_encode($cacheData));
            return $data['rates'];
        }
    }
    
    // Возвращаем запасные значения если API недоступно
    return [
        'USD' => 1,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'JPY' => 148.5,
        'CNY' => 7.3,
        'RUB' => 92.5,
        'BYN' => 3.2,
        'PLN' => 4.0,
        'CHF' => 0.88,
        'CAD' => 1.36,
        'AUD' => 1.52,
        'NZD' => 1.66,
        'SGD' => 1.35,
        'HKD' => 7.82,
        'KRW' => 1320.5,
        'INR' => 83.2,
        'BRL' => 5.05,
        'TRY' => 29.8
    ];
}

$exchangeRates = getExchangeRates();

// Основные валюты для конвертера (расширенный список)
$currencies = [
    'USD' => [
        'name' => t('Доллар США', 'US Dollar', 'Dólar Americano', 'Dollar américain', 'US-Dollar'),
        'name_en' => 'US Dollar',
        'name_pt' => 'Dólar Americano',
        'name_fr' => 'Dollar américain',
        'name_de' => 'US-Dollar',
        'symbol' => '$',
        'icon' => 'fas fa-dollar-sign'
    ],
    'EUR' => [
        'name' => t('Евро', 'Euro', 'Euro', 'Euro', 'Euro'),
        'name_en' => 'Euro',
        'name_pt' => 'Euro',
        'name_fr' => 'Euro',
        'name_de' => 'Euro',
        'symbol' => '€',
        'icon' => 'fas fa-euro-sign'
    ],
    'GBP' => [
        'name' => t('Британский фунт', 'British Pound', 'Libra Esterlina', 'Livre sterling', 'Britisches Pfund'),
        'name_en' => 'British Pound',
        'name_pt' => 'Libra Esterlina',
        'name_fr' => 'Livre sterling',
        'name_de' => 'Britisches Pfund',
        'symbol' => '£',
        'icon' => 'fas fa-pound-sign'
    ],
    'JPY' => [
        'name' => t('Японская иена', 'Japanese Yen', 'Iene Japonês', 'Yen japonais', 'Japanischer Yen'),
        'name_en' => 'Japanese Yen',
        'name_pt' => 'Iene Japonês',
        'name_fr' => 'Yen japonais',
        'name_de' => 'Japanischer Yen',
        'symbol' => '¥',
        'icon' => 'fas fa-yen-sign'
    ],
    'CNY' => [
        'name' => t('Китайский юань', 'Chinese Yuan', 'Yuan Chinês', 'Yuan chinois', 'Chinesischer Yuan'),
        'name_en' => 'Chinese Yuan',
        'name_pt' => 'Yuan Chinês',
        'name_fr' => 'Yuan chinois',
        'name_de' => 'Chinesischer Yuan',
        'symbol' => '¥',
        'icon' => 'fas fa-yen-sign'
    ],
    'RUB' => [
        'name' => t('Российский рубль', 'Russian Ruble', 'Rublo Russo', 'Rouble russe', 'Russischer Rubel'),
        'name_en' => 'Russian Ruble',
        'name_pt' => 'Rublo Russo',
        'name_fr' => 'Rouble russe',
        'name_de' => 'Russischer Rubel',
        'symbol' => '₽',
        'icon' => 'fas fa-ruble-sign'
    ],
    'BYN' => [
        'name' => t('Белорусский рубль', 'Belarusian Ruble', 'Rublo Bielorrusso', 'Rouble biélorusse', 'Weißrussischer Rubel'),
        'name_en' => 'Belarusian Ruble',
        'name_pt' => 'Rublo Bielorrusso',
        'name_fr' => 'Rouble biélorusse',
        'name_de' => 'Weißrussischer Rubel',
        'symbol' => 'Br',
        'icon' => 'fas fa-ruble-sign'
    ],
    'PLN' => [
        'name' => t('Польский злотый', 'Polish Zloty', 'Zloty Polonês', 'Zloty polonais', 'Polnischer Złoty'),
        'name_en' => 'Polish Zloty',
        'name_pt' => 'Zloty Polonês',
        'name_fr' => 'Zloty polonais',
        'name_de' => 'Polnischer Złoty',
        'symbol' => 'zł',
        'icon' => 'fas fa-money-bill-wave'
    ],
    'CHF' => [
        'name' => t('Швейцарский франк', 'Swiss Franc', 'Franco Suíço', 'Franc suisse', 'Schweizer Franken'),
        'name_en' => 'Swiss Franc',
        'name_pt' => 'Franco Suíço',
        'name_fr' => 'Franc suisse',
        'name_de' => 'Schweizer Franken',
        'symbol' => 'Fr',
        'icon' => 'fas fa-franc-sign'
    ],
    'CAD' => [
        'name' => t('Канадский доллар', 'Canadian Dollar', 'Dólar Canadense', 'Dollar canadien', 'Kanadischer Dollar'),
        'name_en' => 'Canadian Dollar',
        'name_pt' => 'Dólar Canadense',
        'name_fr' => 'Dollar canadien',
        'name_de' => 'Kanadischer Dollar',
        'symbol' => 'C$',
        'icon' => 'fas fa-dollar-sign'
    ],
    'AUD' => [
        'name' => t('Австралийский доллар', 'Australian Dollar', 'Dólar Australiano', 'Dollar australien', 'Australischer Dollar'),
        'name_en' => 'Australian Dollar',
        'name_pt' => 'Dólar Australiano',
        'name_fr' => 'Dollar australien',
        'name_de' => 'Australischer Dollar',
        'symbol' => 'A$',
        'icon' => 'fas fa-dollar-sign'
    ],
    'NZD' => [
        'name' => t('Новозеландский доллар', 'New Zealand Dollar', 'Dólar Neozelandês', 'Dollar néo-zélandais', 'Neuseeland-Dollar'),
        'name_en' => 'New Zealand Dollar',
        'name_pt' => 'Dólar Neozelandês',
        'name_fr' => 'Dollar néo-zélandais',
        'name_de' => 'Neuseeland-Dollar',
        'symbol' => 'NZ$',
        'icon' => 'fas fa-dollar-sign'
    ],
    'SGD' => [
        'name' => t('Сингапурский доллар', 'Singapore Dollar', 'Dólar de Cingapura', 'Dollar de Singapour', 'Singapur-Dollar'),
        'name_en' => 'Singapore Dollar',
        'name_pt' => 'Dólar de Cingapura',
        'name_fr' => 'Dollar de Singapour',
        'name_de' => 'Singapur-Dollar',
        'symbol' => 'S$',
        'icon' => 'fas fa-dollar-sign'
    ],
    'HKD' => [
        'name' => t('Гонконгский доллар', 'Hong Kong Dollar', 'Dólar de Hong Kong', 'Dollar de Hong Kong', 'Hongkong-Dollar'),
        'name_en' => 'Hong Kong Dollar',
        'name_pt' => 'Dólar de Hong Kong',
        'name_fr' => 'Dollar de Hong Kong',
        'name_de' => 'Hongkong-Dollar',
        'symbol' => 'HK$',
        'icon' => 'fas fa-dollar-sign'
    ],
    'KRW' => [
        'name' => t('Южнокорейская вона', 'South Korean Won', 'Won Sul-Coreano', 'Won sud-coréen', 'Südkoreanischer Won'),
        'name_en' => 'South Korean Won',
        'name_pt' => 'Won Sul-Coreano',
        'name_fr' => 'Won sud-coréen',
        'name_de' => 'Südkoreanischer Won',
        'symbol' => '₩',
        'icon' => 'fas fa-won-sign'
    ],
    'INR' => [
        'name' => t('Индийская рупия', 'Indian Rupee', 'Rupia Indiana', 'Roupie indienne', 'Indische Rupie'),
        'name_en' => 'Indian Rupee',
        'name_pt' => 'Rupia Indiana',
        'name_fr' => 'Roupie indienne',
        'name_de' => 'Indische Rupie',
        'symbol' => '₹',
        'icon' => 'fas fa-rupee-sign'
    ],
    'BRL' => [
        'name' => t('Бразильский реал', 'Brazilian Real', 'Real Brasileiro', 'Réal brésilien', 'Brasilianischer Real'),
        'name_en' => 'Brazilian Real',
        'name_pt' => 'Real Brasileiro',
        'name_fr' => 'Réal brésilien',
        'name_de' => 'Brasilianischer Real',
        'symbol' => 'R$',
        'icon' => 'fas fa-money-bill-wave'
    ],
    'TRY' => [
        'name' => t('Турецкая лира', 'Turkish Lira', 'Lira Turca', 'Livre turque', 'Türkische Lira'),
        'name_en' => 'Turkish Lira',
        'name_pt' => 'Lira Turca',
        'name_fr' => 'Livre turque',
        'name_de' => 'Türkische Lira',
        'symbol' => '₺',
        'icon' => 'fas fa-lira-sign'
    ]
];

// Тексты для перевода
$translations = [
    'main_title' => t(
        'Конвертер валют - MigraSupport',
        'Currency Converter - MigraSupport',
        'Conversor de Moeda - MigraSupport',
        'Convertisseur de Devises - MigraSupport',
        'Währungsrechner - MigraSupport'
    ),
    'currency_converter' => t(
        'Конвертер валют',
        'Currency Converter',
        'Conversor de Moeda',
        'Convertisseur de Devises',
        'Währungsrechner'
    ),
    'currency_desc' => t(
        'Актуальные курсы валют для мигрантов. Рассчитайте стоимость в нужной валюте.',
        'Current exchange rates for migrants. Calculate cost in desired currency.',
        'Taxas de câmbio atuais para migrantes. Calcule o custo na moeda desejada.',
        'Taux de change actuels pour les migrants. Calculez le coût dans la devise souhaitée.',
        'Aktuelle Wechselkurse für Migranten. Berechnen Sie den Preis in der gewünschten Währung.'
    ),
    'converter_title' => t(
        'Конвертер валют MigraSupport',
        'MigraSupport Currency Converter',
        'Conversor de Moeda MigraSupport',
        'Convertisseur de Devises MigraSupport',
        'MigraSupport Währungsrechner'
    ),
    'rates_updated' => t(
        'Актуальные курсы обновляются каждые 5 минут',
        'Current rates are updated every 5 minutes',
        'As taxas atuais são atualizadas a cada 5 minutos',
        'Les taux actuels sont mis à jour toutes les 5 minutes',
        'Aktuelle Kurse werden alle 5 Minuten aktualisiert'
    ),
    'from_currency' => t(
        'Из валюты:',
        'From currency:',
        'Da moeda:',
        'De la devise:',
        'Von der Währung:'
    ),
    'to_currency' => t(
        'В валюту:',
        'To currency:',
        'Para a moeda:',
        'À la devise:',
        'Zur Währung:'
    ),
    'amount' => t(
        'Сумма:',
        'Amount:',
        'Valor:',
        'Montant:',
        'Betrag:'
    ),
    'result' => t(
        'Результат:',
        'Result:',
        'Resultado:',
        'Résultat:',
        'Ergebnis:'
    ),
    'exchange_rate' => t(
        'Курс обмена:',
        'Exchange rate:',
        'Taxa de câmbio:',
        'Taux de change:',
        'Wechselkurs:'
    ),
    'clear' => t(
        'Очистить',
        'Clear',
        'Limpar',
        'Effacer',
        'Löschen'
    ),
    'current_rates' => t(
        'Актуальные курсы валют',
        'Current Exchange Rates',
        'Taxas de Câmbio Atuais',
        'Taux de Change Actuels',
        'Aktuelle Wechselkurse'
    ),
    'useful_info' => t(
        'Полезная информация',
        'Useful Information',
        'Informação Útil',
        'Informations utiles',
        'Nützliche Informationen'
    ),
    'currency_exchange' => t(
        'Обмен валют в Беларуси',
        'Currency Exchange in Belarus',
        'Câmbio de Moeda na Bielorrússia',
        'Change de devises en Biélorussie',
        'Währungsumtausch in Belarus'
    ),
    'currency_exchange_desc' => t(
        'В Беларуси можно обменять валюту в банках и обменных пунктах. Крупные банки обычно предлагают лучшие курсы.',
        'In Belarus, currency can be exchanged at banks and exchange offices. Large banks usually offer better rates.',
        'Na Bielorrússia, a moeda pode ser trocada em bancos e casas de câmbio. Grandes bancos geralmente oferecem melhores taxas.',
        'En Biélorussie, vous pouvez échanger des devises dans les banques et les bureaux de change. Les grandes banques offrent généralement de meilleurs taux.',
        'In Belarus kann Geld bei Banken und Wechselstuben umgetauscht werden. Große Banken bieten in der Regel bessere Kurse.'
    ),
    'bank_cards' => t(
        'Банковские карты',
        'Bank Cards',
        'Cartões Bancários',
        'Cartes bancaires',
        'Bankkarten'
    ),
    'bank_cards_desc' => t(
        'Международные карты Visa и MasterCard принимаются повсеместно. Рекомендуется иметь немного наличных BYN.',
        'International Visa and MasterCard cards are accepted everywhere. It is recommended to have some BYN cash.',
        'Cartões internacionais Visa e MasterCard são aceitos em todos os lugares. Recomenda-se ter algum dinheiro em BYN.',
        'Les cartes internationales Visa et MasterCard sont acceptées partout. Il est recommandé d\'avoir un peu d\'argent liquide en BYN.',
        'Internationale Visa- und MasterCard-Karten werden überall akzeptiert. Es wird empfohlen, etwas BYN-Bargeld zu haben.'
    ),
    'bank_hours' => t(
        'Часы работы банков',
        'Bank Working Hours',
        'Horário de Funcionamento dos Bancos',
        'Heures d\'ouverture des banques',
        'Banköffnungszeiten'
    ),
    'bank_hours_desc' => t(
        'Банки обычно работают с 9:00 до 18:00 по будням. Обменные пункты могут работать дольше и в выходные.',
        'Banks usually work from 9:00 to 18:00 on weekdays. Exchange offices may work longer and on weekends.',
        'Os bancos geralmente funcionam das 9:00 às 18:00 em dias úteis. As casas de câmbio podem funcionar por mais tempo e nos fins de semana.',
        'Les banques travaillent généralement de 9h00 à 18h00 en semaine. Les bureaux de change peuvent travailler plus longtemps et le week-end.',
        'Banken arbeiten in der Regel von 9:00 bis 18:00 Uhr an Wochentagen. Wechselstuben können länger und am Wochenende geöffnet sein.'
    ),
    'enter_amount' => t(
        'Введите сумму',
        'Enter amount',
        'Digite o valor',
        'Entrez le montant',
        'Betrag eingeben'
    ),
    'swap_languages' => t(
        'Поменять местами',
        'Swap currencies',
        'Trocar moedas',
        'Échanger les devises',
        'Währungen tauschen'
    ),
    'error' => t(
        'Ошибка',
        'Error',
        'Erro',
        'Erreur',
        'Fehler'
    ),
    'enter_valid_amount' => t(
        'Введите корректную сумму',
        'Enter valid amount',
        'Digite um valor válido',
        'Entrez un montant valide',
        'Gültigen Betrag eingeben'
    ),
    'currency_rate_not_found' => t(
        'Курс для выбранных валют не найден',
        'Rate for selected currencies not found',
        'Taxa para as moedas selecionadas não encontrada',
        'Taux pour les devises sélectionnées non trouvé',
        'Kurs für ausgewählte Währungen nicht gefunden'
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
    'auto_conversion' => t(
        'Конвертация происходит автоматически при изменении суммы',
        'Conversion happens automatically when amount changes',
        'A conversão acontece automaticamente quando o valor muda',
        'La conversion se fait automatiquement lorsque le montant change',
        'Die Umrechnung erfolgt automatisch bei Änderung des Betrags'
    ),
    'updates_automatically' => t(
        'Обновляется автоматически',
        'Updates automatically',
        'Atualiza automaticamente',
        'Se met à jour automatiquement',
        'Aktualisiert automatisch'
    ),
    'minsk_belarus' => t(
        'Минск, Беларусь',
        'Minsk, Belarus',
        'Minsk, Bielorrússia',
        'Minsk, Biélorussie',
        'Minsk, Belarus'
    ),
    'go_to_profile' => t(
        'Перейти в профиль',
        'Go to Profile',
        'Ir para o Perfil',
        'Aller au Profil',
        'Zum Profil gehen'
    )
];

// Функция для корректного вывода JSON с экранированием специальных символов
function json_encode_unicode($data) {
    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        $json = json_encode($data);
        $json = str_replace("'", "\\'", $json);
        $json = str_replace('"', '\\"', $json);
        return $json;
    }
}

// Функция для получения названия валюты на текущем языке
function getCurrencyName($currency, $lang) {
    switch($lang) {
        case 'pt':
            return $currency['name_pt'] ?? $currency['name_en'];
        case 'fr':
            return $currency['name_fr'] ?? $currency['name_en'];
        case 'de':
            return $currency['name_de'] ?? $currency['name_en'];
        case 'en':
            return $currency['name_en'];
        default:
            return $currency['name'];
    }
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

        /* Header Right Section */
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
            position: relative;
            overflow: hidden;
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

        /* Main Content */
        main {
            padding: 40px 0;
            margin-top: 120px;
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
            background-image: url('https://images.unsplash.com/photo-1580519542036-c47de6196ba5?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
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

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            color: white;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.4rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        /* Converter Styles */
        .converter-container {
            background: rgba(26, 26, 46, 0.7);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .converter-header {
            background: var(--gradient-success);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .converter-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/always-grey.png');
            opacity: 0.1;
        }

        .converter-header h2 {
            font-size: 1.6rem;
            margin-bottom: 8px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .converter-header p {
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }

        .converter-body {
            padding: 30px;
        }

        .currency-selectors {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .currency-selectors {
                flex-direction: column;
                gap: 15px;
            }
        }

        .currency-selector {
            flex: 1;
        }

        .currency-selector label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }

        .converter-select {
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

        .converter-select option {
            background-color: var(--dark);
            color: white;
        }

        .converter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .currency-swap-button {
            background: var(--gradient-success);
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
            box-shadow: 0 6px 15px rgba(56, 176, 0, 0.3);
            font-size: 1.1rem;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .currency-swap-button {
                margin: 8px auto;
                transform: rotate(90deg);
            }
        }

        .currency-swap-button:hover {
            transform: rotate(180deg) scale(1.1);
            box-shadow: 0 10px 20px rgba(56, 176, 0, 0.4);
        }

        .amount-inputs {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .amount-inputs {
                flex-direction: column;
                gap: 15px;
            }
        }

        .amount-input {
            flex: 1;
            position: relative;
        }

        .amount-input label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }

        .converter-input-group {
            position: relative;
        }

        .converter-input {
            width: 100%;
            padding: 14px 50px 14px 18px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 1.3rem;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            text-align: right;
        }

        .converter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .converter-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .converter-input[readonly] {
            background: rgba(0, 0, 0, 0.2);
            cursor: not-allowed;
        }

        .currency-symbol-left,
        .currency-symbol-right {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .currency-symbol-left {
            left: 18px;
        }

        .currency-symbol-right {
            right: 18px;
        }

        .auto-convert-notice {
            text-align: center;
            margin: 10px 0;
            font-size: 0.85rem;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .exchange-rate {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            font-size: 1rem;
            color: var(--gray-light);
            border-left: 4px solid var(--success);
        }

        .exchange-rate strong {
            color: white;
            font-weight: 700;
        }

        .exchange-rate-info {
            margin-top: 5px;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .converter-controls {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .converter-clear-button {
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
            flex: 1;
        }

        .converter-clear-button:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }

        .converter-error {
            color: var(--danger);
            text-align: center;
            margin-top: 12px;
            padding: 12px;
            background-color: rgba(255, 0, 84, 0.1);
            border-radius: 8px;
            display: none;
            border-left: 4px solid var(--danger);
            font-size: 0.9rem;
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
            position: relative;
            overflow: hidden;
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
            box-shadow: var(--shadow);
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
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .service-info {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--gray-light);
            font-size: 0.9rem;
        }

        .service-info i {
            color: var(--accent);
            margin-top: 3px;
            min-width: 16px;
            font-size: 0.9rem;
        }

        .service-card p {
            color: var(--gray-light);
            line-height: 1.7;
        }

        /* Burger menu */
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

        /* Мобильная навигация */
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

            .services-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 20px;
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
                padding: 20px;
            }
            
            .language-selector {
                flex-wrap: nowrap;
                justify-content: center;
                width: 100%;
                max-width: 280px;
            }
            
            .lang-btn {
                padding: 6px 8px;
                font-size: 0.75rem;
                min-width: 45px;
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
            
            .language-selector {
                flex-wrap: nowrap;
                max-width: 250px;
            }
            
            .lang-btn {
                padding: 5px 6px;
                font-size: 0.7rem;
                min-width: 40px;
            }
        }
        
        @media (max-width: 400px) {
            .language-selector {
                max-width: 220px;
            }
            
            .lang-btn {
                padding: 4px 5px;
                font-size: 0.65rem;
                min-width: 35px;
            }
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
                    
                    <?php if ($isLoggedIn): ?>
                        <div class="user-info">
                            <?php if ($userType === 'admin'): ?>
                                <a href="dashboard.php" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.8rem;">
                                    <i class="fas fa-cog"></i> <?php echo $translations['admin_panel']; ?>
                                </a>
                            <?php endif; ?>
                            <div class="profile-dropdown">
                                <div class="user-avatar" id="profileAvatar" title="<?php echo htmlspecialchars($translations['go_to_profile']); ?>">
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
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Основная навигация -->
            <nav class="header-nav">
                <ul class="nav-tabs" id="mainTabs">
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
                    <li class="nav-tab active">
                        <a href="converter.php" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?>
                        </a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-tab">
                            <a href="chat.php" class="nav-link">
                                <i class="fas fa-comments"></i> <?php echo $translations['city_chat']; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-tab">
                            <a href="login.php" class="nav-link">
                                <i class="fas fa-sign-in-alt"></i> <?php echo $translations['login_nav']; ?>
                            </a>
                        </li>
                        <li class="nav-tab">
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
                    <li class="mobile-nav-tab active">
                        <a href="converter.php" class="mobile-nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?>
                        </a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="mobile-nav-tab">
                            <a href="chat.php" class="mobile-nav-link">
                                <i class="fas fa-comments"></i> <?php echo $translations['city_chat']; ?>
                            </a>
                        </li>
                        <li class="mobile-nav-tab">
                            <a href="profile.php" class="mobile-nav-link">
                                <i class="fas fa-user"></i> <?php echo $translations['profile']; ?>
                            </a>
                        </li>
                        <li class="mobile-nav-tab">
                            <a href="logout.php" class="mobile-nav-link">
                                <i class="fas fa-sign-out-alt"></i> <?php echo $translations['logout']; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="mobile-nav-tab">
                            <a href="login.php" class="mobile-nav-link">
                                <i class="fas fa-sign-in-alt"></i> <?php echo $translations['login_nav']; ?>
                            </a>
                        </li>
                        <li class="mobile-nav-tab">
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
            <h1 class="hero-title"><?php echo $translations['currency_converter']; ?></h1>
            <p class="hero-subtitle"><?php echo $translations['currency_desc']; ?></p>
        </div>

        <!-- Конвертер -->
        <div class="converter-container">
            <div class="converter-header">
                <h2><i class="fas fa-money-bill-wave"></i> <?php echo $translations['converter_title']; ?></h2>
                <p><?php echo $translations['rates_updated']; ?></p>
            </div>
            
            <div class="converter-body">
                <div class="currency-selectors">
                    <div class="currency-selector">
                        <label for="from-currency"><?php echo $translations['from_currency']; ?></label>
                        <select id="from-currency" class="converter-select">
                            <?php foreach ($currencies as $code => $currency): ?>
                                <option value="<?php echo $code; ?>" <?php echo $code === 'USD' ? 'selected' : ''; ?>>
                                    <?php echo $code; ?> - <?php echo getCurrencyName($currency, $lang); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button class="currency-swap-button" id="swap-currencies" title="<?php echo $translations['swap_languages']; ?>">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    
                    <div class="currency-selector">
                        <label for="to-currency"><?php echo $translations['to_currency']; ?></label>
                        <select id="to-currency" class="converter-select">
                            <?php foreach ($currencies as $code => $currency): ?>
                                <option value="<?php echo $code; ?>" <?php echo $code === 'BYN' ? 'selected' : ''; ?>>
                                    <?php echo $code; ?> - <?php echo getCurrencyName($currency, $lang); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="auto-convert-notice">
                    <i class="fas fa-bolt"></i>
                    <span><?php echo $translations['auto_conversion']; ?></span>
                </div>
                
                <div class="amount-inputs">
                    <div class="amount-input">
                        <label for="amount-input"><?php echo $translations['amount']; ?></label>
                        <div class="converter-input-group">
                            <span class="currency-symbol-left" id="from-symbol">
                                <i class="fas fa-dollar-sign"></i>
                            </span>
                            <input type="number" id="amount-input" class="converter-input" value="100" min="0" step="0.01" placeholder="<?php echo $translations['enter_amount']; ?>">
                        </div>
                    </div>
                    
                    <div class="amount-input">
                        <label for="result-input"><?php echo $translations['result']; ?></label>
                        <div class="converter-input-group">
                            <span class="currency-symbol-right" id="to-symbol">
                                <i class="fas fa-ruble-sign"></i>
                            </span>
                            <input type="text" id="result-input" class="converter-input" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="exchange-rate" id="exchange-rate-info">
                    <?php echo $translations['exchange_rate']; ?> <strong id="rate-value">1 USD = 3.20 BYN</strong>
                    <div class="exchange-rate-info">
                        <i class="fas fa-sync-alt"></i> <?php echo $translations['updates_automatically']; ?>
                    </div>
                </div>
                
                <div class="converter-error" id="converter-error">
                    <?php echo $translations['error']; ?>
                </div>
                
                <div class="converter-controls">
                    <button class="converter-clear-button" id="clear-converter">
                        <i class="fas fa-trash"></i> <?php echo $translations['clear']; ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Актуальные курсы -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-chart-line"></i> <?php echo $translations['current_rates']; ?>
            </h2>
            <div class="services-grid">
                <?php 
                $popularRates = [
                    'USD' => ['BYN', 'EUR', 'RUB', 'GBP'],
                    'EUR' => ['BYN', 'USD', 'PLN', 'GBP'],
                    'BYN' => ['USD', 'EUR', 'RUB', 'PLN']
                ];
                
                foreach ($popularRates as $base => $targets): ?>
                    <div class="service-card">
                        <h4><i class="<?php echo $currencies[$base]['icon']; ?>"></i> <?php echo getCurrencyName($currencies[$base], $lang); ?></h4>
                        <?php foreach ($targets as $target): 
                            if (isset($exchangeRates[$base]) && isset($exchangeRates[$target])) {
                                $rate = $exchangeRates[$target] / $exchangeRates[$base];
                                // Показываем курс с точностью до копеек (2 знака после запятой)
                                $rateFormatted = number_format($rate, 2);
                            } else {
                                $rateFormatted = "—";
                            }
                        ?>
                            <div class="service-info">
                                <i class="<?php echo $currencies[$target]['icon']; ?>"></i>
                                <span>1 <?php echo $base; ?> = <?php echo $rateFormatted; ?> <?php echo $target; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Полезная информация -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-lightbulb"></i> <?php echo $translations['useful_info']; ?>
            </h2>
            <div class="services-grid">
                <div class="service-card">
                    <h4><i class="fas fa-bank"></i> <?php echo $translations['currency_exchange']; ?></h4>
                    <p><?php echo $translations['currency_exchange_desc']; ?></p>
                </div>
                
                <div class="service-card">
                    <h4><i class="fas fa-credit-card"></i> <?php echo $translations['bank_cards']; ?></h4>
                    <p><?php echo $translations['bank_cards_desc']; ?></p>
                </div>
                
                <div class="service-card">
                    <h4><i class="fas fa-clock"></i> <?php echo $translations['bank_hours']; ?></h4>
                    <p><?php echo $translations['bank_hours_desc']; ?></p>
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
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация бургер-меню
            initializeBurgerMenu();
            
            // Профиль dropdown
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

            // Инициализация конвертера
            initializeConverter();
        });
        
        // Инициализация бургер-меню
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

        function initializeConverter() {
            const fromCurrencySelect = document.getElementById('from-currency');
            const toCurrencySelect = document.getElementById('to-currency');
            const swapButton = document.getElementById('swap-currencies');
            const amountInput = document.getElementById('amount-input');
            const resultInput = document.getElementById('result-input');
            const clearButton = document.getElementById('clear-converter');
            const fromSymbol = document.getElementById('from-symbol');
            const toSymbol = document.getElementById('to-symbol');
            const exchangeRateInfo = document.getElementById('rate-value');
            const errorMessage = document.getElementById('converter-error');
            
            // Данные о валютах из PHP
            const currencies = <?php echo json_encode_unicode($currencies); ?>;
            const exchangeRates = <?php echo json_encode_unicode($exchangeRates); ?>;
            const translations = <?php echo json_encode_unicode($translations); ?>;
            
            // Обновление символов валют
            function updateCurrencySymbols() {
                const fromCurrency = fromCurrencySelect.value;
                const toCurrency = toCurrencySelect.value;
                
                if (currencies[fromCurrency]) {
                    fromSymbol.innerHTML = `<i class="${currencies[fromCurrency].icon}"></i>`;
                }
                
                if (currencies[toCurrency]) {
                    toSymbol.innerHTML = `<i class="${currencies[toCurrency].icon}"></i>`;
                }
            }
            
            // Функция конвертации
            function convertCurrency() {
                const amount = parseFloat(amountInput.value);
                const fromCurrency = fromCurrencySelect.value;
                const toCurrency = toCurrencySelect.value;
                
                if (isNaN(amount) || amount < 0) {
                    showConverterError(translations.enter_valid_amount);
                    resultInput.value = '';
                    return;
                }
                
                if (amount === 0) {
                    resultInput.value = '0.00';
                    updateExchangeRateText(fromCurrency, toCurrency);
                    hideConverterError();
                    return;
                }
                
                hideConverterError();
                
                if (!exchangeRates[fromCurrency] || !exchangeRates[toCurrency]) {
                    showConverterError(translations.currency_rate_not_found);
                    return;
                }
                
                // Конвертируем через USD как базовую валюту
                const amountInUSD = amount / exchangeRates[fromCurrency];
                const result = amountInUSD * exchangeRates[toCurrency];
                
                // Обновляем результат с точностью до копеек
                resultInput.value = result.toFixed(2);
                
                // Обновляем информацию о курсе
                updateExchangeRateText(fromCurrency, toCurrency);
            }
            
            // Обновление текста курса с точностью до копеек
            function updateExchangeRateText(fromCurrency, toCurrency) {
                if (exchangeRates[fromCurrency] && exchangeRates[toCurrency]) {
                    // Вычисляем курс с точностью до копеек (2 знака после запятой)
                    const rate = (exchangeRates[toCurrency] / exchangeRates[fromCurrency]).toFixed(2);
                    exchangeRateInfo.textContent = `1 ${fromCurrency} = ${rate} ${toCurrency}`;
                }
            }
            
            // Автоматическая конвертация при изменении значений
            let conversionTimeout;
            function scheduleConversion() {
                clearTimeout(conversionTimeout);
                conversionTimeout = setTimeout(convertCurrency, 300);
            }
            
            amountInput.addEventListener('input', scheduleConversion);
            
            fromCurrencySelect.addEventListener('change', function() {
                updateCurrencySymbols();
                scheduleConversion();
            });
            
            toCurrencySelect.addEventListener('change', function() {
                updateCurrencySymbols();
                scheduleConversion();
            });
            
            // Обработчик кнопки очистки
            clearButton.addEventListener('click', function() {
                amountInput.value = '100';
                resultInput.value = '';
                fromCurrencySelect.value = 'USD';
                toCurrencySelect.value = 'BYN';
                updateCurrencySymbols();
                updateExchangeRateText('USD', 'BYN');
                hideConverterError();
                scheduleConversion();
            });
            
            // Обработчик кнопки смены валют
            swapButton.addEventListener('click', function() {
                const tempCurrency = fromCurrencySelect.value;
                fromCurrencySelect.value = toCurrencySelect.value;
                toCurrencySelect.value = tempCurrency;
                
                updateCurrencySymbols();
                scheduleConversion();
            });
            
            // Функции для работы с ошибками
            function showConverterError(message) {
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
                
                setTimeout(hideConverterError, 5000);
            }
            
            function hideConverterError() {
                errorMessage.style.display = 'none';
            }
            
            // Автоматическое обновление курсов каждые 5 минут
            function autoRefreshRates() {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        // Извлекаем новые курсы из HTML
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const scripts = doc.getElementsByTagName('script');
                        
                        // Находим скрипт с новыми курсами и обновляем
                        for (let script of scripts) {
                            if (script.textContent.includes('const exchangeRates =')) {
                                const match = script.textContent.match(/const exchangeRates = ({.*?});/s);
                                if (match) {
                                    try {
                                        const newRates = JSON.parse(match[1]);
                                        // Обновляем глобальную переменную с курсами
                                        Object.assign(exchangeRates, newRates);
                                        // Пересчитываем конвертацию
                                        scheduleConversion();
                                        console.log('Курсы обновлены в', new Date().toLocaleTimeString());
                                    } catch(e) {
                                        console.error('Ошибка при обновлении курсов:', e);
                                    }
                                }
                                break;
                            }
                        }
                    })
                    .catch(error => console.error('Ошибка при обновлении курсов:', error));
            }
            
            // Запускаем автоматическое обновление каждые 5 минут
            setInterval(autoRefreshRates, 300000); // 300000 мс = 5 минут
            
            // Инициализация
            updateCurrencySymbols();
            convertCurrency();
            
            // Фокус на поле ввода суммы
            amountInput.focus();
            amountInput.select();
        }
    </script>
</body>
</html>