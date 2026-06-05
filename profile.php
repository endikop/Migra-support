<?php
// Самая первая операция - буферизация вывода
ob_start();

// Затем стартуем сессию
session_start();

// Подключаем конфигурацию
require_once 'config.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    ob_end_clean();
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

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

// Тексты для перевода
$translations = [
    'title' => t(
        'Мой профиль - MigraSupport',
        'My Profile - MigraSupport',
        'Meu Perfil - MigraSupport',
        'Mon Profil - MigraSupport',
        'Mein Profil - MigraSupport'
    ),
    'profile_title' => t(
        'Мой профиль',
        'My Profile',
        'Meu Perfil',
        'Mon Profil',
        'Mein Profil'
    ),
    'profile_desc' => t(
        'Управление вашим профилем и миграционными данными',
        'Manage your profile and migration data',
        'Gerencie seu perfil e dados de migração',
        'Gérez votre profil et vos données de migration',
        'Verwalten Sie Ihr Profil und Ihre Migrationsdaten'
    ),
    'home' => t('Главная', 'Home', 'Início', 'Accueil', 'Startseite'),
    'information' => t('Информация', 'Information', 'Informação', 'Information', 'Informationen'),
    'map_services' => t('Карта служб', 'Services Map', 'Mapa de Serviços', 'Carte des Services', 'Dienstleistungskarte'),
    'translator' => t('Переводчик', 'Translator', 'Tradutor', 'Traducteur', 'Übersetzer'),
    'currency_converter' => t('Конвертер валют', 'Currency Converter', 'Conversor de Moeda', 'Convertisseur de Devises', 'Währungsrechner'),
    'city_chat' => t('Чат города', 'City Chat', 'Chat da Cidade', 'Chat de la Ville', 'Stadt-Chat'),
    'login_nav' => t('Вход', 'Login', 'Entrar', 'Connexion', 'Anmelden'),
    'register_nav' => t('Регистрация', 'Register', 'Registrar', 'Inscription', 'Registrieren'),
    'logout' => t('Выйти', 'Logout', 'Sair', 'Déconnexion', 'Abmelden'),
    'admin_panel' => t('Админ', 'Admin', 'Admin', 'Admin', 'Admin'),
    'profile' => t('Профиль', 'Profile', 'Perfil', 'Profil', 'Profil'),
    'personal_info' => t(
        'Личная информация',
        'Personal Information',
        'Informações Pessoais',
        'Informations Personnelles',
        'Persönliche Informationen'
    ),
    'migration_data' => t(
        'Миграционные данные',
        'Migration Data',
        'Dados de Migração',
        'Données de Migration',
        'Migrationsdaten'
    ),
    'account_settings' => t(
        'Настройки аккаунта',
        'Account Settings',
        'Configurações da Conta',
        'Paramètres du Compte',
        'Kontoeinstellungen'
    ),
    'first_name' => t('Имя', 'First Name', 'Nome', 'Prénom', 'Vorname'),
    'last_name' => t('Фамилия', 'Last Name', 'Sobrenome', 'Nom', 'Nachname'),
    'email' => t('Email', 'Email', 'E-mail', 'E-mail', 'E-Mail'),
    'phone' => t('Телефон', 'Phone', 'Telefone', 'Téléphone', 'Telefon'),
    'country_origin' => t(
        'Страна происхождения',
        'Country of Origin',
        'País de Origem',
        'Pays d\'Origine',
        'Herkunftsland'
    ),
    'passport_number' => t(
        'Номер паспорта',
        'Passport Number',
        'Número do Passaporte',
        'Numéro de Passeport',
        'Passnummer'
    ),
    'city_residence' => t(
        'Город проживания',
        'City of Residence',
        'Cidade de Residência',
        'Ville de Résidence',
        'Wohnort'
    ),
    'account_status' => t(
        'Статус аккаунта',
        'Account Status',
        'Status da Conta',
        'Statut du Compte',
        'Kontostatus'
    ),
    'registered' => t(
        'Зарегистрирован',
        'Registered',
        'Registrado',
        'Inscrit',
        'Registriert'
    ),
    'visa_type' => t('Тип визы', 'Visa Type', 'Tipo de Visto', 'Type de Visa', 'Visumtyp'),
    'visa_number' => t('Номер визы', 'Visa Number', 'Número do Visto', 'Numéro de Visa', 'Visumnummer'),
    'visa_expiry' => t(
        'Срок действия визы',
        'Visa Expiry Date',
        'Data de Expiração do Visto',
        'Date d\'Expiration du Visa',
        'Visumablaufdatum'
    ),
    'employer' => t('Работодатель', 'Employer', 'Empregador', 'Employeur', 'Arbeitgeber'),
    'arrival_date' => t('Дата прибытия', 'Arrival Date', 'Data de Chegada', 'Date d\'Arrivée', 'Ankunftsdatum'),
    'registration_date' => t(
        'Дата регистрации',
        'Registration Date',
        'Data de Registro',
        'Date d\'Enregistrement',
        'Registrierungsdatum'
    ),
    'current_password' => t(
        'Текущий пароль',
        'Current Password',
        'Senha Atual',
        'Mot de Passe Actuel',
        'Aktuelles Passwort'
    ),
    'new_password' => t(
        'Новый пароль',
        'New Password',
        'Nova Senha',
        'Nouveau Mot de Passe',
        'Neues Passwort'
    ),
    'confirm_password' => t(
        'Подтвердите пароль',
        'Confirm Password',
        'Confirmar Senha',
        'Confirmer le Mot de Passe',
        'Passwort Bestätigen'
    ),
    'update_profile' => t(
        'Обновить профиль',
        'Update Profile',
        'Atualizar Perfil',
        'Mettre à Jour le Profil',
        'Profil Aktualisieren'
    ),
    'change_password' => t(
        'Сменить пароль',
        'Change Password',
        'Alterar Senha',
        'Changer le Mot de Passe',
        'Passwort Ändern'
    ),
    'update_migration' => t(
        'Обновить данные',
        'Update Data',
        'Atualizar Dados',
        'Mettre à Jour les Données',
        'Daten Aktualisieren'
    ),
    'required_field' => t('*', '*', '*', '*', '*'),
    'select_city' => t('Выберите город', 'Select City', 'Selecione a Cidade', 'Sélectionnez la Ville', 'Stadt Auswählen'),
    'select_visa' => t(
        'Выберите тип визы',
        'Select visa type',
        'Selecione o tipo de visto',
        'Sélectionnez le type de visa',
        'Visumtyp auswählen'
    ),
    'select_date' => t('Выберите дату', 'Select date', 'Selecione a data', 'Sélectionnez la date', 'Datum auswählen'),
    'active' => t('Активен', 'Active', 'Ativo', 'Actif', 'Aktiv'),
    'pending' => t('Ожидает подтверждения', 'Pending', 'Pendente', 'En Attente', 'Ausstehend'),
    'verified' => t('Подтвержден', 'Verified', 'Verificado', 'Vérifié', 'Verifiziert'),
    'banned' => t('Заблокирован', 'Banned', 'Banido', 'Banni', 'Gesperrt'),
    'delete_account' => t('Удалить аккаунт', 'Delete Account', 'Excluir Conta', 'Supprimer le Compte', 'Konto Löschen'),
    'delete_warning' => t(
        'Внимание: Удаление аккаунта необратимо. Все ваши данные будут удалены.',
        'Warning: Account deletion is irreversible. All your data will be deleted.',
        'Aviso: A exclusão da conta é irreversível. Todos os seus dados serão excluídos.',
        'Attention : La suppression du compte est irréversible. Toutes vos données seront supprimées.',
        'Warnung: Die Kontolöschung ist unwiderruflich. Alle Ihre Daten werden gelöscht.'
    ),
    'confirm_delete' => t(
        'Вы уверены, что хотите удалить аккаунт?',
        'Are you sure you want to delete your account?',
        'Tem certeza de que deseja excluir sua conta?',
        'Êtes-vous sûr de vouloir supprimer votre compte ?',
        'Sind Sie sicher, dass Sie Ihr Konto löschen möchten?'
    ),
    'cancel' => t('Отмена', 'Cancel', 'Cancelar', 'Annuler', 'Abbrechen'),
    'delete' => t('Удалить', 'Delete', 'Excluir', 'Supprimer', 'Löschen'),
    'purpose_of_stay' => t(
        'Цель пребывания',
        'Purpose of Stay',
        'Propósito da Estadia',
        'But du Séjour',
        'Aufenthaltszweck'
    ),
    'admin_chat' => t(
        'Чат с администрацией',
        'Chat with Administration',
        'Chat com a Administração',
        'Chat avec l\'Administration',
        'Chat mit der Verwaltung'
    ),
    'admin_chat_desc' => t(
        'Задавайте вопросы администрации системы. Ваши сообщения видны только администраторам.',
        'Ask questions to system administration. Your messages are visible only to administrators.',
        'Faça perguntas à administração do sistema. Suas mensagens são visíveis apenas para administradores.',
        'Posez des questions à l\'administration du système. Vos messages ne sont visibles que par les administrateurs.',
        'Stellen Sie Fragen an die Systemverwaltung. Ihre Nachrichten sind nur für Administratoren sichtbar.'
    ),
    'type_message' => t(
        'Введите сообщение...',
        'Type a message...',
        'Digite uma mensagem...',
        'Tapez un message...',
        'Nachricht eingeben...'
    ),
    'send_message' => t(
        'Отправить сообщение',
        'Send Message',
        'Enviar Mensagem',
        'Envoyer le Message',
        'Nachricht Senden'
    ),
    'loading_messages' => t(
        'Загрузка сообщений...',
        'Loading messages...',
        'Carregando mensagens...',
        'Chargement des messages...',
        'Nachrichten werden geladen...'
    ),
    'no_messages' => t(
        'Нет сообщений. Начните общение!',
        'No messages. Start conversation!',
        'Sem mensagens. Inicie uma conversa!',
        'Pas de messages. Commencez la conversation !',
        'Keine Nachrichten. Beginnen Sie ein Gespräch!'
    ),
    'system' => t('Система', 'System', 'Sistema', 'Système', 'System'),
    'administrator' => t('Администратор', 'Administrator', 'Administrador', 'Administrateur', 'Administrator'),
    'you' => t('Вы', 'You', 'Você', 'Vous', 'Sie'),
    'today' => t('Сегодня', 'Today', 'Hoje', 'Aujourd\'hui', 'Heute'),
    'yesterday' => t('Вчера', 'Yesterday', 'Ontem', 'Hier', 'Gestern'),
    'new_messages' => t(
        'Новые сообщения',
        'New messages',
        'Novas mensagens',
        'Nouveaux messages',
        'Neue Nachrichten'
    ),
    'mark_as_read' => t(
        'Отметить как прочитанные',
        'Mark as read',
        'Marcar como lidas',
        'Marquer comme lu',
        'Als gelesen markieren'
    ),
    'admin_online' => t(
        'Администратор онлайн',
        'Admin online',
        'Administrador online',
        'Admin en ligne',
        'Admin online'
    ),
    'admin_offline' => t(
        'Администратор не в сети',
        'Admin offline',
        'Administrador offline',
        'Admin hors ligne',
        'Admin offline'
    ),
    'response_time' => t(
        'Среднее время ответа: 2 часа',
        'Average response time: 2 hours',
        'Tempo médio de resposta: 2 horas',
        'Temps de réponse moyen : 2 heures',
        'Durchschnittliche Antwortzeit: 2 Stunden'
    ),
    'working_hours' => t(
        'Часы работы: Пн-Пт 9:00-18:00',
        'Working hours: Mon-Fri 9:00-18:00',
        'Horário de funcionamento: Seg-Sex 9:00-18:00',
        'Heures d\'ouverture : Lun-Ven 9:00-18:00',
        'Arbeitszeiten: Mo-Fr 9:00-18:00'
    ),
    'quick_questions' => t(
        'Быстрые вопросы',
        'Quick Questions',
        'Perguntas Rápidas',
        'Questions Rapides',
        'Schnelle Fragen'
    ),
    'question_visa' => t(
        'Вопрос по визе',
        'Visa question',
        'Pergunta sobre visto',
        'Question sur le visa',
        'Visumfrage'
    ),
    'question_documents' => t(
        'Вопрос по документам',
        'Documents question',
        'Pergunta sobre documentos',
        'Question sur les documents',
        'Dokumentenfrage'
    ),
    'question_registration' => t(
        'Вопрос по регистрации',
        'Registration question',
        'Pergunta sobre registro',
        'Question sur l\'enregistrement',
        'Registrierungsfrage'
    ),
    'question_other' => t(
        'Другой вопрос',
        'Other question',
        'Outra pergunta',
        'Autre question',
        'Andere Frage'
    ),
    'clear_chat' => t(
        'Очистить чат',
        'Clear Chat',
        'Limpar Chat',
        'Effacer le Chat',
        'Chat Löschen'
    ),
    'attach_file' => t(
        'Прикрепить файл',
        'Attach File',
        'Anexar Arquivo',
        'Joindre un Fichier',
        'Datei Anhängen'
    ),
    'message_sent' => t(
        'Сообщение отправлено',
        'Message sent',
        'Mensagem enviada',
        'Message envoyé',
        'Nachricht gesendet'
    ),
    'error_empty_message' => t(
        'Сообщение не может быть пустым',
        'Message cannot be empty',
        'A mensagem não pode estar vazia',
        'Le message ne peut pas être vide',
        'Nachricht darf nicht leer sein'
    ),
    'error_sending' => t(
        'Ошибка отправки',
        'Error sending',
        'Erro ao enviar',
        'Erreur d\'envoi',
        'Fehler beim Senden'
    ),
    'edit_information' => t(
        'Редактировать информацию',
        'Edit information',
        'Editar informações',
        'Modifier les informations',
        'Informationen bearbeiten'
    ),
    'edit_data' => t(
        'Редактировать данные',
        'Edit data',
        'Editar dados',
        'Modifier les données',
        'Daten bearbeiten'
    ),
    'no_data' => t(
        'Нет данных',
        'No data',
        'Sem dados',
        'Pas de données',
        'Keine Daten'
    ),
    'fill_migration_data' => t(
        'Заполните ваши миграционные данные ниже.',
        'Fill in your migration data below.',
        'Preencha seus dados de migração abaixo.',
        'Remplissez vos données de migration ci-dessous.',
        'Füllen Sie unten Ihre Migrationsdaten aus.'
    ),
    'start_conversation' => t(
        'Начните общение с администрацией',
        'Start conversation with administration',
        'Inicie uma conversa com a administração',
        'Commencez une conversation avec l\'administration',
        'Beginnen Sie ein Gespräch mit der Verwaltung'
    ),
    'messages_protected' => t(
        'Все сообщения защищены и конфиденциальны',
        'All messages are protected and confidential',
        'Todas as mensagens são protegidas e confidenciais',
        'Tous les messages sont protégés et confidentiels',
        'Alle Nachrichten sind geschützt und vertraulich'
    ),
    'go_to_profile' => t(
        'Перейти в профиль',
        'Go to Profile',
        'Ir para o Perfil',
        'Aller au Profil',
        'Zum Profil gehen'
    ),
    'avatar' => t(
        'Аватар',
        'Avatar',
        'Avatar',
        'Avatar',
        'Avatar'
    ),
    'upload_avatar' => t(
        'Загрузить аватар',
        'Upload Avatar',
        'Carregar Avatar',
        'Télécharger Avatar',
        'Avatar Hochladen'
    ),
    'change_avatar' => t(
        'Изменить аватар',
        'Change Avatar',
        'Alterar Avatar',
        'Changer Avatar',
        'Avatar Ändern'
    ),
    'remove_avatar' => t(
        'Удалить аватар',
        'Remove Avatar',
        'Remover Avatar',
        'Supprimer Avatar',
        'Avatar Entfernen'
    ),
    'avatar_uploaded' => t(
        'Аватар успешно загружен',
        'Avatar uploaded successfully',
        'Avatar carregado com sucesso',
        'Avatar téléchargé avec succès',
        'Avatar erfolgreich hochgeladen'
    ),
    'avatar_removed' => t(
        'Аватар удален',
        'Avatar removed',
        'Avatar removido',
        'Avatar supprimé',
        'Avatar entfernt'
    ),
    'avatar_error' => t(
        'Ошибка загрузки аватара',
        'Avatar upload error',
        'Erro ao carregar avatar',
        'Erreur de téléchargement d\'avatar',
        'Avatar-Upload-Fehler'
    ),
    'avatar_size_error' => t(
        'Размер файла не должен превышать 2MB',
        'File size should not exceed 2MB',
        'O tamanho do arquivo não deve exceder 2MB',
        'La taille du fichier ne doit pas dépasser 2 Mo',
        'Die Dateigröße sollte 2 MB nicht überschreiten'
    ),
    'avatar_type_error' => t(
        'Разрешены только файлы JPG, PNG и GIF',
        'Only JPG, PNG and GIF files are allowed',
        'Apenas arquivos JPG, PNG e GIF são permitidos',
        'Seuls les fichiers JPG, PNG et GIF sont autorisés',
        'Nur JPG-, PNG- und GIF-Dateien sind erlaubt'
    ),
    'current_avatar' => t(
        'Текущий аватар',
        'Current Avatar',
        'Avatar Atual',
        'Avatar Actuel',
        'Aktuelles Avatar'
    ),
    'all_rights_reserved' => t(
        'Все права защищены.',
        'All rights reserved.',
        'Todos os direitos reservados.',
        'Tous droits réservés.',
        'Alle Rechte vorbehalten.'
    ),
    'contacts' => t('Контакты', 'Contacts', 'Contatos', 'Contacts', 'Kontakte'),
    'quick_links' => t('Быстрые ссылки', 'Quick Links', 'Links Rápidos', 'Liens Rapides', 'Schnelllinks'),
    'minsk_belarus' => t('Минск, Беларусь', 'Minsk, Belarus', 'Minsk, Bielorrússia', 'Minsk, Biélorussie', 'Minsk, Belarus')
];

// Загружаем данные пользователя
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Загружаем миграционные данные
    $stmt = $pdo->prepare("SELECT * FROM migration_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $migration_data = $stmt->fetch();
    
    // Загружаем чаты с администрацией
    $stmt = $pdo->prepare("
        SELECT * FROM admin_chats 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $admin_chat = $stmt->fetch();
    
    $admin_chat_id = null;
    $admin_messages = [];
    
    if ($admin_chat) {
        $admin_chat_id = $admin_chat['id'];
        
        // Загружаем сообщения чата с администрацией
        $stmt = $pdo->prepare("
            SELECT 
                m.*,
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.user_type
            FROM admin_chat_messages m
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE m.chat_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$admin_chat_id]);
        $admin_messages = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Данные по городам
$cities = [
    'minsk' => t('Минск', 'Minsk', 'Minsk', 'Minsk', 'Minsk'),
    'grodno' => t('Гродно', 'Grodno', 'Grodno', 'Grodno', 'Grodno'),
    'brest' => t('Брест', 'Brest', 'Brest', 'Brest', 'Brest'),
    'vitebsk' => t('Витебск', 'Vitebsk', 'Vitebsk', 'Vitebsk', 'Vitebsk'),
    'gomel' => t('Гомель', 'Gomel', 'Gomel', 'Gomel', 'Gomel'),
    'mogilev' => t('Могилёв', 'Mogilev', 'Mogilev', 'Mogilev', 'Mogilev')
];

// Обработка обновления профиля
$errors = [];
$success = false;

// Обработка отправки сообщения в чат с администрацией
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Обновление основной информации
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        
        if (empty($first_name)) {
            $errors[] = t('Введите имя', 'Enter first name', 'Digite o nome', 'Entrez le prénom', 'Vornamen eingeben');
        }
        
        if (empty($last_name)) {
            $errors[] = t('Введите фамилию', 'Enter last name', 'Digite o sobrenome', 'Entrez le nom', 'Nachnamen eingeben');
        }
        
        if (empty($city)) {
            $errors[] = t('Выберите город', 'Select city', 'Selecione a cidade', 'Sélectionnez la ville', 'Stadt auswählen');
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, phone = ?, city = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $phone, $city, $user_id]);
                
                // Обновляем данные в сессии
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['city'] = $city;
                
                $success = t('Профиль успешно обновлен', 'Profile successfully updated', 'Perfil atualizado com sucesso', 'Profil mis à jour avec succès', 'Profil erfolgreich aktualisiert');
                
                // Перезагружаем данные пользователя
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
            } catch (PDOException $e) {
                $errors[] = t('Ошибка базы данных', 'Database error', 'Erro de banco de dados', 'Erreur de base de données', 'Datenbankfehler');
            }
        }
        
    } elseif (isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
        // Загрузка аватара
        $avatar = $_FILES['avatar'];
        
        // Проверка ошибок загрузки
        if ($avatar['error'] !== UPLOAD_ERR_OK) {
            $errors[] = t('Ошибка загрузки файла', 'File upload error', 'Erro ao carregar arquivo', 'Erreur de téléchargement de fichier', 'Datei-Upload-Fehler');
        } else {
            // Проверка типа файла
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($avatar['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = $translations['avatar_type_error'];
            }
            
            // Проверка размера файла (2MB максимум)
            if ($avatar['size'] > 2 * 1024 * 1024) {
                $errors[] = $translations['avatar_size_error'];
            }
            
            if (empty($errors)) {
                // Создаем директорию если не существует
                if (!is_dir('uploads/avatars')) {
                    mkdir('uploads/avatars', 0777, true);
                }
                
                // Создаем уникальное имя файла
                $extension = pathinfo($avatar['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
                $upload_path = 'uploads/avatars/' . $filename;
                
                // Перемещаем загруженный файл
                if (move_uploaded_file($avatar['tmp_name'], $upload_path)) {
                    // Обновляем путь к аватару в базе данных
                    try {
                        // Удаляем старый аватар, если он существует
                        if ($user['avatar'] && file_exists($user['avatar'])) {
                            unlink($user['avatar']);
                        }
                        
                        $stmt = $pdo->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$upload_path, $user_id]);
                        
                        $success = $translations['avatar_uploaded'];
                        
                        // Обновляем данные пользователя
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch();
                        
                        // Перенаправляем для обновления страницы
                        header('Location: profile.php?lang=' . $lang . '#personal-info');
                        exit();
                        
                    } catch (PDOException $e) {
                        $errors[] = t('Ошибка базы данных: ', 'Database error: ', 'Erro de banco de dados: ', 'Erreur de base de données : ', 'Datenbankfehler: ') . $e->getMessage();
                    }
                } else {
                    $errors[] = $translations['avatar_error'];
                }
            }
        }
        
    } elseif (isset($_POST['remove_avatar'])) {
        // Удаление аватара
        if ($user['avatar'] && file_exists($user['avatar'])) {
            unlink($user['avatar']);
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET avatar = NULL, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $success = $translations['avatar_removed'];
            
            // Обновляем данные пользователя
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            // Перенаправляем для обновления страницы
            header('Location: profile.php?lang=' . $lang . '#personal-info');
            exit();
            
        } catch (PDOException $e) {
            $errors[] = t('Ошибка базы данных: ', 'Database error: ', 'Erro de banco de dados: ', 'Erreur de base de données : ', 'Datenbankfehler: ') . $e->getMessage();
        }
        
    } elseif (isset($_POST['update_password'])) {
        // Смена пароля
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password)) {
            $errors[] = t('Введите текущий пароль', 'Enter current password', 'Digite a senha atual', 'Entrez le mot de passe actuel', 'Aktuelles Passwort eingeben');
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = t('Неверный текущий пароль', 'Invalid current password', 'Senha atual inválida', 'Mot de passe actuel invalide', 'Ungültiges aktuelles Passwort');
        }
        
        if (empty($new_password)) {
            $errors[] = t('Введите новый пароль', 'Enter new password', 'Digite a nova senha', 'Entrez le nouveau mot de passe', 'Neues Passwort eingeben');
        } elseif (strlen($new_password) < 6) {
            $errors[] = t('Новый пароль должен быть не менее 6 символов', 'New password must be at least 6 characters', 'A nova senha deve ter pelo menos 6 caracteres', 'Le nouveau mot de passe doit contenir au moins 6 caractères', 'Das neue Passwort muss mindestens 6 Zeichen lang sein');
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = t('Пароли не совпадают', 'Passwords do not match', 'As senhas não coincidem', 'Les mots de passe ne correspondent pas', 'Passwörter stimmen nicht überein');
        }
        
        if (empty($errors)) {
            try {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$password_hash, $user_id]);
                
                $success = t('Пароль успешно изменен', 'Password successfully changed', 'Senha alterada com sucesso', 'Mot de passe modifié avec succès', 'Passwort erfolgreich geändert');
                
            } catch (PDOException $e) {
                $errors[] = t('Ошибка базы данных: ', 'Database error: ', 'Erro de banco de dados: ', 'Erreur de base de données : ', 'Datenbankfehler: ') . $e->getMessage();
            }
        }
        
    } elseif (isset($_POST['update_migration'])) {
        // Обновление миграционных данных
        $visa_type = trim($_POST['visa_type'] ?? '');
        $visa_number = trim($_POST['visa_number'] ?? '');
        $visa_expiry_date = trim($_POST['visa_expiry_date'] ?? '');
        $employer_name = trim($_POST['employer_name'] ?? '');
        $arrival_date = trim($_POST['arrival_date'] ?? '');
        $registration_date = trim($_POST['registration_date'] ?? '');
        
        try {
            if ($migration_data) {
                // Обновляем существующие данные
                $stmt = $pdo->prepare("
                    UPDATE migration_data 
                    SET visa_type = ?, visa_number = ?, visa_expiry_date = ?, 
                        employer_name = ?, visa_issue_date = ?, registration_date = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $visa_type, $visa_number, $visa_expiry_date,
                    $employer_name, $arrival_date, $registration_date,
                    $user_id
                ]);
            } else {
                // Создаем новые данные
                $stmt = $pdo->prepare("
                    INSERT INTO migration_data (
                        user_id, visa_type, visa_number, visa_expiry_date,
                        employer_name, visa_issue_date, registration_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id, $visa_type, $visa_number, $visa_expiry_date,
                    $employer_name, $arrival_date, $registration_date
                ]);
            }
            
            $success = t('Миграционные данные успешно обновлены', 'Migration data successfully updated', 'Dados de migração atualizados com sucesso', 'Données de migration mises à jour avec succès', 'Migrationsdaten erfolgreich aktualisiert');
            
            // Перезагружаем миграционные данные
            $stmt = $pdo->prepare("SELECT * FROM migration_data WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $migration_data = $stmt->fetch();
            
        } catch (PDOException $e) {
            $errors[] = t('Ошибка базы данных: ', 'Database error: ', 'Erro de banco de dados: ', 'Erreur de base de données : ', 'Datenbankfehler: ') . $e->getMessage();
        }
    } elseif (isset($_POST['send_admin_message'])) {
        // Отправка сообщения в чат с администрацией
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            $errors[] = t('Сообщение не может быть пустым', 'Message cannot be empty', 'A mensagem não pode estar vazia', 'Le message ne peut pas être vide', 'Nachricht darf nicht leer sein');
        } else {
            try {
                // Проверяем, есть ли уже чат у пользователя
                $stmt = $pdo->prepare("
                    SELECT * FROM admin_chats 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$user_id]);
                $admin_chat = $stmt->fetch();
                
                if (!$admin_chat) {
                    // Создаем новый чат
                    $chat_subject = t('Чат от ', 'Chat from ', 'Chat de ', 'Chat de ', 'Chat von ') . $user['first_name'] . ' ' . $user['last_name'];
                    $stmt = $pdo->prepare("
                        INSERT INTO admin_chats (user_id, subject, status, created_at, updated_at)
                        VALUES (?, ?, 'open', NOW(), NOW())
                    ");
                    $stmt->execute([$user_id, $chat_subject]);
                    $admin_chat_id = $pdo->lastInsertId();
                } else {
                    $admin_chat_id = $admin_chat['id'];
                    
                    // Если чат закрыт, открываем его снова
                    if ($admin_chat['status'] === 'closed') {
                        $stmt = $pdo->prepare("
                            UPDATE admin_chats 
                            SET status = 'open', updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$admin_chat_id]);
                    }
                }
                
                // Сохраняем сообщение
                $stmt = $pdo->prepare("
                    INSERT INTO admin_chat_messages (chat_id, sender_id, message_text, is_read, created_at)
                    VALUES (?, ?, ?, 0, NOW())
                ");
                $stmt->execute([$admin_chat_id, $user_id, $message]);
                
                $success = t('Сообщение отправлено администратору', 'Message sent to administrator', 'Mensagem enviada ao administrador', 'Message envoyé à l\'administrateur', 'Nachricht an Administrator gesendet');
                
                // Обновляем данные
                $stmt = $pdo->prepare("
                    SELECT * FROM admin_chats 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$user_id]);
                $admin_chat = $stmt->fetch();
                $admin_chat_id = $admin_chat['id'];
                
                // Обновляем список сообщений
                $stmt = $pdo->prepare("
                    SELECT 
                        m.*,
                        u.id as user_id,
                        u.first_name,
                        u.last_name,
                        u.user_type
                    FROM admin_chat_messages m
                    LEFT JOIN users u ON m.sender_id = u.id
                    WHERE m.chat_id = ?
                    ORDER BY m.created_at ASC
                ");
                $stmt->execute([$admin_chat_id]);
                $admin_messages = $stmt->fetchAll();
                
                // Перенаправляем на тот же раздел чата
                header('Location: profile.php?lang=' . $lang . '#admin-chat');
                exit();
                
            } catch (PDOException $e) {
                $errors[] = t('Ошибка базы данных: ', 'Database error: ', 'Erro de banco de dados: ', 'Erreur de base de données : ', 'Datenbankfehler: ') . $e->getMessage();
            }
        }
    } elseif (isset($_POST['mark_messages_as_read'])) {
        // Пометить сообщения как прочитанные
        if ($admin_chat_id) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE admin_chat_messages 
                    SET is_read = 1 
                    WHERE chat_id = ? AND sender_id != ? AND is_read = 0
                ");
                $stmt->execute([$admin_chat_id, $user_id]);
                
                // Обновляем список сообщений
                $stmt = $pdo->prepare("
                    SELECT 
                        m.*,
                        u.id as user_id,
                        u.first_name,
                        u.last_name,
                        u.user_type
                    FROM admin_chat_messages m
                    LEFT JOIN users u ON m.sender_id = u.id
                    WHERE m.chat_id = ?
                    ORDER BY m.created_at ASC
                ");
                $stmt->execute([$admin_chat_id]);
                $admin_messages = $stmt->fetchAll();
                
                // Перенаправляем на тот же раздел чата
                header('Location: profile.php?lang=' . $lang . '#admin-chat');
                exit();
                
            } catch (PDOException $e) {
                $errors[] = t('Ошибка базы данных: ', 'Database error: ', 'Erro de banco de dados: ', 'Erreur de base de données : ', 'Datenbankfehler: ') . $e->getMessage();
            }
        }
    }
}

// Типы виз
$visa_types = [
    'tourist' => t('Туристическая', 'Tourist', 'Turista', 'Touristique', 'Touristen'),
    'business' => t('Деловая', 'Business', 'Negócios', 'Affaires', 'Geschäftlich'),
    'work' => t('Рабочая', 'Work', 'Trabalho', 'Travail', 'Arbeit'),
    'student' => t('Студенческая', 'Student', 'Estudante', 'Étudiant', 'Student'),
    'family' => t('Семейная', 'Family', 'Família', 'Famille', 'Familie'),
    'permanent' => t('Постоянное проживание', 'Permanent residence', 'Residência permanente', 'Résidence permanente', 'Daueraufenthalt'),
    'other' => t('Другая', 'Other', 'Outro', 'Autre', 'Andere')
];

// Статусы пользователя
$status_texts = [
    'active' => $translations['active'],
    'pending' => $translations['pending'],
    'inactive' => t('Неактивен', 'Inactive', 'Inativo', 'Inactif', 'Inaktiv')
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['title']; ?></title>
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

        /* Header styles */
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

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

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
            position: relative;
            overflow: hidden;
        }

        .user-avatar:hover {
            transform: scale(1.1);
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
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #e6004c;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 25px rgba(255, 0, 84, 0.4);
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

        main {
            padding: 40px 0;
            margin-top: 20px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease;
        }

        .profile-header {
            background: var(--gradient-primary);
            color: white;
            padding: 40px;
            text-align: center;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/diamond-upholstery.png');
            opacity: 0.1;
        }

        .profile-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .profile-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            padding: 30px;
            background: rgba(26, 26, 46, 0.7);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: none;
        }

        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
        }

        .profile-sidebar {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            height: fit-content;
        }

        .user-avatar-large {
            width: 120px;
            height: 120px;
            background: var(--gradient-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 2.5rem;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(255, 0, 110, 0.3);
            border: 4px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        
        .user-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info-sidebar {
            text-align: center;
        }

        .user-name {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .user-email {
            color: var(--gray-light);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .status-active {
            background: rgba(56, 176, 0, 0.2);
            color: var(--success);
            border: 1px solid rgba(56, 176, 0, 0.3);
        }

        .status-pending {
            background: rgba(255, 158, 0, 0.2);
            color: var(--warning);
            border: 1px solid rgba(255, 158, 0, 0.3);
        }

        .status-verified {
            background: rgba(58, 134, 255, 0.2);
            color: var(--primary);
            border: 1px solid rgba(58, 134, 255, 0.3);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: var(--gray-light);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 0.95rem;
            cursor: pointer;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .sidebar-menu a.active {
            background: rgba(58, 134, 255, 0.1);
            color: var(--primary);
            border-left: 3px solid var(--primary);
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .profile-main {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section-title {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
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

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-input:disabled {
            background: rgba(255, 255, 255, 0.03);
            color: var(--gray-light);
            cursor: not-allowed;
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 14px;
            padding-right: 40px;
        }

        input[type="date"].form-input {
            padding: 11px 15px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--gray-light);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .success-message {
            background: rgba(56, 176, 0, 0.1);
            border-radius: var(--radius);
            padding: 15px 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--success);
            color: #c8ffb0;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease;
        }

        .error-list {
            background: rgba(255, 0, 84, 0.1);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 25px;
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

        .delete-section {
            background: rgba(255, 0, 84, 0.05);
            border-radius: var(--radius);
            padding: 25px;
            margin-top: 40px;
            border: 1px solid rgba(255, 0, 84, 0.2);
        }

        .delete-section h3 {
            color: var(--danger);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .delete-section p {
            color: var(--gray-light);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .chat-container {
            background: rgba(26, 26, 46, 0.7);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-header {
            background: var(--gradient-primary);
            color: white;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .chat-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/always-grey.png');
            opacity: 0.1;
        }

        .chat-header h3 {
            font-size: 1.3rem;
            margin-bottom: 8px;
            font-weight: 700;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-header p {
            opacity: 0.9;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
            margin-bottom: 15px;
        }

        .admin-status {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-top: 10px;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse 2s infinite;
        }

        .chat-messages {
            height: 350px;
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

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        .message {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            animation: fadeInUp 0.3s ease;
            word-wrap: break-word;
            line-height: 1.4;
        }

        .message.own {
            align-self: flex-end;
            background: var(--gradient-primary);
            color: white;
            border-bottom-right-radius: 5px;
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.2);
        }

        .message.other {
            align-self: flex-start;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-bottom-left-radius: 5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .message.system {
            align-self: center;
            background: rgba(255, 158, 0, 0.1);
            color: #ffe4b8;
            border-radius: 8px;
            max-width: 90%;
            font-size: 0.85rem;
            text-align: center;
            padding: 10px 15px;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.75rem;
            opacity: 0.9;
        }

        .message-sender {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
        }

        .message-unread {
            border: 2px solid var(--accent);
        }

        .loading-messages {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray-light);
            gap: 15px;
        }

        .loading-messages .fa-spinner {
            font-size: 2rem;
            color: var(--primary);
        }

        .no-messages {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-light);
        }

        .no-messages i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .chat-input-area {
            padding: 20px;
            background: rgba(26, 26, 46, 0.9);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-input-wrapper {
            position: relative;
            margin-bottom: 15px;
        }

        .chat-input {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            resize: none;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 0.95rem;
            line-height: 1.5;
            min-height: 80px;
            max-height: 120px;
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

        .chat-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .quick-questions-section {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            border-left: 3px solid var(--primary);
        }

        .quick-questions-section h4 {
            color: white;
            margin-bottom: 12px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-questions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .quick-question-btn {
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--gray-light);
            text-align: left;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-question-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateY(-2px);
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 992px) {
            .header-nav {
                display: none;
            }
            
            .burger-menu {
                display: flex;
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
            
            main {
                margin-top: 60px;
            }
            
            .mobile-nav {
                top: 60px;
                display: block;
            }
            
            .language-selector {
                flex-wrap: nowrap;
                justify-content: center;
                max-width: 280px;
            }
            
            .lang-btn {
                padding: 6px 8px;
                font-size: 0.75rem;
                min-width: 45px;
            }
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 30px 20px;
            }

            .profile-header h1 {
                font-size: 2rem;
            }

            .profile-content {
                padding: 20px;
                gap: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-actions {
                flex-direction: column;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .chat-messages {
                height: 300px;
            }

            .quick-questions-grid {
                grid-template-columns: 1fr;
            }

            .message {
                max-width: 85%;
            }
            
            .language-selector {
                max-width: 250px;
            }
            
            .lang-btn {
                padding: 5px 6px;
                font-size: 0.7rem;
                min-width: 40px;
            }
        }

        @media (max-width: 576px) {
    /* Общие отступы контейнера */
    .container {
        padding: 0 15px;
    }
    
    /* Уменьшаем отступы шапки профиля и размер шрифта */
    .profile-header {
        padding: 25px 15px;
    }

    .profile-header h1 {
        font-size: 1.6rem;
    }

    /* Уменьшаем отступы основного контента профиля */
    .profile-content {
        padding: 15px;
        gap: 15px;
    }

    .profile-sidebar, .profile-main {
        padding: 15px;
    }

    /* Уменьшаем размер аватарки, чтобы она не съедала пол-экрана */
    .user-avatar-large {
        width: 80px;
        height: 80px;
        font-size: 2rem;
        margin-bottom: 15px;
    }

    /* КРИТИЧНО: Фикс вылезающих за пределы экрана длинных email и данных паспорта */
    .info-value, .user-email {
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-all;
    }

    .info-item {
        padding: 15px;
    }

    .section-title {
        font-size: 1.25rem;
        margin-bottom: 20px;
    }

    /* Адаптивность элементов чата внутри профиля */
    .chat-header {
        padding: 15px;
    }

    .chat-header h3 {
        font-size: 1.1rem;
    }

    .message {
        max-width: 95%; /* Даем сообщениям больше ширины на маленьких экранах */
        padding: 10px 14px;
        font-size: 0.9rem;
    }

    .chat-input-area {
        padding: 15px;
    }
    
    .chat-input {
        padding: 10px;
        font-size: 0.9rem;
        min-height: 60px;
    }

    /* Адаптивность футера (точно как в index.php) */
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

    /* Сохраняем твои стили для переключателя языков */
    .language-selector {
        max-width: 220px;
    }
    
    .lang-btn {
        padding: 4px 5px;
        font-size: 0.65rem;
        min-width: 35px;
    }
}

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

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
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
                    
                    <a href="index.php?lang=<?php echo $lang; ?>" class="logo">
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
                    
                    <div class="user-info">
                        <?php if ($user['user_type'] === 'admin'): ?>
                            <a href="dashboard.php?lang=<?php echo $lang; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.8rem;">
                                <i class="fas fa-cog"></i> <?php echo $translations['admin_panel']; ?>
                            </a>
                        <?php endif; ?>
                        <div class="profile-dropdown">
                            <div class="user-avatar" id="profileAvatar" title="<?php echo $translations['go_to_profile']; ?>">
                                <?php if ($user['avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                         alt="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                         style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo substr($user['first_name'], 0, 1); ?>
                                <?php endif; ?>
                            </div>
                            <div class="dropdown-menu" id="profileDropdown">
                                <a href="profile.php?lang=<?php echo $lang; ?>" class="dropdown-item">
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
                <ul class="nav-tabs" id="mainTabs">
                    <li class="nav-tab">
                        <a href="index.php?lang=<?php echo $lang; ?>" class="nav-link">
                            <i class="fas fa-home"></i> <?php echo $translations['home']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="information.php?lang=<?php echo $lang; ?>" class="nav-link">
                            <i class="fas fa-info-circle"></i> <?php echo $translations['information']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="map.php?lang=<?php echo $lang; ?>" class="nav-link">
                            <i class="fas fa-map-marked-alt"></i> <?php echo $translations['map_services']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="translator.php?lang=<?php echo $lang; ?>" class="nav-link">
                            <i class="fas fa-language"></i> <?php echo $translations['translator']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="converter.php?lang=<?php echo $lang; ?>" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?>
                        </a>
                    </li>
                    <li class="nav-tab">
                        <a href="chat.php?lang=<?php echo $lang; ?>" class="nav-link">
                            <i class="fas fa-comments"></i> <?php echo $translations['city_chat']; ?>
                        </a>
                    </li>
                    <li class="nav-tab active">
                        <a href="profile.php?lang=<?php echo $lang; ?>" class="nav-link">
                            <i class="fas fa-user"></i> <?php echo $translations['profile']; ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Мобильная навигация -->
            <div class="mobile-nav" id="mobileNav">
                <ul class="mobile-nav-tabs">
                    <li class="mobile-nav-tab">
                        <a href="index.php?lang=<?php echo $lang; ?>" class="mobile-nav-link">
                            <i class="fas fa-home"></i> <?php echo $translations['home']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="information.php?lang=<?php echo $lang; ?>" class="mobile-nav-link">
                            <i class="fas fa-info-circle"></i> <?php echo $translations['information']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="map.php?lang=<?php echo $lang; ?>" class="mobile-nav-link">
                            <i class="fas fa-map-marked-alt"></i> <?php echo $translations['map_services']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="translator.php?lang=<?php echo $lang; ?>" class="mobile-nav-link">
                            <i class="fas fa-language"></i> <?php echo $translations['translator']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="converter.php?lang=<?php echo $lang; ?>" class="mobile-nav-link">
                            <i class="fas fa-money-bill-wave"></i> <?php echo $translations['currency_converter']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="chat.php?lang=<?php echo $lang; ?>" class="mobile-nav-link">
                            <i class="fas fa-comments"></i> <?php echo $translations['city_chat']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab active">
                        <a href="profile.php?lang=<?php echo $lang; ?>" class="mobile-nav-link">
                            <i class="fas fa-user"></i> <?php echo $translations['profile']; ?>
                        </a>
                    </li>
                    <li class="mobile-nav-tab">
                        <a href="logout.php" class="mobile-nav-link">
                            <i class="fas fa-sign-out-alt"></i> <?php echo $translations['logout']; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h1><?php echo $translations['profile_title']; ?></h1>
                <p><?php echo $translations['profile_desc']; ?></p>
            </div>

            <div class="profile-content">
                <!-- Сайдбар -->
                <div class="profile-sidebar">
                    <div class="user-avatar-large">
                        <?php if ($user['avatar']): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                 style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-info-sidebar">
                        <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                        
                        <div class="status-badge <?php echo 'status-' . $user['status']; ?>">
                            <?php 
                            echo $status_texts[$user['status']] ?? $user['status'];
                            ?>
                        </div>
                    </div>
                    
                    <ul class="sidebar-menu" id="profileMenu">
                        <li><a href="#personal-info" class="active" onclick="showSection('personal-info')">
                            <i class="fas fa-user-circle"></i>
                            <?php echo $translations['personal_info']; ?>
                        </a></li>
                        <li><a href="#migration-data" onclick="showSection('migration-data')">
                            <i class="fas fa-passport"></i>
                            <?php echo $translations['migration_data']; ?>
                        </a></li>
                        <li><a href="#admin-chat" onclick="showSection('admin-chat')">
                            <i class="fas fa-headset"></i>
                            <?php echo $translations['admin_chat']; ?>
                        </a></li>
                        <li><a href="#account-settings" onclick="showSection('account-settings')">
                            <i class="fas fa-cog"></i>
                            <?php echo $translations['account_settings']; ?>
                        </a></li>
                    </ul>
                </div>

                <!-- Основной контент -->
                <div class="profile-main">
                    <?php if ($success): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="error-list">
                            <h4><i class="fas fa-exclamation-circle"></i> <?php echo t('Ошибка', 'Error', 'Erro', 'Erreur', 'Fehler'); ?></h4>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Личная информация -->
                    <section id="personal-info" class="profile-section active">
                        <h2 class="section-title">
                            <i class="fas fa-user-circle"></i> <?php echo $translations['personal_info']; ?>
                        </h2>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-user"></i> <?php echo $translations['first_name']; ?>
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['first_name']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-user"></i> <?php echo $translations['last_name']; ?>
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['last_name']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-envelope"></i> <?php echo $translations['email']; ?>
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-phone"></i> <?php echo $translations['phone']; ?>
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-globe"></i> <?php echo $translations['country_origin']; ?>
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['country_of_origin'] ?: '-'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-passport"></i> <?php echo $translations['passport_number']; ?>
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['passport_number'] ?: '-'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-city"></i> <?php echo $translations['city_residence']; ?>
                                </div>
                                <div class="info-value"><?php echo $cities[$user['city']] ?? $user['city']; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-calendar-alt"></i> <?php echo $translations['registered']; ?>
                                </div>
                                <div class="info-value"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></div>
                            </div>
                            
                            <div class="info-item" style="display: flex; align-items: center; gap: 15px;">
                                <div style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
                                    <div style="display: flex; flex-direction: column; gap: 8px; width: 100%;">
                                        <form method="POST" enctype="multipart/form-data" id="avatar-upload-form" style="margin: 0; width: 100%;">
                                            <input type="hidden" name="upload_avatar" value="1">
                                            <input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/gif" 
                                                   style="display: none;" onchange="document.getElementById('avatar-upload-form').submit();">
                                            <button type="button" class="btn btn-primary" 
                                                    onclick="document.getElementById('avatar-input').click();"
                                                    style="padding: 10px 20px; font-size: 0.95rem; border-radius: 4px; width: 100%;">
                                                <?php echo $translations['upload_avatar']; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="margin: 0; width: 100%;">
                                            <input type="hidden" name="remove_avatar" value="1">
                                            <button type="submit" class="btn btn-danger" 
                                                    onclick="return confirm('<?php echo t("Вы уверены, что хотите удалить аватар?", "Are you sure you want to remove avatar?", "Tem certeza de que deseja remover o avatar?", "Êtes-vous sûr de vouloir supprimer l'avatar?", "Sind Sie sicher, dass Sie das Avatar entfernen möchten?"); ?>')"
                                                    style="padding: 10px 20px; font-size: 0.95rem; border-radius: 4px; width: 100%;">
                                                <?php echo $translations['remove_avatar']; ?>
                                            </button>
                                        </form>
                                    </div>
                                    <small style="color: #aaa; font-size: 0.75rem; line-height: 1.2; text-align: center; margin-top: 4px;">
                                        <?php echo t('JPG, PNG или GIF до 2MB', 'JPG, PNG or GIF up to 2MB', 'JPG, PNG ou GIF até 2MB', 'JPG, PNG ou GIF jusqu\'à 2 Mo', 'JPG, PNG oder GIF bis zu 2 MB'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <h3 style="color: white; margin: 30px 0 20px; font-size: 1.2rem;">
                            <i class="fas fa-edit"></i> <?php echo $translations['edit_information']; ?>
                        </h3>
                        
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <?php echo $translations['first_name']; ?>
                                        <span class="required"><?php echo $translations['required_field']; ?></span>
                                    </label>
                                    <input type="text" name="first_name" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <?php echo $translations['last_name']; ?>
                                        <span class="required"><?php echo $translations['required_field']; ?></span>
                                    </label>
                                    <input type="text" name="last_name" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label"><?php echo $translations['phone']; ?></label>
                                    <input type="text" name="phone" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <?php echo $translations['city_residence']; ?>
                                        <span class="required"><?php echo $translations['required_field']; ?></span>
                                    </label>
                                    <select name="city" class="form-input" required>
                                        <option value=""><?php echo $translations['select_city']; ?></option>
                                        <?php foreach ($cities as $code => $name): ?>
                                            <option value="<?php echo $code; ?>" 
                                                <?php echo $user['city'] === $code ? 'selected' : ''; ?>>
                                                <?php echo $name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $translations['update_profile']; ?>
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Миграционные данные -->
                    <section id="migration-data" class="profile-section" style="display: none;">
                        <h2 class="section-title">
                            <i class="fas fa-passport"></i> <?php echo $translations['migration_data']; ?>
                        </h2>
                        
                        <?php if ($migration_data): ?>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-file-alt"></i> <?php echo $translations['visa_type']; ?>
                                    </div>
                                    <div class="info-value">
                                        <?php echo $visa_types[$migration_data['visa_type']] ?? $migration_data['visa_type']; ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-hashtag"></i> <?php echo $translations['visa_number']; ?>
                                    </div>
                                    <div class="info-value"><?php echo htmlspecialchars($migration_data['visa_number'] ?: '-'); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar-times"></i> <?php echo $translations['visa_expiry']; ?>
                                    </div>
                                    <div class="info-value">
                                        <?php echo $migration_data['visa_expiry_date'] 
                                            ? date('d.m.Y', strtotime($migration_data['visa_expiry_date'])) 
                                            : '-'; ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-briefcase"></i> <?php echo $translations['employer']; ?>
                                    </div>
                                    <div class="info-value"><?php echo htmlspecialchars($migration_data['employer_name'] ?: '-'); ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-plane-arrival"></i> <?php echo $translations['arrival_date']; ?>
                                    </div>
                                    <div class="info-value">
                                        <?php echo $migration_data['visa_issue_date'] 
                                            ? date('d.m.Y', strtotime($migration_data['visa_issue_date'])) 
                                            : '-'; ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-file-signature"></i> <?php echo $translations['registration_date']; ?>
                                    </div>
                                    <div class="info-value">
                                        <?php echo $migration_data['registration_date'] 
                                            ? date('d.m.Y', strtotime($migration_data['registration_date'])) 
                                            : '-'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="background: rgba(255, 255, 255, 0.03); padding: 30px; border-radius: var(--radius); text-align: center;">
                                <i class="fas fa-info-circle" style="font-size: 3rem; color: var(--gray-light); margin-bottom: 20px;"></i>
                                <h3 style="color: white; margin-bottom: 10px;"><?php echo $translations['no_data']; ?></h3>
                                <p style="color: var(--gray-light);">
                                    <?php echo $translations['fill_migration_data']; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <h3 style="color: white; margin: 30px 0 20px; font-size: 1.2rem;">
                            <i class="fas fa-edit"></i> <?php echo $translations['edit_data']; ?>
                        </h3>
                        
                        <form method="POST">
                            <input type="hidden" name="update_migration" value="1">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label"><?php echo $translations['visa_type']; ?></label>
                                    <select name="visa_type" class="form-input">
                                        <option value=""><?php echo $translations['select_visa']; ?></option>
                                        <?php foreach ($visa_types as $code => $name): ?>
                                            <option value="<?php echo $code; ?>" 
                                                <?php echo ($migration_data['visa_type'] ?? '') === $code ? 'selected' : ''; ?>>
                                                <?php echo $name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label"><?php echo $translations['visa_number']; ?></label>
                                    <input type="text" name="visa_number" class="form-input" 
                                           value="<?php echo htmlspecialchars($migration_data['visa_number'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label"><?php echo $translations['visa_expiry']; ?></label>
                                    <input type="date" name="visa_expiry_date" class="form-input" 
                                           value="<?php echo $migration_data['visa_expiry_date'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label"><?php echo $translations['employer']; ?></label>
                                    <input type="text" name="employer_name" class="form-input" 
                                           value="<?php echo htmlspecialchars($migration_data['employer_name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label"><?php echo $translations['arrival_date']; ?></label>
                                    <input type="date" name="arrival_date" class="form-input" 
                                           value="<?php echo $migration_data['visa_issue_date'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label"><?php echo $translations['registration_date']; ?></label>
                                    <input type="date" name="registration_date" class="form-input" 
                                           value="<?php echo $migration_data['registration_date'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $translations['update_migration']; ?>
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Чат с администрацией -->
                    <section id="admin-chat" class="profile-section" style="display: none;">
                        <h2 class="section-title">
                            <i class="fas fa-headset"></i> <?php echo $translations['admin_chat']; ?>
                        </h2>
                        
                        <div class="chat-container">
                            <div class="chat-header">
                                <h3><i class="fas fa-comments"></i> <?php echo $translations['admin_chat']; ?></h3>
                                <p><?php echo $translations['admin_chat_desc']; ?></p>
                                
                                <div class="admin-status">
                                    <div class="status-indicator"></div>
                                    <span><?php echo $translations['admin_online']; ?></span>
                                </div>
                            </div>
                            
                            <div class="chat-messages" id="admin-chat-messages">
                                <?php if (empty($admin_messages)): ?>
                                    <div class="no-messages">
                                        <i class="fas fa-comment-slash"></i>
                                        <h4><?php echo $translations['no_messages']; ?></h4>
                                        <p><?php echo $translations['start_conversation']; ?></p>
                                    </div>
                                <?php else: 
                                    $last_date = null;
                                    foreach ($admin_messages as $msg): 
                                        $message_date = date('Y-m-d', strtotime($msg['created_at']));
                                        $today = date('Y-m-d');
                                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                                        
                                        if ($last_date !== $message_date) {
                                            $last_date = $message_date;
                                            
                                            $date_display = '';
                                            if ($message_date === $today) {
                                                $date_display = $translations['today'];
                                            } elseif ($message_date === $yesterday) {
                                                $date_display = $translations['yesterday'];
                                            } else {
                                                $date_display = date('d.m.Y', strtotime($message_date));
                                            }
                                            ?>
                                            <div class="message system">
                                                <div class="message-content"><?php echo $date_display; ?></div>
                                            </div>
                                            <?php
                                        }
                                        
                                        $message_class = 'message ';
                                        $sender_name = htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']);
                                        
                                        if ($msg['sender_id'] == $user_id) {
                                            $message_class .= 'own';
                                            $sender_name = $translations['you'];
                                        } else {
                                            $message_class .= 'other';
                                            if ($msg['user_type'] === 'admin') {
                                                $sender_name = $translations['administrator'];
                                            }
                                        }
                                        
                                        if ($msg['is_read'] == 0 && $msg['sender_id'] != $user_id) {
                                            $message_class .= ' message-unread';
                                        }
                                        ?>
                                        <div class="<?php echo $message_class; ?>">
                                            <div class="message-header">
                                                <div class="message-sender">
                                                    <?php if ($msg['sender_id'] == $user_id): ?>
                                                        <i class="fas fa-user"></i>
                                                    <?php elseif ($msg['user_type'] === 'admin'): ?>
                                                        <i class="fas fa-user-shield"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-user"></i>
                                                    <?php endif; ?>
                                                    <?php echo $sender_name; ?>
                                                </div>
                                                <div class="message-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                                            </div>
                                            <div class="message-content"><?php echo htmlspecialchars($msg['message_text']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="chat-input-area">
                                <form method="POST" id="admin-chat-form">
                                    <input type="hidden" name="send_admin_message" value="1">
                                    
                                    <div class="chat-input-wrapper">
                                        <textarea name="message" class="chat-input" id="admin-message-input" 
                                                  placeholder="<?php echo $translations['type_message']; ?>" 
                                                  rows="3" required></textarea>
                                    </div>
                                    
                                    <div class="chat-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> <?php echo $translations['send_message']; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="quick-questions-section">
                            <h4><i class="fas fa-bolt"></i> <?php echo $translations['quick_questions']; ?></h4>
                            <div class="quick-questions-grid">
                                <button type="button" class="quick-question-btn" data-question="<?php echo t('Как продлить визу?', 'How to extend visa?', 'Como prorrogar o visto?', 'Comment prolonger le visa ?', 'Wie kann ich das Visum verlängern?'); ?>">
                                    <i class="fas fa-passport"></i> <?php echo $translations['question_visa']; ?>
                                </button>
                                <button type="button" class="quick-question-btn" data-question="<?php echo t('Какие документы нужны для регистрации?', 'What documents are needed for registration?', 'Quais documentos são necessários para registro?', 'Quels documents sont nécessaires pour l\'enregistrement ?', 'Welche Dokumente werden für die Registrierung benötigt?'); ?>">
                                    <i class="fas fa-file-alt"></i> <?php echo $translations['question_documents']; ?>
                                </button>
                                <button type="button" class="quick-question-btn" data-question="<?php echo t('Как зарегистрироваться по месту жительства?', 'How to register at place of residence?', 'Como registrar no local de residência?', 'Comment s\'inscrire au lieu de résidence ?', 'Wie registriere ich mich am Wohnort?'); ?>">
                                    <i class="fas fa-home"></i> <?php echo $translations['question_registration']; ?>
                                </button>
                                <button type="button" class="quick-question-btn" data-question="<?php echo t('Другой вопрос', 'Other question', 'Outra pergunta', 'Autre question', 'Andere Frage'); ?>">
                                    <i class="fas fa-question-circle"></i> <?php echo $translations['question_other']; ?>
                                </button>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: var(--radius); border-left: 3px solid var(--primary);">
                            <p style="color: var(--gray-light); font-size: 0.85rem; margin-bottom: 8px;">
                                <i class="fas fa-clock"></i> <?php echo $translations['working_hours']; ?>
                            </p>
                            <p style="color: var(--gray-light); font-size: 0.85rem;">
                                <i class="fas fa-shield-alt"></i> <?php echo $translations['messages_protected']; ?>
                            </p>
                        </div>
                    </section>

                    <!-- Настройки аккаунта -->
                    <section id="account-settings" class="profile-section" style="display: none;">
                        <h2 class="section-title">
                            <i class="fas fa-cog"></i> <?php echo $translations['account_settings']; ?>
                        </h2>

                        <!-- Смена пароля -->
                        <h3 style="color: white; margin: 30px 0 20px; font-size: 1.2rem;">
                            <i class="fas fa-key"></i> <?php echo $translations['change_password']; ?>
                        </h3>
                        
                        <form method="POST">
                            <input type="hidden" name="update_password" value="1">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <?php echo $translations['current_password']; ?>
                                        <span class="required"><?php echo $translations['required_field']; ?></span>
                                    </label>
                                    <input type="password" name="current_password" class="form-input" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <?php echo $translations['new_password']; ?>
                                        <span class="required"><?php echo $translations['required_field']; ?></span>
                                    </label>
                                    <input type="password" name="new_password" class="form-input" required minlength="6">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <?php echo $translations['confirm_password']; ?>
                                        <span class="required"><?php echo $translations['required_field']; ?></span>
                                    </label>
                                    <input type="password" name="confirm_password" class="form-input" required minlength="6">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> <?php echo $translations['change_password']; ?>
                                </button>
                            </div>
                        </form>

                        <!-- Удаление аккаунта -->
                        <div class="delete-section">
                            <h3><i class="fas fa-trash-alt"></i> <?php echo $translations['delete_account']; ?></h3>
                            <p><?php echo $translations['delete_warning']; ?></p>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-danger" onclick="confirmDeleteAccount()">
                                    <i class="fas fa-trash-alt"></i> <?php echo $translations['delete_account']; ?>
                                </button>
                            </div>
                        </div>
                    </section>
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
        // Функция смены языка
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        // Инициализация бургер-меню
        function initializeBurgerMenu() {
            const burgerMenu = document.getElementById('burgerMenu');
            const mobileNav = document.getElementById('mobileNav');
            
            if (burgerMenu && mobileNav) {
                // Функция закрытия меню
                function closeMobileMenu() {
                    burgerMenu.classList.remove('active');
                    mobileNav.classList.remove('active');
                    document.body.style.overflow = '';
                }
                
                // Функция открытия/закрытия меню
                function toggleMobileMenu() {
                    burgerMenu.classList.toggle('active');
                    mobileNav.classList.toggle('active');
                    
                    // Prevent body scroll when menu is open
                    if (mobileNav.classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
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

        // Переключение разделов профиля
        function showSection(sectionId) {
            // Скрываем все разделы
            document.querySelectorAll('.profile-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Показываем выбранный раздел
            const selectedSection = document.getElementById(sectionId);
            if (selectedSection) {
                selectedSection.style.display = 'block';
            }
            
            // Обновляем активный пункт меню
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + sectionId) {
                    link.classList.add('active');
                }
            });
            
            // Если открыли чат с администрацией, прокручиваем вниз
            if (sectionId === 'admin-chat') {
                setTimeout(() => {
                    const chatMessages = document.getElementById('admin-chat-messages');
                    if (chatMessages) {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                }, 100);
            }
            
            // Обновляем URL
            window.history.replaceState(null, null, `#${sectionId}`);
        }

        // Подтверждение удаления аккаунта
        function confirmDeleteAccount() {
            if (confirm('<?php echo $translations["confirm_delete"]; ?>')) {
                window.location.href = 'delete_account.php?lang=<?php echo $lang; ?>';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация бургер-меню
            initializeBurgerMenu();
            
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
            
            // Обработка якорных ссылок
            const hash = window.location.hash;
            if (hash) {
                const sectionId = hash.substring(1);
                if (['personal-info', 'migration-data', 'admin-chat', 'account-settings'].includes(sectionId)) {
                    showSection(sectionId);
                }
            }
            
            // Фокус на первое поле при переключении раздела
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sectionId = this.getAttribute('href').substring(1);
                    showSection(sectionId);
                    
                    // Прокрутка к верху
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
            
            // Обработка быстрых вопросов в чате
            document.querySelectorAll('.quick-question-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const question = this.getAttribute('data-question');
                    const messageInput = document.getElementById('admin-message-input');
                    
                    if (question !== '<?php echo t("Другой вопрос", "Other question", "Outra pergunta", "Autre question", "Andere Frage"); ?>') {
                        messageInput.value = question;
                    } else {
                        messageInput.value = '';
                    }
                    
                    messageInput.focus();
                    showSection('admin-chat');
                });
            });
            
            // Автопрокрутка чата при загрузке
            const chatMessages = document.getElementById('admin-chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Обработка Enter для отправки сообщения (Ctrl+Enter для новой строки)
            const adminMessageInput = document.getElementById('admin-message-input');
            if (adminMessageInput) {
                adminMessageInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey) {
                        e.preventDefault();
                        const chatForm = document.getElementById('admin-chat-form');
                        if (chatForm) {
                            chatForm.requestSubmit();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>