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

// Тексты для перевода
$translations = [
    'main_title' => t(
        'Карта служб миграции - MigraSupport',
        'Migration Services Map - MigraSupport',
        'Mapa de Serviços de Migração - MigraSupport',
        'Carte des Services de Migration - MigraSupport',
        'Migrationsdienstleistungskarte - MigraSupport'
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
    'map_title' => t(
        'Карта служб миграции Беларуси',
        'Belarus Migration Services Map',
        'Mapa de Serviços de Migração da Bielorrússia',
        'Carte des Services de Migration de la Biélorussie',
        'Belarus-Migrationsdienstleistungskarte'
    ),
    'map_desc' => t(
        'Интерактивная карта всех миграционных служб, центры поддержки и помощи мигрантам по всей Беларуси.',
        'Interactive map of all migration services, support centers and migrant assistance across Belarus.',
        'Mapa interativo de todos os serviços de migração, centros de apoio e assistência a migrantes em toda a Bielorrússia.',
        'Carte interactive de tous les services de migration, centres de soutien et d\'assistance aux migrants dans toute la Biélorussie.',
        'Interaktive Karte aller Migrationsdienste, Unterstützungszentren und Migrantenhilfe in ganz Belarus.'
    ),
    'view_on_map' => t('Показать на карте', 'Show on Map', 'Mostrar no Mapa', 'Afficher sur la carte', 'Auf Karte anzeigen'),
    'map_not_available' => t('Карта временно недоступна', 'Map temporarily unavailable', 'Mapa temporariamente indisponível', 'Carte temporairement indisponible', 'Karte vorübergehend nicht verfügbar'),
    'get_directions' => t('Проложить маршрут', 'Get Directions', 'Obter Direções', 'Obtenir l\'itinéraire', 'Route anzeigen'),
    'city_map' => t('Карта города', 'City Map', 'Mapa da Cidade', 'Carte de la ville', 'Stadtplan'),
    'interactive_map' => t('Интерактивная карта', 'Interactive Map', 'Mapa Interativo', 'Carte interactive', 'Interaktive Karte'),
    'show_all_cities' => t('Показать все города', 'Show All Cities', 'Mostrar Todas as Cidades', 'Afficher toutes les villes', 'Alle Städte anzeigen'),
    'zoom_in' => t('Приблизить', 'Zoom In', 'Ampliar', 'Zoom avant', 'Vergrößern'),
    'zoom_out' => t('Отдалить', 'Zoom Out', 'Reduzir', 'Dézoomer', 'Verkleinern'),
    'fullscreen' => t('Полный экран', 'Fullscreen', 'Tela Cheia', 'Plein écran', 'Vollbild'),
    'exit_fullscreen' => t('Выйти из полноэкранного режима', 'Exit Fullscreen', 'Sair da Tela Cheia', 'Quitter le mode plein écran', 'Vollbildmodus verlassen'),
    'search_on_map' => t('Поиск на карте', 'Search on Map', 'Pesquisar no Mapa', 'Rechercher sur la carte', 'Auf Karte suchen'),
    'your_location' => t('Ваше местоположение', 'Your Location', 'Sua Localização', 'Votre emplacement', 'Ihr Standort'),
    'all_services' => t('Все службы на карте', 'All Services on Map', 'Todos os Serviços no Mapa', 'Tous les services sur la carte', 'Alle Dienste auf der Karte'),
    'service_types' => t('Типы служб', 'Service Types', 'Tipos de Serviços', 'Types de services', 'Dienstleistungsarten'),
    'migration_services_map' => t('Миграционные службы', 'Migration Services', 'Serviços de Migração', 'Services de Migration', 'Migrationsdienste'),
    'support_centers_map' => t('Центры поддержки', 'Support Centers', 'Centros de Apoio', 'Centres de soutien', 'Unterstützungszentren'),
    'legal_services' => t('Юридические услуги', 'Legal Services', 'Serviços Jurídicos', 'Services juridiques', 'Rechtsdienstleistungen'),
    'medical_services' => t('Медицинские услуги', 'Medical Services', 'Serviços Médicos', 'Services médicaux', 'Medizinische Dienste'),
    'education_centers' => t('Образовательные центры', 'Education Centers', 'Centros Educacionais', 'Centres éducatifs', 'Bildungszentren'),
    'employment_centers' => t('Центры трудоустройства', 'Employment Centers', 'Centros de Emprego', 'Centres d\'emploi', 'Arbeitsvermittlungszentren'),
    'attractions' => t('Достопримечательности', 'Attractions', 'Atrações', 'Attractions', 'Sehenswürdigkeiten'),
    'entertainment' => t('Развлечения', 'Entertainment', 'Entretenimento', 'Divertissement', 'Unterhaltung'),
    'filter_by_type' => t('Фильтровать по типу', 'Filter by Type', 'Filtrar por Tipo', 'Filtrer par type', 'Nach Typ filtern'),
    'show_all' => t('Показать все', 'Show All', 'Mostrar Todos', 'Afficher tout', 'Alle anzeigen'),
    'service_type' => t('Тип службы', 'Service Type', 'Tipo de Serviço', 'Type de service', 'Dienstleistungstyp'),
    'directions' => t('Маршрут', 'Directions', 'Direções', 'Itinéraire', 'Wegbeschreibung'),
    'distance' => t('Расстояние', 'Distance', 'Distância', 'Distance', 'Entfernung'),
    'travel_time' => t('Время в пути', 'Travel Time', 'Tempo de Viagem', 'Temps de trajet', 'Reisezeit'),
    'by_car' => t('На машине', 'By Car', 'De Carro', 'En voiture', 'Mit dem Auto'),
    'by_public_transport' => t('На общественном транспорте', 'By Public Transport', 'De Transporte Público', 'En transport public', 'Mit öffentlichen Verkehrsmitteln'),
    'walking' => t('Пешком', 'Walking', 'A Pé', 'À pied', 'Zu Fuß'),
    'service_details' => t('Подробности службы', 'Service Details', 'Detalhes do Serviço', 'Détails du service', 'Dienstleistungsdetails'),
    'additional_info' => t('Дополнительная информация', 'Additional Info', 'Informações Adicionais', 'Informations supplémentaires', 'Zusätzliche Informationen'),
    'website' => t('Веб-сайт', 'Website', 'Site da Web', 'Site Web', 'Webseite'),
    'services_count' => t('Найдено служб', 'Services Found', 'Serviços Encontrados', 'Services trouvés', 'Gefundene Dienste'),
    'address' => t('Адрес', 'Address', 'Endereço', 'Adresse', 'Adresse'),
    'phone' => t('Телефон', 'Phone', 'Telefone', 'Téléphone', 'Telefon'),
    'hours' => t('Часы работы', 'Working Hours', 'Horário de Funcionamento', 'Heures d\'ouverture', 'Öffnungszeiten'),
    'email' => t('Email', 'Email', 'Email', 'Email', 'E-Mail'),
    'population' => t('Население', 'Population', 'População', 'Population', 'Bevölkerung'),
    'area' => t('Площадь', 'Area', 'Área', 'Superficie', 'Fläche'),
    'select' => t('Выберите', 'Select', 'Selecionar', 'Sélectionner', 'Auswählen'),
    'choose_city' => t('Выберите ваш город', 'Choose Your City', 'Escolha Sua Cidade', 'Choisissez votre ville', 'Wählen Sie Ihre Stadt'),
    'migration_services' => t('Миграционные службы', 'Migration Services', 'Serviços de Migração', 'Services de Migration', 'Migrationsdienste'),
    'quick_links' => t('Быстрые ссылки', 'Quick Links', 'Links Rápidos', 'Liens Rapides', 'Schnelllinks'),
    'contacts' => t('Контакты', 'Contacts', 'Contatos', 'Contacts', 'Kontakte'),
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
    'address_not_specified' => t('Адрес не указан', 'Address not specified', 'Endereço não especificado', 'Adresse non spécifiée', 'Adresse nicht angegeben'),
    'geolocation_not_supported' => t(
        'Геолокация не поддерживается вашим браузером',
        'Geolocation is not supported by your browser',
        'Geolocalização não é suportada pelo seu navegador',
        'La géolocalisation n\'est pas prise en charge par votre navigateur',
        'Geolokalisierung wird von Ihrem Browser nicht unterstützt'
    ),
    'city' => t('Город', 'City', 'Cidade', 'Ville', 'Stadt'),
    'go_to_profile' => t('Перейти в профиль', 'Go to Profile', 'Ir para o Perfil', 'Aller au Profil', 'Zum Profil gehen'),
    'minsk_belarus' => t('Минск, Беларусь', 'Minsk, Belarus', 'Minsk, Bielorrússia', 'Minsk, Biélorussie', 'Minsk, Belarus')
];

// Данные по городам Беларуси с координатами для карты
$cities = array(
    'minsk' => array(
        'name' => t('Минск', 'Minsk', 'Minsk', 'Minsk', 'Minsk'),
        'name_en' => 'Minsk',
        'image' => 'Minsk.webp',
        'description' => t(
            'Столица Беларуси, крупнейший политический, экономический и культурный центр страны. Современный город с развитой инфраструктурой.',
            'The capital of Belarus, the largest political, economic, and cultural center of the country. A modern city with developed infrastructure.',
            'A capital da Bielorrússia, o maior centro político, econômico e cultural do país. Uma cidade moderna com infraestrutura desenvolvida.',
            'La capitale de la Biélorussie, le plus grand centre politique, économique et culturel du pays. Une ville moderne avec une infrastructure développée.',
            'Die Hauptstadt von Belarus, das größte politische, wirtschaftliche und kulturelle Zentrum des Landes. Eine moderne Stadt mit entwickelter Infrastruktur.'
        ),
        'population' => t('2 009 786 человек', '2,009,786 people', '2.009.786 pessoas', '2 009 786 personnes', '2.009.786 Menschen'),
        'area' => t('409,5 км²', '409.5 km²', '409,5 km²', '409,5 km²', '409,5 km²'),
        'lat' => 53.9045,
        'lng' => 27.5615,
        'services' => array(
            array(
                'name' => t(
                    'Главное управление по гражданству и миграции',
                    'Main Department for Citizenship and Migration',
                    'Departamento Principal de Cidadania e Migração',
                    'Direction générale de la citoyenneté et des migrations',
                    'Hauptabteilung für Staatsbürgerschaft und Migration'
                ),
                'address' => t('ул. Володарского, 6', 'Volodarskogo st., 6', 'Rua Volodarskogo, 6', 'rue Volodarskogo, 6', 'Volodarskogo Str. 6'),
                'phone' => '+375 (17) 218-01-02',
                'hours' => t('Пн-Пт 9:00-18:00, обед 13:00-14:00', 'Mon-Fri 9:00-18:00, lunch 13:00-14:00', 'Seg-Sex 9:00-18:00, almoço 13:00-14:00', 'Lun-Ven 9h00-18h00, déjeuner 13h00-14h00', 'Mo-Fr 9:00-18:00, Mittagessen 13:00-14:00'),
                'email' => 'minsk@mvd.gov.by',
                'type' => 'migration',
                'website' => 'mvd.gov.by'
            ),
            array(
                'name' => t(
                    'Центр адаптации мигрантов',
                    'Migrant Adaptation Center',
                    'Centro de Adaptação de Migrantes',
                    'Centre d\'adaptation des migrants',
                    'Migrantenanpassungszentrum'
                ),
                'address' => t('ул. Кальварийская, 62', 'Kalvariyskaya st., 62', 'Rua Kalvariyskaya, 62', 'rue Kalvariyskaya, 62', 'Kalvariyskaya Str. 62'),
                'phone' => '+375 (17) 234-56-78',
                'hours' => t('Пн-Сб 9:00-20:00', 'Mon-Sat 9:00-20:00', 'Seg-Sáb 9:00-20:00', 'Lun-Sam 9h00-20h00', 'Mo-Sa 9:00-20:00'),
                'email' => 'adaptation@minsk.by',
                'type' => 'support',
                'website' => 'minsk.by'
            ),
            array(
                'name' => t(
                    'Юридическая клиника для мигрантов',
                    'Legal Clinic for Migrants',
                    'Clínica Jurídica para Migrantes',
                    'Clinique juridique pour migrants',
                    'Rechtsklinik für Migranten'
                ),
                'address' => t('пр. Независимости, 65', 'Nezavisimosti ave., 65', 'Avenida Nezavisimosti, 65', 'avenue Nezavisimosti, 65', 'Nezavisimosti-Allee 65'),
                'phone' => '+375 (17) 299-88-77',
                'hours' => t('Пн-Пт 10:00-18:00', 'Mon-Fri 10:00-18:00', 'Seg-Sex 10:00-18:00', 'Lun-Ven 10h00-18h00', 'Mo-Fr 10:00-18:00'),
                'email' => 'legal.migrants@edu.by',
                'type' => 'legal'
            ),
            array(
                'name' => t(
                    'Медицинский центр для иностранцев',
                    'Medical Center for Foreigners',
                    'Centro Médico para Estrangeiros',
                    'Centre médical pour étrangers',
                    'Medizinisches Zentrum für Ausländer'
                ),
                'address' => t('ул. Гикало, 5', 'Gikalo st., 5', 'Rua Gikalo, 5', 'rue Gikalo, 5', 'Gikalo Str. 5'),
                'phone' => '+375 (17) 222-33-44',
                'hours' => t('Ежедневно 8:00-22:00', 'Daily 8:00-22:00', 'Diariamente 8:00-22:00', 'Quotidiennement 8h00-22h00', 'Täglich 8:00-22:00'),
                'email' => 'medcenter@minsk.by',
                'type' => 'medical'
            ),
            array(
                'name' => t(
                    'Центр оформления документов',
                    'Document Processing Center',
                    'Centro de Processamento de Documentos',
                    'Centre de traitement des documents',
                    'Dokumentenbearbeitungszentrum'
                ),
                'address' => t('ул. Немига, 40', 'Nemiga st., 40', 'Rua Nemiga, 40', 'rue Nemiga, 40', 'Nemiga Str. 40'),
                'phone' => '+375 (17) 277-88-99',
                'hours' => t('Пн-Пт 8:30-17:30', 'Mon-Fri 8:30-17:30', 'Seg-Sex 8:30-17:30', 'Lun-Ven 8h30-17h30', 'Mo-Fr 8:30-17:30'),
                'email' => 'documents@minsk.by',
                'type' => 'documents'
            )
        ),
        'support_centers' => array(
            array(
                'name' => t(
                    'Белорусское общество Красного Креста',
                    'Belarusian Red Cross Society',
                    'Sociedade da Cruz Vermelha Bielorrussa',
                    'Société biélorusse de la Croix-Rouge',
                    'Belarussische Rotkreuzgesellschaft'
                ),
                'address' => t('ул. Красная, 3', 'Krasnaya st., 3', 'Rua Krasnaya, 3', 'rue Krasnaya, 3', 'Krasnaya Str. 3'),
                'phone' => '+375 (17) 227-41-89',
                'hours' => t('Пн-Пт 9:00-18:00', 'Mon-Fri 9:00-18:00', 'Seg-Sex 9:00-18:00', 'Lun-Ven 9h00-18h00', 'Mo-Fr 9:00-18:00'),
                'email' => 'info@redcross.by',
                'type' => 'humanitarian',
                'lat' => 53.9113,
                'lng' => 27.5693,
                'website' => 'redcross.by'
            ),
            array(
                'name' => t(
                    'Центр поддержки семьи и детей мигрантов',
                    'Family and Migrant Children Support Center',
                    'Centro de Apoio à Família e Crianças Migrantes',
                    'Centre de soutien à la famille et aux enfants migrants',
                    'Familien- und Migrantenkinder-Unterstützungszentrum'
                ),
                'address' => t('ул. Октябрьская, 16', 'Oktyabrskaya st., 16', 'Rua Oktyabrskaya, 16', 'rue Oktyabrskaya, 16', 'Oktyabrskaya Str. 16'),
                'phone' => '+375 (17) 211-22-33',
                'hours' => t('Пн-Пт 8:00-20:00, Сб 9:00-15:00', 'Mon-Fri 8:00-20:00, Sat 9:00-15:00', 'Seg-Sex 8:00-20:00, Sáb 9:00-15:00', 'Lun-Ven 8h00-20h00, Sam 9h00-15h00', 'Mo-Fr 8:00-20:00, Sa 9:00-15:00'),
                'email' => 'family@support.by',
                'type' => 'family',
                'lat' => 53.8902,
                'lng' => 27.5736
            )
        )
    ),
    'grodno' => array(
        'name' => t('Гродно', 'Grodno', 'Grodno', 'Grodno', 'Grodno'),
        'name_en' => 'Grodno',
        'image' => 'Grodno.jpg',
        'description' => t(
            'Город на западе Беларуси, известный своей богатой историей и архитектурой. Культурная столица Беларуси.',
            'A city in western Belarus, known for its rich history and architecture. The cultural capital of Belarus.',
            'Uma cidade no oeste da Bielorrússia, conhecida por sua rica história e arquitetura. A capital cultural da Bielorrússia.',
            'Une ville dans l\'ouest de la Biélorussie, connue pour sa riche histoire et son architecture. La capitale culturelle de la Biélorussie.',
            'Eine Stadt im Westen von Belarus, bekannt für ihre reiche Geschichte und Architektur. Die Kulturhauptstadt von Belarus.'
        ),
        'population' => t('370 919 человек', '370,919 people', '370.919 pessoas', '370 919 personnes', '370.919 Menschen'),
        'area' => t('142,1 км²', '142.1 km²', '142,1 km²', '142,1 km²', '142,1 km²'),
        'lat' => 53.6698,
        'lng' => 23.8131,
        'services' => array(
            array(
                'name' => t(
                    'Отдел по гражданству и миграции',
                    'Department for Citizenship and Migration',
                    'Departamento de Cidadania e Migração',
                    'Département de la citoyenneté et des migrations',
                    'Abteilung für Staatsbürgerschaft und Migration'
                ),
                'address' => t('ул. Ожешко, 3', 'Ozeshko st., 3', 'Rua Ozeshko, 3', 'rue Ozeshko, 3', 'Ozeshko Str. 3'),
                'phone' => '+375 (152) 72-34-56',
                'hours' => t('Пн-Пт 8:00-17:00', 'Mon-Fri 8:00-17:00', 'Seg-Sex 8:00-17:00', 'Lun-Ven 8h00-17h00', 'Mo-Fr 8:00-17:00'),
                'email' => 'grodno@mvd.gov.by',
                'type' => 'migration'
            ),
            array(
                'name' => t(
                    'Консультационный центр для иностранных работников',
                    'Consultation Center for Foreign Workers',
                    'Centro de Consulta para Trabalhadores Estrangeiros',
                    'Centre de consultation pour les travailleurs étrangers',
                    'Beratungszentrum für ausländische Arbeitnehmer'
                ),
                'address' => t('ул. Советская, 8', 'Sovetskaya st., 8', 'Rua Sovetskaya, 8', 'rue Sovetskaya, 8', 'Sovetskaya Str. 8'),
                'phone' => '+375 (152) 55-66-77',
                'hours' => t('Пн-Пт 9:00-18:00', 'Mon-Fri 9:00-18:00', 'Seg-Sex 9:00-18:00', 'Lun-Ven 9h00-18h00', 'Mo-Fr 9:00-18:00'),
                'email' => 'migration@grodno.by',
                'type' => 'consultation'
            ),
            array(
                'name' => t(
                    'Пограничный информационный пункт',
                    'Border Information Point',
                    'Ponto de Informação de Fronteira',
                    'Point d\'information frontalier',
                    'Grenzinformationspunkt'
                ),
                'address' => t('ул. Пограничная, 15', 'Pogranichnaya st., 15', 'Rua Pogranichnaya, 15', 'rue Pogranichnaya, 15', 'Pogranichnaya Str. 15'),
                'phone' => '+375 (152) 44-33-22',
                'hours' => t('Круглосуточно', '24/7', '24 horas por dia', '24h/24', 'Rund um die Uhr'),
                'email' => 'border@grodno.by',
                'type' => 'border'
            )
        ),
        'support_centers' => array(
            array(
                'name' => t(
                    'Гродненский центр социальной адаптации',
                    'Grodno Social Adaptation Center',
                    'Centro de Adaptação Social de Grodno',
                    'Centre d\'adaptation sociale de Grodno',
                    'Soziales Anpassungszentrum Grodno'
                ),
                'address' => t('ул. Дзержинского, 88', 'Dzerzhinskogo st., 88', 'Rua Dzerzhinskogo, 88', 'rue Dzerzhinskogo, 88', 'Dzerzhinskogo Str. 88'),
                'phone' => '+375 (152) 44-55-66',
                'hours' => t('Пн-Пт 9:00-17:00', 'Mon-Fri 9:00-17:00', 'Seg-Sex 9:00-17:00', 'Lun-Ven 9h00-17h00', 'Mo-Fr 9:00-17:00'),
                'email' => 'adaptation@grodno.by',
                'type' => 'adaptation',
                'lat' => 53.7040,
                'lng' => 23.8391
            )
        )
    ),
    'brest' => array(
        'name' => t('Брест', 'Brest', 'Brest', 'Brest', 'Brest'),
        'name_en' => 'Brest',
        'image' => 'Brest.jpg',
        'description' => t(
            'Город-герой на границе с Польшей, известный Брестской крепостью. Крупный транспортный узел.',
            'A hero city on the border with Poland, known for the Brest Fortress. A major transportation hub.',
            'Uma cidade heróica na fronteira com a Polônia, conhecida pela Fortaleza de Brest. Um grande hub de transporte.',
            'Une ville héroïque à la frontière avec la Pologne, connue pour la forteresse de Brest. Un important nœud de transport.',
            'Eine Heldenstadt an der Grenze zu Polen, bekannt für die Brest-Festung. Ein wichtiger Verkehrsknotenpunkt.'
        ),
        'population' => t('350 616 человек', '350,616 people', '350.616 pessoas', '350 616 personnes', '350.616 Menschen'),
        'area' => t('146,1 км²', '146.1 km²', '146,1 km²', '146,1 km²', '146,1 km²'),
        'lat' => 52.0976,
        'lng' => 23.7341,
        'services' => array(
            array(
                'name' => t(
                    'Управление по гражданству и миграции',
                    'Office for Citizenship and Migration',
                    'Escritório de Cidadania e Migração',
                    'Bureau de la citoyenneté et des migrations',
                    'Amt für Staatsbürgerschaft und Migration'
                ),
                'address' => t('ул. Ленина, 19', 'Lenina st., 19', 'Rua Lenina, 19', 'rue Lenina, 19', 'Lenina Str. 19'),
                'phone' => '+375 (162) 23-45-67',
                'hours' => t('Пн-Пт 8:30-17:30', 'Mon-Fri 8:30-17:30', 'Seg-Sex 8:30-17:30', 'Lun-Ven 8h30-17h30', 'Mo-Fr 8:30-17:30'),
                'email' => 'brest@mvd.gov.by',
                'type' => 'migration'
            ),
            array(
                'name' => t(
                    'Погранично-миграционный консультационный пункт',
                    'Border-Migration Consultation Point',
                    'Ponto de Consulta de Fronteira e Migração',
                    'Point de consultation frontière-migration',
                    'Grenz-Migrations-Beratungspunkt'
                ),
                'address' => t('ул. Гоголя, 32', 'Gogolya st., 32', 'Rua Gogolya, 32', 'rue Gogolya, 32', 'Gogolya Str. 32'),
                'phone' => '+375 (162) 77-88-99',
                'hours' => t('Круглосуточно', '24/7', '24 horas por dia', '24h/24', 'Rund um die Uhr'),
                'email' => 'border@brest.by',
                'type' => 'border'
            ),
            array(
                'name' => t(
                    'Таможенно-миграционный центр',
                    'Customs and Migration Center',
                    'Centro de Alfândega e Migração',
                    'Centre des douanes et des migrations',
                    'Zoll- und Migrationszentrum'
                ),
                'address' => t('пр. Машерова, 45', 'Masherova ave., 45', 'Avenida Masherova, 45', 'avenue Masherova, 45', 'Masherova-Allee 45'),
                'phone' => '+375 (162) 66-55-44',
                'hours' => t('Пн-Пт 9:00-18:00', 'Mon-Fri 9:00-18:00', 'Seg-Sex 9:00-18:00', 'Lun-Ven 9h00-18h00', 'Mo-Fr 9:00-18:00'),
                'email' => 'customs@brest.by',
                'type' => 'customs'
            )
        ),
        'support_centers' => array(
            array(
                'name' => t(
                    'Брестский центр помощи беженцам',
                    'Brest Refugee Assistance Center',
                    'Centro de Assistência a Refugiados de Brest',
                    'Centre d\'assistance aux réfugiés de Brest',
                    'Flüchtlingshilfezentrum Brest'
                ),
                'address' => t('ул. Московская, 267', 'Moskovskaya st., 267', 'Rua Moskovskaya, 267', 'rue Moskovskaya, 267', 'Moskovskaya Str. 267'),
                'phone' => '+375 (162) 33-44-55',
                'hours' => t('Пн-Пт 8:00-20:00', 'Mon-Fri 8:00-20:00', 'Seg-Sex 8:00-20:00', 'Lun-Ven 8h00-20h00', 'Mo-Fr 8:00-20:00'),
                'email' => 'refugees@brest.by',
                'type' => 'refugee',
                'lat' => 52.0946,
                'lng' => 23.7383
            )
        )
    ),
    'vitebsk' => array(
        'name' => t('Витебск', 'Vitebsk', 'Vitebsk', 'Vitebsk', 'Vitebsk'),
        'name_en' => 'Vitebsk',
        'image' => 'Vitebsk.webp',
        'description' => t(
            'Город на севере Беларуси, известный фестивалем "Славянский базар". Культурная жемчужина региона.',
            'A city in northern Belarus, known for the "Slavianski Bazaar" festival. A cultural pearl of the region.',
            'Uma cidade no norte da Bielorrússia, conhecida pelo festival "Slavianski Bazaar". Uma pérola cultural da região.',
            'Une ville dans le nord de la Biélorussie, connue pour le festival "Slavianski Bazaar". Une perle culturelle de la région.',
            'Eine Stadt im Norden von Belarus, bekannt für das "Slavianski Bazaar"-Festival. Eine kulturelle Perle der Region.'
        ),
        'population' => t('378 459 человек', '378,459 people', '378.459 pessoas', '378 459 personnes', '378.459 Menschen'),
        'area' => t('124,5 км²', '124.5 km²', '124,5 km²', '124,5 km²', '124,5 km²'),
        'lat' => 55.1848,
        'lng' => 30.2029,
        'services' => array(
            array(
                'name' => t(
                    'Отдел по гражданству и миграции',
                    'Department for Citizenship and Migration',
                    'Departamento de Cidadania e Migração',
                    'Département de la citoyenneté et des migrations',
                    'Abteilung für Staatsbürgerschaft und Migration'
                ),
                'address' => t('ул. Замковая, 5', 'Zamkovaya st., 5', 'Rua Zamkovaya, 5', 'rue Zamkovaya, 5', 'Zamkovaya Str. 5'),
                'phone' => '+375 (212) 23-45-67',
                'hours' => t('Пн-Пт 8:30-17:30', 'Mon-Fri 8:30-17:30', 'Seg-Sex 8:30-17:30', 'Lun-Ven 8h30-17h30', 'Mo-Fr 8:30-17:30'),
                'email' => 'vitebsk@mvd.gov.by',
                'type' => 'migration'
            ),
            array(
                'name' => t(
                    'Центр культурной адаптации мигрантов',
                    'Cultural Adaptation Center for Migrants',
                    'Centro de Adaptação Cultural para Migrantes',
                    'Centre d\'adaptation culturelle pour les migrants',
                    'Kulturelles Anpassungszentrum für Migranten'
                ),
                'address' => t('ул. Пушкина, 12', 'Pushkina st., 12', 'Rua Pushkina, 12', 'rue Pushkina, 12', 'Pushkina Str. 12'),
                'phone' => '+375 (212) 34-56-78',
                'hours' => t('Пн-Пт 10:00-18:00', 'Mon-Fri 10:00-18:00', 'Seg-Sex 10:00-18:00', 'Lun-Ven 10h00-18h00', 'Mo-Fr 10:00-18:00'),
                'email' => 'culture@vitebsk.by',
                'type' => 'cultural'
            )
        ),
        'support_centers' => array(
            array(
                'name' => t(
                    'Витебский центр социальной поддержки',
                    'Vitebsk Social Support Center',
                    'Centro de Apoio Social de Vitebsk',
                    'Centre de soutien social de Vitebsk',
                    'Soziales Unterstützungszentrum Vitebsk'
                ),
                'address' => t('ул. Ленина, 36', 'Lenina st., 36', 'Rua Lenina, 36', 'rue Lenina, 36', 'Lenina Str. 36'),
                'phone' => '+375 (212) 45-67-89',
                'hours' => t('Пн-Пт 9:00-17:00', 'Mon-Fri 9:00-17:00', 'Seg-Sex 9:00-17:00', 'Lun-Ven 9h00-17h00', 'Mo-Fr 9:00-17:00'),
                'email' => 'support@vitebsk.by',
                'type' => 'social',
                'lat' => 55.1956,
                'lng' => 30.2059
            )
        )
    ),
    'gomel' => array(
        'name' => t('Гомель', 'Gomel', 'Gomel', 'Gomel', 'Gomel'),
        'name_en' => 'Gomel',
        'image' => 'Gomel.jpg',
        'description' => t(
            'Второй по величине город Беларуси, важный промышленный и культурный центр на юго-востоке страны.',
            'The second largest city in Belarus, an important industrial and cultural center in the southeast of the country.',
            'A segunda maior cidade da Bielorrússia, um importante centro industrial e cultural no sudeste do país.',
            'La deuxième plus grande ville de Biélorussie, un important centre industriel et culturel dans le sud-est du pays.',
            'Die zweitgrößte Stadt von Belarus, ein wichtiges Industrie- und Kulturzentrum im Südosten des Landes.'
        ),
        'population' => t('535 693 человек', '535,693 people', '535.693 pessoas', '535 693 personnes', '535.693 Menschen'),
        'area' => t('139,8 км²', '139.8 km²', '139,8 km²', '139,8 km²', '139,8 km²'),
        'lat' => 52.4242,
        'lng' => 31.0143,
        'services' => array(
            array(
                'name' => t(
                    'Управление по гражданству и миграции',
                    'Office for Citizenship and Migration',
                    'Escritório de Cidadania e Migração',
                    'Bureau de la citoyenneté et des migrations',
                    'Amt für Staatsbürgerschaft und Migration'
                ),
                'address' => t('пр. Ленина, 10', 'Lenina ave., 10', 'Avenida Lenina, 10', 'avenue Lenina, 10', 'Lenina-Allee 10'),
                'phone' => '+375 (232) 34-56-78',
                'hours' => t('Пн-Пт 8:00-17:00', 'Mon-Fri 8:00-17:00', 'Seg-Sex 8:00-17:00', 'Lun-Ven 8h00-17h00', 'Mo-Fr 8:00-17:00'),
                'email' => 'gomel@mvd.gov.by',
                'type' => 'migration'
            ),
            array(
                'name' => t(
                    'Центр медицинского освидетельствования иностранцев',
                    'Foreigners Medical Examination Center',
                    'Centro de Exame Médico para Estrangeiros',
                    'Centre d\'examen médical pour étrangers',
                    'Medizinisches Untersuchungszentrum für Ausländer'
                ),
                'address' => t('ул. Интернациональная, 35', 'Internatsionalnaya st., 35', 'Rua Internatsionalnaya, 35', 'rue Internatsionalnaya, 35', 'Internatsionalnaya Str. 35'),
                'phone' => '+375 (232) 77-88-99',
                'hours' => t('Пн-Пт 8:00-16:00', 'Mon-Fri 8:00-16:00', 'Seg-Sex 8:00-16:00', 'Lun-Ven 8h00-16h00', 'Mo-Fr 8:00-16:00'),
                'email' => 'medical@gomel.by',
                'type' => 'medical'
            )
        ),
        'support_centers' => array(
            array(
                'name' => t(
                    'Гомельский центр помощи трудовым мигрантам',
                    'Gomel Labor Migrant Assistance Center',
                    'Centro de Assistência a Trabalhadores Migrantes de Gomel',
                    'Centre d\'assistance aux travailleurs migrants de Gomel',
                    'Arbeitsmigranten-Hilfezentrum Gomel'
                ),
                'address' => t('ул. Советская, 25', 'Sovetskaya st., 25', 'Rua Sovetskaya, 25', 'rue Sovetskaya, 25', 'Sovetskaya Str. 25'),
                'phone' => '+375 (232) 22-33-44',
                'hours' => t('Пн-Пт 9:00-18:00', 'Mon-Fri 9:00-18:00', 'Seg-Sex 9:00-18:00', 'Lun-Ven 9h00-18h00', 'Mo-Fr 9:00-18:00'),
                'email' => 'labor@gomel.by',
                'type' => 'labor',
                'lat' => 52.4296,
                'lng' => 31.0115
            )
        )
    ),
    'mogilev' => array(
        'name' => t('Могилёв', 'Mogilev', 'Mogilev', 'Mogilev', 'Mogilev'),
        'name_en' => 'Mogilev',
        'image' => 'Mogilev.jpg',
        'description' => t(
            'Крупный промышленный и культурный центр на востоке Беларуси. Город с богатой историей.',
            'A major industrial and cultural center in eastern Belarus. A city with rich history.',
            'Um grande centro industrial e cultural no leste da Bielorrússia. Uma cidade com rica história.',
            'Un important centre industriel et culturel dans l\'est de la Biélorussie. Une ville avec une histoire riche.',
            'Ein bedeutendes Industrie- und Kulturzentrum im Osten von Belarus. Eine Stadt mit reicher Geschichte.'
        ),
        'population' => t('380 440 человек', '380,440 people', '380.440 pessoas', '380 440 personnes', '380.440 Menschen'),
        'area' => t('118,5 км²', '118.5 km²', '118,5 km²', '118,5 km²', '118,5 km²'),
        'lat' => 53.8945,
        'lng' => 30.3307,
        'services' => array(
            array(
                'name' => t(
                    'Отдел по гражданству и миграции',
                    'Department for Citizenship and Migration',
                    'Departamento de Cidadania e Migração',
                    'Département de la citoyenneté et des migrations',
                    'Abteilung für Staatsbürgerschaft und Migration'
                ),
                'address' => t('ул. Первомайская, 22', 'Pervomayskaya st., 22', 'Rua Pervomayskaya, 22', 'rue Pervomayskaya, 22', 'Pervomayskaya Str. 22'),
                'phone' => '+375 (222) 45-67-89',
                'hours' => t('Пн-Пт 9:00-18:00', 'Mon-Fri 9:00-18:00', 'Seg-Sex 9:00-18:00', 'Lun-Ven 9h00-18h00', 'Mo-Fr 9:00-18:00'),
                'email' => 'mogilev@mvd.gov.by',
                'type' => 'migration'
            ),
            array(
                'name' => t(
                    'Информационно-консультационный пункт для мигрантов',
                    'Information and Consultation Point for Migrants',
                    'Ponto de Informação e Consulta para Migrantes',
                    'Point d\'information et de consultation pour les migrants',
                    'Informations- und Beratungsstelle für Migranten'
                ),
                'address' => t('ул. Ленинская, 58', 'Leninskaya st., 58', 'Rua Leninskaya, 58', 'rue Leninskaya, 58', 'Leninskaya Str. 58'),
                'phone' => '+375 (222) 33-44-55',
                'hours' => t('Пн-Пт 9:00-17:00', 'Mon-Fri 9:00-17:00', 'Seg-Sex 9:00-17:00', 'Lun-Ven 9h00-17h00', 'Mo-Fr 9:00-17:00'),
                'email' => 'info@mogilev-migrants.by',
                'type' => 'information'
            )
        ),
        'support_centers' => array(
            array(
                'name' => t(
                    'Могилевский центр социальной интеграции',
                    'Mogilev Social Integration Center',
                    'Centro de Integração Social de Mogilev',
                    'Centre d\'intégration sociale de Mogilev',
                    'Soziales Integrationszentrum Mogilev'
                ),
                'address' => t('ул. Челюскинцев, 12', 'Chelyuskintsev st., 12', 'Rua Chelyuskintsev, 12', 'rue Chelyuskintsev, 12', 'Chelyuskintsev Str. 12'),
                'phone' => '+375 (222) 66-77-88',
                'hours' => t('Пн-Пт 8:30-17:30', 'Mon-Fri 8:30-17:30', 'Seg-Sex 8:30-17:30', 'Lun-Ven 8h30-17h30', 'Mo-Fr 8:30-17:30'),
                'email' => 'integration@mogilev.by',
                'type' => 'integration',
                'lat' => 53.8914,
                'lng' => 30.2975
            )
        )
    )
);

// Общие центры поддержки для всех городов
$common_support_centers = array(
    array(
        'name' => t(
            'Международная организация по миграции (МОМ)',
            'International Organization for Migration (IOM)',
            'Organização Internacional para as Migrações (OIM)',
            'Organisation internationale pour les migrations (OIM)',
            'Internationale Organisation für Migration (IOM)'
        ),
        'city' => 'minsk',
        'address' => t('пер. Горный, 3', 'Gorny lane, 3', 'Ruela Gorny, 3', 'ruelle Gorny, 3', 'Gorny Gasse 3'),
        'lat' => 53.9103,
        'lng' => 27.5871,
        'type' => 'international',
        'phone' => '+375 (17) 299-99-99',
        'hours' => t('Пн-Пт 9:00-17:00', 'Mon-Fri 9:00-17:00', 'Seg-Sex 9:00-17:00', 'Lun-Ven 9h00-17h00', 'Mo-Fr 9:00-17:00'),
        'email' => 'iomminsk@iom.int',
        'website' => 'iom.int'
    ),
    array(
        'name' => t(
            'Управление Верховного комиссара ООН по делам беженцев (УВКБ)',
            'UNHCR Belarus',
            'ACNUR Belarus',
            'HCR Biélorussie',
            'UNHCR Belarus'
        ),
        'city' => 'minsk',
        'address' => t('ул. Красноармейская, 22А, офис 79-80', 'Krasnoarmeyskaya st., 22A, office 79-80', 'Rua Krasnoarmeyskaya, 22A, escritório 79-80', 'rue Krasnoarmeyskaya, 22A, bureau 79-80', 'Krasnoarmeyskaya Str. 22A, Büro 79-80'),
        'lat' => 53.8977,
        'lng' => 27.5699,
        'type' => 'un',
        'phone' => '+375 (17) 233-33-33',
        'hours' => t('Пн-Пт 8:30-17:30', 'Mon-Fri 8:30-17:30', 'Seg-Sex 8:30-17:30', 'Lun-Ven 8h30-17h30', 'Mo-Fr 8:30-17:30'),
        'email' => 'bel@unhcr.org',
        'website' => 'unhcr.org'
    ),
    array(
        'name' => t(
            'Центр изучения языков для мигрантов',
            'Language Learning Center for Migrants',
            'Centro de Estudo de Idiomas para Migrantes',
            'Centre d\'apprentissage des langues pour les migrants',
            'Sprachlernzentrum für Migranten'
        ),
        'city' => 'minsk',
        'address' => t('ул. Свердлова, 30', 'Sverdlova st., 30', 'Rua Sverdlova, 30', 'rue Sverdlova, 30', 'Sverdlova Str. 30'),
        'lat' => 53.8910,
        'lng' => 27.5567,
        'type' => 'education',
        'phone' => '+375 (17) 244-55-66',
        'hours' => t('Пн-Пт 10:00-19:00, Сб 10:00-15:00', 'Mon-Fri 10:00-19:00, Sat 10:00-15:00', 'Seg-Sex 10:00-19:00, Sáb 10:00-15:00', 'Lun-Ven 10h00-19h00, Sam 10h00-15h00', 'Mo-Fr 10:00-19:00, Sa 10:00-15:00'),
        'email' => 'languages@minsk.by'
    ),
    array(
        'name' => t(
            'Центр трудоустройства иностранцев',
            'Foreign Employment Center',
            'Centro de Emprego para Estrangeiros',
            'Centre d\'emploi pour étrangers',
            'Ausländische Arbeitsvermittlungszentrum'
        ),
        'city' => 'grodno',
        'address' => t('ул. Кирова, 15', 'Kirova st., 15', 'Rua Kirova, 15', 'rue Kirova, 15', 'Kirova Str. 15'),
        'lat' => 53.6787,
        'lng' => 23.8352,
        'type' => 'employment',
        'phone' => '+375 (152) 66-77-88',
        'hours' => t('Пн-Пт 9:00-18:00', 'Mon-Fri 9:00-18:00', 'Seg-Sex 9:00-18:00', 'Lun-Ven 9h00-18h00', 'Mo-Fr 9:00-18:00'),
        'email' => 'employment@grodno.by'
    ),
    array(
        'name' => t(
            'Правозащитный центр "Миграция и право"',
            'Human Rights Center "Migration and Law"',
            'Centro de Direitos Humanos "Migração e Lei"',
            'Centre des droits de l\'homme "Migration et Droit"',
            'Menschenrechtszentrum "Migration und Recht"'
        ),
        'city' => 'vitebsk',
        'address' => t('ул. Ленина, 28', 'Lenina st., 28', 'Rua Lenina, 28', 'rue Lenina, 28', 'Lenina Str. 28'),
        'lat' => 55.1920,
        'lng' => 30.2051,
        'type' => 'legal',
        'phone' => '+375 (212) 44-55-66',
        'hours' => t('Пн-Пт 9:00-17:00', 'Mon-Fri 9:00-17:00', 'Seg-Sex 9:00-17:00', 'Lun-Ven 9h00-17h00', 'Mo-Fr 9:00-17:00'),
        'email' => 'rights@vitebsk.by'
    ),
    array(
        'name' => t(
            'Центр медицинской помощи мигрантам',
            'Migrant Medical Assistance Center',
            'Centro de Assistência Médica para Migrantes',
            'Centre d\'assistance médicale aux migrants',
            'Medizinisches Hilfszentrum für Migranten'
        ),
        'city' => 'gomel',
        'address' => t('ул. Советская, 40', 'Sovetskaya st., 40', 'Rua Sovetskaya, 40', 'rue Sovetskaya, 40', 'Sovetskaya Str. 40'),
        'lat' => 52.4341,
        'lng' => 31.0083,
        'type' => 'medical',
        'phone' => '+375 (232) 77-88-99',
        'hours' => t('Пн-Пт 8:00-20:00, Сб 9:00-15:00', 'Mon-Fri 8:00-20:00, Sat 9:00-15:00', 'Seg-Sex 8:00-20:00, Sáb 9:00-15:00', 'Lun-Ven 8h00-20h00, Sam 9h00-15h00', 'Mo-Fr 8:00-20:00, Sa 9:00-15:00'),
        'email' => 'medicalhelp@gomel.by'
    ),
    array(
        'name' => t(
            'Социальная столовая для нуждающихся мигрантов',
            'Social Canteen for Needy Migrants',
            'Refeitório Social para Migrantes Carentes',
            'Cantine sociale pour les migrants dans le besoin',
            'Soziale Kantine für bedürftige Migranten'
        ),
        'city' => 'mogilev',
        'address' => t('ул. Первомайская, 15', 'Pervomayskaya st., 15', 'Rua Pervomayskaya, 15', 'rue Pervomayskaya, 15', 'Pervomayskaya Str. 15'),
        'lat' => 53.8993,
        'lng' => 30.3335,
        'type' => 'social',
        'phone' => '+375 (222) 55-66-77',
        'hours' => t('Ежедневно 11:00-19:00', 'Daily 11:00-19:00', 'Diariamente 11:00-19:00', 'Quotidiennement 11h00-19h00', 'Täglich 11:00-19:00'),
        'email' => 'canteen@mogilev.by'
    ),
    array(
        'name' => t(
            'Кризисный центр для женщин-мигрантов',
            'Crisis Center for Migrant Women',
            'Centro de Crise para Mulheres Migrantes',
            'Centre de crise pour les femmes migrantes',
            'Krisenzentrum für Migrantinnen'
        ),
        'city' => 'brest',
        'address' => t('ул. Московская, 25', 'Moskovskaya st., 25', 'Rua Moskovskaya, 25', 'rue Moskovskaya, 25', 'Moskovskaya Str. 25'),
        'lat' => 52.0965,
        'lng' => 23.7095,
        'type' => 'women',
        'phone' => '+375 (162) 88-99-00',
        'hours' => t('Пн-Пт 9:00-20:00, Сб 10:00-16:00', 'Mon-Fri 9:00-20:00, Sat 10:00-16:00', 'Seg-Sex 9:00-20:00, Sáb 10:00-16:00', 'Lun-Ven 9h00-20h00, Sam 10h00-16h00', 'Mo-Fr 9:00-20:00, Sa 10:00-16:00'),
        'email' => 'women@brest.by'
    ),
    array(
        'name' => t(
            'Центр психологической помощи',
            'Psychological Assistance Center',
            'Centro de Assistência Psicológica',
            'Centre d\'assistance psychologique',
            'Psychologisches Hilfszentrum'
        ),
        'city' => 'minsk',
        'address' => t('ул. Мясникова, 40', 'Myasnikova st., 40', 'Rua Myasnikova, 40', 'rue Myasnikova, 40', 'Myasnikova Str. 40'),
        'lat' => 53.8960,
        'lng' => 27.5450,
        'type' => 'psychology',
        'phone' => '+375 (17) 255-66-77',
        'hours' => t('Пн-Пт 10:00-20:00, Сб 10:00-18:00', 'Mon-Fri 10:00-20:00, Sat 10:00-18:00', 'Seg-Sex 10:00-20:00, Sáb 10:00-18:00', 'Lun-Ven 10h00-20h00, Sam 10h00-18h00', 'Mo-Fr 10:00-20:00, Sa 10:00-18:00'),
        'email' => 'psychology@minsk.by'
    ),
    array(
        'name' => t(
            'Юридическая помощь для трудовых мигрантов',
            'Legal Aid for Labor Migrants',
            'Assistência Jurídica para Trabalhadores Migrantes',
            'Aide juridique pour les travailleurs migrants',
            'Rechtshilfe für Arbeitsmigranten'
        ),
        'city' => 'grodno',
        'address' => t('ул. Дзержинского, 25', 'Dzerzhinskogo st., 25', 'Rua Dzerzhinskogo, 25', 'rue Dzerzhinskogo, 25', 'Dzerzhinskogo Str. 25'),
        'lat' => 53.6961,
        'lng' => 23.8405,
        'type' => 'labor_law',
        'phone' => '+375 (152) 33-44-55',
        'hours' => t('Пн-Пт 9:00-18:00', 'Mon-Fri 9:00-18:00', 'Seg-Sex 9:00-18:00', 'Lun-Ven 9h00-18h00', 'Mo-Fr 9:00-18:00'),
        'email' => 'laborlaw@grodno.by'
    ),
    array(
        'name' => t(
            'Центр помощи детям-мигрантам',
            'Migrant Children Assistance Center',
            'Centro de Assistência a Crianças Migrantes',
            'Centre d\'assistance aux enfants migrants',
            'Hilfszentrum für Migrantenkinder'
        ),
        'city' => 'minsk',
        'address' => t('ул. Притыцкого, 60', 'Prititskogo st., 60', 'Rua Prititskogo, 60', 'rue Prititskogo, 60', 'Prititskogo Str. 60'),
        'lat' => 53.9074,
        'lng' => 27.4660,
        'type' => 'children',
        'phone' => '+375 (17) 266-77-88',
        'hours' => t('Пн-Пт 8:00-20:00, Сб 9:00-17:00', 'Mon-Fri 8:00-20:00, Sat 9:00-17:00', 'Seg-Sex 8:00-20:00, Sáb 9:00-17:00', 'Lun-Ven 8h00-20h00, Sam 9h00-17h00', 'Mo-Fr 8:00-20:00, Sa 9:00-17:00'),
        'email' => 'children@minsk.by'
    ),
    array(
        'name' => t(
            'Бюро переводов для официальных документов',
            'Official Documents Translation Bureau',
            'Agência de Tradução de Documentos Oficiais',
            'Bureau de traduction de documents officiels',
            'Übersetzungsbüro für offizielle Dokumente'
        ),
        'city' => 'minsk',
        'address' => t('ул. Кальварийская, 17', 'Kalvariyskaya st., 17', 'Rua Kalvariyskaya, 17', 'rue Kalvariyskaya, 17', 'Kalvariyskaya Str. 17'),
        'lat' => 53.9059,
        'lng' => 27.5296,
        'type' => 'translation',
        'phone' => '+375 (17) 277-88-99',
        'hours' => t('Пн-Пт 9:00-19:00, Сб 10:00-16:00', 'Mon-Fri 9:00-19:00, Sat 10:00-16:00', 'Seg-Sex 9:00-19:00, Sáb 10:00-16:00', 'Lun-Ven 9h00-19h00, Sam 10h00-16h00', 'Mo-Fr 9:00-19:00, Sa 10:00-16:00'),
        'email' => 'translations@minsk.by'
    )
);

// ========== НОВЫЕ МАРКЕРЫ (ДОБАВЛЕННЫЕ АДРЕСА) ==========

// --- Минск ---
$new_markers[] = [
    'title' => 'Департамент по гражданству и миграции МВД РБ (Главное управление)',
    'address' => 'г. Минск, ул. Городской Вал, 4 (вход через 3-й подъезд)',
    'lat' => 53.89926,
    'lng' => 27.55269,
    'city' => 'minsk',
    'type' => 'migration',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => 'mvd.gov.by'
];

$new_markers[] = [
    'title' => 'УГиМ ГУВД Мингорисполкома (Городское управление)',
    'address' => 'г. Минск, пр-т Независимости, 46В',
    'lat' => 53.91333,
    'lng' => 27.58373,
    'city' => 'minsk',
    'type' => 'migration',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'УГиМ УВД Минского облисполкома (Областное управление)',
    'address' => 'г. Минск, ул. Кальварийская, 29',
    'lat' => 53.90623,
    'lng' => 27.52220,
    'city' => 'minsk',
    'type' => 'migration',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

// --- Центры поддержки и юридические услуги Минска ---
$new_markers[] = [
    'title' => 'Международная организация по миграции (МОМ) в Беларуси',
    'address' => 'г. Минск, пер. Горный, 3',
    'lat' => 53.910303,
    'lng' => 27.587121,
    'city' => 'minsk',
    'type' => 'international',
    'phone' => '',
    'hours' => '',
    'email' => 'iomminsk@iom.int',
    'website' => 'iom.int'
];

$new_markers[] = [
    'title' => 'Белорусский Красный Крест (Генеральный секретариат)',
    'address' => 'г. Минск, ул. Карла Маркса, 35',
    'lat' => 53.899737,
    'lng' => 27.560825,
    'city' => 'minsk',
    'type' => 'humanitarian',
    'phone' => '',
    'hours' => '',
    'email' => 'info@redcross.by',
    'website' => 'redcross.by'
];

$new_markers[] = [
    'title' => 'Служба по консультированию беженцев (Правовая помощь ООН)',
    'address' => 'г. Минск, ул. Ольшевского, 74',
    'lat' => 53.924218,
    'lng' => 27.489150,
    'city' => 'minsk',
    'type' => 'legal',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

// --- Медицинские услуги Минска ---
$new_markers[] = [
    'title' => 'Городская клиническая больница скорой медицинской помощи (Экстренная)',
    'address' => 'г. Минск, ул. Кижеватова, 58',
    'lat' => 53.85216,
    'lng' => 27.53480,
    'city' => 'minsk',
    'type' => 'medical',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => '1-я Центральная районная клиническая поликлиника',
    'address' => 'г. Минск, ул. Сурганова, 45, корп. 4',
    'lat' => 53.92842,
    'lng' => 27.58752,
    'city' => 'minsk',
    'type' => 'medical',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => '6-я Центральная районная клиническая поликлиника',
    'address' => 'г. Минск, ул. Ульяновская, 5',
    'lat' => 53.89381,
    'lng' => 27.56784,
    'city' => 'minsk',
    'type' => 'medical',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

// --- Брест и Брестская область ---
$new_markers[] = [
    'title' => 'УГиМ УВД Брестского облисполкома',
    'address' => 'г. Брест, пр-т Машерова, 18',
    'lat' => 52.08726,
    'lng' => 23.69346,
    'city' => 'brest',
    'type' => 'migration',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Служба по консультированию беженцев (Брестский филиал)',
    'address' => 'г. Брест, б-р Шевченко, 4',
    'lat' => 52.08375,
    'lng' => 23.70425,
    'city' => 'brest',
    'type' => 'legal',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Брестская областная организация Красного Креста',
    'address' => 'г. Брест, ул. Рокоссовского, 9',
    'lat' => 52.05925,
    'lng' => 23.73269,
    'city' => 'brest',
    'type' => 'humanitarian',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Брестская центральная поликлиника',
    'address' => 'г. Брест, ул. Советской Конституции, 8',
    'lat' => 52.10356,
    'lng' => 23.74718,
    'city' => 'brest',
    'type' => 'medical',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Брестская городская больница скорой медицинской помощи',
    'address' => 'г. Брест, ул. Ленина, 15',
    'lat' => 52.09117,
    'lng' => 23.68453,
    'city' => 'brest',
    'type' => 'medical',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

// --- Гродно и Гродненская область ---
$new_markers[] = [
    'title' => 'УГиМ УВД Гродненского облисполкома',
    'address' => 'г. Гродно, ул. Карбышева, 3',
    'lat' => 53.68007,
    'lng' => 23.83785,
    'city' => 'grodno',
    'type' => 'migration',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Служба по консультированию беженцев (Гродненский филиал)',
    'address' => 'г. Гродно, ул. Октябрьская, 4',
    'lat' => 53.67849,
    'lng' => 23.83062,
    'city' => 'grodno',
    'type' => 'legal',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Гродненская областная организация Красного Креста и Кризисный центр',
    'address' => 'г. Гродно, ул. Карбышева, 24',
    'lat' => 53.68266,
    'lng' => 23.83220,
    'city' => 'grodno',
    'type' => 'humanitarian',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Гродненская центральная городская поликлиника',
    'address' => 'г. Гродно, ул. Транспортная, 3',
    'lat' => 53.65154,
    'lng' => 23.81985,
    'city' => 'grodno',
    'type' => 'medical',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Городская поликлиника №1 г. Гродно',
    'address' => 'г. Гродно, ул. Островского, 17',
    'lat' => 53.68913,
    'lng' => 23.83413,
    'city' => 'grodno',
    'type' => 'medical',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

// --- Гомель и Гомельская область ---
$new_markers[] = [
    'title' => 'УГиМ УВД Гомельского облисполкома',
    'address' => 'г. Гомель, пр-т Ленина, 45а',
    'lat' => 52.42784,
    'lng' => 30.99812,
    'city' => 'gomel',
    'type' => 'migration',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Служба по консультированию беженцев (Гомельский филиал)',
    'address' => 'г. Гомель, ул. Интернациональная, 10А',
    'lat' => 52.42398,
    'lng' => 31.00645,
    'city' => 'gomel',
    'type' => 'legal',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Гомельская областная организация Красного Креста',
    'address' => 'г. Гомель, ул. Пролетарская, 9',
    'lat' => 52.42212,
    'lng' => 31.01461,
    'city' => 'gomel',
    'type' => 'humanitarian',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Гомельская центральная городская клиническая поликлиника',
    'address' => 'г. Гомель, ул. Юбилейная, 7А',
    'lat' => 52.45780,
    'lng' => 31.00122,
    'city' => 'gomel',
    'type' => 'medical',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Гомельская городская клиническая больница скорой медицинской помощи',
    'address' => 'г. Гомель, ул. Комиссарова, 13',
    'lat' => 52.41941,
    'lng' => 31.01946,
    'city' => 'gomel',
    'type' => 'medical',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

// --- Витебск и Витебская область ---
$new_markers[] = [
    'title' => 'УГиМ УВД Витебского облисполкома',
    'address' => 'г. Витебск, пр-т Фрунзе, 41а',
    'lat' => 55.19233,
    'lng' => 30.22895,
    'city' => 'vitebsk',
    'type' => 'migration',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Служба по консультированию беженцев (Витебский филиал)',
    'address' => 'г. Витебск, ул. Буденного, 11',
    'lat' => 55.18567,
    'lng' => 30.20312,
    'city' => 'vitebsk',
    'type' => 'legal',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Кризисный центр Красного Креста (Временное проживание)',
    'address' => 'г. Витебск, ул. Лазо, 4',
    'lat' => 55.19835,
    'lng' => 30.26125,
    'city' => 'vitebsk',
    'type' => 'humanitarian',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Витебская городская центральная поликлиника',
    'address' => 'г. Витебск, ул. Генерала Маргелова, 2',
    'lat' => 55.16341,
    'lng' => 30.21144,
    'city' => 'vitebsk',
    'type' => 'medical',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Витебская городская клиническая больница скорой медицинской помощи',
    'address' => 'г. Витебск, ул. Фрунзе, 71',
    'lat' => 55.19512,
    'lng' => 30.24563,
    'city' => 'vitebsk',
    'type' => 'medical',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

// --- Могилев и Могилевская область ---
$new_markers[] = [
    'title' => 'УГиМ УВД Могилевского облисполкома',
    'address' => 'г. Могилев, пер. Буянова, 22',
    'lat' => 53.90562,
    'lng' => 30.33125,
    'city' => 'mogilev',
    'type' => 'migration',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Служба по консультированию беженцев (Могилевский филиал)',
    'address' => 'г. Могилев, пр-т Шмидта, 80',
    'lat' => 53.86512,
    'lng' => 30.33419,
    'city' => 'mogilev',
    'type' => 'legal',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Могилевская областная организация Красного Креста',
    'address' => 'г. Могилев, пер. Пожарный, 7',
    'lat' => 53.90141,
    'lng' => 30.33921,
    'city' => 'mogilev',
    'type' => 'humanitarian',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Могилевская центральная поликлиника',
    'address' => 'г. Могилев, ул. Пионерская, 15',
    'lat' => 53.90112,
    'lng' => 30.33457,
    'city' => 'mogilev',
    'type' => 'medical',
    'phone' => '',
    'hours' => '',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Могилевская городская больница скорой медицинской помощи',
    'address' => 'г. Могилев, ул. Боткина, 2',
    'lat' => 53.91343,
    'lng' => 30.34513,
    'city' => 'mogilev',
    'type' => 'medical',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

// ========== ДОСТОПРИМЕЧАТЕЛЬНОСТИ ==========

// --- Минск ---
$new_markers[] = [
    'title' => 'Национальная библиотека Беларуси',
    'address' => 'г. Минск, пр-т Независимости, 116',
    'lat' => 53.93139,
    'lng' => 27.64583,
    'city' => 'minsk',
    'type' => 'attractions',
    'phone' => '+375 (17) 293-27-57',
    'hours' => 'Пн-Пт 10:00-21:00, Сб-Вс 10:00-18:00',
    'email' => '',
    'website' => 'nlb.by'
];

$new_markers[] = [
    'title' => 'Троицкое предместье',
    'address' => 'г. Минск, ул. Богдановича',
    'lat' => 53.90972,
    'lng' => 27.55417,
    'city' => 'minsk',
    'type' => 'attractions',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Площадь Независимости',
    'address' => 'г. Минск, пл. Независимости',
    'lat' => 53.89361,
    'lng' => 27.54917,
    'city' => 'minsk',
    'type' => 'attractions',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Верхний город',
    'address' => 'г. Минск, пл. Свободы',
    'lat' => 53.90528,
    'lng' => 27.55556,
    'city' => 'minsk',
    'type' => 'attractions',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Национальный художественный музей',
    'address' => 'г. Минск, ул. Ленина, 20',
    'lat' => 53.89889,
    'lng' => 27.54722,
    'city' => 'minsk',
    'type' => 'attractions',
    'phone' => '+375 (17) 327-71-63',
    'hours' => 'Ср-Вс 11:00-19:00',
    'email' => '',
    'website' => 'artmuseum.by'
];

$new_markers[] = [
    'title' => 'Остров слёз (Остров Мужества и Скорби)',
    'address' => 'г. Минск, ул. Немига',
    'lat' => 53.90806,
    'lng' => 27.54861,
    'city' => 'minsk',
    'type' => 'attractions',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

// --- Брест ---
$new_markers[] = [
    'title' => 'Брестская крепость',
    'address' => 'г. Брест, ул. Героев обороны Брестской крепости, 60',
    'lat' => 52.082,
    'lng' => 23.658,
    'city' => 'brest',
    'type' => 'attractions',
    'phone' => '+375 (162) 20-00-32',
    'hours' => 'Ежедневно 9:00-18:00',
    'email' => '',
    'website' => 'brest-fortress.by'
];

$new_markers[] = [
    'title' => 'Музей обороны Брестской крепости',
    'address' => 'г. Брест, ул. Героев обороны Брестской крепости, 60',
    'lat' => 52.083333,
    'lng' => 23.656111,
    'city' => 'brest',
    'type' => 'attractions',
    'phone' => '+375 (162) 20-00-32',
    'hours' => 'Ежедневно 9:00-18:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Брестский областной краеведческий музей',
    'address' => 'г. Брест, ул. Карла Маркса, 60',
    'lat' => 52.095556,
    'lng' => 23.685833,
    'city' => 'brest',
    'type' => 'attractions',
    'phone' => '+375 (162) 20-73-91',
    'hours' => 'Вт-Вс 10:00-18:00',
    'email' => '',
    'website' => ''
];

// --- Гродно ---
$new_markers[] = [
    'title' => 'Старый замок (Гродненский замок)',
    'address' => 'г. Гродно, ул. Замковая, 22',
    'lat' => 53.677778,
    'lng' => 23.821111,
    'city' => 'grodno',
    'type' => 'attractions',
    'phone' => '+375 (152) 74-50-32',
    'hours' => 'Вт-Вс 10:00-18:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Новый замок',
    'address' => 'г. Гродно, ул. Замковая, 20',
    'lat' => 53.678333,
    'lng' => 23.822222,
    'city' => 'grodno',
    'type' => 'attractions',
    'phone' => '',
    'hours' => 'Вт-Вс 10:00-18:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Коложская церковь (Борисоглебская церковь)',
    'address' => 'г. Гродно, ул. Коложа, 6',
    'lat' => 53.676111,
    'lng' => 23.816667,
    'city' => 'grodno',
    'type' => 'attractions',
    'phone' => '',
    'hours' => 'Ежедневно 8:00-20:00',
    'email' => '',
    'website' => ''
];

// --- Витебск ---
$new_markers[] = [
    'title' => 'Летний амфитеатр (Славянский базар)',
    'address' => 'г. Витебск, ул. Фрунзе, 13а',
    'lat' => 55.193889,
    'lng' => 30.204167,
    'city' => 'vitebsk',
    'type' => 'attractions',
    'phone' => '+375 (212) 36-26-26',
    'hours' => 'По расписанию мероприятий',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Дом-музей Марка Шагала',
    'address' => 'г. Витебск, ул. Покровская, 11',
    'lat' => 55.194444,
    'lng' => 30.202778,
    'city' => 'vitebsk',
    'type' => 'attractions',
    'phone' => '+375 (212) 36-15-28',
    'hours' => 'Вт-Вс 11:00-18:30',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Ратуша',
    'address' => 'г. Витебск, ул. Ленина, 36',
    'lat' => 55.195556,
    'lng' => 30.205833,
    'city' => 'vitebsk',
    'type' => 'attractions',
    'phone' => '',
    'hours' => 'Вт-Вс 10:00-18:00',
    'email' => '',
    'website' => ''
];

// --- Гомель ---
$new_markers[] = [
    'title' => 'Дворцово-парковый ансамбль Румянцевых-Паскевичей',
    'address' => 'г. Гомель, пл. Ленина, 4',
    'lat' => 52.424722,
    'lng' => 30.990556,
    'city' => 'gomel',
    'type' => 'attractions',
    'phone' => '+375 (232) 70-34-91',
    'hours' => 'Вт-Вс 11:00-19:00',
    'email' => '',
    'website' => 'palacegomel.by'
];

$new_markers[] = [
    'title' => 'Гомельский областной драматический театр',
    'address' => 'г. Гомель, пл. Ленина, 1',
    'lat' => 52.425278,
    'lng' => 30.989167,
    'city' => 'gomel',
    'type' => 'attractions',
    'phone' => '+375 (232) 74-41-61',
    'hours' => 'По расписанию спектаклей',
    'email' => '',
    'website' => ''
];

// --- Могилев ---
$new_markers[] = [
    'title' => 'Могилевская ратуша',
    'address' => 'г. Могилев, пл. Славы, 1',
    'lat' => 53.900556,
    'lng' => 30.339167,
    'city' => 'mogilev',
    'type' => 'attractions',
    'phone' => '+375 (222) 22-43-56',
    'hours' => 'Вт-Вс 10:00-18:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Буйничское поле (Мемориальный комплекс)',
    'address' => 'г. Могилев, д. Буйничи',
    'lat' => 53.850000,
    'lng' => 30.283333,
    'city' => 'mogilev',
    'type' => 'attractions',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

// ========== РАЗВЛЕЧЕНИЯ ==========

// --- Минск ---
$new_markers[] = [
    'title' => 'Кинотеатр "Октябрь"',
    'address' => 'г. Минск, пр-т Независимости, 93',
    'lat' => 53.918056,
    'lng' => 27.591667,
    'city' => 'minsk',
    'type' => 'entertainment',
    'phone' => '+375 (17) 334-54-54',
    'hours' => 'Ежедневно 10:00-23:00',
    'email' => '',
    'website' => 'oktyabr.by'
];

$new_markers[] = [
    'title' => 'Кинотеатр "Беларусь"',
    'address' => 'г. Минск, ул. Тимирязева, 5',
    'lat' => 53.906111,
    'lng' => 27.478889,
    'city' => 'minsk',
    'type' => 'entertainment',
    'phone' => '+375 (17) 203-90-90',
    'hours' => 'Ежедневно 10:00-23:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Центральный детский парк им. Горького',
    'address' => 'г. Минск, ул. Фрунзе, 2',
    'lat' => 53.906944,
    'lng' => 27.553333,
    'city' => 'minsk',
    'type' => 'entertainment',
    'phone' => '+375 (17) 327-43-00',
    'hours' => 'Ежедневно 11:00-22:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Минск-Арена',
    'address' => 'г. Минск, пр-т Победителей, 111',
    'lat' => 53.917222,
    'lng' => 27.475556,
    'city' => 'minsk',
    'type' => 'entertainment',
    'phone' => '+375 (17) 396-00-00',
    'hours' => 'По расписанию мероприятий',
    'email' => '',
    'website' => 'minsk-arena.by'
];

$new_markers[] = [
    'title' => 'Боулинг-центр "Космос"',
    'address' => 'г. Минск, пр-т Независимости, 65',
    'lat' => 53.913333,
    'lng' => 27.583889,
    'city' => 'minsk',
    'type' => 'entertainment',
    'phone' => '+375 (17) 290-90-90',
    'hours' => 'Ежедневно 12:00-02:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Ботанический сад',
    'address' => 'г. Минск, ул. Сурганова, 2в',
    'lat' => 53.927778,
    'lng' => 27.639167,
    'city' => 'minsk',
    'type' => 'entertainment',
    'phone' => '+375 (17) 284-15-84',
    'hours' => 'Ежедневно 10:00-20:00',
    'email' => '',
    'website' => ''
];

// --- Брест ---
$new_markers[] = [
    'title' => 'Кинотеатр "Беларусь"',
    'address' => 'г. Брест, ул. Советская, 62',
    'lat' => 52.095000,
    'lng' => 23.688333,
    'city' => 'brest',
    'type' => 'entertainment',
    'phone' => '+375 (162) 23-70-70',
    'hours' => 'Ежедневно 10:00-23:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Парк культуры и отдыха',
    'address' => 'г. Брест, б-р Космонавтов',
    'lat' => 52.088889,
    'lng' => 23.700000,
    'city' => 'brest',
    'type' => 'entertainment',
    'phone' => '',
    'hours' => 'Ежедневно 10:00-22:00',
    'email' => '',
    'website' => ''
];

// --- Гродно ---
$new_markers[] = [
    'title' => 'Кинотеатр "Октябрь"',
    'address' => 'г. Гродно, ул. Советская, 17',
    'lat' => 53.678889,
    'lng' => 23.831111,
    'city' => 'grodno',
    'type' => 'entertainment',
    'phone' => '+375 (152) 72-00-00',
    'hours' => 'Ежедневно 10:00-23:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Гродненский зоопарк',
    'address' => 'г. Гродно, ул. Тимирязева, 11',
    'lat' => 53.663889,
    'lng' => 23.813333,
    'city' => 'grodno',
    'type' => 'entertainment',
    'phone' => '+375 (152) 75-43-21',
    'hours' => 'Ежедневно 9:00-20:00',
    'email' => '',
    'website' => 'grodnozoo.by'
];

// --- Витебск ---
$new_markers[] = [
    'title' => 'Кинотеатр "Витебск"',
    'address' => 'г. Витебск, ул. Ленина, 32',
    'lat' => 55.195278,
    'lng' => 30.205278,
    'city' => 'vitebsk',
    'type' => 'entertainment',
    'phone' => '+375 (212) 37-00-00',
    'hours' => 'Ежедневно 10:00-23:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Парк Победителей',
    'address' => 'г. Витебск, ул. Калинина',
    'lat' => 55.180556,
    'lng' => 30.211111,
    'city' => 'vitebsk',
    'type' => 'entertainment',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

// --- Гомель ---
$new_markers[] = [
    'title' => 'Кинотеатр "Октябрь"',
    'address' => 'г. Гомель, пр-т Ленина, 10',
    'lat' => 52.427500,
    'lng' => 30.998056,
    'city' => 'gomel',
    'type' => 'entertainment',
    'phone' => '+375 (232) 70-00-00',
    'hours' => 'Ежедневно 10:00-23:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Гомельский цирк',
    'address' => 'г. Гомель, пл. Восстания, 1',
    'lat' => 52.428333,
    'lng' => 31.013889,
    'city' => 'gomel',
    'type' => 'entertainment',
    'phone' => '+375 (232) 74-00-00',
    'hours' => 'По расписанию представлений',
    'email' => '',
    'website' => ''
];

// --- Могилев ---
$new_markers[] = [
    'title' => 'Кинотеатр "Родина"',
    'address' => 'г. Могилев, пр-т Мира, 14',
    'lat' => 53.898889,
    'lng' => 30.336111,
    'city' => 'mogilev',
    'type' => 'entertainment',
    'phone' => '+375 (222) 24-00-00',
    'hours' => 'Ежедневно 10:00-23:00',
    'email' => '',
    'website' => ''
];

$new_markers[] = [
    'title' => 'Парк Подниколье',
    'address' => 'г. Могилев, ул. Ленинская',
    'lat' => 53.895556,
    'lng' => 30.341667,
    'city' => 'mogilev',
    'type' => 'entertainment',
    'phone' => '',
    'hours' => 'Круглосуточно',
    'email' => '',
    'website' => ''
];

// ========== КОНЕЦ НОВЫХ МАРКЕРОВ ==========

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
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['main_title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
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
            from {
                opacity: 0;
                transform: translateY(30px);
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

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Применение анимаций */
        header {
            animation: slideInLeft 0.5s ease;
        }

        .hero-section {
            animation: fadeInUp 0.8s ease;
        }

        .map-container {
            animation: fadeInUp 1s ease;
        }

        .map-filters-container {
            animation: fadeInUp 1.2s ease;
        }

        footer {
            animation: fadeIn 1.4s ease;
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

        /* Language Selector */
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

        /* Profile Dropdown */
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

        /* Burger Menu */
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

        /* Основная навигация в хедере */
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

        /* Карта служб */
        .map-container {
            background: rgba(26, 26, 46, 0.7);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeInUp 0.6s ease;
        }

        .map-header {
            background: var(--gradient-secondary);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .map-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/always-grey.png');
            opacity: 0.1;
        }

        .map-header h2 {
            font-size: 1.6rem;
            margin-bottom: 8px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .map-header p {
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }

        .map-wrapper {
            position: relative;
            height: 600px;
            overflow: hidden;
        }

        #migration-map {
            width: 100%;
            height: 100%;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        .map-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .map-control-button {
            background: rgba(26, 26, 46, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            font-size: 1.1rem;
        }

        .map-control-button:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3);
        }

        .map-location-info {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 15px;
            color: white;
            z-index: 1000;
            max-width: 300px;
            box-shadow: var(--shadow);
            display: none;
        }

        .map-location-info.active {
            display: block;
            animation: fadeInUp 0.3s ease;
        }

        .location-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .location-info-header h4 {
            font-size: 1rem;
            font-weight: 600;
            color: white;
        }

        .close-info {
            background: none;
            border: none;
            color: var(--gray-light);
            cursor: pointer;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .close-info:hover {
            color: var(--danger);
        }

        .location-info-content {
            font-size: 0.9rem;
            color: var(--gray-light);
            line-height: 1.5;
        }

        .location-info-content .service-info {
            margin: 8px 0;
        }

        .view-on-map-btn {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .view-on-map-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .services-counter {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(26, 26, 46, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 10px 20px;
            color: white;
            z-index: 1000;
            box-shadow: var(--shadow);
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Leaflet стили */
        .leaflet-container {
            font-family: 'Inter', sans-serif;
            background: var(--dark);
        }

        .leaflet-popup-content-wrapper {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            color: white;
            border-radius: var(--radius);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .leaflet-popup-content {
            margin: 15px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .leaflet-popup-tip {
            background: rgba(26, 26, 46, 0.95);
        }

        .leaflet-control-attribution {
            display: none !important;
        }

        .leaflet-control-zoom {
            border: none !important;
            background: transparent !important;
        }

        .leaflet-control-zoom a {
            background: rgba(26, 26, 46, 0.9) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            border-radius: 8px !important;
            transition: var(--transition) !important;
        }

        .leaflet-control-zoom a:hover {
            background: var(--primary) !important;
            transform: translateY(-2px);
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
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            color: var(--accent);
            font-size: 1.4rem;
            animation: float 3s ease-in-out infinite;
        }

        /* Service Info */
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

        /* Фильтры под картой */
        .map-filters-container {
            background: rgba(26, 26, 46, 0.7);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-section h4 {
            margin-bottom: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 6px 0;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .filter-option:hover {
            color: var(--primary-light);
        }

        .filter-option input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
        }

        .filter-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
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
            
            .map-wrapper {
                height: 500px;
            }
            
            .map-controls {
                top: 10px;
                right: 10px;
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
            
            .map-wrapper {
                height: 400px;
            }
            
            .map-location-info {
                max-width: 250px;
                bottom: 10px;
                right: 10px;
            }
            
            .map-controls {
                display: none;
            }
            
            .services-counter {
                display: none;
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
            
            .map-wrapper {
                height: 300px;
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
                    <!-- Language Selector -->
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
                    <li class="nav-tab active">
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
                    <li class="mobile-nav-tab active">
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
            <h1 class="hero-title"><?php echo $translations['map_title']; ?></h1>
            <p class="hero-subtitle"><?php echo $translations['map_desc']; ?></p>
        </div>

        <!-- Карта служб -->
        <div class="map-container">
            <div class="map-header">
                <h2><i class="fas fa-map-marked-alt"></i> <?php echo $translations['interactive_map']; ?></h2>
                <p><?php echo $translations['all_services']; ?></p>
            </div>
            
            <div class="map-wrapper">
                <div id="migration-map"></div>
                
                <!-- Счетчик служб -->
                <div class="services-counter">
                    <i class="fas fa-map-marker-alt"></i>
                    <span id="services-count">0</span> <?php echo $translations['services_count']; ?>
                </div>
                
                <!-- Управление картой -->
                <div class="map-controls">
                    <button class="map-control-button" id="zoom-in" title="<?php echo htmlspecialchars($translations['zoom_in']); ?>">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="map-control-button" id="zoom-out" title="<?php echo htmlspecialchars($translations['zoom_out']); ?>">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button class="map-control-button" id="toggle-fullscreen" title="<?php echo htmlspecialchars($translations['fullscreen']); ?>">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button class="map-control-button" id="locate-me" title="<?php echo htmlspecialchars($translations['your_location']); ?>">
                        <i class="fas fa-location-arrow"></i>
                    </button>
                </div>
                
                <!-- Информация о локации -->
                <div class="map-location-info" id="location-info">
                    <div class="location-info-header">
                        <h4 id="location-name"><?php echo $translations['service_details']; ?></h4>
                        <button class="close-info" id="close-info">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="location-info-content" id="location-content">
                        <!-- Информация о службе будет загружена динамически -->
                    </div>
                    <button class="view-on-map-btn" id="get-directions">
                        <i class="fas fa-route"></i> <?php echo $translations['get_directions']; ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Фильтры под картой -->
        <div class="map-filters-container">
            <h3><i class="fas fa-filter"></i> <?php echo $translations['filter_by_type']; ?></h3>
            <div class="filters-grid">
                <div class="filter-section">
                    <h4><?php echo $translations['service_types']; ?></h4>
                    <div class="filter-options">
                        <div class="filter-option" data-type="all">
                            <input type="checkbox" id="filter-all" checked>
                            <label for="filter-all"><?php echo $translations['show_all']; ?></label>
                        </div>
                        <div class="filter-option" data-type="migration">
                            <div class="filter-color" style="background: #3a86ff;"></div>
                            <input type="checkbox" id="filter-migration" checked>
                            <label for="filter-migration"><?php echo $translations['migration_services_map']; ?></label>
                        </div>
                        <div class="filter-option" data-type="support">
                            <div class="filter-color" style="background: #8338ec;"></div>
                            <input type="checkbox" id="filter-support" checked>
                            <label for="filter-support"><?php echo $translations['support_centers_map']; ?></label>
                        </div>
                        <div class="filter-option" data-type="legal">
                            <div class="filter-color" style="background: #38b000;"></div>
                            <input type="checkbox" id="filter-legal" checked>
                            <label for="filter-legal"><?php echo $translations['legal_services']; ?></label>
                        </div>
                        <div class="filter-option" data-type="medical">
                            <div class="filter-color" style="background: #ff006e;"></div>
                            <input type="checkbox" id="filter-medical" checked>
                            <label for="filter-medical"><?php echo $translations['medical_services']; ?></label>
                        </div>
                        <div class="filter-option" data-type="attractions">
                            <div class="filter-color" style="background: #ff9e00;"></div>
                            <input type="checkbox" id="filter-attractions" checked>
                            <label for="filter-attractions"><?php echo $translations['attractions']; ?></label>
                        </div>
                        <div class="filter-option" data-type="entertainment">
                            <div class="filter-color" style="background: #00b4d8;"></div>
                            <input type="checkbox" id="filter-entertainment" checked>
                            <label for="filter-entertainment"><?php echo $translations['entertainment']; ?></label>
                        </div>
                    </div>
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
        // Функция смены языка
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            initializeBurgerMenu();
            initializeProfileDropdown();
            initializeMap();
            initializeMapFilters();
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

        // Инициализация карты
        let map;
        let markers = [];
        let citiesData = <?php echo json_encode_unicode($cities); ?>;
        let commonSupportCenters = <?php echo json_encode_unicode($common_support_centers); ?>;
        let newMarkers = <?php echo json_encode_unicode($new_markers); ?>;
        let currentLang = '<?php echo $lang; ?>';

        function initializeMap() {
            // Инициализация карты с центром на Беларуси
            map = L.map('migration-map').setView([53.9, 27.5], 7);
            
            // Добавление базового слоя карты
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                minZoom: 6
            }).addTo(map);
            
            // Загрузка маркеров
            loadAllMarkers();
            
            // Инициализация контролов карты
            initializeMapControls();
            
            // Обновление счетчика служб
            updateServicesCount();
        }

        function initializeMapFilters() {
            const filterOptions = document.querySelectorAll('.filter-option');
            
            filterOptions.forEach(option => {
                const checkbox = option.querySelector('input[type="checkbox"]');
                const type = option.getAttribute('data-type');
                
                checkbox.addEventListener('change', function() {
                    // Если выбран "все", снимаем все остальные флажки
                    if (type === 'all' && this.checked) {
                        filterOptions.forEach(opt => {
                            if (opt.getAttribute('data-type') !== 'all') {
                                opt.querySelector('input[type="checkbox"]').checked = false;
                            }
                        });
                    } else if (type !== 'all' && this.checked) {
                        // Если выбран конкретный тип, снимаем флажок "все"
                        const allCheckbox = document.getElementById('filter-all');
                        if (allCheckbox) allCheckbox.checked = false;
                    }
                    
                    applyFilters();
                });
            });
        }

        function loadAllMarkers() {
            // Очистка существующих маркеров
            markers.forEach(marker => marker.remove());
            markers = [];
            
            // Добавление маркеров для всех городов из существующих данных
            Object.values(citiesData).forEach(city => {
                // Добавление маркеров миграционных служб города
                if (city.services) {
                    city.services.forEach(service => {
                        addMarker(
                            city.lat + (Math.random() - 0.5) * 0.02,
                            city.lng + (Math.random() - 0.5) * 0.02,
                            service.name,
                            service.address,
                            service.phone,
                            service.hours,
                            service.email,
                            service.type || 'migration',
                            service.website,
                            city.name
                        );
                    });
                }
                
                // Добавление маркеров центров поддержки города
                if (city.support_centers) {
                    city.support_centers.forEach(center => {
                        addMarker(
                            center.lat,
                            center.lng,
                            center.name,
                            center.address,
                            center.phone,
                            center.hours,
                            center.email,
                            center.type || 'support',
                            center.website,
                            city.name
                        );
                    });
                }
                
                // Добавление маркера самого города
                addCityMarker(
                    city.lat,
                    city.lng,
                    city.name,
                    city
                );
            });
            
            // Добавление общих центров поддержки
            commonSupportCenters.forEach(center => {
                const city = citiesData[center.city];
                if (city) {
                    addMarker(
                        center.lat,
                        center.lng,
                        center.name,
                        center.address || '',
                        center.phone,
                        center.hours || '',
                        center.email,
                        center.type || 'support',
                        center.website,
                        city.name
                    );
                }
            });
            
            // Добавление новых маркеров из предоставленных адресов
            if (newMarkers && newMarkers.length > 0) {
                newMarkers.forEach(marker => {
                    const cityName = citiesData[marker.city] ? citiesData[marker.city].name : marker.city;
                    addMarker(
                        marker.lat,
                        marker.lng,
                        marker.title,
                        marker.address,
                        marker.phone || '',
                        marker.hours || '',
                        marker.email || '',
                        marker.type,
                        marker.website || '',
                        cityName
                    );
                });
            }
        }

        function addMarker(lat, lng, name, address, phone, hours, email, type, website, city) {
            // Определение цвета и иконки в зависимости от типа
            let iconColor, iconClass;
            
            switch(type) {
                case 'migration':
                    iconColor = '#3a86ff';
                    iconClass = 'fas fa-passport';
                    break;
                case 'support':
                    iconColor = '#8338ec';
                    iconClass = 'fas fa-hands-helping';
                    break;
                case 'legal':
                    iconColor = '#38b000';
                    iconClass = 'fas fa-gavel';
                    break;
                case 'medical':
                    iconColor = '#ff006e';
                    iconClass = 'fas fa-heartbeat';
                    break;
                case 'education':
                    iconColor = '#ff9e00';
                    iconClass = 'fas fa-graduation-cap';
                    break;
                case 'employment':
                    iconColor = '#00b4d8';
                    iconClass = 'fas fa-briefcase';
                    break;
                case 'documents':
                    iconColor = '#8e44ad';
                    iconClass = 'fas fa-file-alt';
                    break;
                case 'consultation':
                    iconColor = '#f39c12';
                    iconClass = 'fas fa-comments';
                    break;
                case 'border':
                    iconColor = '#27ae60';
                    iconClass = 'fas fa-border-all';
                    break;
                case 'customs':
                    iconColor = '#16a085';
                    iconClass = 'fas fa-box';
                    break;
                case 'cultural':
                    iconColor = '#9b59b6';
                    iconClass = 'fas fa-theater-masks';
                    break;
                case 'information':
                    iconColor = '#3498db';
                    iconClass = 'fas fa-info-circle';
                    break;
                case 'humanitarian':
                    iconColor = '#e74c3c';
                    iconClass = 'fas fa-hand-holding-heart';
                    break;
                case 'family':
                    iconColor = '#1abc9c';
                    iconClass = 'fas fa-users';
                    break;
                case 'adaptation':
                    iconColor = '#d35400';
                    iconClass = 'fas fa-hands-helping';
                    break;
                case 'refugee':
                    iconColor = '#c0392b';
                    iconClass = 'fas fa-hotel';
                    break;
                case 'social':
                    iconColor = '#2980b9';
                    iconClass = 'fas fa-hands';
                    break;
                case 'labor':
                    iconColor = '#7f8c8d';
                    iconClass = 'fas fa-briefcase';
                    break;
                case 'integration':
                    iconColor = '#8e44ad';
                    iconClass = 'fas fa-handshake';
                    break;
                case 'international':
                    iconColor = '#e67e22';
                    iconClass = 'fas fa-globe-americas';
                    break;
                case 'un':
                    iconColor = '#0099ff';
                    iconClass = 'fas fa-university';
                    break;
                case 'women':
                    iconColor = '#e84393';
                    iconClass = 'fas fa-female';
                    break;
                case 'psychology':
                    iconColor = '#6c5ce7';
                    iconClass = 'fas fa-brain';
                    break;
                case 'labor_law':
                    iconColor = '#00b894';
                    iconClass = 'fas fa-balance-scale';
                    break;
                case 'children':
                    iconColor = '#fd79a8';
                    iconClass = 'fas fa-child';
                    break;
                case 'translation':
                    iconColor = '#636e72';
                    iconClass = 'fas fa-language';
                    break;
                case 'attractions':
                    iconColor = '#ff9e00';
                    iconClass = 'fas fa-landmark';
                    break;
                case 'entertainment':
                    iconColor = '#00b4d8';
                    iconClass = 'fas fa-ticket-alt';
                    break;
                default:
                    iconColor = '#3a86ff';
                    iconClass = 'fas fa-map-marker-alt';
            }
            
            // Создание кастомной иконки
            const customIcon = L.divIcon({
                className: 'custom-marker',
                html: `<div style="background: ${iconColor}; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); border: 2px solid white;">
                    <i class="${iconClass}"></i>
                </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            });
            
            // Создание маркера
            const marker = L.marker([lat, lng], { icon: customIcon })
                .addTo(map);
            
            // Добавление данных о типе для фильтрации
            marker.type = type;
            markers.push(marker);
            
            // Добавление обработчика клика для показа информации
            marker.on('click', function(e) {
                showLocationInfo({ lat, lng, name, address, phone, hours, email, type, website, city });
                map.setView([lat, lng], 15);
            });
            
            // При наведении показываем название
            marker.bindTooltip(name, {
                permanent: false,
                direction: 'top',
                className: 'custom-tooltip'
            });
        }

        function addCityMarker(lat, lng, name, cityData) {
            const cityIcon = L.divIcon({
                className: 'city-marker',
                html: `<div style="background: #ff006e; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; box-shadow: 0 4px 10px rgba(255,0,110,0.3); border: 3px solid white;">
                    <i class="fas fa-city"></i>
                </div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });
            
            const marker = L.marker([lat, lng], { icon: cityIcon })
                .addTo(map);
            
            marker.type = 'city';
            markers.push(marker);
            
            // Добавление обработчика клика для города
            marker.on('click', function(e) {
                showCityInfo({ lat, lng, name, cityData });
            });
            
            // При наведении показываем название города
            marker.bindTooltip(name, {
                permanent: false,
                direction: 'top',
                className: 'custom-tooltip'
            });
        }

        function getTypeName(type) {
            const typeNames = {
                'migration': '<?php echo addslashes($translations["migration_services_map"]); ?>',
                'support': '<?php echo addslashes($translations["support_centers_map"]); ?>',
                'legal': '<?php echo addslashes($translations["legal_services"]); ?>',
                'medical': '<?php echo addslashes($translations["medical_services"]); ?>',
                'education': '<?php echo addslashes($translations["education_centers"]); ?>',
                'employment': '<?php echo addslashes($translations["employment_centers"]); ?>',
                'city': '<?php echo addslashes($translations["city"]); ?>',
                'documents': '<?php echo addslashes(t("Оформление документов", "Document Processing", "Processamento de Documentos", "Traitement des documents", "Dokumentenbearbeitung")); ?>',
                'consultation': '<?php echo addslashes(t("Консультации", "Consultations", "Consultas", "Consultations", "Beratungen")); ?>',
                'border': '<?php echo addslashes(t("Пограничные службы", "Border Services", "Serviços de Fronteira", "Services frontaliers", "Grenzdienste")); ?>',
                'customs': '<?php echo addslashes(t("Таможенные службы", "Customs Services", "Serviços Alfandegários", "Services douaniers", "Zolldienste")); ?>',
                'cultural': '<?php echo addslashes(t("Культурная адаптация", "Cultural Adaptation", "Adaptação Cultural", "Adaptation culturelle", "Kulturelle Anpassung")); ?>',
                'information': '<?php echo addslashes(t("Информационные центры", "Information Centers", "Centros de Informação", "Centres d\'information", "Informationszentren")); ?>',
                'humanitarian': '<?php echo addslashes(t("Гуманитарная помощь", "Humanitarian Aid", "Ajuda Humanitária", "Aide humanitaire", "Humanitäre Hilfe")); ?>',
                'family': '<?php echo addslashes(t("Поддержка семьи", "Family Support", "Apoio Familiar", "Soutien familial", "Familienunterstützung")); ?>',
                'adaptation': '<?php echo addslashes(t("Социальная адаптация", "Social Adaptation", "Adaptação Social", "Adaptation sociale", "Soziale Anpassung")); ?>',
                'refugee': '<?php echo addslashes(t("Помощь беженцам", "Refugee Assistance", "Assistência a Refugiados", "Aide aux réfugiés", "Flüchtlingshilfe")); ?>',
                'social': '<?php echo addslashes(t("Социальная поддержка", "Social Support", "Apoio Social", "Soutien social", "Soziale Unterstützung")); ?>',
                'labor': '<?php echo addslashes(t("Трудовые мигранты", "Labor Migrants", "Trabalhadores Migrantes", "Travailleurs migrants", "Arbeitsmigranten")); ?>',
                'integration': '<?php echo addslashes(t("Социальная интеграция", "Social Integration", "Integração Social", "Intégration sociale", "Soziale Integration")); ?>',
                'international': '<?php echo addslashes(t("Международные организации", "International Organizations", "Organizações Internacionais", "Organisations internationales", "Internationale Organisationen")); ?>',
                'un': '<?php echo addslashes(t("ООН", "UN", "ONU", "ONU", "UN")); ?>',
                'women': '<?php echo addslashes(t("Женщины-мигранты", "Migrant Women", "Mulheres Migrantes", "Femmes migrantes", "Migrantinnen")); ?>',
                'psychology': '<?php echo addslashes(t("Психологическая помощь", "Psychological Assistance", "Assistência Psicológica", "Assistance psychologique", "Psychologische Hilfe")); ?>',
                'labor_law': '<?php echo addslashes(t("Трудовое право", "Labor Law", "Direito do Trabalho", "Droit du travail", "Arbeitsrecht")); ?>',
                'children': '<?php echo addslashes(t("Дети-мигранты", "Migrant Children", "Crianças Migrantes", "Enfants migrants", "Migrantenkinder")); ?>',
                'translation': '<?php echo addslashes(t("Перевод документов", "Document Translation", "Tradução de Documentos", "Traduction de documents", "Dokumentenübersetzung")); ?>',
                'attractions': '<?php echo addslashes($translations["attractions"]); ?>',
                'entertainment': '<?php echo addslashes($translations["entertainment"]); ?>'
            };
            return typeNames[type] || type;
        }

        function initializeMapControls() {
            // Кнопка увеличения
            document.getElementById('zoom-in').addEventListener('click', function() {
                map.zoomIn();
            });
            
            // Кнопка уменьшения
            document.getElementById('zoom-out').addEventListener('click', function() {
                map.zoomOut();
            });
            
            // Кнопка полноэкранного режима
            const fullscreenBtn = document.getElementById('toggle-fullscreen');
            fullscreenBtn.addEventListener('click', function() {
                const mapContainer = document.getElementById('migration-map');
                
                if (!document.fullscreenElement) {
                    if (mapContainer.requestFullscreen) {
                        mapContainer.requestFullscreen();
                        fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
                        fullscreenBtn.title = '<?php echo addslashes($translations["exit_fullscreen"]); ?>';
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                        fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
                        fullscreenBtn.title = '<?php echo addslashes($translations["fullscreen"]); ?>';
                    }
                }
            });
            
            // Кнопка определения местоположения
            document.getElementById('locate-me').addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        
                        // Добавление маркера пользователя
                        const userIcon = L.divIcon({
                            className: 'user-marker',
                            html: `<div style="background: #38b000; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); border: 2px solid white;">
                                <i class="fas fa-user"></i>
                            </div>`,
                            iconSize: [28, 28],
                            iconAnchor: [14, 28]
                        });
                        
                        L.marker([userLat, userLng], { icon: userIcon })
                            .addTo(map)
                            .bindPopup('<?php echo addslashes($translations["your_location"]); ?>')
                            .openPopup();
                        
                        // Центрирование карты на пользователе
                        map.setView([userLat, userLng], 14);
                    });
                } else {
                    alert('<?php echo addslashes($translations["geolocation_not_supported"]); ?>');
                }
            });
            
            // Кнопка закрытия информации
            document.getElementById('close-info').addEventListener('click', function() {
                document.getElementById('location-info').classList.remove('active');
            });
            
            // Кнопка построения маршрута
            document.getElementById('get-directions').addEventListener('click', function() {
                const infoDiv = document.getElementById('location-info');
                const name = document.getElementById('location-name').textContent;
                const address = document.querySelector('#location-content .address')?.textContent || '';
                
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        
                        // URL для построения маршрута в Google Maps
                        const googleMapsUrl = `https://www.google.com/maps/dir/${userLat},${userLng}/${encodeURIComponent(address)}`;
                        window.open(googleMapsUrl, '_blank');
                    });
                } else {
                    // Альтернативный URL без текущего местоположения
                    const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
                    window.open(googleMapsUrl, '_blank');
                }
            });
        }

        function applyFilters() {
            const selectedTypes = [];
            
            // Сбор выбранных типов
            document.querySelectorAll('.filter-option input[type="checkbox"]:checked').forEach(checkbox => {
                const type = checkbox.closest('.filter-option').getAttribute('data-type');
                if (type !== 'all') {
                    selectedTypes.push(type);
                }
            });
            
            // Применение фильтров к маркерам
            markers.forEach(marker => {
                if (selectedTypes.length === 0 || selectedTypes.includes('all') || selectedTypes.includes(marker.type)) {
                    marker.addTo(map);
                } else {
                    marker.remove();
                }
            });
            
            // Обновление счетчика
            updateServicesCount();
        }

        function updateServicesCount() {
            const visibleMarkers = markers.filter(marker => 
                marker._map && marker.type !== 'city'
            ).length;
            
            document.getElementById('services-count').textContent = visibleMarkers;
        }

        function showLocationInfo(data) {
            const infoDiv = document.getElementById('location-info');
            const nameElement = document.getElementById('location-name');
            const contentElement = document.getElementById('location-content');
            
            // Заполнение информации
            nameElement.textContent = data.name;
            
            let contentHTML = '';
            
            if (data.city) {
                contentHTML += `
                    <div class="service-info">
                        <i class="fas fa-city"></i>
                        <span>${escapeHtml(data.city)}</span>
                    </div>
                `;
            }
            
            contentHTML += `
                <div class="service-info">
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="address">${escapeHtml(data.address || '<?php echo addslashes($translations["address_not_specified"]); ?>')}</span>
                </div>
            `;
            
            if (data.phone) {
                contentHTML += `
                    <div class="service-info">
                        <i class="fas fa-phone"></i>
                        <span>${escapeHtml(data.phone)}</span>
                    </div>
                `;
            }
            
            if (data.hours) {
                contentHTML += `
                    <div class="service-info">
                        <i class="fas fa-clock"></i>
                        <span>${escapeHtml(data.hours)}</span>
                    </div>
                `;
            }
            
            if (data.email) {
                contentHTML += `
                    <div class="service-info">
                        <i class="fas fa-envelope"></i>
                        <span>${escapeHtml(data.email)}</span>
                    </div>
                `;
            }
            
            if (data.website) {
                contentHTML += `
                    <div class="service-info">
                        <i class="fas fa-globe"></i>
                        <span>${escapeHtml(data.website)}</span>
                    </div>
                `;
            }
            
            contentHTML += `
                <div class="service-info">
                    <i class="fas fa-tag"></i>
                    <span>${getTypeName(data.type)}</span>
                </div>
            `;
            
            contentElement.innerHTML = contentHTML;
            
            // Показ информационного блока
            infoDiv.classList.add('active');
        }

        function showCityInfo(data) {
            const infoDiv = document.getElementById('location-info');
            const nameElement = document.getElementById('location-name');
            const contentElement = document.getElementById('location-content');
            
            // Заполнение информации
            nameElement.textContent = data.name;
            
            let contentHTML = `
                <div class="service-info">
                    <i class="fas fa-users"></i>
                    <span>${escapeHtml(data.cityData.population)}</span>
                </div>
                <div class="service-info">
                    <i class="fas fa-arrows-alt"></i>
                    <span>${escapeHtml(data.cityData.area)}</span>
                </div>
                <div class="service-info">
                    <i class="fas fa-info-circle"></i>
                    <span>${escapeHtml(data.cityData.description)}</span>
                </div>
                <div class="service-info">
                    <i class="fas fa-tag"></i>
                    <span><?php echo addslashes($translations["city"]); ?></span>
                </div>
            `;
            
            contentElement.innerHTML = contentHTML;
            
            // Показ информационного блока
            infoDiv.classList.add('active');
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Обработчик выхода из полноэкранного режима
        document.addEventListener('fullscreenchange', function() {
            const fullscreenBtn = document.getElementById('toggle-fullscreen');
            if (!document.fullscreenElement) {
                fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
                fullscreenBtn.title = '<?php echo addslashes($translations["fullscreen"]); ?>';
            }
        });
    </script>
</body>
</html>