<?php
function getCityData($city) {
    $cities = [
        'minsk' => [
            'name' => 'Минск',
            'image' => 'https://images.unsplash.com/photo-1596467888261-6a1e4c0b152a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Столица Беларуси, крупнейший политический, экономический и культурный центр страны. Современный город с развитой инфраструктурой.',
            'population' => '2 009 786 человек',
            'area' => '409,5 км²',
            'services' => [
                [
                    'name' => 'Главное управление по гражданству и миграции',
                    'address' => 'ул. Володарского, 6',
                    'phone' => '+375 (17) 218-01-02',
                    'hours' => 'Пн-Пт 9:00-18:00, обед 13:00-14:00',
                    'email' => 'minsk@mvd.gov.by'
                ]
            ]
        ],
        'grodno' => [
            'name' => 'Гродно',
            'image' => 'https://images.unsplash.com/photo-1620053281310-7048673451e6?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Город на западе Беларуси, известный своей богатой историей и архитектурой. Культурная столица Беларуси.',
            'population' => '370 919 человек',
            'area' => '142,1 км²',
            'services' => [
                [
                    'name' => 'Отдел по гражданству и миграции',
                    'address' => 'ул. Ожешко, 3',
                    'phone' => '+375 (152) 72-34-56',
                    'hours' => 'Пн-Пт 8:00-17:00',
                    'email' => 'grodno@mvd.gov.by'
                ]
            ]
        ],
        'brest' => [
            'name' => 'Брест',
            'image' => 'https://images.unsplash.com/photo-1599396170196-429b63e8c8a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Город-герой на границе с Польшей, известный Брестской крепостью. Крупный транспортный узел.',
            'population' => '350 616 человек',
            'area' => '146,1 км²',
            'services' => [
                [
                    'name' => 'Управление по гражданству и миграции',
                    'address' => 'ул. Ленина, 19',
                    'phone' => '+375 (162) 23-45-67',
                    'hours' => 'Пн-Пт 8:30-17:30',
                    'email' => 'brest@mvd.gov.by'
                ]
            ]
        ],
        'vitebsk' => [
            'name' => 'Витебск',
            'image' => 'https://images.unsplash.com/photo-1601919051955-9a4d1f4e6a72?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Город на севере Беларуси, известный фестивалем "Славянский базара". Культурная жемчужина региона.',
            'population' => '378 459 человек',
            'area' => '124,5 км²',
            'services' => [
                [
                    'name' => 'Отдел по гражданству и миграции',
                    'address' => 'ул. Замковая, 5',
                    'phone' => '+375 (212) 23-45-67',
                    'hours' => 'Пн-Пт 8:30-17:30',
                    'email' => 'vitebsk@mvd.gov.by'
                ]
            ]
        ],
        'gomel' => [
            'name' => 'Гомель',
            'image' => 'https://images.unsplash.com/photo-1574362849222-7875e732f6f7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Второй по величине город Беларуси, важный промышленный и культурный центр на юго-востоке страны.',
            'population' => '535 693 человек',
            'area' => '139,8 км²',
            'services' => [
                [
                    'name' => 'Управление по гражданству и миграции',
                    'address' => 'пр. Ленина, 10',
                    'phone' => '+375 (232) 34-56-78',
                    'hours' => 'Пн-Пт 8:00-17:00',
                    'email' => 'gomel@mvd.gov.by'
                ]
            ]
        ],
        'mogilev' => [
            'name' => 'Могилёв',
            'image' => 'https://images.unsplash.com/photo-1548013146-72479768bada?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
            'description' => 'Крупный промышленный и культурный центр на востоке Беларуси. Город с богатой историей.',
            'population' => '380 440 человек',
            'area' => '118,5 км²',
            'services' => [
                [
                    'name' => 'Отдел по гражданству и миграции',
                    'address' => 'ул. Первомайская, 22',
                    'phone' => '+375 (222) 45-67-89',
                    'hours' => 'Пн-Пт 9:00-18:00',
                    'email' => 'mogilev@mvd.gov.by'
                ]
            ]
        ]
    ];

    return $cities[$city] ?? $cities['minsk'];
}
?>