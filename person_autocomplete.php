<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || strlen(trim($_GET['q'])) < 2) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['q']);
$type = isset($_GET['type']) ? $_GET['type'] : null; // 'director' o 'actor'

$params = [
    'query' => $query,
    'page' => 1,
    'include_adult' => false
];

$data = fetchFromApi('search/person', $params);
$results = [];

// Modifica la parte di filtraggio
foreach ($data['results'] as $person) {
    // Se stiamo cercando un regista, mostra solo chi ha diretto
    if ($type === 'director') {
        if (stripos($person['known_for_department'], 'Directing') === false) {
            continue;
        }
    } 
    // Se stiamo cercando un attore, mostra solo chi ha recitato
    elseif ($type === 'actor') {
        if (stripos($person['known_for_department'], 'Acting') === false) {
            continue;
        }
    }
    
    $results[] = [
        'id' => $person['id'],
        'name' => $person['name'],
        'department' => $person['known_for_department'],
        'popularity' => $person['popularity']
    ];
}

// Ordina per popolarità
usort($results, function($a, $b) {
    return $b['popularity'] <=> $a['popularity'];
});

echo json_encode(array_slice($results, 0, 10));
?>