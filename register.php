<?php
// Включаем буферизацию вывода ДО любого кода


session_start();
require_once 'config.php';

// ПРОСТАЯ ФУНКЦИЯ ЦЕНЗУРЫ ПРЯМО В КОДЕ
function containsBannedWordsSimple($text) {
    // Список запрещенных слов (можно добавить больше)
    $bannedWords = ['негр', 'мат1', 'мат2', 'плохоеслово'];
    
    // Также проверяем слова из базы данных
    global $pdo;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT word FROM banned_words WHERE is_active = 1");
            $stmt->execute();
            $dbWords = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $bannedWords = array_merge($bannedWords, $dbWords);
        } catch (Exception $e) {
            // Игнорируем ошибки БД
        }
    }
    
    $textLower = mb_strtolower($text, 'UTF-8');
    foreach ($bannedWords as $word) {
        $word = trim($word);
        if (!empty($word) && mb_stripos($text, $word, 0, 'UTF-8') !== false) {
            return true;
        }
    }
    
    return false;
}

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
    
    // Если для языка нет перевода, используем английский
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

// Функция для экранирования строк для JavaScript
function js_escape($string) {
    return str_replace(
        ["'", "\n", "\r"],
        ["\\'", "\\n", "\\r"],
        $string
    );
}

// Данные по городам для выпадающего списка
$cities = [
    'minsk' => t('Минск', 'Minsk', 'Minsk', 'Minsk', 'Minsk'),
    'grodno' => t('Гродно', 'Grodno', 'Grodno', 'Grodno', 'Grodno'),
    'brest' => t('Брест', 'Brest', 'Brest', 'Brest', 'Brest'),
    'vitebsk' => t('Витебск', 'Vitebsk', 'Vitebsk', 'Vitebsk', 'Vitebsk'),
    'gomel' => t('Гомель', 'Gomel', 'Gomel', 'Gomel', 'Gomel'),
    'mogilev' => t('Могилёв', 'Mogilev', 'Mogilev', 'Mogilev', 'Mogilev')
];

// Обработка формы регистрации
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $country_of_origin = trim($_POST['country_of_origin'] ?? '');
    $passport_number = trim($_POST['passport_number'] ?? '');
    $city = trim($_POST['city'] ?? '');
    
    // Валидация данных с переведенными сообщениями
    if (empty($username)) {
        $errors[] = t('Введите имя пользователя', 'Enter username', 'Digite nome de usuário', 'Entrez le nom d\'utilisateur', 'Geben Sie Benutzernamen ein');
    } elseif (strlen($username) < 3) {
        $errors[] = t('Имя пользователя должно быть не менее 3 символов', 'Username must be at least 3 characters', 'Nome de usuário deve ter pelo menos 3 caracteres', 'Le nom d\'utilisateur doit comporter au moins 3 caractères', 'Benutzername muss mindestens 3 Zeichen lang sein');
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = t('Имя пользователя может содержать только буквы, цифры и подчеркивания', 'Username can only contain letters, numbers and underscores', 'Nome de usuário pode conter apenas letras, números e sublinhados', 'Le nom d\'utilisateur ne peut contenir que des lettres, des chiffres et des tirets bas', 'Benutzername darf nur Buchstaben, Zahlen und Unterstriche enthalten');
    } elseif (containsBannedWordsSimple($username)) {
        $errors[] = t('Имя пользователя содержит запрещенные слова', 'Username contains banned words', 'Nome de usuário contém palavras proibidas', 'Le nom d\'utilisateur contient des mots interdits', 'Benutzername enthält verbotene Wörter');
    }
    
    if (empty($email)) {
        $errors[] = t('Введите email', 'Enter email', 'Digite email', 'Entrez l\'email', 'E-Mail eingeben');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('Введите корректный email', 'Enter valid email', 'Digite email válido', 'Entrez un email valide', 'Gültige E-Mail eingeben');
    } elseif (strlen($email) > 100) {
        $errors[] = t('Email не должен превышать 100 символов', 'Email must not exceed 100 characters', 'Email não deve exceder 100 caracteres', 'L\'email ne doit pas dépasser 100 caractères', 'E-Mail darf 100 Zeichen nicht überschreiten');
    }
    
    if (empty($password)) {
        $errors[] = t('Введите пароль', 'Enter password', 'Digite a senha', 'Entrez le mot de passe', 'Passwort eingeben');
    } elseif (strlen($password) < 8) {
        $errors[] = t('Пароль должен быть не менее 8 символов', 'Password must be at least 8 characters', 'A senha deve ter pelo menos 8 caracteres', 'Le mot de passe doit comporter au moins 8 caractères', 'Passwort muss mindestens 8 Zeichen lang sein');
    } elseif (strlen($password) > 100) {
        $errors[] = t('Пароль не должен превышать 100 символов', 'Password must not exceed 100 characters', 'A senha não deve exceder 100 caracteres', 'Le mot de passe ne doit pas dépasser 100 caractères', 'Passwort darf 100 Zeichen nicht überschreiten');
    } elseif ($password !== $confirm_password) {
        $errors[] = t('Пароли не совпадают', 'Passwords do not match', 'As senhas não coincidem', 'Les mots de passe ne correspondent pas', 'Passwörter stimmen nicht überein');
    }
    
    if (empty($first_name)) {
        $errors[] = t('Введите имя', 'Enter first name', 'Digite o nome', 'Entrez le prénom', 'Vornamen eingeben');
    } elseif (strlen($first_name) < 2) {
        $errors[] = t('Имя должно быть не менее 2 символов', 'First name must be at least 2 characters', 'Nome deve ter pelo menos 2 caracteres', 'Le prénom doit comporter au moins 2 caractères', 'Vorname muss mindestens 2 Zeichen lang sein');
    } elseif (strlen($first_name) > 50) {
        $errors[] = t('Имя не должно превышать 50 символов', 'First name must not exceed 50 characters', 'Nome não deve exceder 50 caracteres', 'Le prénom ne doit pas dépasser 50 caractères', 'Vorname darf 50 Zeichen nicht überschreiten');
    } elseif (containsBannedWordsSimple($first_name)) {
        $errors[] = t('Имя содержит запрещенные слова', 'First name contains banned words', 'Nome contém palavras proibidas', 'Le prénom contient des mots interdits', 'Vorname enthält verbotene Wörter');
    }
    
    if (empty($last_name)) {
        $errors[] = t('Введите фамилию', 'Enter last name', 'Digite o sobrenome', 'Entrez le nom de famille', 'Nachnamen eingeben');
    } elseif (strlen($last_name) < 2) {
        $errors[] = t('Фамилия должна быть не менее 2 символов', 'Last name must be at least 2 characters', 'Sobrenome deve ter pelo menos 2 caracteres', 'Le nom de famille doit comporter au moins 2 caractères', 'Nachname muss mindestens 2 Zeichen lang sein');
    } elseif (strlen($last_name) > 50) {
        $errors[] = t('Фамилия не должна превышать 50 символов', 'Last name must not exceed 50 characters', 'Sobrenome não deve exceder 50 caracteres', 'Le nom de famille ne doit pas dépasser 50 caractères', 'Nachname darf 50 Zeichen nicht überschreiten');
    } elseif (containsBannedWordsSimple($last_name)) {
        $errors[] = t('Фамилия содержит запрещенные слова', 'Last name contains banned words', 'Sobrenome contém palavras proibidas', 'Le nom de famille contient des mots interdits', 'Nachname enthält verbotene Wörter');
    }
    
    if (!empty($phone)) {
        // Убираем все нецифровые символы
        $phoneDigits = preg_replace('/\D/', '', $phone);
        
        // Проверяем длину (12 цифр для белорусского номера: 375 + 9 цифр)
        if (strlen($phoneDigits) !== 12) {
            $errors[] = t('Номер телефона должен содержать 12 цифр', 'Phone number must contain 12 digits', 'Número de telefone deve conter 12 dígitos', 'Le numéro de téléphone doit contenir 12 chiffres', 'Telefonnummer muss 12 Ziffern enthalten');
        }
        
        // Проверяем код страны (используем substr для совместимости с PHP < 8.0)
        if (substr($phoneDigits, 0, 3) !== '375') {
            $errors[] = t('Номер телефона должен начинаться с +375', 'Phone number must start with +375', 'Número de telefone deve começar com +375', 'Le numéro de téléphone doit commencer par +375', 'Telefonnummer muss mit +375 beginnen');
        }
        
        // Проверяем максимальную длину исходной строки
        if (strlen($phone) > 20) {
            $errors[] = t('Номер телефона не должен превышать 20 символов', 'Phone number must not exceed 20 characters', 'Número de telefone não deve exceder 20 caracteres', 'Le numéro de téléphone ne doit pas dépasser 20 caractères', 'Telefonnummer darf 20 Zeichen nicht überschreiten');
        }
    }
    
    if (empty($country_of_origin)) {
        $errors[] = t('Введите страну происхождения', 'Enter country of origin', 'Digite o país de origem', 'Entrez le pays d\'origine', 'Herkunftsland eingeben');
    } elseif (strlen($country_of_origin) > 100) {
        $errors[] = t('Название страны не должно превышать 100 символов', 'Country name must not exceed 100 characters', 'Nome do país não deve exceder 100 caracteres', 'Le nom du pays ne doit pas dépasser 100 caractères', 'Ländernamen darf 100 Zeichen nicht überschreiten');
    } elseif (containsBannedWordsSimple($country_of_origin)) {
        $errors[] = t('Название страны содержит запрещенные слова', 'Country name contains banned words', 'Nome do país contém palavras proibidas', 'Le nom du pays contient des mots interdits', 'Ländername enthält verbotene Wörter');
    }
    
    if (empty($passport_number)) {
        $errors[] = t('Введите номер паспорта', 'Enter passport number', 'Digite o número do passaporte', 'Entrez le numéro de passeport', 'Passnummer eingeben');
    } elseif (strlen($passport_number) < 3) {
        $errors[] = t('Номер паспорта должен быть не менее 3 символов', 'Passport number must be at least 3 characters', 'Número do passaporte deve ter pelo menos 3 caracteres', 'Le numéro de passeport doit comporter au moins 3 caractères', 'Passnummer muss mindestens 3 Zeichen lang sein');
    } elseif (strlen($passport_number) > 50) {
        $errors[] = t('Номер паспорта не должен превышать 50 символов', 'Passport number must not exceed 50 characters', 'Número do passaporte não deve exceder 50 caracteres', 'Le numéro de passeport ne doit pas dépasser 50 caractères', 'Passnummer darf 50 Zeichen nicht überschreiten');
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $passport_number)) {
        $errors[] = t('Номер паспорта может содержать только буквы и цифры', 'Passport number can only contain letters and numbers', 'Número do passaporte pode conter apenas letras e números', 'Le numéro de passeport ne peut contenir que des lettres et des chiffres', 'Passnummer darf nur Buchstaben und Zahlen enthalten');
    }
    
    if (empty($city)) {
        $errors[] = t('Выберите город проживания', 'Select city of residence', 'Selecione a cidade de residência', 'Sélectionnez la ville de résidence', 'Wohnort auswählen');
    }
    
    // Проверка уникальности имени пользователя и email
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                $errors[] = t('Пользователь с таким именем или email уже существует', 'User with this username or email already exists', 'Usuário com este nome de usuário ou email já existe', 'Un utilisateur avec ce nom d\'utilisateur ou cet email existe déjà', 'Ein Benutzer mit diesem Benutzernamen oder dieser E-Mail existiert bereits');
            }
        } catch (PDOException $e) {
            $errors[] = t('Ошибка при проверке данных', 'Error checking data', 'Erro ao verificar dados', 'Erreur lors de la vérification des données', 'Fehler beim Überprüfen der Daten');
        }
    }
    
    // Если ошибок нет, создаем пользователя
    if (empty($errors)) {
        try {
            // Хешируем пароль
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Создаем пользователя (используем правильные названия полей из вашей БД)
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    username, email, password, first_name, last_name, 
                    phone, country_of_origin, passport_number, city, 
                    user_type, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'migrant', 'pending')
            ");
            
            $result = $stmt->execute([
                $username, $email, $password_hash, $first_name, $last_name,
                $phone, $country_of_origin, $passport_number, $city
            ]);
            
            if (!$result) {
                throw new Exception(t('Не удалось создать пользователя', 'Failed to create user', 'Falha ao criar usuário', 'Échec de la création de l\'utilisateur', 'Fehler beim Erstellen des Benutzers'));
            }
            
            $user_id = $pdo->lastInsertId();
            
            // Создаем запись миграционных данных
            $stmt = $pdo->prepare("
                INSERT INTO migration_data (user_id) 
                VALUES (?)
            ");
            $stmt->execute([$user_id]);
            
            // НЕ логиним пользователя автоматически, так как статус pending
            // Вместо этого показываем сообщение об успешной регистрации
            
            $success = true;
            
        } catch (PDOException $e) {
            // Логируем ошибку
            error_log("Registration error: " . $e->getMessage());
            
            // Проверяем на дублирование
            if ($e->getCode() == 23000) {
                $errors[] = t('Пользователь с таким именем или email уже существует', 'User with this username or email already exists', 'Usuário com este nome de usuário ou email já existe', 'Un utilisateur avec ce nom d\'utilisateur ou cet email existe déjà', 'Ein Benutzer mit diesem Benutzernamen oder dieser E-Mail existiert bereits');
            } else {
                $errors[] = t('Ошибка при создании пользователя', 'Error creating user', 'Erro ao criar usuário', 'Erreur lors de la création de l\'utilisateur', 'Fehler beim Erstellen des Benutzers') . ': ' . $e->getMessage();
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// Тексты для перевода с экранированием для JavaScript
$translations = [
    'main_title' => t(
        'Регистрация - MigraSupport', 
        'Registration - MigraSupport', 
        'Registro - MigraSupport', 
        'Inscription - MigraSupport', 
        'Registrierung - MigraSupport'
    ),
    'register_title' => t(
        'Регистрация в системе', 
        'System Registration', 
        'Registro no Sistema', 
        'Inscription au Système', 
        'Systemregistrierung'
    ),
    'register_desc' => t(
        'Создайте аккаунт для доступа ко всем возможностям системы поддержки мигрантов.', 
        'Create an account to access all features of the migrant support system.', 
        'Crie uma conta para acessar todos os recursos do sistema de apoio a migrantes.', 
        'Créez un compte pour accéder à toutes les fonctionnalités du système de soutien aux migrants.', 
        'Erstellen Sie ein Konto, um auf alle Funktionen des Migrantenunterstützungssystems zuzugreifen.'
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
    'required_field' => t('*', '*', '*', '*', '*'),
    'username' => t(
        'Имя пользователя', 
        'Username', 
        'Nome de usuário', 
        'Nom d\'utilisateur', 
        'Benutzername'
    ),
    'username_help' => t(
        'От 3 до 20 символов. Можно использовать буквы, цифры и подчеркивания.', 
        '3 to 20 characters. You can use letters, numbers and underscores.', 
        '3 a 20 caracteres. Pode usar letras, números e sublinhados.', 
        '3 à 20 caractères. Vous pouvez utiliser des lettres, des chiffres et des tirets bas.', 
        '3 bis 20 Zeichen. Sie können Buchstaben, Zahlen und Unterstriche verwenden.'
    ),
    'email' => t('Email', 'Email', 'Email', 'Email', 'E-Mail'),
    'email_help' => t(
        'На этот email будет отправлено письмо для подтверждения.', 
        'A confirmation email will be sent to this address.', 
        'Um email de confirmação será enviado para este endereço.', 
        'Un email de confirmation sera envoyé à cette adresse.', 
        'Eine Bestätigungs-E-Mail wird an diese Adresse gesendet.'
    ),
    'password' => t(
        'Пароль', 
        'Password', 
        'Senha', 
        'Mot de passe', 
        'Passwort'
    ),
    'password_help' => t(
        'Не менее 8 символов', 
        'At least 8 characters', 
        'Pelo menos 8 caracteres', 
        'Au moins 8 caractères', 
        'Mindestens 8 Zeichen'
    ),
    'confirm_password' => t(
        'Подтверждение пароля', 
        'Confirm password', 
        'Confirmar senha', 
        'Confirmer le mot de passe', 
        'Passwort bestätigen'
    ),
    'first_name' => t(
        'Имя', 
        'First name', 
        'Nome', 
        'Prénom', 
        'Vorname'
    ),
    'last_name' => t(
        'Фамилия', 
        'Last name', 
        'Sobrenome', 
        'Nom de famille', 
        'Nachname'
    ),
    'phone' => t(
        'Телефон', 
        'Phone', 
        'Telefone', 
        'Téléphone', 
        'Telefon'
    ),
    'phone_help' => t(
        'Формат: +375 (XX) XXX-XX-XX (12 цифр)', 
        'Format: +375 (XX) XXX-XX-XX (12 digits)', 
        'Formato: +375 (XX) XXX-XX-XX (12 dígitos)', 
        'Format : +375 (XX) XXX-XX-XX (12 chiffres)', 
        'Format: +375 (XX) XXX-XX-XX (12 Ziffern)'
    ),
    'country_origin' => t(
        'Страна происхождения', 
        'Country of origin', 
        'País de origem', 
        'Pays d\'origine', 
        'Herkunftsland'
    ),
    'passport_number' => t(
        'Номер паспорта', 
        'Passport number', 
        'Número do passaporte', 
        'Numéro de passeport', 
        'Passnummer'
    ),
    'city_residence' => t(
        'Город проживания в Беларуси', 
        'City of residence in Belarus', 
        'Cidade de residência na Bielorrússia', 
        'Ville de résidence en Biélorussie', 
        'Wohnort in Belarus'
    ),
    'register_btn' => t(
        'Зарегистрироваться', 
        'Register', 
        'Registrar', 
        'S\'inscrire', 
        'Registrieren'
    ),
    'already_have_account' => t(
        'Уже есть аккаунт?', 
        'Already have an account?', 
        'Já tem uma conta?', 
        'Vous avez déjà un compte ?', 
        'Sie haben bereits ein Konto?'
    ),
    'login_here' => t(
        'Войти здесь', 
        'Login here', 
        'Entre aqui', 
        'Connectez-vous ici', 
        'Hier anmelden'
    ),
    'registration_success' => t(
        'Регистрация успешна!', 
        'Registration successful!', 
        'Registro bem-sucedido!', 
        'Inscription réussie !', 
        'Registrierung erfolgreich!'
    ),
    'verification_sent' => t(
        'На ваш email отправлено письмо для подтверждения.', 
        'A confirmation email has been sent to your email address.', 
        'Um email de confirmação foi enviado para seu endereço de email.', 
        'Un email de confirmation a été envoyé à votre adresse email.', 
        'Eine Bestätigungs-E-Mail wurde an Ihre E-Mail-Adresse gesendet.'
    ),
    'check_email' => t(
        'Пожалуйста, проверьте вашу почту и подтвердите email.', 
        'Please check your email and confirm your email address.', 
        'Por favor, verifique seu email e confirme seu endereço de email.', 
        'Veuillez vérifier votre email et confirmer votre adresse email.', 
        'Bitte überprüfen Sie Ihre E-Mail und bestätigen Sie Ihre E-Mail-Adresse.'
    ),
    'go_to_profile' => t(
        'Перейти в профиль', 
        'Go to profile', 
        'Ir para o perfil', 
        'Aller au profil', 
        'Zum Profil gehen'
    ),
    'registration_error' => t(
        'Ошибка регистрации', 
        'Registration error', 
        'Erro de registro', 
        'Erreur d\'inscription', 
        'Registrierungsfehler'
    ),
    'try_again' => t(
        'Пожалуйста, попробуйте еще раз', 
        'Please try again', 
        'Por favor, tente novamente', 
        'Veuillez réessayer', 
        'Bitte versuchen Sie es erneut'
    ),
    'password_strength' => t(
        'Надежность пароля', 
        'Password strength', 
        'Força da senha', 
        'Force du mot de passe', 
        'Passwortstärke'
    ),
    'weak' => t(
        'Слабый', 
        'Weak', 
        'Fraca', 
        'Faible', 
        'Schwach'
    ),
    'medium' => t(
        'Средний', 
        'Medium', 
        'Média', 
        'Moyen', 
        'Mittel'
    ),
    'strong' => t(
        'Сильный', 
        'Strong', 
        'Forte', 
        'Fort', 
        'Stark'
    ),
    'very_strong' => t(
        'Очень сильный', 
        'Very strong', 
        'Muito forte', 
        'Très fort', 
        'Sehr stark'
    ),
    'password_length' => t(
        'Длина', 
        'Length', 
        'Comprimento', 
        'Longueur', 
        'Länge'
    ),
    'characters' => t(
        'символов', 
        'characters', 
        'caracteres', 
        'caractères', 
        'Zeichen'
    ),
    'account_created' => t(
        'Аккаунт успешно создан!', 
        'Account successfully created!', 
        'Conta criada com sucesso!', 
        'Compte créé avec succès !', 
        'Konto erfolgreich erstellt!'
    ),
    'redirecting' => t(
        'Вы будете перенаправлены на страницу входа через %d секунд...', 
        'You will be redirected to login page in %d seconds...', 
        'Você será redirecionado para a página de login em %d segundos...', 
        'Vous serez redirigé vers la page de connexion dans %d secondes...', 
        'Sie werden in %d Sekunden zur Anmeldeseite weitergeleitet...'
    ),
    'immediate_redirect' => t(
        'Немедленный переход', 
        'Immediate redirect', 
        'Redirecionamento imediato', 
        'Redirection immédiate', 
        'Sofortige Weiterleitung'
    ),
    'awaiting_approval' => t(
        'Ожидание подтверждения администратором', 
        'Awaiting administrator approval', 
        'Aguardando aprovação do administrador', 
        'En attente d\'approbation de l\'administrateur', 
        'Warten auf Administratorgenehmigung'
    ),
    'approval_message' => t(
        'Ваша регистрация успешно завершена. Ваш аккаунт ожидает подтверждения администратором. После подтверждения вы сможете войти в систему.', 
        'Your registration is complete. Your account is awaiting administrator approval. After approval, you will be able to log in.', 
        'Seu registro foi concluído. Sua conta está aguardando aprovação do administrador. Após a aprovação, você poderá fazer login.', 
        'Votre inscription est terminée. Votre compte est en attente d\'approbation par l\'administrateur. Après approbation, vous pourrez vous connecter.', 
        'Ihre Registrierung ist abgeschlossen. Ihr Konto wartet auf die Genehmigung durch den Administrator. Nach der Genehmigung können Sie sich anmelden.'
    ),
    'footer_title' => t(
        'Комплексная система поддержки мигрантов в Беларуси.',
        'Comprehensive migrant support system in Belarus.',
        'Sistema abrangente de apoio a migrantes na Bielorrússia.',
        'Système complet de soutien aux migrants en Biélorussie.',
        'Umfassendes Migrantenunterstützungssystem in Belarus.'
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
    'account_credentials' => t(
        'Учетные данные', 
        'Account Credentials', 
        'Credenciais da Conta', 
        'Informations de compte', 
        'Kontoinformationen'
    ),
    'personal_information' => t(
        'Личная информация', 
        'Personal Information', 
        'Informações Pessoais', 
        'Informations personnelles', 
        'Persönliche Informationen'
    ),
    'password_requirements' => t(
        'Требования к паролю', 
        'Password requirements', 
        'Requisitos de senha', 
        'Exigences de mot de passe', 
        'Passwortanforderungen'
    ),
    'lowercase_letter' => t(
        'Строчная буква', 
        'Lowercase letter', 
        'Letra minúscula', 
        'Lettre minuscule', 
        'Kleinbuchstabe'
    ),
    'uppercase_letter' => t(
        'Заглавная буква', 
        'Uppercase letter', 
        'Letra maiúscula', 
        'Lettre majuscule', 
        'Großbuchstabe'
    ),
    'number' => t(
        'Цифра', 
        'Number', 
        'Número', 
        'Chiffre', 
        'Zahl'
    ),
    'special_character' => t(
        'Специальный символ', 
        'Special character', 
        'Caractere especial', 
        'Caractère spécial', 
        'Sonderzeichen'
    ),
    'username_available' => t(
        'Имя пользователя доступно', 
        'Username is available', 
        'Nome de usuário disponível', 
        'Nom d\'utilisateur disponible', 
        'Benutzername verfügbar'
    ),
    'username_taken' => t(
        'Имя пользователя уже занято', 
        'Username is already taken', 
        'Nome de usuário já está em uso', 
        'Nom d\'utilisateur déjà pris', 
        'Benutzername bereits vergeben'
    ),
    'email_available' => t(
        'Email доступен', 
        'Email is available', 
        'Email disponível', 
        'Email disponible', 
        'E-Mail verfügbar'
    ),
    'email_taken' => t(
        'Email уже используется', 
        'Email is already in use', 
        'Email já está em uso', 
        'Email déjà utilisé', 
        'E-Mail bereits verwendet'
    ),
    'passwords_match' => t(
        'Пароли совпадают', 
        'Passwords match', 
        'As senhas coincidem', 
        'Les mots de passe correspondent', 
        'Passwörter stimmen überein'
    ),
    'passwords_not_match' => t(
        'Пароли не совпадают', 
        'Passwords do not match', 
        'As senhas não coincidem', 
        'Les mots de passe ne correspondent pas', 
        'Passwörter stimmen nicht überein'
    ),
    'correct_phone_format' => t(
        'Корректный формат номера', 
        'Correct phone number format', 
        'Formato de telefone correto', 
        'Format de téléphone correct', 
        'Korrektes Telefonformat'
    ),
    'enter_12_digits' => t(
        'Введите 12 цифр', 
        'Enter 12 digits', 
        'Digite 12 dígitos', 
        'Entrez 12 chiffres', 
        'Geben Sie 12 Ziffern ein'
    ),
    'only_letters_numbers_underscores' => t(
        'Можно использовать только буквы, цифры и подчеркивания', 
        'Only letters, numbers and underscores allowed', 
        'Apenas letras, números e sublinhados são permitidos', 
        'Seules les lettres, les chiffres et les tirets bas sont autorisés', 
        'Nur Buchstaben, Zahlen und Unterstriche sind erlaubt'
    ),
    'select_city' => t(
        'Выберите город', 
        'Select city', 
        'Selecione a cidade', 
        'Sélectionnez la ville', 
        'Stadt auswählen'
    ),
    'select_country' => t(
        'Выберите страну', 
        'Select country', 
        'Selecione o país', 
        'Sélectionnez le pays', 
        'Land auswählen'
    ),
    'first_name_help' => t(
        'От 2 до 50 символов. Можно использовать любые буквы.', 
        '2 to 50 characters. You can use any letters.', 
        '2 a 50 caracteres. Pode usar qualquer letra.', 
        '2 à 50 caractères. Vous pouvez utiliser n\'importe quelle lettre.', 
        '2 bis 50 Zeichen. Sie können beliebige Buchstaben verwenden.'
    ),
    'last_name_help' => t(
        'От 2 до 50 символов. Можно использовать любые буквы.', 
        '2 to 50 characters. You can use any letters.', 
        '2 a 50 caracteres. Pode usar qualquer letra.', 
        '2 à 50 caractères. Vous pouvez utiliser n\'importe quelle lettre.', 
        '2 bis 50 Zeichen. Sie können beliebige Buchstaben verwenden.'
    ),
    'passport_number_help' => t(
        'От 3 до 50 символов. Можно использовать только буквы и цифры.', 
        '3 to 50 characters. You can only use letters and numbers.', 
        '3 a 50 caracteres. Você só pode usar letras e números.', 
        '3 à 50 caractères. Vous ne pouvez utiliser que des lettres et des chiffres.', 
        '3 bis 50 Zeichen. Sie können nur Buchstaben und Zahlen verwenden.'
    ),
    'country_origin_help' => t(
        'Выберите страну из списка. Максимум 100 символов.', 
        'Select country from the list. Maximum 100 characters.', 
        'Selecione o país da lista. Máximo 100 caracteres.', 
        'Sélectionnez le pays dans la liste. Maximum 100 caractères.', 
        'Wählen Sie das Land aus der Liste. Maximal 100 Zeichen.'
    ),
    'city_help' => t(
        'Выберите город проживания в Беларуси.', 
        'Select city of residence in Belarus.', 
        'Selecione a cidade de residência na Bielorrússia.', 
        'Sélectionnez la ville de résidence en Biélorussie.', 
        'Wählen Sie den Wohnort in Belarus.'
    ),
    'username_rules' => t(
        'Только английские буквы (a-z), цифры (0-9) и подчеркивания (_)', 
        'Only English letters (a-z), numbers (0-9) and underscores (_)', 
        'Apenas letras inglesas (a-z), números (0-9) e sublinhados (_)', 
        'Seules les lettres anglaises (a-z), les chiffres (0-9) et les tirets bas (_)', 
        'Nur englische Buchstaben (a-z), Zahlen (0-9) und Unterstriche (_)'
    ),
    'email_rules' => t(
        'Введите действительный email. Максимум 100 символов.', 
        'Enter a valid email address. Maximum 100 characters.', 
        'Digite um endereço de email válido. Máximo 100 caracteres.', 
        'Entrez une adresse email valide. Maximum 100 caractères.', 
        'Geben Sie eine gültige E-Mail-Adresse ein. Maximal 100 Zeichen.'
    ),
    'password_rules' => t(
        'От 8 до 100 символов. Должен содержать строчные и заглавные буквы, цифры и специальные символы.', 
        '8 to 100 characters. Must contain lowercase and uppercase letters, numbers and special characters.', 
        '8 a 100 caracteres. Deve conter letras minúsculas e maiúsculas, números e caracteres especiais.', 
        '8 à 100 caractères. Doit contenir des lettres minuscules et majuscules, des chiffres et des caractères spéciaux.', 
        '8 bis 100 Zeichen. Muss Klein- und Großbuchstaben, Zahlen und Sonderzeichen enthalten.'
    ),
    'phone_rules' => t(
        'Формат: +375 (XX) XXX-XX-XX. Только для белорусских номеров.', 
        'Format: +375 (XX) XXX-XX-XX. Only for Belarusian numbers.', 
        'Formato: +375 (XX) XXX-XX-XX. Apenas para números bielorrussos.', 
        'Format : +375 (XX) XXX-XX-XX. Uniquement pour les numéros biélorusses.', 
        'Format: +375 (XX) XXX-XX-XX. Nur für belarussische Nummern.'
    ),
    'enter_valid_email' => t(
        'Введите корректный email', 
        'Enter a valid email', 
        'Digite um email válido', 
        'Entrez un email valide', 
        'Geben Sie eine gültige E-Mail ein'
    )
];

// Экранированные версии для JavaScript
$js_translations = [];
foreach ($translations as $key => $value) {
    $js_translations[$key] = js_escape($value);
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
            transform: translateX(-10px);
        }

        .burger-menu.active .burger-line:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
            background: var(--accent);
        }

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

        /* Mobile navigation - ИСПРАВЛЕНА */
        .mobile-nav {
            display: none;
            position: fixed;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 0 0 var(--radius) var(--radius);
            box-shadow: var(--shadow-xl);
            z-index: 1000;
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
            padding: 15px;
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
            font-size: 1rem;
            width: 22px;
        }

        /* Main Content */
        main {
            padding: 40px 0;
            margin-top: 0;
        }

        .registration-container {
            max-width: 800px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease;
        }

        .registration-header {
            background: var(--gradient-primary);
            color: white;
            padding: 40px;
            text-align: center;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            position: relative;
            overflow: hidden;
        }

        .registration-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/diamond-upholstery.png');
            opacity: 0.1;
        }

        .registration-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .registration-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .registration-form {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: none;
        }

        .form-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.4s ease;
            animation-fill-mode: both;
        }

        .form-section:nth-child(2) { animation-delay: 0.1s; }
        .form-section:nth-child(3) { animation-delay: 0.2s; }
        .form-section:nth-child(4) { animation-delay: 0.3s; }

        .section-title {
            color: white;
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--accent);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
            font-size: 0.95rem;
        }

        .form-label .required {
            color: var(--danger);
            margin-left: 4px;
        }

        .form-help {
            display: block;
            margin-top: 5px;
            font-size: 0.85rem;
            color: var(--gray-light);
        }

        .info-banner {
            background: rgba(58, 134, 255, 0.1);
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 10px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: var(--gray-light);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }

        .info-banner i {
            color: var(--primary);
            font-size: 1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .info-banner.warning {
            background: rgba(255, 158, 0, 0.1);
            border-left-color: var(--warning);
        }

        .info-banner.warning i {
            color: var(--warning);
        }

        .info-banner.success {
            background: rgba(56, 176, 0, 0.1);
            border-left-color: var(--success);
        }

        .info-banner.success i {
            color: var(--success);
        }

        .info-banner.error {
            background: rgba(255, 0, 84, 0.1);
            border-left-color: var(--danger);
        }

        .info-banner.error i {
            color: var(--danger);
        }

        .field-rules {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .rule-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--gray-light);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .rule-badge i {
            font-size: 0.75rem;
        }

        .rule-badge.valid {
            background: rgba(56, 176, 0, 0.1);
            color: var(--success);
            border-color: rgba(56, 176, 0, 0.3);
        }

        .rule-badge.invalid {
            background: rgba(255, 0, 84, 0.1);
            color: #ffb8d0;
            border-color: rgba(255, 0, 84, 0.3);
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            font-family: 'Inter', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background: white;
        }

        .form-input.error {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(255, 0, 84, 0.2);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-input.success {
            border-color: var(--success);
            box-shadow: 0 0 0 3px rgba(56, 176, 0, 0.2);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-input::placeholder {
            color: #666;
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 16px;
            background-color: rgba(255, 255, 255, 0.95);
            padding-right: 45px;
        }

        .password-requirements {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            padding: 15px;
            margin-top: 10px;
            border-left: 3px solid var(--primary);
        }

        .password-requirements h4 {
            color: white;
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: var(--gray-light);
        }

        .requirement i {
            font-size: 0.8rem;
        }

        .requirement.valid {
            color: var(--success);
        }

        .requirement.invalid {
            color: var(--danger);
        }

        .password-strength-container {
            margin-top: 15px;
        }

        .strength-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .strength-labels span {
            font-size: 0.8rem;
            color: var(--gray-light);
        }

        .strength-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: var(--transition);
            background: var(--danger);
            border-radius: 3px;
        }

        .strength-fill.weak { width: 25%; background: var(--danger); }
        .strength-fill.medium { width: 50%; background: var(--warning); }
        .strength-fill.strong { width: 75%; background: #ff9e00; }
        .strength-fill.very-strong { width: 100%; background: var(--success); }

        .strength-text {
            font-size: 0.85rem;
            text-align: center;
            margin-top: 5px;
        }

        .register-button {
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
            margin: 25px 0 15px;
        }

        .register-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(58, 134, 255, 0.3);
        }

        .register-button:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .login-link {
            text-align: center;
            color: var(--gray-light);
            font-size: 0.9rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .login-link a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .success-message {
            text-align: center;
            padding: 50px;
            background: rgba(56, 176, 0, 0.1);
            border-radius: var(--radius);
            border-left: 4px solid var(--success);
            animation: fadeIn 0.5s ease;
        }

        .success-message i {
            font-size: 3rem;
            color: var(--success);
            margin-bottom: 20px;
        }

        .success-message h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .success-message p {
            color: var(--gray-light);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .warning-message {
            text-align: center;
            padding: 50px;
            background: rgba(255, 158, 0, 0.1);
            border-radius: var(--radius);
            border-left: 4px solid var(--warning);
            animation: fadeIn 0.5s ease;
        }

        .warning-message i {
            font-size: 3rem;
            color: var(--warning);
            margin-bottom: 20px;
        }

        .warning-message h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .warning-message p {
            color: var(--gray-light);
            margin-bottom: 25px;
            line-height: 1.6;
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

        .footer-section {
            animation: fadeInUp 0.8s ease;
        }

        .footer-section:nth-child(2) { animation-delay: 0.1s; }
        .footer-section:nth-child(3) { animation-delay: 0.2s; }

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

        /* Responsive */
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
            
            main {
                margin-top: 60px;
            }
            
            .mobile-nav {
                display: block;
            }
            
            .registration-header h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .registration-header {
                padding: 30px 20px;
            }

            .registration-header h1 {
                font-size: 2rem;
            }

            .registration-form {
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .section-title {
                font-size: 1.1rem;
            }
            
            .logo {
                font-size: 1.3rem;
            }
            
            .logo-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
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

            .registration-header h1 {
                font-size: 1.8rem;
            }

            .registration-header p {
                font-size: 1rem;
            }

            .form-input {
                padding: 12px 15px;
                font-size: 0.9rem;
            }

            .register-button {
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
        
        /* Loading spinner */
        .loading {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Auto-redirect styles */
        .redirect-countdown {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-light);
            margin: 20px 0;
            animation: pulse 1s infinite;
        }
        
        .redirect-button {
            display: none;
        }

        /* ===== UNIVERSAL MOBILE FIXES (auto-patched) ===== */

        /* Предотвращаем горизонтальный скролл */
        html, body { max-width: 100%; overflow-x: hidden; }

        /* Фикс backdrop-filter на старых Android */
        @supports not (backdrop-filter: blur(1px)) {
            header, .header-nav, .mobile-nav, .card, footer {
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }
            header { background: rgba(26, 26, 46, 0.98) !important; }
            .mobile-nav { background: rgba(26, 26, 46, 0.99) !important; }
            footer { background: rgba(13, 13, 23, 0.99) !important; }
        }

        /* Скрываем логотип-текст на очень маленьких экранах */
        @media (max-width: 420px) {
            .logo-text { display: none; }
        }

        @media (max-width: 768px) {
            /* Хедер — одна строка, без переноса */
            .header-top {
                flex-wrap: nowrap !important;
                gap: 8px !important;
                padding: 0.75rem 0 !important;
            }
            .logo { font-size: 1.2rem !important; }
            .logo-icon { width: 35px !important; height: 35px !important; font-size: 1rem !important; }
            .user-avatar { width: 35px !important; height: 35px !important; font-size: 0.9rem !important; }
            .header-right { gap: 8px !important; }
            /* Языковые кнопки компактнее */
            .language-selector { flex-wrap: nowrap !important; gap: 3px !important; }
            .lang-btn { padding: 6px 7px !important; font-size: 0.72rem !important; min-width: 36px !important; }
            /* Кнопки */
            .btn { padding: 8px 14px !important; font-size: 0.8rem !important; }
            /* Карточки */
            .card { padding: 20px !important; }
        }

        @media (max-width: 480px) {
            .lang-btn { padding: 5px 5px !important; font-size: 0.68rem !important; min-width: 30px !important; }
            .dropdown-menu { right: -10px !important; min-width: 160px !important; }
        }

        /* iOS Safari sticky fix */
        @supports (-webkit-touch-callout: none) {
            header { position: -webkit-sticky; position: sticky; }
        }

        /* Touch: убираем hover-transform для производительности */
        @media (hover: none) and (pointer: coarse) {
            .card:hover, .service-card:hover, .service-card-4:hover,
            .service-slide:hover, .mission-section:hover, .quick-help-section:hover {
                transform: none !important;
            }
        }
        /* ===== END MOBILE FIXES ===== */
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
                    <li class="nav-tab">
                        <a href="converter.php" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="login.php" class="nav-link">
                            <i class="fas fa-sign-in-alt"></i> <?php echo $translations['login_nav']; ?>
                        </a>
                    </li>
                    <li class="nav-tab active">
                        <a href="register.php" class="nav-link">
                            <i class="fas fa-user-plus"></i> <?php echo $translations['register_nav']; ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Мобильная навигация - ИСПРАВЛЕНА -->
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
                    <li class="mobile-nav-tab">
                        <a href="login.php" class="mobile-nav-link">
                            <i class="fas fa-sign-in-alt"></i> <?php echo $translations['login_nav']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab active">
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
        <div class="registration-container">
            <?php if ($success): ?>
                <!-- Сообщение об успешной регистрации с ожиданием подтверждения -->
                <div class="warning-message">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $translations['awaiting_approval']; ?></h3>
                    <p><?php echo $translations['approval_message']; ?></p>
                    <div class="redirect-countdown" id="countdown">10</div>
                </div>
                <script>
                    // Автоматический редирект на главную через 10 секунд
                    let seconds = 10;
                    const countdownEl = document.getElementById('countdown');
                    const countdown = setInterval(function() {
                        seconds--;
                        countdownEl.textContent = seconds;
                        if (seconds <= 0) {
                            clearInterval(countdown);
                            window.location.href = 'index.php';
                        }
                    }, 1000);
                </script>
            <?php else: ?>
                <!-- Форма регистрации -->
                <div class="registration-header">
                    <h1><?php echo $translations['register_title']; ?></h1>
                    <p><?php echo $translations['register_desc']; ?></p>
                </div>

                <form method="POST" action="" class="registration-form" id="registrationForm" onsubmit="return validateForm()">
                    <?php if (!empty($errors)): ?>
                        <div class="error-list">
                            <h4><i class="fas fa-exclamation-circle"></i> <?php echo $translations['registration_error']; ?></h4>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Секция учетных данных -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-user-lock"></i> <?php echo $translations['account_credentials']; ?>
                        </h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $translations['username']; ?>
                                    <span class="required"><?php echo $translations['required_field']; ?></span>
                                </label>
                                <input type="text" 
                                       name="username" 
                                       class="form-input <?php echo isset($_POST['username']) && empty($_POST['username']) ? 'error' : ''; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       required
                                       minlength="3"
                                       maxlength="20"
                                       oninput="checkUsernameAvailability(this.value)">
                                <span class="form-help"><?php echo $translations['username_help']; ?></span>
                                <div id="username-availability" style="font-size: 0.85rem; margin-top: 5px;"></div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    Email
                                    <span class="required"><?php echo $translations['required_field']; ?></span>
                                </label>
                                <input type="email" 
                                       name="email" 
                                       class="form-input <?php echo isset($_POST['email']) && (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || empty($_POST['email'])) ? 'error' : ''; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required
                                       maxlength="100"
                                       oninput="checkEmailAvailability(this.value)">
                                <span class="form-help"><?php echo $translations['email_help']; ?></span>
                                <div id="email-availability" style="font-size: 0.85rem; margin-top: 5px;"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $translations['password']; ?>
                                    <span class="required"><?php echo $translations['required_field']; ?></span>
                                </label>
                                <input type="password" 
                                       name="password" 
                                       class="form-input" 
                                       id="password"
                                       required
                                       minlength="8"
                                       maxlength="100"
                                       oninput="checkPasswordStrength(this.value)">
                                <span class="form-help"><?php echo $translations['password_help']; ?></span>
                                
                                <div class="password-requirements">
                                    <h4><i class="fas fa-shield-alt"></i> <?php echo $translations['password_requirements']; ?></h4>
                                    <div class="requirement" id="req-length">
                                        <i class="fas fa-circle" id="req-length-icon"></i>
                                        <span id="req-length-text"><?php echo $translations['password_length']; ?>: 0/8 <?php echo $translations['characters']; ?></span>
                                    </div>
                                    <div class="requirement" id="req-lowercase">
                                        <i class="fas fa-circle" id="req-lowercase-icon"></i>
                                        <span><?php echo $translations['lowercase_letter']; ?></span>
                                    </div>
                                    <div class="requirement" id="req-uppercase">
                                        <i class="fas fa-circle" id="req-uppercase-icon"></i>
                                        <span><?php echo $translations['uppercase_letter']; ?></span>
                                    </div>
                                    <div class="requirement" id="req-number">
                                        <i class="fas fa-circle" id="req-number-icon"></i>
                                        <span><?php echo $translations['number']; ?></span>
                                    </div>
                                    <div class="requirement" id="req-special">
                                        <i class="fas fa-circle" id="req-special-icon"></i>
                                        <span><?php echo $translations['special_character']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="password-strength-container">
                                    <div class="strength-labels">
                                        <span><?php echo $translations['weak']; ?></span>
                                        <span><?php echo $translations['medium']; ?></span>
                                        <span><?php echo $translations['strong']; ?></span>
                                        <span><?php echo $translations['very_strong']; ?></span>
                                    </div>
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="password-strength-fill"></div>
                                    </div>
                                    <div class="strength-text" id="password-strength-text"><?php echo $translations['password_strength']; ?></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $translations['confirm_password']; ?>
                                    <span class="required"><?php echo $translations['required_field']; ?></span>
                                </label>
                                <input type="password" 
                                       name="confirm_password" 
                                       class="form-input" 
                                       id="confirm_password"
                                       required
                                       minlength="8"
                                       maxlength="100"
                                       oninput="checkPasswordMatch()">
                                <span class="form-help"></span>
                                <div id="password-match" style="font-size: 0.85rem; margin-top: 5px; margin-bottom: 10px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Секция личной информации -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-user-circle"></i> <?php echo $translations['personal_information']; ?>
                        </h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $translations['first_name']; ?>
                                    <span class="required"><?php echo $translations['required_field']; ?></span>
                                </label>
                                <input type="text" 
                                       name="first_name" 
                                       class="form-input <?php echo isset($_POST['first_name']) && empty($_POST['first_name']) ? 'error' : ''; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                       required
                                       minlength="2"
                                       maxlength="50">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $translations['last_name']; ?>
                                    <span class="required"><?php echo $translations['required_field']; ?></span>
                                </label>
                                <input type="text" 
                                       name="last_name" 
                                       class="form-input <?php echo isset($_POST['last_name']) && empty($_POST['last_name']) ? 'error' : ''; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                       required
                                       minlength="2"
                                       maxlength="50">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $translations['phone']; ?>
                                </label>
                                <input type="tel" 
                                       name="phone" 
                                       class="form-input" 
                                       id="phone"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       placeholder="+375 (XX) XXX-XX-XX"
                                       maxlength="20"
                                       oninput="formatPhoneNumber(this)">
                                <span class="form-help"><?php echo $translations['phone_help']; ?></span>
                                <div id="phone-format" style="font-size: 0.85rem; margin-top: 5px; margin-bottom: 10px;"></div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $translations['country_origin']; ?>
                                    <span class="required"><?php echo $translations['required_field']; ?></span>
                                </label>
                                <select name="country_of_origin" 
                                       class="form-input <?php echo isset($_POST['country_of_origin']) && empty($_POST['country_of_origin']) ? 'error' : ''; ?>" 
                                       required>
                                    <option value=""><?php echo $translations['select_country'] ?? 'Выберите страну'; ?></option>
                                    <?php 
                                    require_once 'countries.php';
                                    echo getCountriesSelectOptions($_POST['country_of_origin'] ?? '');
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $translations['passport_number']; ?>
                                    <span class="required"><?php echo $translations['required_field']; ?></span>
                                </label>
                                <input type="text" 
                                       name="passport_number" 
                                       class="form-input <?php echo isset($_POST['passport_number']) && empty($_POST['passport_number']) ? 'error' : ''; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['passport_number'] ?? ''); ?>"
                                       required
                                       minlength="3"
                                       maxlength="50"
                                       pattern="[A-Za-z0-9]+"
                                       title="<?php echo $translations['passport_number_help']; ?>"
                                       oninput="validatePassportNumber(this)">
                                <span class="form-help"><?php echo $translations['passport_number_help']; ?></span>
                                <div id="passport-validation" style="font-size: 0.85rem; margin-top: 5px;"></div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $translations['city_residence']; ?>
                                    <span class="required"><?php echo $translations['required_field']; ?></span>
                                </label>
                                <select name="city" 
                                        class="form-input <?php echo isset($_POST['city']) && empty($_POST['city']) ? 'error' : ''; ?>"
                                        required>
                                    <option value=""><?php echo $translations['select_city']; ?></option>
                                    <?php foreach ($cities as $code => $name): ?>
                                        <option value="<?php echo $code; ?>" <?php echo ($_POST['city'] ?? '') === $code ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопки формы -->
                    <div class="form-actions">
                        <button type="submit" class="register-button" id="registerButton">
                            <i class="fas fa-user-plus"></i>
                            <span class="button-text"><?php echo $translations['register_btn']; ?></span>
                            <div class="loading" id="registerLoading" style="display: none;"></div>
                        </button>
                        
                        <div class="login-link">
                            <?php echo $translations['already_have_account']; ?>
                            <a href="login.php"><?php echo $translations['login_here']; ?></a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
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

        // Экранированные переводы для JavaScript
        const translations = {
            <?php foreach ($js_translations as $key => $value): ?>
            '<?php echo $key; ?>': '<?php echo $value; ?>',
            <?php endforeach; ?>
        };

        // Исправленная функция форматирования номера телефона
        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, '');
            
            // Добавляем код страны если нужно
            if (value.startsWith('80')) {
                value = '375' + value.substring(2);
            }
            
            // Ограничиваем максимальную длину (12 цифр для Беларуси)
            value = value.substring(0, 12);
            
            let formatted = '';
            
            if (value.length > 0) {
                formatted = '+' + value.substring(0, 3);
            }
            if (value.length > 3) {
                formatted += ' (' + value.substring(3, 5);
            }
            if (value.length > 5) {
                formatted += ') ' + value.substring(5, 8);
            }
            if (value.length > 8) {
                formatted += '-' + value.substring(8, 10);
            }
            if (value.length > 10) {
                formatted += '-' + value.substring(10, 12);
            }
            
            input.value = formatted;
            
            // Показываем информацию о формате
            const phoneFormatEl = document.getElementById('phone-format');
            if (value.length === 12) {
                phoneFormatEl.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> ' + translations.correct_phone_format;
            } else if (value.length > 0) {
                phoneFormatEl.innerHTML = '<i class="fas fa-info-circle" style="color: var(--warning);"></i> ' + translations.enter_12_digits + ': ' + value.length + '/12';
            } else {
                phoneFormatEl.innerHTML = '';
            }
        }

        // Функция валидации номера паспорта
        function validatePassportNumber(input) {
            const value = input.value;
            const regex = /^[A-Za-z0-9]*$/;
            const validationEl = document.getElementById('passport-validation');
            
            if (value.length > 0 && !regex.test(value)) {
                input.value = value.replace(/[^A-Za-z0-9]/g, '');
                validationEl.innerHTML = '<i class="fas fa-times-circle" style="color: var(--danger);"></i> ' + translations.passport_number_help;
            } else if (value.length > 0) {
                validationEl.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> ' + translations.passport_number_help;
            } else {
                validationEl.innerHTML = '';
            }
        }

        // Проверка доступности имени пользователя
        function checkUsernameAvailability(username) {
            if (username.length < 3) {
                document.getElementById('username-availability').innerHTML = '';
                return;
            }
            
            const usernameRegex = /^[a-zA-Z0-9_]+$/;
            if (!usernameRegex.test(username)) {
                document.getElementById('username-availability').innerHTML = 
                    '<i class="fas fa-times-circle" style="color: var(--danger);"></i> ' + translations.only_letters_numbers_underscores;
                return;
            }
            
            // AJAX запрос для проверки доступности
            fetch(`check_availability.php?field=username&value=${encodeURIComponent(username)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const availabilityEl = document.getElementById('username-availability');
                    if (data.available) {
                        availabilityEl.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> ' + translations.username_available;
                    } else {
                        availabilityEl.innerHTML = '<i class="fas fa-times-circle" style="color: var(--danger);"></i> ' + translations.username_taken;
                    }
                })
                .catch(error => {
                    console.error('Error checking username availability:', error);
                });
        }

        // Проверка доступности email
        function checkEmailAvailability(email) {
            if (!email.includes('@') || email.length < 3) {
                document.getElementById('email-availability').innerHTML = '';
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                document.getElementById('email-availability').innerHTML = 
                    '<i class="fas fa-times-circle" style="color: var(--danger);"></i> ' + translations.enter_valid_email;
                return;
            }
            
            // AJAX запрос для проверки доступности
            fetch(`check_availability.php?field=email&value=${encodeURIComponent(email)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const availabilityEl = document.getElementById('email-availability');
                    if (data.available) {
                        availabilityEl.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> ' + translations.email_available;
                    } else {
                        availabilityEl.innerHTML = '<i class="fas fa-times-circle" style="color: var(--danger);"></i> ' + translations.email_taken;
                    }
                })
                .catch(error => {
                    console.error('Error checking email availability:', error);
                });
        }

        // Проверка надежности пароля
        function checkPasswordStrength(password) {
            const fill = document.getElementById('password-strength-fill');
            const text = document.getElementById('password-strength-text');
            
            // Проверяем требования
            const hasLength = password.length >= 8;
            const hasLower = /[a-z]/.test(password);
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^a-zA-Z0-9]/.test(password);
            
            // Обновляем иконки требований
            updateRequirement('length', hasLength, translations.password_length + ': ' + password.length + '/8 ' + translations.characters);
            updateRequirement('lowercase', hasLower);
            updateRequirement('uppercase', hasUpper);
            updateRequirement('number', hasNumber);
            updateRequirement('special', hasSpecial);
            
            // Считаем силу пароля
            let strength = 0;
            if (hasLength) strength += 20;
            if (hasLower) strength += 20;
            if (hasUpper) strength += 20;
            if (hasNumber) strength += 20;
            if (hasSpecial) strength += 20;
            
            // Обновляем индикатор
            if (strength < 40) {
                fill.className = 'strength-fill weak';
                text.textContent = translations.weak;
                text.style.color = 'var(--danger)';
            } else if (strength < 60) {
                fill.className = 'strength-fill medium';
                text.textContent = translations.medium;
                text.style.color = 'var(--warning)';
            } else if (strength < 80) {
                fill.className = 'strength-fill strong';
                text.textContent = translations.strong;
                text.style.color = '#ff9e00';
            } else {
                fill.className = 'strength-fill very-strong';
                text.textContent = translations.very_strong;
                text.style.color = 'var(--success)';
            }
            
            fill.style.width = strength + '%';
            
            checkPasswordMatch();
        }

        function updateRequirement(type, isValid, customText = null) {
            const icon = document.getElementById(`req-${type}-icon`);
            const text = document.getElementById(`req-${type}-text`);
            const requirement = document.getElementById(`req-${type}`);
            
            if (isValid) {
                icon.className = 'fas fa-check-circle';
                icon.style.color = 'var(--success)';
                requirement.className = 'requirement valid';
            } else {
                icon.className = 'fas fa-times-circle';
                icon.style.color = 'var(--danger)';
                requirement.className = 'requirement invalid';
            }
            
            if (customText && text) {
                text.textContent = customText;
            }
        }

        // Проверка совпадения паролей
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchEl = document.getElementById('password-match');
            
            if (!confirmPassword) {
                matchEl.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchEl.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> ' + translations.passwords_match;
            } else {
                matchEl.innerHTML = '<i class="fas fa-times-circle" style="color: var(--danger);"></i> ' + translations.passwords_not_match;
            }
        }

        // Валидация всей формы перед отправкой
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const phone = document.getElementById('phone').value;
            const passportNumber = document.querySelector('input[name="passport_number"]').value;
            const passportRegex = /^[A-Za-z0-9]+$/;
            
            // Проверяем пароли
            if (password !== confirmPassword) {
                alert(translations.passwords_not_match);
                return false;
            }
            
            // Проверяем длину пароля
            if (password.length < 8) {
                alert('Пароль должен быть не менее 8 символов');
                return false;
            }
            
            // Проверяем номер паспорта
            if (passportNumber && !passportRegex.test(passportNumber)) {
                alert(translations.passport_number_help);
                return false;
            }
            
            // Проверяем номер телефона если он введен
            if (phone) {
                const phoneDigits = phone.replace(/\D/g, '');
                if (phoneDigits.length !== 12) {
                    alert(translations.enter_12_digits);
                    return false;
                }
                
                if (!phoneDigits.startsWith('375')) {
                    alert('Номер телефона должен начинаться с +375');
                    return false;
                }
            }
            
            // Показываем индикатор загрузки
            const submitButton = document.getElementById('registerButton');
            const buttonText = submitButton.querySelector('.button-text');
            const loadingSpinner = document.getElementById('registerLoading');
            
            buttonText.style.display = 'none';
            loadingSpinner.style.display = 'inline-block';
            submitButton.disabled = true;
            
            return true;
        }

        // Инициализация при загрузке - ИСПРАВЛЕНА
        document.addEventListener('DOMContentLoaded', function() {
            // Динамическая позиция мобильной навигации
            var mobileNavEl = document.getElementById('mobileNav');
            var headerEl = document.querySelector('header');
            
            if (mobileNavEl && headerEl) {
                function updateMobileNavPosition() {
                    mobileNavEl.style.top = headerEl.offsetHeight + 'px';
                }
                
                function closeMobileMenu() {
                    var burgerMenu = document.getElementById('burgerMenu');
                    if (burgerMenu) burgerMenu.classList.remove('active');
                    mobileNavEl.classList.remove('active');
                    document.body.style.overflow = '';
                }
                
                function toggleMobileMenu(e) {
                    if (e) e.stopPropagation();
                    var burgerMenu = document.getElementById('burgerMenu');
                    if (burgerMenu) {
                        burgerMenu.classList.toggle('active');
                        mobileNavEl.classList.toggle('active');
                        
                        if (mobileNavEl.classList.contains('active')) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    }
                }
                
                // Устанавливаем начальную позицию
                updateMobileNavPosition();
                
                // Обновляем позицию при изменении размера окна
                window.addEventListener('resize', updateMobileNavPosition);
                
                // Закрываем меню при изменении ориентации
                window.addEventListener('orientationchange', function() {
                    closeMobileMenu();
                    setTimeout(updateMobileNavPosition, 100);
                });
                
                // Обработчик клика по бургер-меню
                var burgerMenu = document.getElementById('burgerMenu');
                if (burgerMenu) {
                    burgerMenu.addEventListener('click', toggleMobileMenu);
                }
                
                // Закрытие при клике вне меню
                document.addEventListener('click', function(event) {
                    if (mobileNavEl.classList.contains('active')) {
                        var burgerMenu = document.getElementById('burgerMenu');
                        if (!burgerMenu.contains(event.target) && !mobileNavEl.contains(event.target)) {
                            closeMobileMenu();
                        }
                    }
                });
                
                // Предотвращаем закрытие при клике внутри меню
                mobileNavEl.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                
                // Закрытие при клике на ссылку в меню
                document.querySelectorAll('.mobile-nav-link').forEach(function(link) {
                    link.addEventListener('click', closeMobileMenu);
                });
            }

            // Проверяем поля при загрузке
            const username = document.querySelector('input[name="username"]');
            const email = document.querySelector('input[name="email"]');
            const password = document.getElementById('password');
            const phone = document.getElementById('phone');
            const passportNumber = document.querySelector('input[name="passport_number"]');
            
            if (username && username.value) {
                checkUsernameAvailability(username.value);
            }
            
            if (email && email.value) {
                checkEmailAvailability(email.value);
            }
            
            if (password && password.value) {
                checkPasswordStrength(password.value);
            }
            
            if (phone && phone.value) {
                formatPhoneNumber(phone);
            }
            
            if (passportNumber && passportNumber.value) {
                validatePassportNumber(passportNumber);
            }
        });
    </script>
</body>
</html>