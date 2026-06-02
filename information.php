<?php
require_once 'config.php';


// Подключаем файл с данными аватара
require_once 'include_avatar.php';

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

// Тексты для перевода с добавлением всех языков
$translations = [
    'main_title' => t(
        'Информация для мигрантов - MigraSupport',
        'Migrant Information - MigraSupport',
        'Informações para Migrantes - MigraSupport',
        'Information pour les Migrants - MigraSupport',
        'Informationen für Migranten - MigraSupport'
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
    'info_title' => t(
        'Информация для мигрантов',
        'Information for Migrants',
        'Informações para Migrantes',
        'Information pour les Migrants',
        'Informationen für Migranten'
    ),
    'info_desc' => t(
        'Все что нужно знать мигранту в Беларуси: процедуры, документы, права и обязанности.',
        'Everything a migrant needs to know in Belarus: procedures, documents, rights and obligations.',
        'Tudo o que um migrante precisa saber na Bielorrússia: procedimentos, documentos, direitos e obrigações.',
        'Tout ce qu\'un migrant doit savoir en Biélorussie : procédures, documents, droits et obligations.',
        'Alles, was ein Migrant in Belarus wissen muss: Verfahren, Dokumente, Rechte und Pflichten.'
    ),
    'registration_process' => t(
        'Процесс регистрации',
        'Registration Process',
        'Processo de Registro',
        'Processus d\'enregistrement',
        'Registrierungsprozess'
    ),
    'step1_title' => t(
        'Регистрация по месту проживания',
        'Registration at Place of Residence',
        'Registro no Local de Residência',
        'Enregistrement au lieu de résidence',
        'Registrierung am Wohnort'
    ),
    'step1_desc' => t(
        'В течение 5 дней после прибытия необходимо зарегистрироваться по месту проживания.',
        'Within 5 days of arrival, you must register at your place of residence.',
        'Dentro de 5 dias após a chegada, você deve se registrar em seu local de residência.',
        'Dans les 5 jours suivant votre arrivée, vous devez vous inscrire à votre lieu de résidence.',
        'Innerhalb von 5 Tagen nach der Ankunft müssen Sie sich an Ihrem Wohnort registrieren.'
    ),
    'step2_title' => t(
        'Разрешение на проживание',
        'Residence Permit',
        'Autorização de Residência',
        'Permis de séjour',
        'Aufenthaltserlaubnis'
    ),
    'step2_desc' => t(
        'Оформление разрешения на временное проживание при необходимости длительного пребывания.',
        'Processing of temporary residence permit for long-term stay if necessary.',
        'Processamento de autorização de residência temporária para estadia de longo prazo, se necessário.',
        'Traitement du permis de séjour temporaire pour un séjour de longue durée si nécessaire.',
        'Bearbeitung einer befristeten Aufenthaltserlaubnis für einen längeren Aufenthalt bei Bedarf.'
    ),
    'step3_title' => t(
        'Миграционный учет',
        'Migration Registration',
        'Registro de Migração',
        'Enregistrement migratoire',
        'Migrationsregistrierung'
    ),
    'step3_desc' => t(
        'Постановка на учет в миграционной службе и получение необходимых документов.',
        'Registration with the migration service and obtaining necessary documents.',
        'Registro junto ao serviço de migração e obtenção dos documentos necessários.',
        'Inscription auprès du service des migrations et obtention des documents nécessaires.',
        'Registrierung beim Migrationsdienst und Beschaffung der erforderlichen Dokumente.'
    ),
    'required_docs' => t(
        'Необходимые документы',
        'Required Documents',
        'Documentos Necessários',
        'Documents requis',
        'Erforderliche Dokumente'
    ),
    'main_docs' => t('Основные документы', 'Main Documents', 'Documentos Principais', 'Documents principaux', 'Hauptdokumente'),
    'passport' => t(
        'Действующий заграничный паспорт',
        'Valid foreign passport',
        'Passaporte estrangeiro válido',
        'Passeport étranger valide',
        'Gültiger ausländischer Reisepass'
    ),
    'migration_card' => t('Миграционная карта', 'Migration card', 'Cartão de migração', 'Carte de migration', 'Migrationskarte'),
    'purpose_docs' => t(
        'Документы о цели пребывания',
        'Documents about purpose of stay',
        'Documentos sobre o propósito da estadia',
        'Documents sur le but du séjour',
        'Dokumente über den Aufenthaltszweck'
    ),
    'additional_docs' => t(
        'Дополнительные документы',
        'Additional Documents',
        'Documentos Adicionais',
        'Documents supplémentaires',
        'Zusätzliche Dokumente'
    ),
    'insurance' => t('Медицинская страховка', 'Medical insurance', 'Seguro médico', 'Assurance médicale', 'Krankenversicherung'),
    'photos' => t('Фотографии 3x4 см', '3x4 cm photos', 'Fotos 3x4 cm', 'Photos 3x4 cm', 'Fotos 3x4 cm'),
    'fee_receipt' => t(
        'Квитанция об оплате пошлины',
        'Fee payment receipt',
        'Recibo de pagamento de taxa',
        'Reçu de paiement des frais',
        'Gebührenzahlungsbeleg'
    ),
    'visa_types' => t('Типы виз', 'Visa Types', 'Tipos de Visto', 'Types de visa', 'Visumarten'),
    'tourist_visa' => t('Туристическая виза', 'Tourist Visa', 'Visto de Turista', 'Visa touristique', 'Touristenvisum'),
    'tourist_visa_desc' => t(
        'Для туризма и посещения друзей/родственников. Срок до 90 дней.',
        'For tourism and visiting friends/relatives. Up to 90 days.',
        'Para turismo e visita a amigos/familiares. Até 90 dias.',
        'Pour le tourisme et la visite d\'amis/famille. Jusqu\'à 90 jours.',
        'Für Tourismus und Besuche bei Freunden/Verwandten. Bis zu 90 Tage.'
    ),
    'business_visa' => t('Деловая виза', 'Business Visa', 'Visto de Negócios', 'Visa d\'affaires', 'Geschäftsvisum'),
    'business_visa_desc' => t(
        'Для ведения бизнеса и деловых встреч. Требуется приглашение.',
        'For business activities and meetings. Invitation required.',
        'Para atividades comerciais e reuniões. Convite necessário.',
        'Pour les activités commerciales et les réunions. Invitation requise.',
        'Für Geschäftstätigkeiten und Meetings. Einladung erforderlich.'
    ),
    'work_visa' => t('Рабочая виза', 'Work Visa', 'Visto de Trabalho', 'Visa de travail', 'Arbeitsvisum'),
    'work_visa_desc' => t(
        'Для трудоустройства. Требуется разрешение на работу.',
        'For employment. Work permit required.',
        'Para emprego. Permissão de trabalho necessária.',
        'Pour l\'emploi. Permis de travail requis.',
        'Für Beschäftigung. Arbeitserlaubnis erforderlich.'
    ),
    'student_visa' => t('Студенческая виза', 'Student Visa', 'Visto de Estudante', 'Visa étudiant', 'Studentenvisum'),
    'student_visa_desc' => t(
        'Для обучения в учебных заведениях Беларуси.',
        'For studying at educational institutions in Belarus.',
        'Para estudar em instituições educacionais na Bielorrússia.',
        'Pour étudier dans des établissements d\'enseignement en Biélorussie.',
        'Für das Studium an Bildungseinrichtungen in Belarus.'
    ),
    'rights_obligations' => t(
        'Права и обязанности',
        'Rights and Obligations',
        'Direitos e Obrigações',
        'Droits et obligations',
        'Rechte und Pflichten'
    ),
    'rights' => t('Права мигрантов', 'Migrant Rights', 'Direitos dos Migrantes', 'Droits des migrants', 'Migrantenrechte'),
    'rights_list' => [
        t(
            'Право на медицинскую помощь',
            'Right to medical care',
            'Direito a cuidados médicos',
            'Droit aux soins médicaux',
            'Recht auf medizinische Versorgung'
        ),
        t(
            'Право на образование для детей',
            'Right to education for children',
            'Direito à educação para crianças',
            'Droit à l\'éducation pour les enfants',
            'Recht auf Bildung für Kinder'
        ),
        t(
            'Право на защиту от дискриминации',
            'Right to protection from discrimination',
            'Direito à proteção contra discriminação',
            'Droit à la protection contre la discrimination',
            'Recht auf Schutz vor Diskriminierung'
        ),
        t(
            'Право на обращение в суд',
            'Right to apply to court',
            'Direito de recorrer ao tribunal',
            'Droit de saisir la justice',
            'Recht auf Anrufung des Gerichts'
        ),
        t(
            'Право на свободу передвижения',
            'Right to freedom of movement',
            'Direito à liberdade de circulação',
            'Droit à la liberté de circulation',
            'Recht auf Freizügigkeit'
        )
    ],
    'obligations' => t(
        'Обязанности мигрантов',
        'Migrant Obligations',
        'Obrigações dos Migrantes',
        'Obligations des migrants',
        'Migrantenpflichten'
    ),
    'obligations_list' => [
        t(
            'Соблюдение законов Республики Беларусь',
            'Compliance with laws of the Republic of Belarus',
            'Cumprimento das leis da República da Bielorrússia',
            'Respect des lois de la République de Biélorussie',
            'Einhaltung der Gesetze der Republik Belarus'
        ),
        t(
            'Своевременная регистрация',
            'Timely registration',
            'Registro oportuno',
            'Enregistrement en temps opportun',
            'Fristgerechte Registrierung'
        ),
        t(
            'Уплата налогов',
            'Payment of taxes',
            'Pagamento de impostos',
            'Paiement des impôts',
            'Steuerzahlung'
        ),
        t(
            'Соблюдение визового режима',
            'Compliance with visa regime',
            'Cumprimento do regime de visto',
            'Respect du régime des visas',
            'Einhaltung des Visaregimes'
        ),
        t(
            'Уважение традиций и культуры',
            'Respect for traditions and culture',
            'Respeito às tradições e cultura',
            'Respect des traditions et de la culture',
            'Respekt vor Traditionen und Kultur'
        )
    ],
    'useful_contacts' => t(
        'Полезные контакты',
        'Useful Contacts',
        'Contatos Úteis',
        'Contacts utiles',
        'Nützliche Kontakte'
    ),
    'emergency_services' => t(
        'Экстренные службы',
        'Emergency Services',
        'Serviços de Emergência',
        'Services d\'urgence',
        'Notdienste'
    ),
    'police' => t('Полиция', 'Police', 'Polícia', 'Police', 'Polizei'),
    'police_phone' => '102',
    'ambulance' => t('Скорая помощь', 'Ambulance', 'Ambulância', 'Ambulance', 'Krankenwagen'),
    'ambulance_phone' => '103',
    'fire_service' => t('Пожарная служба', 'Fire Service', 'Serviço de Incêndio', 'Service d\'incendie', 'Feuerwehr'),
    'fire_phone' => '101',
    'gas_service' => t(
        'Аварийная газовая служба',
        'Emergency Gas Service',
        'Serviço de Gás de Emergência',
        'Service de gaz d\'urgence',
        'Notgasdienst'
    ),
    'gas_phone' => '104',
    'support_centers' => t(
        'Центры поддержки',
        'Support Centers',
        'Centros de Apoio',
        'Centres de soutien',
        'Unterstützungszentren'
    ),
    'migration_services' => t(
        'Миграционные службы',
        'Migration Services',
        'Serviços de Migração',
        'Services de Migration',
        'Migrationsdienste'
    ),
    'legal_aid' => t(
        'Бесплатная юридическая помощь',
        'Free Legal Aid',
        'Ajuda Jurídica Gratuita',
        'Aide juridique gratuite',
        'Kostenlose Rechtsberatung'
    ),
    'faq' => t(
        'Часто задаваемые вопросы',
        'Frequently Asked Questions',
        'Perguntas Frequentes',
        'Questions fréquemment posées',
        'Häufig gestellte Fragen'
    ),
    'faq1_q' => t(
        'Сколько дней можно находиться в Беларуси без регистрации?',
        'How many days can I stay in Belarus without registration?',
        'Quantos dias posso ficar na Bielorrússia sem registro?',
        'Combien de jours puis-je rester en Biélorussie sans enregistrement?',
        'Wie viele Tage kann ich ohne Registrierung in Belarus bleiben?'
    ),
    'faq1_a' => t(
        'Иностранные граждане могут находиться в Беларуси без регистрации до 5 дней с момента въезда.',
        'Foreign citizens can stay in Belarus without registration for up to 5 days from the date of entry.',
        'Cidadãos estrangeiros podem ficar na Bielorrússia sem registro por até 5 dias a partir da data de entrada.',
        'Les citoyens étrangers peuvent séjourner en Biélorussie sans enregistrement jusqu\'à 5 jours à compter de la date d\'entrée.',
        'Ausländische Bürger können sich ohne Registrierung bis zu 5 Tage ab dem Einreisedatum in Belarus aufhalten.'
    ),
    'faq2_q' => t(
        'Нужна ли медицинская страховка?',
        'Is medical insurance required?',
        'O seguro médico é necessário?',
        'L\'assurance maladie est-elle obligatoire?',
        'Ist eine Krankenversicherung erforderlich?'
    ),
    'faq2_a' => t(
        'Да, медицинская страховка обязательна для всех иностранных граждан, прибывающих в Беларусь.',
        'Yes, medical insurance is mandatory for all foreign citizens arriving in Belarus.',
        'Sim, o seguro médico é obrigatório para todos os cidadãos estrangeiros que chegam à Bielorrússia.',
        'Oui, l\'assurance maladie est obligatoire pour tous les citoyens étrangers arrivant en Biélorussie.',
        'Ja, eine Krankenversicherung ist für alle ausländischen Bürger, die nach Belarus einreisen, obligatorisch.'
    ),
    'faq3_q' => t(
        'Можно ли работать по туристической визе?',
        'Can I work on a tourist visa?',
        'Posso trabalhar com visto de turista?',
        'Puis-je travailler avec un visa touristique?',
        'Kann ich mit einem Touristenvisum arbeiten?'
    ),
    'faq3_a' => t(
        'Нет, работа по туристической визе запрещена. Для работы необходимо оформить рабочую визу и разрешение на работу.',
        'No, work on a tourist visa is prohibited. To work, you need to obtain a work visa and work permit.',
        'Não, o trabalho com visto de turista é proibido. Para trabalhar, é necessário obter visto de trabalho e autorização de trabalho.',
        'Non, le travail avec un visa touristique est interdit. Pour travailler, vous devez obtenir un visa de travail et un permis de travail.',
        'Nein, die Arbeit mit einem Touristenvisum ist verboten. Zum Arbeiten benötigen Sie ein Arbeitsvisum und eine Arbeitserlaubnis.'
    ),
    'faq4_q' => t(
        'Как продлить визу?',
        'How to extend a visa?',
        'Como prorrogar um visto?',
        'Comment prolonger un visa?',
        'Wie kann ich ein Visum verlängern?'
    ),
    'faq4_a' => t(
        'Для продления визы необходимо обратиться в управление по гражданству и миграции не позднее чем за 15 дней до истечения срока действия визы.',
        'To extend a visa, you must apply to the citizenship and migration department no later than 15 days before the visa expires.',
        'Para prorrogar um visto, você deve solicitar ao departamento de cidadania e migração o mais tardar 15 dias antes do vencimento do visto.',
        'Pour prolonger un visa, vous devez vous adresser au service de la citoyenneté et des migrations au plus tard 15 jours avant l\'expiration du visa.',
        'Um ein Visum zu verlängern, müssen Sie sich spätestens 15 Tage vor Ablauf des Visums an die Abteilung für Staatsbürgerschaft und Migration wenden.'
    ),
    'faq5_q' => t(
        'Что делать при утере паспорта?',
        'What to do if passport is lost?',
        'O que fazer se o passaporte for perdido?',
        'Que faire en cas de perte du passeport?',
        'Was tun, wenn der Reisepass verloren geht?'
    ),
    'faq5_a' => t(
        'Немедленно обратиться в полицию и в консульство своей страны для получения временного документа.',
        'Immediately contact the police and the consulate of your country to obtain a temporary document.',
        'Entre imediatamente em contato com a polícia e o consulado de seu país para obter um documento temporário.',
        'Contactez immédiatement la police et le consulat de votre pays pour obtenir un document temporaire.',
        'Wenden Sie sich sofort an die Polizei und das Konsulat Ihres Landes, um ein vorläufiges Dokument zu erhalten.'
    ),
    'latest_news' => t(
        'Последние новости',
        'Latest News',
        'Últimas Notícias',
        'Dernières Nouvelles',
        'Aktuelle Nachrichten'
    ),
    'updated_today' => t('Обновлено сегодня', 'Updated Today', 'Atualizado Hoje', 'Mis à jour aujourd\'hui', 'Heute aktualisiert'),
    'legislation' => t('Законодательство', 'Legislation', 'Legislação', 'Législation', 'Gesetzgebung'),
    'news' => t('Новости', 'News', 'Notícias', 'Actualités', 'Nachrichten'),
    'news_immigration_rules' => t(
        'Изменения в правилах иммиграции',
        'Changes in Immigration Rules',
        'Mudanças nas Regras de Imigração',
        'Changements dans les règles d\'immigration',
        'Änderungen der Einwanderungsvorschriften'
    ),
    'news_immigration_rules_text' => t(
        'С 1 февраля 2026 года вступают в силу новые правила оформления временного проживания для иностранных граждан. Упрощена процедура для высококвалифицированных специалистов.',
        'From February 1, 2026, new rules for temporary residence permits for foreign citizens come into force. The procedure for highly qualified specialists has been simplified.',
        'A partir de 1 de fevereiro de 2026, novas regras para autorizações de residência temporária para cidadãos estrangeiros entram em vigor. O procedimento para especialistas altamente qualificados foi simplificado.',
        'À partir du 1er février 2026, de nouvelles règles pour les permis de séjour temporaire pour les citoyens étrangers entrent en vigueur. La procédure pour les spécialistes hautement qualifiés a été simplifiée.',
        'Ab dem 1. Februar 2026 treten neue Regeln für Aufenthaltsgenehmigungen für ausländische Bürger in Kraft. Das Verfahren für hochqualifizierte Fachkräfte wurde vereinfacht.'
    ),
    'news_refugee_support' => t(
        'Расширение программ поддержки беженцев',
        'Expansion of Refugee Support Programs',
        'Expansão dos Programas de Apoio a Refugiados',
        'Expansion des programmes de soutien aux réfugiés',
        'Ausweitung der Flüchtlingsunterstützungsprogramme'
    ),
    'news_refugee_support_text' => t(
        'В 2026 году планируется расширение программ социальной адаптации и языковой подготовки для беженцев в Беларуси. Бюджет программы увеличен на 25%.',
        'In 2026, it is planned to expand social adaptation and language training programs for refugees in Belarus. The program budget has been increased by 25%.',
        'Em 2026, planeja-se expandir os programas de adaptação social e treinamento de idiomas para refugiados na Bielorrússia. O orçamento do programa foi aumentado em 25%.',
        'En 2026, il est prévu d\'étendre les programmes d\'adaptation sociale et de formation linguistique pour les réfugiés en Biélorussie. Le budget du programme a été augmenté de 25%.',
        '2026 ist die Ausweitung der Programme zur sozialen Anpassung und Sprachausbildung für Flüchtlinge in Belarus geplant. Das Programmbudget wurde um 25 % erhöht.'
    ),
    'news_visa_center' => t(
        'Открытие нового визового центра',
        'Opening of a New Visa Center',
        'Abertura de um Novo Centro de Vistos',
        'Ouverture d\'un nouveau centre de visas',
        'Eröffnung eines neuen Visazentrums'
    ),
    'news_visa_center_text' => t(
        'В Минске открылся новый визовый центр для граждан стран Азии. Центр работает по расширенному графику и предлагает консультации на нескольких языках.',
        'A new visa center for citizens of Asian countries has opened in Minsk. The center operates on an extended schedule and offers consultations in several languages.',
        'Um novo centro de vistos para cidadãos de países asiáticos foi inaugurado em Minsk. O centro opera em horário estendido e oferece consultas em vários idiomas.',
        'Un nouveau centre de visas pour les citoyens des pays asiatiques a ouvert à Minsk. Le centre fonctionne selon un horaire élargi et propose des consultations en plusieurs langues.',
        'Ein neues Visazentrum für Bürger asiatischer Länder hat in Minsk eröffnet. Das Zentrum hat erweiterte Öffnungszeiten und bietet Beratungen in mehreren Sprachen an.'
    ),
    'news_language_courses' => t(
        'Бесплатные курсы белорусского языка',
        'Free Belarusian Language Courses',
        'Cursos Gratuitos de Bielorrusso',
        'Cours gratuits de biélorusse',
        'Kostenlose Belarussischkurse'
    ),
    'news_language_courses_text' => t(
        'С января 2026 года стартует программа бесплатного обучения белорусскому языку для мигрантов. Курсы будут доступны в 6 городах Беларуси.',
        'From January 2026, a program of free Belarusian language training for migrants will start. Courses will be available in 6 cities of Belarus.',
        'A partir de janeiro de 2026, começará um programa de treinamento gratuito da língua bielorrussa para migrantes. Os cursos estarão disponíveis em 6 cidades da Bielorrússia.',
        'À partir de janvier 2026, un programme de formation gratuite en langue biélorusse pour les migrants débutera. Les cours seront disponibles dans 6 villes de Biélorussie.',
        'Ab Januar 2026 startet ein Programm für kostenloses Belarussisch-Training für Migranten. Die Kurse werden in 6 Städten in Belarus verfügbar sein.'
    ),
    'hotline' => t('Горячая линия', 'Hotline', 'Linha Direta', 'Ligne d\'assistance', 'Hotline'),
    'quick_links' => t('Быстрые ссылки', 'Quick Links', 'Links Rápidos', 'Liens Rapides', 'Schnelllinks'),
    'contacts' => t('Контакты', 'Contacts', 'Contatos', 'Contacts', 'Kontakte'),
    'select' => t('Выберите', 'Select', 'Selecione', 'Sélectionner', 'Auswählen'),
    'population' => t('Население', 'Population', 'População', 'Population', 'Bevölkerung'),
    'area' => t('Площадь', 'Area', 'Área', 'Superficie', 'Fläche'),
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
    )
];
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
    
    <!-- Единая мобильная адаптивность -->
    <link rel="stylesheet" href="css/mobile-responsive.css">
    
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
            background: #1a1a2e;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gradient-dark);
            background-attachment: fixed;
            color: var(--light);
            line-height: 1.7;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        @supports (-webkit-touch-callout: none) {
            body {
                background-attachment: scroll;
            }
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
            pointer-events: none;
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
            margin-left: auto;
        }

        .language-selector {
            display: flex;
            gap: 5px;
            flex-wrap: nowrap;
            align-items: center;
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
            transform: translateX(-10px);
        }

        .burger-menu.active .burger-line:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
            background: var(--accent);
        }

        /* Mobile navigation */
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
            transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .mobile-nav.active {
            max-height: 500px;
        }
        
        .mobile-nav-tabs {
            display: flex;
            flex-direction: column;
            list-style: none;
            padding: 15px;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
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
            transform: translateX(-10px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .mobile-nav.active .mobile-nav-tab {
            transform: translateX(0);
            opacity: 1;
        }
        
        .mobile-nav.active .mobile-nav-tab:nth-child(1) { transition-delay: 0.1s; }
        .mobile-nav.active .mobile-nav-tab:nth-child(2) { transition-delay: 0.15s; }
        .mobile-nav.active .mobile-nav-tab:nth-child(3) { transition-delay: 0.2s; }
        .mobile-nav.active .mobile-nav-tab:nth-child(4) { transition-delay: 0.25s; }
        .mobile-nav.active .mobile-nav-tab:nth-child(5) { transition-delay: 0.3s; }
        .mobile-nav.active .mobile-nav-tab:nth-child(6) { transition-delay: 0.35s; }
        .mobile-nav.active .mobile-nav-tab:nth-child(7) { transition-delay: 0.4s; }
        .mobile-nav.active .mobile-nav-tab:nth-child(8) { transition-delay: 0.45s; }
        
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
            transform: translateX(5px) !important;
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
            background-image: url('https://images.unsplash.com/photo-1589829545856-d10d557cf95f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-blend-mode: overlay;
            background-color: rgba(26, 26, 46, 0.8);
            animation: fadeInUp 1s ease;
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
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            color: white;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            position: relative;
            z-index: 1;
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
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
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
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: left 0.6s ease;
        }

        .service-card:hover::before {
            left: 100%;
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
            transition: var(--transition);
        }

        .service-info:hover {
            transform: translateX(5px);
            color: white;
        }

        .service-info i {
            color: var(--accent);
            margin-top: 3px;
            min-width: 16px;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .service-info:hover i {
            transform: scale(1.2);
        }

        .service-card p {
            color: var(--gray-light);
            line-height: 1.7;
        }

        /* Step numbers */
        .step-number {
            background: var(--gradient-primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
            animation: pulse 2s infinite;
        }

        /* FAQ items */
        .faq-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: var(--radius);
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .faq-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-left-color: var(--primary);
            transform: translateX(5px);
        }

        .faq-item h4 {
            color: white;
            margin-bottom: 10px;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .faq-item:hover h4 {
            color: var(--primary-light);
        }

        .faq-item p {
            color: var(--gray-light);
            line-height: 1.7;
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
                display: block;
                top: 60px;
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
            
            .lang-btn {
                padding: 6px 8px;
                font-size: 0.75rem;
                min-width: 42px;
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
            
            .lang-btn {
                padding: 5px 7px;
                font-size: 0.72rem;
                min-width: 38px;
            }
        }
        
        @media (max-width: 400px) {
            .lang-btn {
                padding: 4px 6px;
                font-size: 0.68rem;
                min-width: 34px;
            }
        }

        /* Слайдер для типов виз */
        .services-slider-container {
            position: relative;
            margin-top: 20px;
            overflow: hidden;
            padding: 0 50px;
        }

        .services-slider {
            display: flex;
            gap: 30px;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }

        .service-slide {
            flex: 0 0 calc(33.333% - 20px);
            min-width: calc(33.333% - 20px);
            min-height: 280px;
            background: rgba(255, 255, 255, 0.05);
            padding: 35px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            display: flex;
            flex-direction: column;
        }

        .service-slide h4 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            line-height: 1.3;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 60px;
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 45px;
            height: 45px;
            background: var(--gradient-primary);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: none;
        }

        .slider-btn:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: none;
        }

        .slider-btn:active {
            transform: translateY(-50%) scale(0.95);
        }

        .slider-btn.prev {
            left: 0;
        }

        .slider-btn.next {
            right: 0;
        }

        .slider-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            background: rgba(255, 255, 255, 0.1);
        }

        .slider-btn:disabled:hover {
            transform: translateY(-50%) scale(1);
            box-shadow: none;
        }

        /* Адаптивность слайдера */
        @media (max-width: 1200px) {
            .service-slide {
                flex: 0 0 calc(50% - 15px);
                min-width: calc(50% - 15px);
            }
        }

        @media (max-width: 768px) {
            .services-slider-container {
                padding: 0 40px;
            }

            .service-slide {
                flex: 0 0 100%;
                min-width: 100%;
            }

            .slider-btn {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Декоративные элементы -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>

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
                        <?php if (isset($userType) && $userType === 'admin'): ?>
                            <a href="dashboard.php" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.8rem;">
                                <i class="fas fa-cog"></i> <?php echo $translations['admin_panel']; ?>
                            </a>
                        <?php endif; ?>
                        <div class="profile-dropdown">
                            <div class="user-avatar" id="profileAvatar" title="<?php echo t('Перейти в профиль', 'Go to Profile', 'Ir para o Perfil', 'Aller au Profil', 'Zum Profil gehen'); ?>">
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
                <ul class="nav-tabs" id="mainTabs">
                    <li class="nav-tab">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-home"></i> <?php echo $translations['home']; ?>
                        </a>
                    </li>
                    <li class="nav-tab active">
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
                    <li class="mobile-nav-tab active">
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
            <h1 class="hero-title"><?php echo $translations['info_title']; ?></h1>
            <p class="hero-subtitle"><?php echo $translations['info_desc']; ?></p>
        </div>

        <!-- Registration Process -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-passport"></i> <?php echo $translations['registration_process']; ?>
            </h2>
            <div style="display: grid; gap: 15px;">
                <div style="display: flex; gap: 15px; padding: 20px; background: rgba(255, 255, 255, 0.05); border-radius: var(--radius); transition: var(--transition);">
                    <div class="step-number">1</div>
                    <div>
                        <h4 style="margin-bottom: 8px; color: white; font-size: 1.1rem;"><?php echo $translations['step1_title']; ?></h4>
                        <p style="color: var(--gray-light); line-height: 1.7;"><?php echo $translations['step1_desc']; ?></p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; padding: 20px; background: rgba(255, 255, 255, 0.05); border-radius: var(--radius); transition: var(--transition);">
                    <div class="step-number">2</div>
                    <div>
                        <h4 style="margin-bottom: 8px; color: white; font-size: 1.1rem;"><?php echo $translations['step2_title']; ?></h4>
                        <p style="color: var(--gray-light); line-height: 1.7;"><?php echo $translations['step2_desc']; ?></p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; padding: 20px; background: rgba(255, 255, 255, 0.05); border-radius: var(--radius); transition: var(--transition);">
                    <div class="step-number">3</div>
                    <div>
                        <h4 style="margin-bottom: 8px; color: white; font-size: 1.1rem;"><?php echo $translations['step3_title']; ?></h4>
                        <p style="color: var(--gray-light); line-height: 1.7;"><?php echo $translations['step3_desc']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Required Documents -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-file-alt"></i> <?php echo $translations['required_docs']; ?>
            </h2>
            <div class="services-grid">
                <div class="service-card">
                    <h4><i class="fas fa-id-card"></i> <?php echo $translations['main_docs']; ?></h4>
                    <div class="service-info">
                        <i class="fas fa-check"></i>
                        <span><?php echo $translations['passport']; ?></span>
                    </div>
                    <div class="service-info">
                        <i class="fas fa-check"></i>
                        <span><?php echo $translations['migration_card']; ?></span>
                    </div>
                    <div class="service-info">
                        <i class="fas fa-check"></i>
                        <span><?php echo $translations['purpose_docs']; ?></span>
                    </div>
                </div>
                
                <div class="service-card">
                    <h4><i class="fas fa-shield-alt"></i> <?php echo $translations['additional_docs']; ?></h4>
                    <div class="service-info">
                        <i class="fas fa-check"></i>
                        <span><?php echo $translations['insurance']; ?></span>
                    </div>
                    <div class="service-info">
                        <i class="fas fa-check"></i>
                        <span><?php echo $translations['photos']; ?></span>
                    </div>
                    <div class="service-info">
                        <i class="fas fa-check"></i>
                        <span><?php echo $translations['fee_receipt']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visa Types -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-stamp"></i> <?php echo $translations['visa_types']; ?>
            </h2>
            <div class="services-slider-container">
                <button class="slider-btn prev" id="prevBtnVisa" onclick="slideVisa('prev')">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="services-slider" id="visaSlider">
                    <div class="service-slide">
                        <h4><i class="fas fa-umbrella-beach"></i> <?php echo $translations['tourist_visa']; ?></h4>
                        <p style="color: var(--gray-light); line-height: 1.7; flex-grow: 1;"><?php echo $translations['tourist_visa_desc']; ?></p>
                    </div>
                    
                    <div class="service-slide">
                        <h4><i class="fas fa-briefcase"></i> <?php echo $translations['business_visa']; ?></h4>
                        <p style="color: var(--gray-light); line-height: 1.7; flex-grow: 1;"><?php echo $translations['business_visa_desc']; ?></p>
                    </div>
                    
                    <div class="service-slide">
                        <h4><i class="fas fa-tools"></i> <?php echo $translations['work_visa']; ?></h4>
                        <p style="color: var(--gray-light); line-height: 1.7; flex-grow: 1;"><?php echo $translations['work_visa_desc']; ?></p>
                    </div>
                    
                    <div class="service-slide">
                        <h4><i class="fas fa-graduation-cap"></i> <?php echo $translations['student_visa']; ?></h4>
                        <p style="color: var(--gray-light); line-height: 1.7; flex-grow: 1;"><?php echo $translations['student_visa_desc']; ?></p>
                    </div>
                </div>
                <button class="slider-btn next" id="nextBtnVisa" onclick="slideVisa('next')">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Rights and Obligations -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-balance-scale"></i> <?php echo $translations['rights_obligations']; ?>
            </h2>
            <div class="services-grid">
                <div class="service-card">
                    <h4><i class="fas fa-hand-paper"></i> <?php echo $translations['rights']; ?></h4>
                    <ul style="color: var(--gray-light); padding-left: 20px; line-height: 1.8;">
                        <?php foreach ($translations['rights_list'] as $right): ?>
                            <li><?php echo $right; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="service-card">
                    <h4><i class="fas fa-tasks"></i> <?php echo $translations['obligations']; ?></h4>
                    <ul style="color: var(--gray-light); padding-left: 20px; line-height: 1.8;">
                        <?php foreach ($translations['obligations_list'] as $obligation): ?>
                            <li><?php echo $obligation; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Useful Contacts -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-address-book"></i> <?php echo $translations['useful_contacts']; ?>
            </h2>
            <div class="services-grid">
                <div class="service-card">
                    <h4><i class="fas fa-exclamation-triangle"></i> <?php echo $translations['emergency_services']; ?></h4>
                    <div class="service-info">
                        <i class="fas fa-shield-alt"></i>
                        <span><?php echo $translations['police']; ?>: <?php echo $translations['police_phone']; ?></span>
                    </div>
                    <div class="service-info">
                        <i class="fas fa-ambulance"></i>
                        <span><?php echo $translations['ambulance']; ?>: <?php echo $translations['ambulance_phone']; ?></span>
                    </div>
                    <div class="service-info">
                        <i class="fas fa-fire"></i>
                        <span><?php echo $translations['fire_service']; ?>: <?php echo $translations['fire_phone']; ?></span>
                    </div>
                    <div class="service-info">
                        <i class="fas fa-fire-alt"></i>
                        <span><?php echo $translations['gas_service']; ?>: <?php echo $translations['gas_phone']; ?></span>
                    </div>
                </div>
                
                <div class="service-card">
                    <h4><i class="fas fa-hands-helping"></i> <?php echo $translations['support_centers']; ?></h4>
                    <div class="service-info">
                        <i class="fas fa-passport"></i>
                        <span><?php echo $translations['migration_services']; ?></span>
                    </div>
                    <div class="service-info">
                        <i class="fas fa-gavel"></i>
                        <span><?php echo $translations['legal_aid']; ?></span>
                    </div>
                    <div class="service-info">
                        <i class="fas fa-phone-alt"></i>
                        <span><?php echo $translations['hotline']; ?>: +375 (17) 555-55-55</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-question-circle"></i> <?php echo $translations['faq']; ?>
            </h2>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div class="faq-item">
                    <h4><?php echo $translations['faq1_q']; ?></h4>
                    <p><?php echo $translations['faq1_a']; ?></p>
                </div>
                
                <div class="faq-item">
                    <h4><?php echo $translations['faq2_q']; ?></h4>
                    <p><?php echo $translations['faq2_a']; ?></p>
                </div>
                
                <div class="faq-item">
                    <h4><?php echo $translations['faq3_q']; ?></h4>
                    <p><?php echo $translations['faq3_a']; ?></p>
                </div>
                
                <div class="faq-item">
                    <h4><?php echo $translations['faq4_q']; ?></h4>
                    <p><?php echo $translations['faq4_a']; ?></p>
                </div>
                
                <div class="faq-item">
                    <h4><?php echo $translations['faq5_q']; ?></h4>
                    <p><?php echo $translations['faq5_a']; ?></p>
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
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo t('Минск, Беларусь', 'Minsk, Belarus', 'Minsk, Bielorrússia', 'Minsk, Biélorussie', 'Minsk, Belarus'); ?></li>
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
            
            // Burger menu functionality with smooth animation
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
                
                // Close mobile nav when clicking outside
                document.addEventListener('click', function(event) {
                    if (!burgerMenu.contains(event.target) && !mobileNav.contains(event.target)) {
                        burgerMenu.classList.remove('active');
                        mobileNav.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
                
                // Close mobile nav when clicking on a link
                document.querySelectorAll('.mobile-nav-link').forEach(link => {
                    link.addEventListener('click', function() {
                        burgerMenu.classList.remove('active');
                        mobileNav.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                });
            }
        });

        // Слайдер для типов виз
        let currentSlideVisa = 0;

        function getSlidesPerView() {
            if (window.innerWidth <= 768) return 1;
            if (window.innerWidth <= 1200) return 2;
            return 3;
        }

        function slideVisa(direction) {
            const slider = document.getElementById('visaSlider');
            if (!slider) return;
            
            const slides = slider.querySelectorAll('.service-slide');
            const totalSlides = slides.length;
            const slidesPerView = getSlidesPerView();
            const maxSlide = Math.max(0, totalSlides - slidesPerView);

            if (direction === 'next') {
                currentSlideVisa = Math.min(currentSlideVisa + 1, maxSlide);
            } else {
                currentSlideVisa = Math.max(currentSlideVisa - 1, 0);
            }

            updateVisaSlider();
        }

        function updateVisaSlider() {
            const slider = document.getElementById('visaSlider');
            if (!slider) return;
            
            const slides = slider.querySelectorAll('.service-slide');
            const totalSlides = slides.length;
            const slidesPerView = getSlidesPerView();
            const maxSlide = Math.max(0, totalSlides - slidesPerView);
            
            // Ограничиваем currentSlideVisa
            currentSlideVisa = Math.min(currentSlideVisa, maxSlide);
            
            const slideWidth = slides[0].offsetWidth;
            const gap = 30;
            const offset = -(currentSlideVisa * (slideWidth + gap));
            
            slider.style.transform = `translateX(${offset}px)`;

            // Обновляем состояние кнопок
            const prevBtn = document.getElementById('prevBtnVisa');
            const nextBtn = document.getElementById('nextBtnVisa');
            
            if (prevBtn) prevBtn.disabled = currentSlideVisa === 0;
            if (nextBtn) nextBtn.disabled = currentSlideVisa >= maxSlide;
        }

        // Обновление при изменении размера окна
        window.addEventListener('resize', function() {
            updateVisaSlider();
        });

        // Инициализация слайдера при загрузке
        window.addEventListener('load', function() {
            updateVisaSlider();
        });
    </script>
    
    <!-- Единая мобильная адаптивность -->
    <script src="js/mobile-responsive.js"></script>
</body>
</html>