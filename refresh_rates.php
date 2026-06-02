<?php
// refresh_rates.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Увеличиваем время выполнения и память для API запросов
set_time_limit(30);
ini_set('memory_limit', '256M');

function refreshExchangeRates() {
    // Используем разные бесплатные API с fallback
    $apiSources = [
        [
            'name' => 'Frankfurter API',
            'url' => 'https://api.frankfurter.app/latest?base=USD',
            'parse_function' => function($data) {
                if (isset($data['rates'])) {
                    $rates = $data['rates'];
                    // Добавляем USD как базовую валюту
                    $rates['USD'] = 1;
                    // BYN может отсутствовать, добавляем примерный курс
                    if (!isset($rates['BYN'])) {
                        // Примерный курс BYN к USD
                        $rates['BYN'] = 3.2;
                    }
                    return $rates;
                }
                return null;
            }
        ],
        [
            'name' => 'ExchangeRate.host',
            'url' => 'https://api.exchangerate.host/latest?base=USD',
            'parse_function' => function($data) {
                if (isset($data['rates'])) {
                    $rates = $data['rates'];
                    $rates['USD'] = 1;
                    if (!isset($rates['BYN'])) {
                        $rates['BYN'] = 3.2;
                    }
                    return $rates;
                }
                return null;
            }
        ],
        [
            'name' => 'Currency API',
            'url' => 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json',
            'parse_function' => function($data) {
                if (isset($data['usd'])) {
                    $rates = $data['usd'];
                    // Приводим ключи к верхнему регистру
                    $upperRates = [];
                    foreach ($rates as $key => $value) {
                        $upperRates[strtoupper($key)] = $value;
                    }
                    $upperRates['USD'] = 1;
                    if (!isset($upperRates['BYN'])) {
                        $upperRates['BYN'] = 3.2;
                    }
                    return $upperRates;
                }
                return null;
            }
        ],
        [
            'name' => 'National Bank of Belarus (NBRB)',
            'url' => 'https://www.nbrb.by/api/exrates/rates?periodicity=0',
            'parse_function' => function($data) {
                if (is_array($data) && count($data) > 0) {
                    $rates = ['USD' => 1];
                    
                    foreach ($data as $currency) {
                        if (isset($currency['Cur_Abbreviation'], $currency['Cur_OfficialRate'], $currency['Cur_Scale'])) {
                            $code = $currency['Cur_Abbreviation'];
                            $rate = $currency['Cur_OfficialRate'] / $currency['Cur_Scale'];
                            
                            if ($code === 'EUR') $rates['EUR'] = $rate;
                            elseif ($code === 'RUB') $rates['RUB'] = $rate;
                            elseif ($code === 'PLN') $rates['PLN'] = $rate;
                            elseif ($code === 'UAH') $rates['UAH'] = $rate;
                            elseif ($code === 'CNY') $rates['CNY'] = $rate;
                            elseif ($code === 'JPY') $rates['JPY'] = $rate;
                            elseif ($code === 'GBP') $rates['GBP'] = $rate;
                        }
                    }
                    
                    // Добавляем BYN
                    $rates['BYN'] = 1;
                    
                    // Дополняем недостающие курсы
                    $commonRates = [
                        'EUR' => 0.92,
                        'GBP' => 0.79,
                        'JPY' => 148.5,
                        'CNY' => 7.3,
                        'RUB' => 92.5,
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
                    
                    foreach ($commonRates as $code => $defaultRate) {
                        if (!isset($rates[$code])) {
                            $rates[$code] = $defaultRate;
                        }
                    }
                    
                    return $rates;
                }
                return null;
            }
        ]
    ];
    
    // Пробуем каждый API по очереди
    foreach ($apiSources as $source) {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $source['url'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response && $httpCode === 200) {
                $data = json_decode($response, true);
                $rates = $source['parse_function']($data);
                
                if ($rates && count($rates) > 5) { // Проверяем что получили достаточно валют
                    // Нормализуем курсы (делаем USD базовой валютой)
                    if (isset($rates['USD']) && $rates['USD'] != 1) {
                        $baseRate = $rates['USD'];
                        foreach ($rates as $currency => $rate) {
                            $rates[$currency] = $rate / $baseRate;
                        }
                        $rates['USD'] = 1;
                    }
                    
                    // Сохраняем в кэш
                    $cacheFile = 'exchange_rates_cache.json';
                    $cacheData = [
                        'timestamp' => time(),
                        'rates' => $rates,
                        'source' => $source['name']
                    ];
                    
                    if (file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT))) {
                        return [
                            'success' => true,
                            'rates' => $rates,
                            'source' => $source['name'],
                            'message' => 'Курсы успешно обновлены'
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Продолжаем пробовать следующий API
            continue;
        }
    }
    
    // Если все API не сработали, используем статические данные
    $fallbackRates = [
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
    
    $cacheFile = 'exchange_rates_cache.json';
    $cacheData = [
        'timestamp' => time(),
        'rates' => $fallbackRates,
        'source' => 'Fallback (оффлайн)'
    ];
    
    file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
    
    return [
        'success' => true,
        'rates' => $fallbackRates,
        'source' => 'Fallback (оффлайн)',
        'message' => 'Используются резервные курсы'
    ];
}

$result = refreshExchangeRates();
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);