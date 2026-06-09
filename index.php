<?php
ob_start();
session_start();
require_once 'config.php';

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

// Данные по городам Беларуси с координатами для карты
$cities = [
    'minsk' => [
        'name' => 'Минск',
        'name_ru' => 'Минск',
        'name_en' => 'Minsk',
        'name_pt' => 'Minsk',
        'name_fr' => 'Minsk',
        'name_de' => 'Minsk',
        'image' => 'img/Minsk.webp',
        'description' => 'Столица Беларуси, крупнейший политический, экономический и культурный центр страны. Современный город с развитой инфраструктурой.',
        'description_ru' => 'Столица Беларуси, крупнейший политический, экономический и культурный центр страны. Современный город с развитой инфраструктурой.',
        'description_en' => 'The capital of Belarus, the largest political, economic, and cultural center of the country. A modern city with developed infrastructure.',
        'description_pt' => 'A capital da Bielorrússia, o maior centro político, econômico e cultural do país. Uma cidade moderna com infraestrutura desenvolvida.',
        'description_fr' => 'La capitale de la Biélorussie, le plus grand centre politique, économique et culturel du pays. Une ville moderne avec une infrastructure développée.',
        'description_de' => 'Die Hauptstadt von Belarus, das größte politische, wirtschaftliche und kulturelle Zentrum des Landes. Eine moderne Stadt mit entwickelter Infrastruktur.',
        'population' => '2 009 786 человек',
        'population_ru' => '2 009 786 человек',
        'population_en' => '2,009,786 people',
        'population_pt' => '2.009.786 pessoas',
        'population_fr' => '2 009 786 personnes',
        'population_de' => '2.009.786 Einwohner',
        'area' => '409,5 км²',
        'area_ru' => '409,5 км²',
        'area_en' => '409.5 km²',
        'area_pt' => '409,5 km²',
        'area_fr' => '409,5 km²',
        'area_de' => '409,5 km²',
        'lat' => 53.9045,
        'lng' => 27.5615,
        'services' => [
            [
                'name' => 'Главное управление по гражданству и миграции',
                'name_ru' => 'Главное управление по гражданству и миграции',
                'name_en' => 'Main Department for Citizenship and Migration',
                'name_pt' => 'Departamento Principal de Cidadania e Migração',
                'name_fr' => 'Direction générale de la citoyenneté et des migrations',
                'name_de' => 'Hauptabteilung für Staatsbürgerschaft und Migration',
                'address' => 'ул. Володарского, 6',
                'address_ru' => 'ул. Володарского, 6',
                'address_en' => 'Volodarskogo st., 6',
                'address_pt' => 'Rua Volodarskogo, 6',
                'address_fr' => 'Rue Volodarskogo, 6',
                'address_de' => 'Volodarskogo Str., 6',
                'phone' => '+375 (17) 218-01-02',
                'hours' => 'Пн-Пт 9:00-18:00, обед 13:00-14:00',
                'hours_ru' => 'Пн-Пт 9:00-18:00, обед 13:00-14:00',
                'hours_en' => 'Mon-Fri 9:00-18:00, lunch 13:00-14:00',
                'hours_pt' => 'Seg-Sex 9:00-18:00, almoço 13:00-14:00',
                'hours_fr' => 'Lun-Ven 9:00-18:00, déjeuner 13:00-14:00',
                'hours_de' => 'Mo-Fr 9:00-18:00, Mittagspause 13:00-14:00',
                'email' => 'minsk@mvd.gov.by',
                'type' => 'migration',
                'website' => 'mvd.gov.by'
            ],
            [
                'name' => 'Центр адаптации мигрантов',
                'name_ru' => 'Центр адаптации мигрантов',
                'name_en' => 'Migrant Adaptation Center',
                'name_pt' => 'Centro de Adaptação de Migrantes',
                'name_fr' => 'Centre d\'adaptation des migrants',
                'name_de' => 'Migrantenanpassungszentrum',
                'address' => 'ул. Кальварийская, 62',
                'address_ru' => 'ул. Кальварийская, 62',
                'address_en' => 'Kalvariyskaya st., 62',
                'address_pt' => 'Rua Kalvariyskaya, 62',
                'address_fr' => 'Rue Kalvariyskaya, 62',
                'address_de' => 'Kalvariyskaya Str., 62',
                'phone' => '+375 (17) 234-56-78',
                'hours' => 'Пн-Сб 9:00-20:00',
                'hours_ru' => 'Пн-Сб 9:00-20:00',
                'hours_en' => 'Mon-Sat 9:00-20:00',
                'hours_pt' => 'Seg-Sáb 9:00-20:00',
                'hours_fr' => 'Lun-Sam 9:00-20:00',
                'hours_de' => 'Mo-Sa 9:00-20:00',
                'email' => 'adaptation@minsk.by',
                'type' => 'support',
                'website' => 'minsk.by'
            ],
            [
                'name' => 'Юридическая клиника для мигрантов',
                'name_ru' => 'Юридическая клиника для мигрантов',
                'name_en' => 'Legal Clinic for Migrants',
                'name_pt' => 'Clínica Jurídica para Migrantes',
                'name_fr' => 'Clinique juridique pour migrants',
                'name_de' => 'Rechtsklinik für Migranten',
                'address' => 'пр. Независимости, 65',
                'address_ru' => 'пр. Независимости, 65',
                'address_en' => 'Nezavisimosti ave., 65',
                'address_pt' => 'Av. Nezavisimosti, 65',
                'address_fr' => 'Av. Nezavisimosti, 65',
                'address_de' => 'Nezavisimosti Ave., 65',
                'phone' => '+375 (17) 299-88-77',
                'hours' => 'Пн-Пт 10:00-18:00',
                'hours_ru' => 'Пн-Пт 10:00-18:00',
                'hours_en' => 'Mon-Fri 10:00-18:00',
                'hours_pt' => 'Seg-Sex 10:00-18:00',
                'hours_fr' => 'Lun-Ven 10:00-18:00',
                'hours_de' => 'Mo-Fr 10:00-18:00',
                'email' => 'legal.migrants@edu.by',
                'type' => 'legal'
            ],
            [
                'name' => 'Медицинский центр для иностранцев',
                'name_ru' => 'Медицинский центр для иностранцев',
                'name_en' => 'Medical Center for Foreigners',
                'name_pt' => 'Centro Médico para Estrangeiros',
                'name_fr' => 'Centre médical pour étrangers',
                'name_de' => 'Medizinisches Zentrum für Ausländer',
                'address' => 'ул. Гикало, 5',
                'address_ru' => 'ул. Гикало, 5',
                'address_en' => 'Gikalo st., 5',
                'address_pt' => 'Rua Gikalo, 5',
                'address_fr' => 'Rue Gikalo, 5',
                'address_de' => 'Gikalo Str., 5',
                'phone' => '+375 (17) 222-33-44',
                'hours' => 'Ежедневно 8:00-22:00',
                'hours_ru' => 'Ежедневно 8:00-22:00',
                'hours_en' => 'Daily 8:00-22:00',
                'hours_pt' => 'Diariamente 8:00-22:00',
                'hours_fr' => 'Quotidiennement 8:00-22:00',
                'hours_de' => 'Täglich 8:00-22:00',
                'email' => 'medcenter@minsk.by',
                'type' => 'medical'
            ],
            [
                'name' => 'Центр оформления документов',
                'name_ru' => 'Центр оформления документов',
                'name_en' => 'Document Processing Center',
                'name_pt' => 'Centro de Processamento de Documentos',
                'name_fr' => 'Centre de traitement des documents',
                'name_de' => 'Dokumentenbearbeitungszentrum',
                'address' => 'ул. Немига, 40',
                'address_ru' => 'ул. Немига, 40',
                'address_en' => 'Nemiga st., 40',
                'address_pt' => 'Rua Nemiga, 40',
                'address_fr' => 'Rue Nemiga, 40',
                'address_de' => 'Nemiga Str., 40',
                'phone' => '+375 (17) 277-88-99',
                'hours' => 'Пн-Пт 8:30-17:30',
                'hours_ru' => 'Пн-Пт 8:30-17:30',
                'hours_en' => 'Mon-Fri 8:30-17:30',
                'hours_pt' => 'Seg-Sex 8:30-17:30',
                'hours_fr' => 'Lun-Ven 8:30-17:30',
                'hours_de' => 'Mo-Fr 8:30-17:30',
                'email' => 'documents@minsk.by',
                'type' => 'documents'
            ]
        ],
        'support_centers' => [
            [
                'name' => 'Белорусское общество Красного Креста',
                'name_ru' => 'Белорусское общество Красного Креста',
                'name_en' => 'Belarusian Red Cross Society',
                'name_pt' => 'Sociedade da Cruz Vermelha Bielorrussa',
                'name_fr' => 'Société du Croissant-Rouge biélorusse',
                'name_de' => 'Belarussische Rotkreuz-Gesellschaft',
                'address' => 'ул. Красная, 3',
                'address_ru' => 'ул. Красная, 3',
                'address_en' => 'Krasnaya st., 3',
                'address_pt' => 'Rua Krasnaya, 3',
                'address_fr' => 'Rue Krasnaya, 3',
                'address_de' => 'Krasnaya Str., 3',
                'phone' => '+375 (17) 227-41-89',
                'hours' => 'Пн-Пт 9:00-18:00',
                'hours_ru' => 'Пн-Пт 9:00-18:00',
                'hours_en' => 'Mon-Fri 9:00-18:00',
                'hours_pt' => 'Seg-Sex 9:00-18:00',
                'hours_fr' => 'Lun-Ven 9:00-18:00',
                'hours_de' => 'Mo-Fr 9:00-18:00',
                'email' => 'info@redcross.by',
                'type' => 'humanitarian',
                'lat' => 53.9023,
                'lng' => 27.5619,
                'website' => 'redcross.by'
            ],
            [
                'name' => 'Центр поддержки семьи и детей мигрантов',
                'name_ru' => 'Центр поддержки семьи и детей мигрантов',
                'name_en' => 'Family and Migrant Children Support Center',
                'name_pt' => 'Centro de Apoio à Família e Crianças Migrantes',
                'name_fr' => 'Centre de soutien à la famille et aux enfants migrants',
                'name_de' => 'Zentrum für Familien- und Migrantenkinderunterstützung',
                'address' => 'ул. Октябрьская, 16',
                'address_ru' => 'ул. Октябрьская, 16',
                'address_en' => 'Oktyabrskaya st., 16',
                'address_pt' => 'Rua Oktyabrskaya, 16',
                'address_fr' => 'Rue Oktyabrskaya, 16',
                'address_de' => 'Oktyabrskaya Str., 16',
                'phone' => '+375 (17) 211-22-33',
                'hours' => 'Пн-Пт 8:00-20:00, Сб 9:00-15:00',
                'hours_ru' => 'Пн-Пт 8:00-20:00, Сб 9:00-15:00',
                'hours_en' => 'Mon-Fri 8:00-20:00, Sat 9:00-15:00',
                'hours_pt' => 'Seg-Sex 8:00-20:00, Sáb 9:00-15:00',
                'hours_fr' => 'Lun-Ven 8:00-20:00, Sam 9:00-15:00',
                'hours_de' => 'Mo-Fr 8:00-20:00, Sa 9:00-15:00',
                'email' => 'family@support.by',
                'type' => 'family',
                'lat' => 53.8985,
                'lng' => 27.5578
            ]
        ]
    ],
    'grodno' => [
        'name' => 'Гродно',
        'name_ru' => 'Гродно',
        'name_en' => 'Grodno',
        'name_pt' => 'Grodno',
        'name_fr' => 'Grodno',
        'name_de' => 'Grodno',
        'image' => 'img/Grodno.jpg',
        'description' => 'Город на западе Беларуси, известный своей богатой историей и архитектурой. Культурная столица Беларуси.',
        'description_ru' => 'Город на западе Беларуси, известный своей богатой историей и архитектурой. Культурная столица Беларуси.',
        'description_en' => 'A city in western Belarus, known for its rich history and architecture. The cultural capital of Belarus.',
        'description_pt' => 'Uma cidade no oeste da Bielorrússia, conhecida por sua rica história e arquitetura. A capital cultural da Bielorrússia.',
        'description_fr' => 'Une ville de l\'ouest de la Biélorussie, connue pour sa riche histoire et son architecture. La capitale culturelle de la Biélorussie.',
        'description_de' => 'Eine Stadt im Westen von Belarus, bekannt für ihre reiche Geschichte und Architektur. Die Kulturhauptstadt von Belarus.',
        'population' => '370 919 человек',
        'population_ru' => '370 919 человек',
        'population_en' => '370,919 people',
        'population_pt' => '370.919 pessoas',
        'population_fr' => '370 919 pessoas',
        'population_de' => '370.919 Einwohner',
        'area' => '142,1 км²',
        'area_ru' => '142,1 км²',
        'area_en' => '142.1 km²',
        'area_pt' => '142,1 km²',
        'area_fr' => '142,1 km²',
        'area_de' => '142,1 km²',
        'lat' => 53.6698,
        'lng' => 23.8131,
        'services' => [
            [
                'name' => 'Отдел по гражданству и миграции',
                'name_ru' => 'Отдел по гражданству и миграции',
                'name_en' => 'Department for Citizenship and Migration',
                'name_pt' => 'Departamento de Cidadania e Migração',
                'name_fr' => 'Département de la citoyenneté et des migrations',
                'name_de' => 'Abteilung für Staatsbürgerschaft und Migration',
                'address' => 'ул. Ожешко, 3',
                'address_ru' => 'ул. Ожешко, 3',
                'address_en' => 'Ozeshko st., 3',
                'address_pt' => 'Rua Ozeshko, 3',
                'address_fr' => 'Rue Ozeshko, 3',
                'address_de' => 'Ozeshko Str., 3',
                'phone' => '+375 (152) 72-34-56',
                'hours' => 'Пн-Пт 8:00-17:00',
                'hours_ru' => 'Пн-Пт 8:00-17:00',
                'hours_en' => 'Mon-Fri 8:00-17:00',
                'hours_pt' => 'Seg-Sex 8:00-17:00',
                'hours_fr' => 'Lun-Ven 8:00-17:00',
                'hours_de' => 'Mo-Fr 8:00-17:00',
                'email' => 'grodno@mvd.gov.by',
                'type' => 'migration'
            ],
            [
                'name' => 'Консультационный центр для иностранных работников',
                'name_ru' => 'Консультационный центр для иностранных работников',
                'name_en' => 'Consultation Center for Foreign Workers',
                'name_pt' => 'Centro de Consultoria para Trabalhadores Estrangeiros',
                'name_fr' => 'Centre de consultation pour travailleurs étrangers',
                'name_de' => 'Beratungszentrum für ausländische Arbeitnehmer',
                'address' => 'ул. Советская, 8',
                'address_ru' => 'ул. Советская, 8',
                'address_en' => 'Sovetskaya st., 8',
                'address_pt' => 'Rua Sovetskaya, 8',
                'address_fr' => 'Rue Sovetskaya, 8',
                'address_de' => 'Sovetskaya Str., 8',
                'phone' => '+375 (152) 55-66-77',
                'hours' => 'Пн-Пт 9:00-18:00',
                'hours_ru' => 'Пн-Пт 9:00-18:00',
                'hours_en' => 'Mon-Fri 9:00-18:00',
                'hours_pt' => 'Seg-Sex 9:00-18:00',
                'hours_fr' => 'Lun-Ven 9:00-18:00',
                'hours_de' => 'Mo-Fr 9:00-18:00',
                'email' => 'migration@grodno.by',
                'type' => 'consultation'
            ],
            [
                'name' => 'Пограничный информационный пункт',
                'name_ru' => 'Пограничный информационный пункт',
                'name_en' => 'Border Information Point',
                'name_pt' => 'Ponto de Informação de Fronteira',
                'name_fr' => 'Point d\'information frontalier',
                'name_de' => 'Grenzinformationspunkt',
                'address' => 'ул. Пограничная, 15',
                'address_ru' => 'ул. Пограничная, 15',
                'address_en' => 'Pogranichnaya st., 15',
                'address_pt' => 'Rua Pogranichnaya, 15',
                'address_fr' => 'Rue Pogranichnaya, 15',
                'address_de' => 'Pogranichnaya Str., 15',
                'phone' => '+375 (152) 44-33-22',
                'hours' => 'Круглосуточно',
                'hours_ru' => 'Круглосуточно',
                'hours_en' => '24/7',
                'hours_pt' => '24 horas',
                'hours_fr' => '24h/24',
                'hours_de' => '24/7',
                'email' => 'border@grodno.by',
                'type' => 'border'
            ]
        ],
        'support_centers' => [
            [
                'name' => 'Гродненский центр социальной адаптации',
                'name_ru' => 'Гродненский центр социальной адаптации',
                'name_en' => 'Grodno Social Adaptation Center',
                'name_pt' => 'Centro de Adaptação Social de Grodno',
                'name_fr' => 'Centre d\'adaptation sociale de Grodno',
                'name_de' => 'Soziales Anpassungszentrum Grodno',
                'address' => 'ул. Дзержинского, 88',
                'address_ru' => 'ул. Дзержинского, 88',
                'address_en' => 'Dzerzhinskogo st., 88',
                'address_pt' => 'Rua Dzerzhinskogo, 88',
                'address_fr' => 'Rue Dzerzhinskogo, 88',
                'address_de' => 'Dzerzhinskogo Str., 88',
                'phone' => '+375 (152) 44-55-66',
                'hours' => 'Пн-Пт 9:00-17:00',
                'hours_ru' => 'Пн-Пт 9:00-17:00',
                'hours_en' => 'Mon-Fri 9:00-17:00',
                'hours_pt' => 'Seg-Sex 9:00-17:00',
                'hours_fr' => 'Lun-Ven 9:00-17:00',
                'hours_de' => 'Mo-Fr 9:00-17:00',
                'email' => 'adaptation@grodno.by',
                'type' => 'adaptation',
                'lat' => 53.6789,
                'lng' => 23.8345
            ]
        ]
    ],
    'brest' => [
        'name' => 'Брест',
        'name_ru' => 'Брест',
        'name_en' => 'Brest',
        'name_pt' => 'Brest',
        'name_fr' => 'Brest',
        'name_de' => 'Brest',
        'image' => 'img/Brest.jpg',
        'description' => 'Город-герой на границе с Польшей, известный Брестской крепостью. Крупный транспортный узел.',
        'description_ru' => 'Город-герой на границе с Польшей, известный Брестской крепостью. Крупный транспортный узел.',
        'description_en' => 'A hero city on the border with Poland, known for the Brest Fortress. A major transportation hub.',
        'description_pt' => 'Uma cidade heróica na fronteira com a Polônia, conhecida pela Fortaleza de Brest. Um importante centro de transporte.',
        'description_fr' => 'Une ville héroïque à la frontière avec la Pologne, connue pour la forteresse de Brest. Un important carrefour de transport.',
        'description_de' => 'Eine Heldenstadt an der Grenze zu Polen, bekannt für die Brester Festung. Ein wichtiger Verkehrsknotenpunkt.',
        'population' => '350 616 человек',
        'population_ru' => '350 616 человек',
        'population_en' => '350,616 people',
        'population_pt' => '350.616 pessoas',
        'population_fr' => '350 616 personnes',
        'population_de' => '350.616 Einwohner',
        'area' => '146,1 км²',
        'area_ru' => '146,1 км²',
        'area_en' => '146.1 km²',
        'area_pt' => '146,1 km²',
        'area_fr' => '146,1 km²',
        'area_de' => '146,1 km²',
        'lat' => 52.0976,
        'lng' => 23.7341,
        'services' => [
            [
                'name' => 'Управление по гражданству и миграции',
                'name_ru' => 'Управление по гражданству и миграции',
                'name_en' => 'Office for Citizenship and Migration',
                'name_pt' => 'Escritório de Cidadania e Migração',
                'name_fr' => 'Bureau de la citoyenneté et des migrations',
                'name_de' => 'Amt für Staatsbürgerschaft und Migration',
                'address' => 'ул. Ленина, 19',
                'address_ru' => 'ул. Ленина, 19',
                'address_en' => 'Lenina st., 19',
                'address_pt' => 'Rua Lenina, 19',
                'address_fr' => 'Rue Lenina, 19',
                'address_de' => 'Lenina Str., 19',
                'phone' => '+375 (162) 23-45-67',
                'hours' => 'Пн-Пт 8:30-17:30',
                'hours_ru' => 'Пн-Пт 8:30-17:30',
                'hours_en' => 'Mon-Fri 8:30-17:30',
                'hours_pt' => 'Seg-Sex 8:30-17:30',
                'hours_fr' => 'Lun-Ven 8:30-17:30',
                'hours_de' => 'Mo-Fr 8:30-17:30',
                'email' => 'brest@mvd.gov.by',
                'type' => 'migration'
            ],
            [
                'name' => 'Погранично-миграционный консультационный пункт',
                'name_ru' => 'Погранично-миграционный консультационный пункт',
                'name_en' => 'Border-Migration Consultation Point',
                'name_pt' => 'Ponto de Consulta de Fronteira-Migração',
                'name_fr' => 'Point de consultation frontière-migration',
                'name_de' => 'Grenz-Migrations-Beratungsstelle',
                'address' => 'ул. Гоголя, 32',
                'address_ru' => 'ул. Гоголя, 32',
                'address_en' => 'Gogolya st., 32',
                'address_pt' => 'Rua Gogolya, 32',
                'address_fr' => 'Rue Gogolya, 32',
                'address_de' => 'Gogolya Str., 32',
                'phone' => '+375 (162) 77-88-99',
                'hours' => 'Круглосуточно',
                'hours_ru' => 'Круглосуточно',
                'hours_en' => '24/7',
                'hours_pt' => '24 horas',
                'hours_fr' => '24h/24',
                'hours_de' => '24/7',
                'email' => 'border@brest.by',
                'type' => 'border'
            ],
            [
                'name' => 'Таможенно-миграционный центр',
                'name_ru' => 'Таможенно-миграционный центр',
                'name_en' => 'Customs and Migration Center',
                'name_pt' => 'Centro Alfandegário e de Migração',
                'name_fr' => 'Centre douanier et migratoire',
                'name_de' => 'Zoll- und Migrationszentrum',
                'address' => 'пр. Машерова, 45',
                'address_ru' => 'пр. Машерова, 45',
                'address_en' => 'Masherova ave., 45',
                'address_pt' => 'Av. Masherova, 45',
                'address_fr' => 'Av. Masherova, 45',
                'address_de' => 'Masherova Ave., 45',
                'phone' => '+375 (162) 66-55-44',
                'hours' => 'Пн-Пт 9:00-18:00',
                'hours_ru' => 'Пн-Пт 9:00-18:00',
                'hours_en' => 'Mon-Fri 9:00-18:00',
                'hours_pt' => 'Seg-Sex 9:00-18:00',
                'hours_fr' => 'Lun-Ven 9:00-18:00',
                'hours_de' => 'Mo-Fr 9:00-18:00',
                'email' => 'customs@brest.by',
                'type' => 'customs'
            ]
        ],
        'support_centers' => [
            [
                'name' => 'Брестский центр помощи беженцам',
                'name_ru' => 'Брестский центр помощи беженцам',
                'name_en' => 'Brest Refugee Assistance Center',
                'name_pt' => 'Centro de Assistência a Refugiados de Brest',
                'name_fr' => 'Centre d\'assistance aux réfugiés de Brest',
                'name_de' => 'Flüchtlingshilfezentrum Brest',
                'address' => 'ул. Московская, 267',
                'address_ru' => 'ул. Московская, 267',
                'address_en' => 'Moskovskaya st., 267',
                'address_pt' => 'Rua Moskovskaya, 267',
                'address_fr' => 'Rue Moskovskaya, 267',
                'address_de' => 'Moskovskaya Str., 267',
                'phone' => '+375 (162) 33-44-55',
                'hours' => 'Пн-Пт 8:00-20:00',
                'hours_ru' => 'Пн-Пт 8:00-20:00',
                'hours_en' => 'Mon-Fri 8:00-20:00',
                'hours_pt' => 'Seg-Sex 8:00-20:00',
                'hours_fr' => 'Lun-Ven 8:00-20:00',
                'hours_de' => 'Mo-Fr 8:00-20:00',
                'email' => 'refugees@brest.by',
                'type' => 'refugee',
                'lat' => 52.0865,
                'lng' => 23.7012
            ]
        ]
    ],
    'vitebsk' => [
        'name' => 'Витебск',
        'name_ru' => 'Витебск',
        'name_en' => 'Vitebsk',
        'name_pt' => 'Vitebsk',
        'name_fr' => 'Vitebsk',
        'name_de' => 'Wizebsk',
        'image' => 'img/Vitebsk.webp',
        'description' => 'Город на севере Беларуси, известный фестивалем "Славянский базара". Культурная жемчужина региона.',
        'description_ru' => 'Город на севере Беларуси, известный фестивалем "Славянский базара". Культурная жемчужина региона.',
        'description_en' => 'A city in northern Belarus, known for the "Slavianski Bazaar" festival. A cultural pearl of the region.',
        'description_pt' => 'Uma cidade no norte da Bielorrússia, conhecida pelo festival "Slavianski Bazaar". Uma pérola cultural da região.',
        'description_fr' => 'Une ville du nord de la Biélorussie, connue pour le festival "Slavianski Bazaar". Une perle culturelle de la région.',
        'description_de' => 'Eine Stadt im Norden von Belarus, bekannt für das Festival "Slavianski Bazaar". Ein kulturelles Juwel der Region.',
        'population' => '378 459 человек',
        'population_ru' => '378 459 человек',
        'population_en' => '378,459 people',
        'population_pt' => '378.459 pessoas',
        'population_fr' => '378 459 pessoas',
        'population_de' => '378.459 Einwohner',
        'area' => '124,5 км²',
        'area_ru' => '124,5 км²',
        'area_en' => '124.5 km²',
        'area_pt' => '124,5 km²',
        'area_fr' => '124,5 km²',
        'area_de' => '124,5 km²',
        'lat' => 55.1848,
        'lng' => 30.2029,
        'services' => [
            [
                'name' => 'Отдел по гражданству и миграции',
                'name_ru' => 'Отдел по гражданству и миграции',
                'name_en' => 'Department for Citizenship and Migration',
                'name_pt' => 'Departamento de Cidadania e Migração',
                'name_fr' => 'Département de la citoyenneté et des migrations',
                'name_de' => 'Abteilung für Staatsbürgerschaft und Migration',
                'address' => 'ул. Замковая, 5',
                'address_ru' => 'ул. Замковая, 5',
                'address_en' => 'Zamkovaya st., 5',
                'address_pt' => 'Rua Zamkovaya, 5',
                'address_fr' => 'Rue Zamkovaya, 5',
                'address_de' => 'Zamkovaya Str., 5',
                'phone' => '+375 (212) 23-45-67',
                'hours' => 'Пн-Пт 8:30-17:30',
                'hours_ru' => 'Пн-Пт 8:30-17:30',
                'hours_en' => 'Mon-Fri 8:30-17:30',
                'hours_pt' => 'Seg-Sex 8:30-17:30',
                'hours_fr' => 'Lun-Ven 8:30-17:30',
                'hours_de' => 'Mo-Fr 8:30-17:30',
                'email' => 'vitebsk@mvd.gov.by',
                'type' => 'migration'
            ],
            [
                'name' => 'Центр культурной адаптации мигрантов',
                'name_ru' => 'Центр культурной адаптации мигрантов',
                'name_en' => 'Cultural Adaptation Center for Migrants',
                'name_pt' => 'Centro de Adaptação Cultural para Migrantes',
                'name_fr' => 'Centre d\'adaptation culturelle pour migrants',
                'name_de' => 'Kulturelles Anpassungszentrum für Migranten',
                'address' => 'ул. Пушкина, 12',
                'address_ru' => 'ул. Пушкина, 12',
                'address_en' => 'Pushkina st., 12',
                'address_pt' => 'Rua Pushkina, 12',
                'address_fr' => 'Rue Pushkina, 12',
                'address_de' => 'Pushkina Str., 12',
                'phone' => '+375 (212) 34-56-78',
                'hours' => 'Пн-Пт 10:00-18:00',
                'hours_ru' => 'Пн-Пт 10:00-18:00',
                'hours_en' => 'Mon-Fri 10:00-18:00',
                'hours_pt' => 'Seg-Sex 10:00-18:00',
                'hours_fr' => 'Lun-Ven 10:00-18:00',
                'hours_de' => 'Mo-Fr 10:00-18:00',
                'email' => 'culture@vitebsk.by',
                'type' => 'cultural'
            ]
        ],
        'support_centers' => [
            [
                'name' => 'Витебский центр социальной поддержки',
                'name_ru' => 'Витебский центр социальной поддержки',
                'name_en' => 'Vitebsk Social Support Center',
                'name_pt' => 'Centro de Apoio Social de Vitebsk',
                'name_fr' => 'Centre de soutien social de Vitebsk',
                'name_de' => 'Soziales Unterstützungszentrum Wizebsk',
                'address' => 'ул. Ленина, 36',
                'address_ru' => 'ул. Ленина, 36',
                'address_en' => 'Lenina st., 36',
                'address_pt' => 'Rua Lenina, 36',
                'address_fr' => 'Rue Lenina, 36',
                'address_de' => 'Lenina Str., 36',
                'phone' => '+375 (212) 45-67-89',
                'hours' => 'Пн-Пт 9:00-17:00',
                'hours_ru' => 'Пн-Пт 9:00-17:00',
                'hours_en' => 'Mon-Fri 9:00-17:00',
                'hours_pt' => 'Seg-Sex 9:00-17:00',
                'hours_fr' => 'Lun-Ven 9:00-17:00',
                'hours_de' => 'Mo-Fr 9:00-17:00',
                'email' => 'support@vitebsk.by',
                'type' => 'social',
                'lat' => 55.1923,
                'lng' => 30.2156
            ]
        ]
    ],
    'gomel' => [
        'name' => 'Гомель',
        'name_ru' => 'Гомель',
        'name_en' => 'Gomel',
        'name_pt' => 'Gomel',
        'name_fr' => 'Gomel',
        'name_de' => 'Gomel',
        'image' => 'img/Gomel.jpg',
        'description' => 'Второй по величине город Беларуси, важный промышленный и культурный центр на юго-востоке страны.',
        'description_ru' => 'Второй по величине город Беларуси, важный промышленный и культурный центр на юго-востоке страны.',
        'description_en' => 'The second largest city in Belarus, an important industrial and cultural center in the southeast of the country.',
        'description_pt' => 'A segunda maior cidade da Bielorrússia, um importante centro industrial e cultural no sudeste do país.',
        'description_fr' => 'La deuxième plus grande ville de Biélorussie, un important centre industriel et culturel dans le sud-est du pays.',
        'description_de' => 'Die zweitgrößte Stadt in Belarus, ein wichtiges Industrie- und Kulturzentrum im Südosten des Landes.',
        'population' => '535 693 человек',
        'population_ru' => '535 693 человек',
        'population_en' => '535,693 people',
        'population_pt' => '535.693 pessoas',
        'population_fr' => '535 693 pessoas',
        'population_de' => '535.693 Einwohner',
        'area' => '139,8 км²',
        'area_ru' => '139,8 км²',
        'area_en' => '139.8 km²',
        'area_pt' => '139,8 km²',
        'area_fr' => '139,8 km²',
        'area_de' => '139,8 km²',
        'lat' => 52.4242,
        'lng' => 31.0143,
        'services' => [
            [
                'name' => 'Управление по гражданству и миграции',
                'name_ru' => 'Управление по гражданству и миграции',
                'name_en' => 'Office for Citizenship and Migration',
                'name_pt' => 'Escritório de Cidadania e Migração',
                'name_fr' => 'Bureau de la citoyenneté et des migrations',
                'name_de' => 'Amt für Staatsbürgerschaft und Migration',
                'address' => 'пр. Ленина, 10',
                'address_ru' => 'пр. Ленина, 10',
                'address_en' => 'Lenina ave., 10',
                'address_pt' => 'Av. Lenina, 10',
                'address_fr' => 'Av. Lenina, 10',
                'address_de' => 'Lenina Ave., 10',
                'phone' => '+375 (232) 34-56-78',
                'hours' => 'Пн-Пт 8:00-17:00',
                'hours_ru' => 'Пн-Пт 8:00-17:00',
                'hours_en' => 'Mon-Fri 8:00-17:00',
                'hours_pt' => 'Seg-Sex 8:00-17:00',
                'hours_fr' => 'Lun-Ven 8:00-17:00',
                'hours_de' => 'Mo-Fr 8:00-17:00',
                'email' => 'gomel@mvd.gov.by',
                'type' => 'migration'
            ],
            [
                'name' => 'Центр медицинского освидетельствования иностранцев',
                'name_ru' => 'Центр медицинского освидетельствования иностранцев',
                'name_en' => 'Foreigners Medical Examination Center',
                'name_pt' => 'Centro de Exame Médico para Estrangeiros',
                'name_fr' => 'Centre d\'examen médical pour étrangers',
                'name_de' => 'Medizinisches Untersuchungszentrum für Ausländer',
                'address' => 'ул. Интернациональная, 35',
                'address_ru' => 'ул. Интернациональная, 35',
                'address_en' => 'Internatsionalnaya st., 35',
                'address_pt' => 'Rua Internatsionalnaya, 35',
                'address_fr' => 'Rue Internatsionalnaya, 35',
                'address_de' => 'Internatsionalnaya Str., 35',
                'phone' => '+375 (232) 77-88-99',
                'hours' => 'Пн-Пт 8:00-16:00',
                'hours_ru' => 'Пн-Пт 8:00-16:00',
                'hours_en' => 'Mon-Fri 8:00-16:00',
                'hours_pt' => 'Seg-Sex 8:00-16:00',
                'hours_fr' => 'Lun-Ven 8:00-16:00',
                'hours_de' => 'Mo-Fr 8:00-16:00',
                'email' => 'medical@gomel.by',
                'type' => 'medical'
            ]
        ],
        'support_centers' => [
            [
                'name' => 'Гомельский центр помощи трудовым мигрантам',
                'name_ru' => 'Гомельский центр помощи трудовым мигрантам',
                'name_en' => 'Gomel Labor Migrant Assistance Center',
                'name_pt' => 'Centro de Assistência a Trabalhadores Migrantes de Gomel',
                'name_fr' => 'Centre d\'assistance aux travailleurs migrants de Gomel',
                'name_de' => 'Hilfszentrum für Arbeitsmigranten Gomel',
                'address' => 'ул. Советская, 25',
                'address_ru' => 'ул. Советская, 25',
                'address_en' => 'Sovetskaya st., 25',
                'address_pt' => 'Rua Sovetskaya, 25',
                'address_fr' => 'Rue Sovetskaya, 25',
                'address_de' => 'Sovetskaya Str., 25',
                'phone' => '+375 (232) 22-33-44',
                'hours' => 'Пн-Пт 9:00-18:00',
                'hours_ru' => 'Пн-Пт 9:00-18:00',
                'hours_en' => 'Mon-Fri 9:00-18:00',
                'hours_pt' => 'Seg-Sex 9:00-18:00',
                'hours_fr' => 'Lun-Ven 9:00-18:00',
                'hours_de' => 'Mo-Fr 9:00-18:00',
                'email' => 'labor@gomel.by',
                'type' => 'labor',
                'lat' => 52.4345,
                'lng' => 31.0098
            ]
        ]
    ],
    'mogilev' => [
        'name' => 'Могилёв',
        'name_ru' => 'Могилёв',
        'name_en' => 'Mogilev',
        'name_pt' => 'Mogilev',
        'name_fr' => 'Mogilev',
        'name_de' => 'Mahiljou',
        'image' => 'img/Mogilev.jpg',
        'description' => 'Крупный промышленный и культурный центр на востоке Беларуси. Город с богатой историей.',
        'description_ru' => 'Крупный промышленный и культурный центр на востоке Беларуси. Город с богатой историей.',
        'description_en' => 'A major industrial and cultural center in eastern Belarus. A city with rich history.',
        'description_pt' => 'Um importante centro industrial e cultural no leste da Bielorrússia. Uma cidade com história rica.',
        'description_fr' => 'Un important centre industriel et culturel dans l\'est de la Biélorussie. Une ville à la riche histoire.',
        'description_de' => 'Ein bedeutendes Industrie- und Kulturzentrum im Osten von Belarus. Eine Stadt mit reicher Geschichte.',
        'population' => '380 440 человек',
        'population_ru' => '380 440 человек',
        'population_en' => '380,440 people',
        'population_pt' => '380.440 pessoas',
        'population_fr' => '380 440 pessoas',
        'population_de' => '380.440 Einwohner',
        'area' => '118,5 км²',
        'area_ru' => '118,5 км²',
        'area_en' => '118.5 km²',
        'area_pt' => '118,5 km²',
        'area_fr' => '118,5 km²',
        'area_de' => '118,5 km²',
        'lat' => 53.8945,
        'lng' => 30.3307,
        'services' => [
            [
                'name' => 'Отдел по гражданству и миграции',
                'name_ru' => 'Отдел по гражданству и миграции',
                'name_en' => 'Department for Citizenship and Migration',
                'name_pt' => 'Departamento de Cidadania e Migração',
                'name_fr' => 'Département de la citoyenneté et des migrations',
                'name_de' => 'Abteilung für Staatsbürgerschaft und Migration',
                'address' => 'ул. Первомайская, 22',
                'address_ru' => 'ул. Первомайская, 22',
                'address_en' => 'Pervomayskaya st., 22',
                'address_pt' => 'Rua Pervomayskaya, 22',
                'address_fr' => 'Rue Pervomayskaya, 22',
                'address_de' => 'Pervomayskaya Str., 22',
                'phone' => '+375 (222) 45-67-89',
                'hours' => 'Пн-Пт 9:00-18:00',
                'hours_ru' => 'Пн-Пт 9:00-18:00',
                'hours_en' => 'Mon-Fri 9:00-18:00',
                'hours_pt' => 'Seg-Sex 9:00-18:00',
                'hours_fr' => 'Lun-Ven 9:00-18:00',
                'hours_de' => 'Mo-Fr 9:00-18:00',
                'email' => 'mogilev@mvd.gov.by',
                'type' => 'migration'
            ],
            [
                'name' => 'Информационно-консультационный пункт для мигрантов',
                'name_ru' => 'Информационно-консультационный пункт для мигрантов',
                'name_en' => 'Information and Consultation Point for Migrants',
                'name_pt' => 'Ponto de Informação e Consulta para Migrantes',
                'name_fr' => 'Point d\'information et de consultation pour migrants',
                'name_de' => 'Informations- und Beratungsstelle für Migranten',
                'address' => 'ул. Ленинская, 58',
                'address_ru' => 'ул. Ленинская, 58',
                'address_en' => 'Leninskaya st., 58',
                'address_pt' => 'Rua Leninskaya, 58',
                'address_fr' => 'Rue Leninskaya, 58',
                'address_de' => 'Leninskaya Str., 58',
                'phone' => '+375 (222) 33-44-55',
                'hours' => 'Пн-Пт 9:00-17:00',
                'hours_ru' => 'Пн-Пт 9:00-17:00',
                'hours_en' => 'Mon-Fri 9:00-17:00',
                'hours_pt' => 'Seg-Sex 9:00-17:00',
                'hours_fr' => 'Lun-Ven 9:00-17:00',
                'hours_de' => 'Mo-Fr 9:00-17:00',
                'email' => 'info@mogilev-migrants.by',
                'type' => 'information'
            ]
        ],
        'support_centers' => [
            [
                'name' => 'Могилевский центр социальной интеграции',
                'name_ru' => 'Могилевский центр социальной интеграции',
                'name_en' => 'Mogilev Social Integration Center',
                'name_pt' => 'Centro de Integração Social de Mogilev',
                'name_fr' => 'Centre d\'intégration sociale de Mogilev',
                'name_de' => 'Soziales Integrationszentrum Mahiljou',
                'address' => 'ул. Челюскинцев, 12',
                'address_ru' => 'ул. Челюскинцев, 12',
                'address_en' => 'Chelyuskintsev st., 12',
                'address_pt' => 'Rua Chelyuskintsev, 12',
                'address_fr' => 'Rue Chelyuskintsev, 12',
                'address_de' => 'Chelyuskintsev Str., 12',
                'phone' => '+375 (222) 66-77-88',
                'hours' => 'Пн-Пт 8:30-17:30',
                'hours_ru' => 'Пн-Пт 8:30-17:30',
                'hours_en' => 'Mon-Fri 8:30-17:30',
                'hours_pt' => 'Seg-Sex 8:30-17:30',
                'hours_fr' => 'Lun-Ven 8:30-17:30',
                'hours_de' => 'Mo-Fr 8:30-17:30',
                'email' => 'integration@mogilev.by',
                'type' => 'integration',
                'lat' => 53.9023,
                'lng' => 30.3401
            ]
        ]
    ]
];

// Город по умолчанию
$currentCity = $_GET['city'] ?? $userCity;
if (!array_key_exists($currentCity, $cities)) {
    $currentCity = 'minsk';
}
$cityData = $cities[$currentCity];

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

// Функция для получения переведенных данных из массива - ИСПРАВЛЕНА
function getTranslated($data, $key) {
    global $lang;
    
    // Проверяем наличие перевода для текущего языка
    $langKey = $key . '_' . $lang;
    if (isset($data[$langKey]) && !empty($data[$langKey])) {
        return $data[$langKey];
    }
    
    // Для русского языка проверяем базовый ключ, если нет перевода с суффиксом
    if ($lang === 'ru' && isset($data[$key]) && !empty($data[$key])) {
        return $data[$key];
    }
    
    // Если нет, используем английский
    $enKey = $key . '_en';
    if (isset($data[$enKey]) && !empty($data[$enKey])) {
        return $data[$enKey];
    }
    
    // Если нет английского, используем базовый ключ
    if (isset($data[$key]) && !empty($data[$key])) {
        return $data[$key];
    }
    
    return '';
}

// Тексты для перевода с поддержкой всех языков
$translations = [
    'main_title' => t(
        'MigraSupport - Система поддержки мигрантов в Беларуси',
        'MigraSupport - Migrant Support System in Belarus',
        'MigraSupport - Sistema de Apoio a Migrantes na Bielorrússia',
        'MigraSupport - Système de soutien aux migrants en Biélorussie',
        'MigraSupport - Migrantenunterstützungssystem in Belarus'
    ),
    'welcome_title' => t(
        'Добро пожаловать в MigraSupport',
        'Welcome to MigraSupport',
        'Bem-vindo ao MigraSupport',
        'Bienvenue sur MigraSupport',
        'Willkommen bei MigraSupport'
    ),
    'welcome_desc' => t(
        'Комплексная система поддержки мигрантов в Беларуси. Получите помощь, консультации и общайтесь с сообществом.',
        'Comprehensive migrant support system in Belarus. Get help, consultations and communicate with the community.',
        'Sistema abrangente de apoio a migrantes na Bielorrússia. Obtenha ajuda, consultas e comunique-se com a comunidade.',
        'Système complet de soutien aux migrants en Biélorussie. Obtenez de l\'aide, des consultations et communiquez avec la communauté.',
        'Umfassendes Migrantenunterstützungssystem in Belarus. Holen Sie sich Hilfe, Beratung und kommunizieren Sie mit der Gemeinschaft.'
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
    'choose_city' => t(
        'Выберите ваш город',
        'Choose Your City',
        'Escolha sua cidade',
        'Choisissez votre ville',
        'Wählen Sie Ihre Stadt'
    ),
    'population' => t('Население', 'Population', 'População', 'Population', 'Bevölkerung'),
    'area' => t('Площадь', 'Area', 'Área', 'Superficie', 'Fläche'),
    'migration_services' => t(
        'Миграционные службы',
        'Migration Services',
        'Serviços de Migração',
        'Services de Migration',
        'Migrationsdienste'
    ),
    'address' => t('Адрес', 'Address', 'Endereço', 'Adresse', 'Adresse'),
    'phone' => t('Телефон', 'Phone', 'Telefone', 'Téléphone', 'Telefon'),
    'hours' => t('Часы работы', 'Working Hours', 'Horário de Funcionamento', 'Heures d\'ouverture', 'Öffnungszeiten'),
    'email' => t('Email', 'Email', 'E-mail', 'E-Mail', 'E-Mail'),
    'website' => t('Веб-сайт', 'Website', 'Site da Web', 'Site Web', 'Webseite'),
    'view_on_map' => t('Показать на карте', 'Show on Map', 'Mostrar no Mapa', 'Afficher sur la carte', 'Auf Karte anzeigen'),
    'hero_title' => t(
        'Ваш надежный помощник в Беларуси',
        'Your Reliable Assistant in Belarus',
        'Seu Assistente Confiável na Bielorrússia',
        'Votre assistant fiable en Biélorussie',
        'Ihr zuverlässiger Assistent in Belarus'
    ),
    'hero_subtitle' => t(
        'Полная поддержка мигрантов: от документов до интеграции в общество',
        'Complete migrant support: from documents to social integration',
        'Suporte completo a migrantes: desde documentos até integração social',
        'Soutien complet aux migrants : des documents à l\'intégration sociale',
        'Vollständige Migrantenunterstützung: von Dokumenten bis zur sozialen Integration'
    ),
    'get_help_now' => t('Получить помощь', 'Get Help Now', 'Obter Ajuda Agora', 'Obtenir de l\'aide maintenant', 'Jetzt Hilfe erhalten'),
    'our_mission' => t('Наша миссия', 'Our Mission', 'Nossa Missão', 'Notre Mission', 'Unsere Mission'),
    'mission_text' => t(
        'Мы помогаем мигрантам успешно адаптироваться в Беларуси, предоставляя полный спектр услуг: юридические консультации, помощь с документами, языковые курсы и социальную поддержку.',
        'We help migrants successfully adapt in Belarus by providing a full range of services: legal consultations, document assistance, language courses and social support.',
        'Ajudamos migrantes a se adaptarem com sucesso na Bielorrússia, fornecendo uma gama completa de serviços: consultas jurídicas, assistência documental, cursos de idiomas e apoio social.',
        'Nous aidons les migrants à s\'adapter avec succès en Biélorussie en fournissant une gamme complète de services : consultations juridiques, aide aux documents, cours de langue et soutien social.',
        'Wir helfen Migranten, sich erfolgreich in Belarus anzupassen, indem wir ein umfassendes Dienstleistungsangebot bereitstellen: Rechtsberatung, Dokumentenhilfe, Sprachkurse und soziale Unterstützung.'
    ),
    'quick_help' => t('Быстрая помощь', 'Quick Help', 'Ajuda Rápida', 'Aide Rapide', 'Schnelle Hilfe'),
    'learn_more' => t('Узнать больше', 'Learn More', 'Saiba Mais', 'En Savoir Plus', 'Mehr Erfahren'),
    'latest_news' => t('Последние новости', 'Latest News', 'Últimas Notícias', 'Dernières Nouvelles', 'Aktuelle Nachrichten'),
    'updated_today' => t('Обновлено сегодня', 'Updated Today', 'Atualizado Hoje', 'Mis à jour aujourd\'hui', 'Heute aktualisiert'),
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
    'legislation' => t('Законодательство', 'Legislation', 'Legislação', 'Législation', 'Gesetzgebung'),
    'news' => t('Новости', 'News', 'Notícias', 'Actualités', 'Nachrichten'),
    'footer_title' => t('MigraSupport', 'MigraSupport', 'MigraSupport', 'MigraSupport', 'MigraSupport'),
    'footer_desc' => t(
        'Комплексная система поддержки мигрантов в Беларуси. Мы помогаем с адаптацией, документами и интеграцией.',
        'Comprehensive migrant support system in Belarus. We help with adaptation, documents and integration.',
        'Sistema abrangente de apoio a migrantes na Bielorrússia. Ajudamos com adaptação, documentos e integração.',
        'Système complet de soutien aux migrants en Biélorussie. Nous aidons à l\'adaptation, aux documents et à l\'intégration.',
        'Umfassendes Migrantenunterstützungssystem in Belarus. Wir helfen bei Anpassung, Dokumenten und Integration.'
    ),
    'quick_links' => t('Быстрые ссылки', 'Quick Links', 'Links Rápidos', 'Liens Rapides', 'Schnelllinks'),
    'contacts' => t('Контакты', 'Contacts', 'Contatos', 'Contacts', 'Kontakte'),
    'support_247' => t('Поддержка 24/7', '24/7 Support', 'Suporte 24/7', 'Support 24/7', '24/7 Support'),
    'copyright' => t('Все права защищены.', 'All rights reserved.', 'Todos os direitos reservados.', 'Tous droits réservés.', 'Alle Rechte vorbehalten.'),
    'emergency_help' => t('Экстренная помощь', 'Emergency Help', 'Ajuda de Emergência', 'Aide d\'urgence', 'Notfallhilfe'),
    'emergency_text' => t(
        'Если вам нужна срочная помощь, позвоните на нашу горячую линию',
        'If you need urgent help, call our hotline',
        'Se precisar de ajuda urgente, ligue para nossa linha direta',
        'Si vous avez besoin d\'aide urgente, appelez notre hotline',
        'Wenn Sie dringend Hilfe benötigen, rufen Sie unsere Hotline an'
    ),
    'hotline' => t('Горячая линия', 'Hotline', 'Linha Direta', 'Ligne d\'assistance', 'Hotline'),
    'available_24_7' => t('Доступно 24/7', 'Available 24/7', 'Disponível 24/7', 'Disponible 24/7', 'Verfügbar 24/7'),
    'join_chat' => t('Присоединиться к чату', 'Join Chat', 'Entrar no Chat', 'Rejoindre le Chat', 'Chat beitreten'),
    'current_rates' => t('Актуальные курсы', 'Current Rates', 'Taxas Atuais', 'Taux actuels', 'Aktuelle Kurse'),
    'translate' => t('Перевести', 'Translate', 'Traduzir', 'Traduire', 'Übersetzen'),
    'convert' => t('Конвертировать', 'Convert', 'Converter', 'Convertir', 'Umrechnen'),
    'join' => t('Присоединиться', 'Join', 'Juntar-se', 'Rejoindre', 'Beitreten'),
    'chat' => t('Чат', 'Chat', 'Chat', 'Chat', 'Chat'),
    'documents' => t('Документы', 'Documents', 'Documentos', 'Documents', 'Dokumente'),
    'legal_consultations' => t('Юридические консультации', 'Legal Consultations', 'Consultas Jurídicas', 'Consultations juridiques', 'Rechtsberatungen'),
    'language_courses' => t('Языковые курсы', 'Language Courses', 'Cursos de Idiomas', 'Cours de langues', 'Sprachkurse'),
    'social_support' => t('Социальная поддержка', 'Social Support', 'Apoio Social', 'Soutien social', 'Soziale Unterstützung'),
    'all_rights_reserved' => t('Все права защищены.', 'All rights reserved.', 'Todos os direitos reservados.', 'Tous droits réservés.', 'Alle Rechte vorbehalten.')
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="yandex-verification" content="ebe4b7cb6371cb03" />
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
            body { background-attachment: scroll; }
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

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header - Обновленное меню навигации как во втором коде */
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
            margin-left: auto;
        }

        /* Language Selector */
        .language-selector {
            display: flex;
            gap: 5px;
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

        /* User Info with Profile Dropdown */
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

        /* Burger Menu - обновленный как во втором коде */
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

        /* Основная навигация в хедере - обновлена как во втором коде */
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

        /* Hero Section - ЗАТЕМНЕННЫЙ БАННЕР */
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
            background-image: url('https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-blend-mode: overlay;
            background-color: rgba(26, 26, 46, 0.85);
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

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-buttons .btn {
            padding: 16px 32px;
            font-size: 1rem;
            font-weight: 700;
        }

        /* Mission and Quick Help Section - обновленные стили */
        .mission-quick-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 60px;
        }

        @media (max-width: 992px) {
            .mission-quick-section {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        /* Mission Section - слева */
        .mission-section {
            background: linear-gradient(135deg, rgba(58, 134, 255, 0.15) 0%, rgba(26, 26, 46, 0.7) 100%);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 40px;
            border: 1px solid rgba(58, 134, 255, 0.2);
            box-shadow: var(--shadow-xl);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .mission-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(58, 134, 255, 0.2);
            border-color: rgba(58, 134, 255, 0.4);
        }

        .mission-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .mission-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mission-title i {
            color: var(--primary);
            background: rgba(58, 134, 255, 0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .mission-text {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.7;
            margin-bottom: 25px;
        }

        /* Quick Help Section - справа */
        .quick-help-section {
            background: linear-gradient(135deg, rgba(131, 56, 236, 0.15) 0%, rgba(26, 26, 46, 0.7) 100%);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 40px;
            border: 1px solid rgba(131, 56, 236, 0.2);
            box-shadow: var(--shadow-xl);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .quick-help-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(131, 56, 236, 0.2);
            border-color: rgba(131, 56, 236, 0.4);
        }

        .quick-help-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-secondary);
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--secondary);
            background: rgba(131, 56, 236, 0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .quick-help-desc {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.7;
            margin-bottom: 30px;
        }

        /* Блоки с 4 в ряд */
        .services-grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .service-card-4 {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            text-align: left;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .service-card-4:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
        }

        .service-icon-4 {
            width: 70px;
            height: 70px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 10px 25px rgba(58, 134, 255, 0.3);
        }

        .service-card-4 h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 1.3rem;
            font-weight: 600;
            line-height: 1.3;
            text-align: center;
        }

        .service-card-4 p {
            color: var(--gray-light);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 25px;
            flex-grow: 1;
            text-align: left;
        }

        /* Стандартные блоки услуг */
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
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
        }

        /* Слайдер для миграционных служб */
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
            min-height: 420px;
            background: rgba(255, 255, 255, 0.05);
            padding: 35px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            text-align: center;
            display: flex;
            flex-direction: column;
        }

        .service-slide:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
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

        .service-slide .service-info {
            flex-grow: 0;
        }

        .service-slide .btn {
            margin-top: auto;
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

        /* City Selector */
        .city-selector {
            background: var(--gradient-primary);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 35px;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .city-selector h3 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-size: 1.3rem;
            position: relative;
            z-index: 1;
        }

        .cities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .city-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
            text-decoration: none;
            display: block;
        }

        .city-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-4px);
            border-color: white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .city-btn.active {
            background: white;
            color: var(--primary);
            border-color: white;
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.25);
        }

        /* City Info */
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

        .city-header {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 35px;
            margin-bottom: 35px;
            align-items: start;
        }

        @media (max-width: 992px) {
            .city-header {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }

        .city-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: var(--radius);
            box-shadow: var(--shadow-xl);
            transition: var(--transition);
        }

        .city-image:hover {
            transform: scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .city-info-content {
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .card-title i {
            color: var(--accent);
            font-size: 1.4rem;
        }

        .city-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .stat-card {
            background: var(--gradient-primary);
            color: white;
            padding: 20px;
            border-radius: var(--radius);
            text-align: center;
            transition: var(--transition);
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(58, 134, 255, 0.3);
        }

        .stat-card h4 {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .stat-card p {
            font-size: 1.6rem;
            font-weight: 800;
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

        /* News Section */
        .news-item {
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            transition: var(--transition);
            margin-bottom: 15px;
        }

        .news-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .news-item h4 {
            margin-bottom: 10px;
            color: white;
            font-size: 1.1rem;
        }

        .news-item p {
            margin-bottom: 12px;
            color: var(--gray-light);
            line-height: 1.7;
        }

        .news-meta {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: var(--gray-light);
        }

        .news-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Экстренная помощь с подсветкой при наведении */
        .emergency-help {
            background: linear-gradient(135deg, rgba(255, 0, 84, 0.15) 0%, rgba(26, 26, 46, 0.8) 100%);
            border-left: 4px solid var(--danger);
            padding: 30px;
            border-radius: var(--radius-lg);
            margin: 50px 0;
            display: flex;
            align-items: center;
            gap: 25px;
            backdrop-filter: blur(10px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 0, 84, 0.3);
            box-shadow: 0 15px 35px rgba(255, 0, 84, 0.1);
        }

        .emergency-help:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 30px 60px rgba(255, 0, 84, 0.25);
            border-color: rgba(255, 0, 84, 0.6);
            background: linear-gradient(135deg, rgba(255, 0, 84, 0.25) 0%, rgba(26, 26, 46, 0.9) 100%);
        }

        .emergency-help::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-secondary);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.6s ease;
        }

        .emergency-help:hover::before {
            transform: scaleX(1);
        }

        .emergency-icon {
            width: 80px;
            height: 80px;
            background: var(--danger);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 10px 25px rgba(255, 0, 84, 0.4);
            transition: var(--transition);
        }

        .emergency-help:hover .emergency-icon {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 15px 35px rgba(255, 0, 84, 0.6);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 0, 84, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(255, 0, 84, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 0, 84, 0); }
        }

        .emergency-content {
            flex: 1;
        }

        .emergency-content h3 {
            margin-bottom: 12px;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 8px;
        }

        .emergency-content h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--danger);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .emergency-help:hover .emergency-content h3::after {
            width: 100px;
        }

        .emergency-content p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .hotline-number {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 800;
            color: white;
            margin: 12px 0;
            border-left: 4px solid var(--danger);
            font-size: 1.2rem;
            letter-spacing: 1px;
            transition: var(--transition);
        }

        .emergency-help:hover .hotline-number {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(10px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
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

        /* Мобильная навигация - обновлена как во втором коде */
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

        /* Декоративные элементы */
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), transparent);
            filter: blur(40px);
            opacity: 0.3;
            z-index: -1;
        }

        .floating-element:nth-child(1) {
            width: 250px;
            height: 250px;
            top: 10%;
            left: 5%;
        }

        .floating-element:nth-child(2) {
            width: 180px;
            height: 180px;
            bottom: 20%;
            right: 10%;
            background: linear-gradient(135deg, var(--secondary), transparent);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .services-grid-4 {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .mission-title, .section-title {
                font-size: 2rem;
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
            }
            
            .services-grid,
            .services-grid-4 {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
            
            .mobile-nav {
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

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .hero-buttons .btn {
                width: 100%;
                max-width: 280px;
            }

            .mission-title, .section-title {
                font-size: 1.8rem;
            }

            .mission-text, .quick-help-desc {
                font-size: 1rem;
            }

            .services-grid,
            .services-grid-4 {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 20px;
            }

            .city-selector {
                padding: 20px;
            }

            .emergency-help {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .emergency-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
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
            
            .service-card,
            .service-card-4 {
                padding: 25px;
            }
            
            .service-icon,
            .service-icon-4 {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
            
            /* Language selector для мобильных */
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

            .cities-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .city-header {
                grid-template-columns: 1fr;
            }
            
            .city-image {
                height: 200px;
            }
            
            .mission-quick-section {
                gap: 30px;
            }

            .header-right {
                gap: 8px;
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
                    <!-- Language Selector с 5 языками - ИСПРАВЛЕНО -->
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
                            <div class="user-avatar" id="profileAvatar" title="<?php echo t('Перейти в профиль', 'Go to Profile', 'Ir para o Perfil', 'Aller au Profil', 'Zum Profil gehen'); ?>">
                                <?php 
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
                                ?>
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
            
            <!-- Основная навигация - обновлена как во втором коде -->
            <nav class="header-nav">
                <ul class="nav-tabs" id="mainTabs">
                    <li class="nav-tab active">
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
            
            <!-- Мобильная навигация - обновлена как во втором коде -->
            <div class="mobile-nav" id="mobileNav">
                <ul class="mobile-nav-tabs">
                    <li class="mobile-nav-tab active">
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
        <!-- Герой-секция (с затемненным баннером) -->
        <div class="hero-section">
            <h1 class="hero-title"><?php echo $translations['hero_title']; ?></h1>
            <p class="hero-subtitle"><?php echo $translations['hero_subtitle']; ?></p>
            <?php if (!$isLoggedIn): ?>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> <?php echo $translations['get_help_now']; ?>
                    </a>
                    <a href="information.php" class="btn btn-outline">
                        <i class="fas fa-book"></i> <?php echo $translations['learn_more']; ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="hero-buttons">
                    <a href="chat.php" class="btn btn-primary">
                        <i class="fas fa-comments"></i> <?php echo $translations['join_chat']; ?>
                    </a>
                    <a href="map.php" class="btn btn-secondary">
                        <i class="fas fa-map-marked-alt"></i> <?php echo $translations['map_services']; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Наша миссия и Быстрая помощь рядом (слева и справа) -->
        <div class="mission-quick-section">
            <!-- Наша миссия (слева) -->
            <div class="mission-section">
                <h2 class="mission-title">
                    <i class="fas fa-bullseye"></i> <?php echo $translations['our_mission']; ?>
                </h2>
                <p class="mission-text"><?php echo $translations['mission_text']; ?></p>
                <div style="margin-top: 25px;">
                    <a href="information.php" class="btn btn-primary" style="padding: 12px 25px;">
                        <i class="fas fa-book"></i> <?php echo $translations['learn_more']; ?>
                    </a>
                </div>
            </div>
            
            <!-- Быстрая помощь (справа) -->
            <div class="quick-help-section">
                <h2 class="section-title">
                    <i class="fas fa-handshake"></i> <?php echo $translations['quick_help']; ?>
                </h2>
                <p class="quick-help-desc">
                    <?php echo t(
                        'MigraSupport создан для помощи мигрантам в Беларуси. Мы предоставляем комплексную поддержку на всех этапах адаптации в новой стране.',
                        'MigraSupport is created to help migrants in Belarus. We provide comprehensive support at all stages of adaptation in a new country.',
                        'MigraSupport foi criado para ajudar migrantes na Bielorrússia. Fornecemos suporte abrangente em todas as etapas da adaptação em um novo país.',
                        'MigraSupport est créé pour aider les migrants en Biélorussie. Nous fournissons un soutien complet à toutes les étapes de l\'adaptation dans un nouveau pays.',
                        'MigraSupport wurde gegründet, um Migranten in Belarus zu helfen. Wir bieten umfassende Unterstützung in allen Phasen der Anpassung in einem neuen Land.'
                    ); ?>
                </p>
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <a href="register.php" class="btn btn-secondary">
                        <i class="fas fa-user-plus"></i> <?php echo $translations['join']; ?>
                    </a>
                    <a href="chat.php" class="btn btn-outline">
                        <i class="fas fa-comments"></i> <?php echo $translations['chat']; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 блока в ряд -->
        <div class="services-grid-4">
            <div class="service-card-4">
                <div class="service-icon-4" style="background: var(--gradient-primary);">
                    <i class="fas fa-info-circle"></i>
                </div>
                <h3><?php echo $translations['information']; ?></h3>
                <p><?php echo t(
                    'Полная информация о процедурах регистрации, необходимых документах и правилах пребывания в Беларуси.',
                    'Complete information about registration procedures, required documents and rules of stay in Belarus.',
                    'Informações completas sobre procedimentos de registro, documentos necessários e regras de permanência na Bielorrússia.',
                    'Informations complètes sur les procédures d\'enregistrement, les documents requis et les règles de séjour en Biélorussie.',
                    'Vollständige Informationen über Registrierungsverfahren, erforderliche Dokumente und Aufenthaltsregeln in Belarus.'
                ); ?></p>
                <a href="information.php" class="btn btn-outline" style="margin-top: 20px; width: 100%;">
                    <i class="fas fa-book"></i> <?php echo $translations['learn_more']; ?>
                </a>
            </div>
            
            <div class="service-card-4">
                <div class="service-icon-4" style="background: var(--gradient-success);">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3><?php echo $translations['map_services']; ?></h3>
                <p><?php echo t(
                    'Интерактивная карта всех миграционных служб, центров поддержки и помощи по всей Беларуси.',
                    'Interactive map of all migration services, support centers and assistance throughout Belarus.',
                    'Mapa interativo de todos os serviços de migração, centros de apoio e assistência em toda a Bielorrússia.',
                    'Carte interactive de tous les services de migration, centres de soutien et d\'assistance dans toute la Biélorussie.',
                    'Interaktive Karte aller Migrationsdienste, Unterstützungszentren und Hilfen in ganz Belarus.'
                ); ?></p>
                <a href="map.php" class="btn btn-outline" style="margin-top: 20px; width: 100%;">
                    <i class="fas fa-map-marker-alt"></i> <?php echo $translations['view_on_map']; ?>
                </a>
            </div>
            
            <div class="service-card-4">
                <div class="service-icon-4" style="background: var(--gradient-secondary);">
                    <i class="fas fa-language"></i>
                </div>
                <h3><?php echo t('Переводчик', 'Translator', 'Tradutor', 'Traducteur', 'Übersetzer'); ?></h3>
                <p><?php echo t(
                    'Быстрый и точный перевод текстов, документов и сообщений. Преодолейте языковой барьер.',
                    'Fast and accurate translation of texts, documents and messages. Overcome language barriers.',
                    'Tradução rápida e precisa de textos, documentos e mensagens. Supere as barreiras linguísticas.',
                    'Traduction rapide et précise de textes, documents et messages. Surmontez les barrières linguistiques.',
                    'Schnelle und genaue Übersetzung von Texten, Dokumenten und Nachrichten. Überwinden Sie Sprachbarrieren.'
                ); ?></p>
                <a href="translator.php" class="btn btn-outline" style="margin-top: 20px; width: 100%;">
                    <i class="fas fa-exchange-alt"></i> <?php echo $translations['translate']; ?>
                </a>
            </div>
            
            <div class="service-card-4">
                <div class="service-icon-4" style="background: linear-gradient(135deg, #ff9e00 0%, #ff006e 100%);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3><?php echo $translations['currency_converter']; ?></h3>
                <p><?php echo t(
                    'Актуальные курсы валют и удобный конвертер для расчета расходов. Узнайте текущие курсы обмена.',
                    'Current exchange rates and convenient converter for expense calculation. Find out current exchange rates.',
                    'Taxas de câmbio atuais e conversor conveniente para cálculo de despesas. Descubra as taxas de câmbio atuais.',
                    'Taux de change actuels et convertisseur pratique pour le calcul des dépenses. Découvrez les taux de change actuels.',
                    'Aktuelle Wechselkurse und praktischer Umrechner für die Ausgabenberechnung. Erfahren Sie die aktuellen Wechselkurse.'
                ); ?></p>
                <a href="converter.php" class="btn btn-outline" style="margin-top: 20px; width: 100%;">
                    <i class="fas fa-calculator"></i> <?php echo $translations['convert']; ?>
                </a>
            </div>
        </div>

        <!-- Городской выбор и информация -->
        <div class="city-selector">
            <h3><i class="fas fa-map-marker-alt"></i> <?php echo $translations['choose_city']; ?></h3>
            <div class="cities-grid">
                <?php foreach ($cities as $key => $city): ?>
                    <a href="index.php?city=<?php echo $key; ?>&lang=<?php echo $lang; ?>" 
                       class="city-btn <?php echo $key === $currentCity ? 'active' : ''; ?>">
                        <?php echo getTranslated($city, 'name'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- City Information -->
        <div class="card">
            <div class="city-header">
                <img src="<?php echo $cityData['image']; ?>" alt="<?php echo getTranslated($cityData, 'name'); ?>" class="city-image">
                <div class="city-info-content">
                    <h2 class="card-title">
                        <i class="fas fa-city"></i> <?php echo getTranslated($cityData, 'name'); ?>
                    </h2>
                    <p style="margin-bottom: 20px; color: rgba(255, 255, 255, 0.85); line-height: 1.8;">
                        <?php echo getTranslated($cityData, 'description'); ?>
                    </p>
                    
                    <div class="city-stats">
                        <div class="stat-card">
                            <h4><i class="fas fa-users"></i> <?php echo $translations['population']; ?></h4>
                            <p><?php echo getTranslated($cityData, 'population'); ?></p>
                        </div>
                        <div class="stat-card">
                            <h4><i class="fas fa-arrows-alt"></i> <?php echo $translations['area']; ?></h4>
                            <p><?php echo getTranslated($cityData, 'area'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="card-title" style="margin-top: 30px;">
                <i class="fas fa-landmark"></i> <?php echo $translations['migration_services']; ?>
            </h3>
            <div class="services-slider-container">
                <button class="slider-btn prev" id="prevBtn" onclick="slideServices('prev')">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="services-slider" id="servicesSlider">
                    <?php foreach ($cityData['services'] as $service): ?>
                        <div class="service-slide">
                            <h4><i class="fas fa-passport"></i> <?php echo getTranslated($service, 'name'); ?></h4>
                            <div class="service-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo $translations['address']; ?>: <?php echo getTranslated($service, 'address'); ?></span>
                            </div>
                            <div class="service-info">
                                <i class="fas fa-phone"></i>
                                <span><?php echo $translations['phone']; ?>: <?php echo $service['phone']; ?></span>
                            </div>
                            <div class="service-info">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $translations['hours']; ?>: <?php echo getTranslated($service, 'hours'); ?></span>
                            </div>
                            <?php if (!empty($service['email'])): ?>
                            <div class="service-info">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo $translations['email']; ?>: <?php echo $service['email']; ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($service['website'])): ?>
                            <div class="service-info">
                                <i class="fas fa-globe"></i>
                                <span><?php echo $translations['website']; ?>: <?php echo $service['website']; ?></span>
                            </div>
                            <?php endif; ?>
                            <a href="map.php?city=<?php echo $currentCity; ?>" class="btn btn-outline" style="margin-top: 15px; width: 100%;">
                                <i class="fas fa-map-marker-alt"></i> <?php echo $translations['view_on_map']; ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="slider-btn next" id="nextBtn" onclick="slideServices('next')">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- News Section -->
        <?php
        $newsItems = [];
        try {
            $newsStmt = $pdo->query("SELECT n.*, u.first_name, u.last_name FROM news n LEFT JOIN users u ON n.author_id = u.id WHERE n.is_active = 1 ORDER BY n.created_at DESC LIMIT 5");
            $newsItems = $newsStmt->fetchAll();
        } catch (PDOException $e) {
            // Если таблица не существует или ошибка — просто не показываем новости
        }
        if (!empty($newsItems)): ?>
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                <h2 class="card-title">
                    <i class="fas fa-newspaper"></i> <?php echo $translations['latest_news']; ?>
                </h2>
                <span style="color: rgba(255, 255, 255, 0.7); font-size: 0.85rem;"><?php echo $translations['updated_today']; ?></span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($newsItems as $newsItem): ?>
                <a href="news_simple.php?id=<?php echo $newsItem['id']; ?>" style="text-decoration: none; color: inherit;">
                <div class="news-item" style="cursor: pointer; transition: all 0.3s; border-radius: 10px; padding: 15px; border: 1px solid rgba(255,255,255,0.1);" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                    <?php if (!empty($newsItem['image'])): ?>
                    <img src="<?php echo htmlspecialchars($newsItem['image']); ?>" alt="<?php echo htmlspecialchars($newsItem['title']); ?>" style="width:100%;max-height:200px;object-fit:cover;border-radius:10px;margin-bottom:12px;">
                    <?php endif; ?>
                    <h4><?php echo htmlspecialchars($newsItem['title']); ?></h4>
                    <p><?php echo nl2br(htmlspecialchars(mb_substr($newsItem['content'], 0, 300))) . (mb_strlen($newsItem['content']) > 300 ? '...' : ''); ?></p>
                    <div class="news-meta">
                        <span><i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($newsItem['created_at'])); ?></span>
                        <?php if (!empty($newsItem['first_name'])): ?>
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($newsItem['first_name'] . ' ' . $newsItem['last_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Экстренная помощь (с подсветкой при наведении) -->

    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo $translations['footer_title']; ?></h3>
                    <p><?php echo $translations['footer_desc']; ?></p>

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
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo t('Минск, Беларусь', 'Minsk, Belarus', 'Minsk, Bielorrússia', 'Minsk, Biélorussie', 'Minsk, Belarus'); ?></li>
                        <li><i class="fas fa-clock"></i> <?php echo $translations['support_247']; ?></li>
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

        // Инициализация профиля и dropdown
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация бургер-меню и мобильной навигации
            const burgerMenu = document.getElementById('burgerMenu');
            const mobileNav = document.getElementById('mobileNav');
            
            if (burgerMenu && mobileNav) {
                // Убедимся, что элементы существуют
                console.log('Burger menu and mobile nav found');
                
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
            
            // Инициализация профиля и выпадающего меню
            const profileAvatar = document.getElementById('profileAvatar');
            const dropdownMenu = document.getElementById('profileDropdown');
            
            if (profileAvatar && dropdownMenu) {
                // Открытие/закрытие dropdown
                profileAvatar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });
                
                // Закрытие dropdown при клике вне его
                document.addEventListener('click', function(e) {
                    if (!profileAvatar.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
                
                // Предотвращение закрытия при клике внутри dropdown
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Инициализация слайдера
            updateSlider();
        });

        // Слайдер для миграционных служб
        let currentSlide = 0;

        function getSlidesPerView() {
            if (window.innerWidth <= 768) return 1;
            if (window.innerWidth <= 1200) return 2;
            return 3;
        }

        function slideServices(direction) {
            const slider = document.getElementById('servicesSlider');
            const slides = slider.querySelectorAll('.service-slide');
            const totalSlides = slides.length;
            const slidesPerView = getSlidesPerView();
            const maxSlide = Math.max(0, totalSlides - slidesPerView);

            if (direction === 'next') {
                currentSlide = Math.min(currentSlide + 1, maxSlide);
            } else {
                currentSlide = Math.max(currentSlide - 1, 0);
            }

            updateSlider();
        }

        function updateSlider() {
            const slider = document.getElementById('servicesSlider');
            const slides = slider.querySelectorAll('.service-slide');
            const totalSlides = slides.length;
            const slidesPerView = getSlidesPerView();
            const maxSlide = Math.max(0, totalSlides - slidesPerView);
            
            // Ограничиваем currentSlide
            currentSlide = Math.min(currentSlide, maxSlide);
            
            const slideWidth = slides[0] ? slides[0].offsetWidth : 0;
            const gap = 30;
            const offset = -(currentSlide * (slideWidth + gap));
            
            slider.style.transform = `translateX(${offset}px)`;

            // Обновляем состояние кнопок
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            if (prevBtn) prevBtn.disabled = currentSlide === 0;
            if (nextBtn) nextBtn.disabled = currentSlide >= maxSlide;
        }

        // Обновление при изменении размера окна
        window.addEventListener('resize', function() {
            updateSlider();
        });
    </script>
</body>
</html>