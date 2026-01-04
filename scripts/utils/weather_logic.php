<?php
function cleanCityName($string) {
    $transliterator = 'Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove';
    if (function_exists('transliterator_transliterate')) {
        return transliterator_transliterate($transliterator, $string);
    } else {
        $chars = array(
            'ă'=>'a', 'â'=>'a', 'î'=>'i', 'ș'=>'s', 'ț'=>'t',
            'Ă'=>'A', 'Â'=>'A', 'Î'=>'I', 'Ș'=>'S', 'Ț'=>'T',
            'ş'=>'s', 'ţ'=>'t', 'Ş'=>'S', 'Ţ'=>'T'
        );
        return strtr($string, $chars);
    }
}

function getCityCoordinates($cityName) {
    if (empty($cityName)) return null;

    $parts = explode(',', $cityName);
    // Take only the first part (e.g., from "Brasov, ROU" keeps "Brasov")
    $onlyCity = trim($parts[0]);
    
    // Clean diacritics
    $cleanName = cleanCityName($onlyCity);
    $encodedName = urlencode($cleanName);

    // API URL
    $url = "https://geocoding-api.open-meteo.com/v1/search?name={$encodedName}&count=1&language=en&format=json";

    $options = [
        "http" => [
            "header" => "User-Agent: GoStayBookingPlatform/1.0\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    try {
        $response = @file_get_contents($url, false, $context);
        
        if ($response === FALSE) return null;

        $data = json_decode($response, true);
        
        if (isset($data['results'][0])) {
            return [
                'lat' => $data['results'][0]['latitude'],
                'lon' => $data['results'][0]['longitude'],
                // Return the original name from the API for nice display in the widget (e.g., "Paris")
                'name' => $data['results'][0]['name'] 
            ];
        }
    } catch (Exception $e) {
        return null;
    }
    return null;
}

function getWeatherData($lat, $lon) {
    // Requesting 16 days of forecast
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&daily=weathercode,temperature_2m_max,temperature_2m_min&timezone=auto&forecast_days=16";
    
    $options = [
        "http" => [
            "header" => "User-Agent: GoStayBookingPlatform/1.0\r\n"
        ]
    ];
    $context = stream_context_create($options);

    try {
        $response = @file_get_contents($url, false, $context);
        if ($response === FALSE) return null;
        return json_decode($response, true); 
    } catch (Exception $e) {
        return null;
    }
}

function getWeatherIcon($code) {
    if ($code === 0) return ['icon' => 'fa-sun', 'color' => '#f9d71c'];
    if ($code >= 1 && $code <= 3) return ['icon' => 'fa-cloud-sun', 'color' => '#ffbf00'];
    if ($code >= 45 && $code <= 48) return ['icon' => 'fa-smog', 'color' => '#a0a0a0'];
    if ($code >= 51 && $code <= 67) return ['icon' => 'fa-cloud-rain', 'color' => '#7b2bd4'];
    if ($code >= 71 && $code <= 86) return ['icon' => 'fa-snowflake', 'color' => '#6dd5ed'];
    if ($code >= 95) return ['icon' => 'fa-bolt', 'color' => '#ffcc00'];
    return ['icon' => 'fa-cloud', 'color' => '#b0b0b0'];
}
?>