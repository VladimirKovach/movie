<?php
require_once 'config.php';

if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("Location: index.html");
    exit();
}

$movieId = intval($_GET['id']);
$movie = fetchFromApi("movie/$movieId", ['append_to_response' => 'credits,videos,similar']);

if (!isset($movie['id'])) {
    header("Location: 404.php");
    exit();
}

// Ottieni i provider di streaming (paese IT)
$watch = fetchFromApi("movie/$movieId/watch/providers");
$providers = $watch['results']['IT']['flatrate'] ?? [];

// Funzione helper per generare link al provider
function providerUrl(string $name, string $title): string {
    $q   = rawurlencode($title);
    $key = mb_strtolower($name);
    if (strpos($key, 'netflix') !== false) {
        return "https://www.netflix.com/search?q={$q}";
    }
    if (strpos($key, 'prime') !== false) {
        return "https://www.primevideo.com/search/ref=atv_sr_sug_Home?phrase={$q}";
    }
    if (strpos($key, 'disney') !== false) {
        return "https://www.disneyplus.com/it-it";
    }
    if (strpos($key, 'now') !== false) {
        return "https://www.nowtv.it/cinema-entertainment";
    }
    if (strpos($key, 'tim') !== false) {
        return "https://www.timvision.it/";
    }
    if (strpos($key, 'sky') !== false) {
        return "https://www.sky.it";
    }
    return '#';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($movie['title']) ?> | FindFilm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        .navbar-custom {
            background-color: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid var(--secondary-color);
        }
        .detail-header {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)),
                        url('https://image.tmdb.org/t/p/original<?= $movie['backdrop_path'] ?>');
            background-size: cover;
            background-position: center;
            color: var(--secondary-color);
            padding: 5rem 0;
            margin-bottom: 3rem;
        }
        .movie-poster {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 100%;
            height: auto;
            border: 2px solid var(--secondary-color);
        }
        .info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(142,22,22,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--secondary-color);
        }
        .badge-rating {
            background-color: var(--primary-color);
            color: var(--light-color);
            font-weight: 500;
        }
        .genre-badge {
            background-color: var(--secondary-color);
            color: var(--dark-color);
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .cast-card, .similar-card {
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
            border: 1px solid var(--secondary-color);
        }
        .cast-card:hover, .similar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(142,22,22,0.1);
        }
        .cast-img, .provider-logo {
            object-fit: cover;
            width: 100%;
        }
        .cast-img { height: 200px; }
        .video-thumbnail {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
            border: 1px solid var(--secondary-color);
        }
        .play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%,-50%);
            font-size: 3rem;
            color: var(--light-color);
            opacity: 0.8;
            transition: all 0.3s;
        }
        .video-thumbnail:hover .play-icon {
            opacity: 1;
            transform: translate(-50%,-50%) scale(1.1);
        }
        .provider-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .provider-card {
            flex: 1 1 120px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            padding: 1rem;
            transition: transform 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .provider-card:hover {
            transform: translateY(-3px);
        }
        .provider-logo {
            max-width: 60px;
            margin-bottom: 0.5rem;
        }
        .provider-name {
            font-size: 0.9rem;
            color: var(--dark-color);
        }
        .back-btn {
            border-radius: 50px;
            padding: 10px 25px;
            background-color: var(--primary-color);
            color: var(--light-color);
            border: none;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background-color: var(--dark-color);
            color: var(--secondary-color);
        }
        footer {
            background-color: var(--dark-color);
            color: var(--secondary-color);
            padding: 3rem 0;
            margin-top: 5rem;
        }
        @media (max-width: 768px) {
            .detail-header {
                padding: 3rem 0;
                text-align: center;
            }
            .movie-poster {
                margin-bottom: 2rem;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">
                <i class="bi bi-film me-2"></i>FindFilm
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#">Film</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Movie Header -->
    <div class="detail-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center text-md-start">
                    <img 
                        src="<?= !empty($movie['poster_path'])
                            ? "https://image.tmdb.org/t/p/w500" . $movie['poster_path']
                            : "image_not_found.jpg" ?>"
                        onerror="this.onerror=null;this.src='image_not_found.jpg';"
                        alt="<?= htmlspecialchars($movie['title']) ?>"
                        class="movie-poster">
                </div>
                <div class="col-md-9 mt-4 mt-md-0">
                    <h1 class="display-4 fw-bold"><?= htmlspecialchars($movie['title']) ?></h1>
                    <div class="d-flex align-items-center flex-wrap my-3">
                        <span class="badge-rating me-3 mb-2">
                            <i class="bi bi-star-fill me-1"></i><?= number_format($movie['vote_average'], 1) ?>
                        </span>
                        <span class="me-3 mb-2">
                            <i class="bi bi-calendar me-2"></i>
                            <?= !empty($movie['release_date'])
                                ? date('d/m/Y', strtotime($movie['release_date']))
                                : 'Data sconosciuta' ?>
                        </span>
                        <span class="me-3 mb-2">
                            <i class="bi bi-clock me-2"></i>
                            <?= isset($movie['runtime'])
                                ? floor($movie['runtime'] / 60) . 'h ' . ($movie['runtime'] % 60) . 'm'
                                : '--' ?>
                        </span>
                        <?php if ($movie['adult']): ?>
                            <span class="badge bg-danger me-2 mb-2">+18</span>
                        <?php endif; ?>
                        <?php if (!empty($movie['imdb_id'])): ?>
                            <a href="https://www.imdb.com/title/<?= $movie['imdb_id'] ?>"
                               target="_blank"
                               class="badge bg-warning text-dark me-2 mb-2">
                                <i class="bi bi-film me-1"></i>IMDb
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <?php foreach ($movie['genres'] as $genre): ?>
                            <span class="badge genre-badge"><?= htmlspecialchars($genre['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($movie['tagline'])): ?>
                        <p class="lead fst-italic">"<?= htmlspecialchars($movie['tagline']) ?>"</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <!-- Overview -->
                <div class="info-card">
                    <h3 class="mb-4"><i class="bi bi-card-text me-2"></i>Trama</h3>
                    <p><?= !empty($movie['overview']) ? htmlspecialchars($movie['overview']) : 'Nessuna descrizione disponibile.' ?></p>
                </div>

                <!-- Videos -->
                <?php if (!empty($movie['videos']['results'])): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-play-circle me-2"></i>Video</h3>
                        <div class="row">
                            <?php 
                            $videoCount = 0;
                            foreach ($movie['videos']['results'] as $video):
                                if ($video['site'] === 'YouTube' && $videoCount < 2):
                                    $videoCount++;
                            ?>
                            <div class="col-md-6 mb-4">
                                <div class="video-thumbnail">
                                    <img 
                                        src="https://img.youtube.com/vi/<?= $video['key'] ?>/hqdefault.jpg"
                                        onerror="this.onerror=null;this.src='image_not_found.jpg';"
                                        alt="<?= htmlspecialchars($video['name']) ?>"
                                        class="img-fluid">
                                    <a href="https://www.youtube.com/watch?v=<?= $video['key'] ?>"
                                       class="stretched-link"
                                       target="_blank"
                                       data-bs-toggle="tooltip"
                                       title="Guarda '<?= htmlspecialchars($video['name']) ?>'">
                                        <i class="bi bi-play-circle-fill play-icon"></i>
                                    </a>
                                </div>
                                <h5><?= htmlspecialchars($video['name']) ?></h5>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Dove guardare -->
                <?php if (!empty($providers)): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-tv me-2"></i>Dove guardare</h3>
                        <div class="provider-list">
                            <?php foreach ($providers as $prov): ?>
                                <a href="<?= providerUrl($prov['provider_name'], $movie['title']) ?>"
                                   target="_blank"
                                   class="provider-card">
                                    <img 
                                        src="https://image.tmdb.org/t/p/w92<?= $prov['logo_path'] ?>"
                                        onerror="this.onerror=null;this.src='image_not_found.jpg';"
                                        alt="<?= htmlspecialchars($prov['provider_name']) ?>"
                                        class="provider-logo">
                                    <div class="provider-name"><?= htmlspecialchars($prov['provider_name']) ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Similar Movies -->
                <?php if (!empty($movie['similar']['results'])): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-collection-play me-2"></i>Film Simili</h3>
                        <div class="row row-cols-2 row-cols-md-3 g-3">
                            <?php foreach (array_slice($movie['similar']['results'], 0, 6) as $sim): ?>
                                <div class="col">
                                    <a href="movie_details.php?id=<?= $sim['id'] ?>" class="text-decoration-none">
                                        <div class="similar-card">
                                            <img
                                                src="<?= !empty($sim['poster_path'])
                                                    ? 'https://image.tmdb.org/t/p/w200' . $sim['poster_path']
                                                    : 'image_not_found.jpg' ?>"
                                                onerror="this.onerror=null;this.src='image_not_found.jpg';"
                                                class="card-img-top"
                                                alt="<?= htmlspecialchars($sim['title']) ?>">
                                            <div class="card-body p-2">
                                                <h6 class="card-title mb-0"><?= htmlspecialchars($sim['title']) ?></h6>
                                                <small class="text-muted">
                                                    <?= !empty($sim['release_date'])
                                                        ? date('Y', strtotime($sim['release_date']))
                                                        : 'N/A' ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar Details, Cast, Crew -->
            <div class="col-lg-4">
                <div class="info-card">
                    <h3 class="mb-4"><i class="bi bi-info-circle me-2"></i>Dettagli</h3>
                    <ul class="list-unstyled">
                        <?php if (isset($movie['original_title']) && $movie['original_title'] !== $movie['title']): ?>
                            <li class="mb-2"><strong>Titolo originale:</strong> <?= htmlspecialchars($movie['original_title']) ?></li>
                        <?php endif; ?>
                        <li class="mb-2"><strong>Stato:</strong> <?= htmlspecialchars($movie['status'] ?? 'Sconosciuto') ?></li>
                        <li class="mb-2"><strong>Lingua originale:</strong> <?= strtoupper($movie['original_language'] ?? '') ?></li>
                        <li class="mb-2"><strong>Budget:</strong> <?= isset($movie['budget']) && $movie['budget'] > 0 ? '$' . number_format($movie['budget']) : 'Sconosciuto' ?></li>
                        <li class="mb-2"><strong>Incassi:</strong> <?= isset($movie['revenue']) && $movie['revenue'] > 0 ? '$' . number_format($movie['revenue']) : 'Sconosciuto' ?></li>
                        <li class="mb-2"><strong>Durata:</strong> <?= isset($movie['runtime']) ? floor($movie['runtime'] / 60) . 'h ' . ($movie['runtime'] % 60) . 'm' : '--' ?></li>
                        <?php if (!empty($movie['production_companies'])): ?>
                            <li class="mb-2"><strong>Produzione:</strong> <?= implode(', ', array_column($movie['production_companies'], 'name')) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($movie['homepage'])): ?>
                            <li class="mt-3">
                                <a href="<?= htmlspecialchars($movie['homepage']) ?>" target="_blank" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-globe me-2"></i>Sito Ufficiale
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Cast -->
                <?php if (!empty($movie['credits']['cast'])): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-people me-2"></i>Cast Principale</h3>
                        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-2 g-3">
                            <?php foreach (array_slice($movie['credits']['cast'], 0, 6) as $c): ?>
                                <div class="col">
                                    <div class="cast-card">
                                        <img
                                            src="<?= !empty($c['profile_path'])
                                                ? 'https://image.tmdb.org/t/p/w200' . $c['profile_path']
                                                : 'image_not_found.jpg' ?>"
                                            onerror="this.onerror=null;this.src='image_not_found.jpg';"
                                            class="cast-img"
                                            alt="<?= htmlspecialchars($c['name']) ?>">
                                        <div class="p-2">
                                            <h6 class="mb-0"><?= htmlspecialchars($c['name']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($c['character']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($movie['credits']['cast']) > 6): ?>
                            <a href="#" class="btn btn-outline-secondary w-100 mt-3">Vedi tutto il cast</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Crew -->
                <?php if (!empty($movie['credits']['crew'])): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-person-badge me-2"></i>Regia e Produzione</h3>
                        <?php
                        $dirs = array_filter($movie['credits']['crew'], fn($p) => $p['job'] === 'Director');
                        $prods = array_filter($movie['credits']['crew'], fn($p) => in_array($p['job'], ['Producer', 'Executive Producer']));
                        ?>
                        <?php if ($dirs): ?>
                            <h5 class="mt-3">Regia</h5>
                            <ul>
                                <?php foreach (array_slice($dirs, 0, 3) as $d): ?>
                                    <li><?= htmlspecialchars($d['name']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($prods): ?>
                            <h5 class="mt-3">Produzione</h5>
                            <ul>
                                <?php foreach (array_slice($prods, 0, 5) as $p): ?>
                                    <li><?= htmlspecialchars($p['name']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex justify-content-center my-5">
            <a href="javascript:history.back()" class="btn back-btn">
                <i class="bi bi-arrow-left me-2"></i>Torna indietro
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h3>FindFilm</h3>
                    <p>La tua guida completa al mondo del cinema e della televisione.</p>
                </div>
                <div class="col-md-3">
                    <h5>Link Utili</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.html" class="text-secondary">Home</a></li>
                        <li><a href="#" class="text-secondary">Film</a></li>
                        <li><a href="#" class="text-secondary">Serie TV</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contatti</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-envelope me-2"></i> info@findfilm.com</li>
                        <li><i class="bi bi-twitter me-2"></i> @FindFilm</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; 2023 FindFilm. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
    </script>
</body>
</html>