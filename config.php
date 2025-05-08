<?php
// Configurazioni condivise
define('API_KEY', 'e449d338be87977065418467adfdadb5');
define('API_URL', 'https://api.themoviedb.org/3/');
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_TIME', 3600); // 1 ora

// Nuova palette di colori
define('COLOR_DARK', '#000000');
define('COLOR_PRIMARY', '#8E1616');
define('COLOR_SECONDARY', '#E8C999');
define('COLOR_LIGHT', '#F8EEDF');

// Abilita error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Funzione per chiamate API con cache
function fetchFromApi($endpoint, $params = []) {
    $params['api_key'] = API_KEY;
    $params['language'] = 'it-IT';
    $cacheFile = CACHE_DIR . md5($endpoint . json_encode($params)) . '.json';
    
    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < CACHE_TIME) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    
    $url = API_URL . $endpoint . '?' . http_build_query($params);
    $response = file_get_contents($url);
    
    if ($response === FALSE) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!file_exists(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
    }
    
    file_put_contents($cacheFile, $response);
    return $data;
}
?>

