<?php
require_once 'config.php';

$type = isset($_GET['filterType']) && $_GET['filterType'] === 'tv' ? 'tv' : 'movie';
$genres = isset($_GET['randomGenre']) ? trim($_GET['randomGenre']) : '';
$yearStart = isset($_GET['yearStart']) ? intval($_GET['yearStart']) : 1950;
$yearEnd = isset($_GET['yearEnd']) ? intval($_GET['yearEnd']) : date('Y');
$director = isset($_GET['randomDirector']) ? trim($_GET['randomDirector']) : '';
$actor = isset($_GET['randomActor']) ? trim($_GET['randomActor']) : '';

// Mappa generi italiano -> ID TMDb
$genreMap = [
    'Azione' => 28, 'Commedia' => 35, 'Drammatico' => 18, 'Fantascienza' => 878,
    'Horror' => 27, 'Animazione' => 16, 'Romantico' => 10749, 'Thriller' => 53
];

$genreIds = [];
foreach(explode(',', $genres) as $genre) {
    $genre = trim($genre);
    if(isset($genreMap[$genre])) {
        $genreIds[] = $genreMap[$genre];
    }
}

// Funzione per ottenere ID persona con cache
function getPersonId($name, $type) {
    if(empty($name)) return null;
    
    $cacheKey = 'person_' . md5(strtolower($name) . '_' . $type);
    $cacheFile = CACHE_DIR . $cacheKey . '.json';
    
    if(file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        return $data['id'] ?? null;
    }
    
    $params = [
        'query' => $name,
        'page' => 1
    ];
    
    $data = fetchFromApi('search/person', $params);
    
    if(empty($data['results'])) return null;
    
    // Cerca la persona più rilevante del tipo corretto
    foreach($data['results'] as $person) {
        if($type === 'director' && $person['known_for_department'] === 'Directing') {
            file_put_contents($cacheFile, json_encode($person));
            return $person['id'];
        }
        if($type === 'actor' && $person['known_for_department'] === 'Acting') {
            file_put_contents($cacheFile, json_encode($person));
            return $person['id'];
        }
    }
    
    return null;
}

$directorId = $director ? getPersonId($director, 'director') : null;
$actorId = $actor ? getPersonId($actor, 'actor') : null;

// Costruisci la query per l'API

$params = [
    'sort_by' => 'vote_average.desc',
    'vote_count.gte' => 50,
    'with_genres' => implode(',', $genreIds),
    'page' => 1
];

if($type === 'movie') {
    $params['primary_release_date.gte'] = "$yearStart-01-01";
    $params['primary_release_date.lte'] = "$yearEnd-12-31";
} else {
    $params['first_air_date.gte'] = "$yearStart-01-01";
    $params['first_air_date.lte'] = "$yearEnd-12-31";
}

if($actorId) {
    $params['with_people'] = $actorId; // Cerca solo film/serie con questo attore
}
if($directorId) {
    if($type === 'movie') {
        $params['with_crew'] = $directorId . '|Directing'; // Solo come regista
    } else {
        $params['with_people'] = $directorId; // Per serie TV
    }
}

// Ottieni i risultati
$data = fetchFromApi("discover/$type", $params);
$totalPages = min(5, $data['total_pages'] ?? 1);

// Seleziona 2 pagine casuali
$pages = $totalPages > 1 ? [1, rand(2, $totalPages)] : [1];
$allResults = [];

foreach($pages as $page) {
    $pageData = fetchFromApi("discover/$type", array_merge($params, ['page' => $page]));
    if(isset($pageData['results'])) {
        $allResults = array_merge($allResults, $pageData['results']);
    }
}

// Ordina e seleziona 5 suggerimenti casuali
usort($allResults, function($a, $b) {
    return $b['vote_average'] <=> $a['vote_average'];
});

// Prende i primi 20 elementi dell’array $allResults (che sono già ordinati per voto più alto).
$suggestions = array_slice($allResults, 0, 20);
// Mescola casualmente questi 20 elementi, così l’ordine cambia ogni volta.
shuffle($suggestions);
// Prende i primi 5 elementi dall’array mescolato.
$suggestions = array_slice($suggestions, 0, 5);

// Prepara URL per il reroll
$rerollParams = [];
foreach(['filterType','randomGenre','yearStart','yearEnd','randomDirector','randomActor'] as $param) {
    if(!empty($_GET[$param])) {
        $rerollParams[$param] = $_GET[$param];
    }
}
$rerollUrl = 'suggest.php?' . http_build_query($rerollParams);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Suggerimenti per te | FindFilm</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --dark-color: #000000;
            --primary-color: #8E1616;
            --secondary-color: #E8C999;
            --light-color: #F8EEDF;
        }
        
        body {
            background-color: var(--light-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .suggest-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(142, 22, 22, 0.10);
            display: flex;
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid var(--secondary-color);
        }
        
        .suggest-card:hover {
            box-shadow: 0 16px 40px rgba(142, 22, 22, 0.15);
            transform: translateY(-5px);
        }
        
        .poster-img {
            width: 150px;
            min-width: 150px;
            height: 225px;
            object-fit: cover;
            background: #eaeaea;
        }
        
        .suggest-body {
            flex: 1;
            padding: 1.8rem;
        }
        
        .badge-type {
            background-color: var(--primary-color);
            color: var(--light-color);
        }
        
        .btn-suggest {
            background-color: var(--primary-color);
            color: var(--light-color);
            border-radius: 50px;
            padding: 10px 25px;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-suggest:hover {
            background-color: var(--dark-color);
            transform: translateY(-2px);
        }
        
        .reroll-btn {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: var(--light-color);
            border-radius: 50px;
            padding: 10px 30px;
            transition: all 0.3s;
        }
        
        .reroll-btn:hover {
            background: var(--primary-color);
            color: var(--light-color);
        }
        
        .no-suggest {
            background-color: var(--secondary-color);
            color: var(--dark-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .suggest-card {
                flex-direction: column;
            }
            .poster-img {
                width: 100%;
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4 text-center fw-bold"><i class="bi bi-stars me-2"></i>I tuoi suggerimenti</h1>
        
        <?php if(($director && !$directorId) || ($actor && !$actorId)): ?>
            <div class="alert alert-warning text-center">
                <?php if($director && !$directorId): ?>
                    <p>Regista "<b><?= htmlspecialchars($director) ?></b>" non trovato</p>
                <?php endif; ?>
                <?php if($actor && !$actorId): ?>
                    <p>Attore "<b><?= htmlspecialchars($actor) ?></b>" non trovato</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="suggest-list">
            <?php if(!empty($suggestions)): ?>
                <?php foreach($suggestions as $item): ?>
                    <?php
                    $poster = !empty($item['poster_path'])
                        ? "https://image.tmdb.org/t/p/w500" . $item['poster_path']
                        : "https://via.placeholder.com/500x750?text=Nessuna+immagine";
                    
                    $title = htmlspecialchars($item['title'] ?? $item['name']);
                    $overview = htmlspecialchars($item['overview'] ?? '');
                    $year = '';
                    
                    if (!empty($item['release_date'])) {
                        $year = date('Y', strtotime($item['release_date']));
                    } elseif (!empty($item['first_air_date'])) {
                        $year = date('Y', strtotime($item['first_air_date']));
                    }
                    
                    $vote = number_format($item['vote_average'], 1);
                    $link = $type === 'movie'
                        ? "movie_details.php?id=" . $item['id']
                        : "tv_details.php?id=" . $item['id'];
                    ?>
                    
                    <div class="suggest-card mb-4">
                        <img src="<?= $poster ?>" alt="Poster" class="poster-img">
                        <div class="suggest-body">
                            <span class="badge badge-type mb-2">
                                <i class="bi <?= $type === 'movie' ? 'bi-camera-reels' : 'bi-tv' ?> me-1"></i>
                                <?= $type === 'movie' ? 'Film' : 'Serie TV' ?>
                            </span>
                            
                            <?php if($year): ?>
                                <span class="badge bg-secondary ms-2"><?= $year ?></span>
                            <?php endif; ?>
                            
                            <span class="badge bg-warning text-dark ms-2">
                                <i class="bi bi-star-fill me-1"></i><?= $vote ?>
                            </span>
                            
                            <h3 class="mt-2 mb-1"><?= $title ?></h3>
                            <p class="mb-3"><?= $overview ?></p>
                            
                            <a href="<?= $link ?>" class="btn btn-suggest">
                                <i class="bi bi-info-circle me-2"></i>Scopri di più
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-suggest">
                    <i class="bi bi-emoji-frown mb-2" style="font-size:2.4rem"></i>
                    <h3>Nessun suggerimento trovato</h3>
                    <p class="mb-3">Prova ad allargare i filtri di ricerca</p>
                    <a href="index.html" class="btn btn-outline-primary">Torna alla Home</a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if(!empty($suggestions)): ?>
            <form method="get" action="suggest.php" class="text-center mt-4">
                <?php foreach($rerollParams as $param => $value): ?>
                    <input type="hidden" name="<?= htmlspecialchars($param) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endforeach; ?>
                <button type="submit" class="reroll-btn">
                    <i class="bi bi-shuffle me-2"></i>Reroll
                </button>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="index.html" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Torna alla Home
            </a>
        </div>
    </div>
</body>
</html>