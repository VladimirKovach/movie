<?php
require_once 'config.php';

if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("Location: index.html");
    exit();
}

$tvId = intval($_GET['id']);
$tv = fetchFromApi("tv/$tvId", ['append_to_response' => 'credits,videos,similar']);

if (!isset($tv['id'])) {
    header("Location: 404.php");
    exit();
}

// Trova il primo episodio trasmesso
$firstEpisode = null;
if (isset($tv['seasons']) && is_array($tv['seasons'])) {
    foreach ($tv['seasons'] as $season) {
        if ($season['season_number'] == 1) {
            $seasonData = fetchFromApi("tv/$tvId/season/1");
            if (isset($seasonData['episodes']) && is_array($seasonData['episodes']) && count($seasonData['episodes']) > 0) {
                $firstEpisode = $seasonData['episodes'][0];
            }
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tv['name']) ?> | FindFilm</title>
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
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://image.tmdb.org/t/p/original<?= $tv['backdrop_path'] ?>');
            background-size: cover;
            background-position: center;
            color: var(--secondary-color);
            padding: 5rem 0;
            margin-bottom: 3rem;
        }
        
        .tv-poster {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 100%;
            height: auto;
            border: 2px solid var(--secondary-color);
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(142, 22, 22, 0.08);
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
        
        .season-card {
            border: 1px solid var(--secondary-color);
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .season-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(142, 22, 22, 0.1);
        }
        
        .season-img {
            height: 200px;
            object-fit: cover;
        }
        
        .cast-card {
            border: 1px solid var(--secondary-color);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
        }
        
        .cast-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(142, 22, 22, 0.1);
        }
        
        .cast-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
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
            transform: translate(-50%, -50%);
            font-size: 3rem;
            color: var(--light-color);
            opacity: 0.8;
            transition: all 0.3s;
        }
        
        .video-thumbnail:hover .play-icon {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1.1);
        }
        
        .similar-card {
            border: 1px solid var(--secondary-color);
            border-radius: 10px;
            transition: all 0.3s;
            height: 100%;
        }
        
        .similar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(142, 22, 22, 0.1);
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
            
            .tv-poster {
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
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Serie TV</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- TV Show Header -->
    <div class="detail-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center text-md-start">
                    <img src="<?= !empty($tv['poster_path']) ? 
                        "https://image.tmdb.org/t/p/w500" . $tv['poster_path'] : 
                        "https://via.placeholder.com/500x750?text=Locandina+non+disponibile" ?>" 
                         alt="<?= htmlspecialchars($tv['name']) ?>" class="tv-poster">
                </div>
                <div class="col-md-9 mt-4 mt-md-0">
                    <h1 class="display-4 fw-bold"><?= htmlspecialchars($tv['name']) ?></h1>
                    
                    <div class="d-flex align-items-center flex-wrap my-3">
                        <span class="badge-rating me-3 mb-2">
                            <i class="bi bi-star-fill me-1"></i><?= number_format($tv['vote_average'], 1) ?>
                        </span>
                        <span class="me-3 mb-2">
                            <i class="bi bi-calendar me-2"></i>
                            <?= !empty($tv['first_air_date']) ? date('d/m/Y', strtotime($tv['first_air_date'])) : 'Data sconosciuta' ?>
                        </span>
                        <span class="me-3 mb-2">
                            <i class="bi bi-collection me-2"></i>
                            <?= $tv['number_of_seasons'] ?? '--' ?> stagioni
                        </span>
                        <span class="me-3 mb-2">
                            <i class="bi bi-tv me-2"></i>
                            <?= $tv['number_of_episodes'] ?? '--' ?> episodi
                        </span>
                        <?php if(isset($tv['networks']) && !empty($tv['networks'])): ?>
                            <span class="me-3 mb-2">
                                <i class="bi bi-broadcast me-2"></i>
                                <?= htmlspecialchars($tv['networks'][0]['name']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <?php if(isset($tv['genres'])): ?>
                            <?php foreach($tv['genres'] as $genre): ?>
                                <span class="badge genre-badge"><?= htmlspecialchars($genre['name']) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(!empty($tv['tagline'])): ?>
                        <p class="lead fst-italic">"<?= htmlspecialchars($tv['tagline']) ?>"</p>
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
                    <p><?= !empty($tv['overview']) ? htmlspecialchars($tv['overview']) : 'Nessuna descrizione disponibile.' ?></p>
                </div>
                
                <!-- First Episode -->
                <?php if(isset($firstEpisode)): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-tv me-2"></i>Primo Episodio</h3>
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <span class="badge bg-secondary">S<?= $firstEpisode['season_number'] ?>E<?= $firstEpisode['episode_number'] ?></span>
                            </div>
                            <div class="flex-grow-1">
                                <h5><?= htmlspecialchars($firstEpisode['name']) ?></h5>
                                <p class="mb-1"><small class="text-muted"><?= date('d/m/Y', strtotime($firstEpisode['air_date'])) ?></small></p>
                                <p><?= htmlspecialchars($firstEpisode['overview'] ?? 'Nessuna descrizione disponibile.') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Next Episode -->
                <?php if(isset($tv['next_episode_to_air'])): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-tv me-2"></i>Prossimo Episodio</h3>
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <span class="badge bg-secondary">S<?= $tv['next_episode_to_air']['season_number'] ?>E<?= $tv['next_episode_to_air']['episode_number'] ?></span>
                            </div>
                            <div class="flex-grow-1">
                                <h5><?= htmlspecialchars($tv['next_episode_to_air']['name']) ?></h5>
                                <p class="mb-1"><small class="text-muted"><?= date('d/m/Y', strtotime($tv['next_episode_to_air']['air_date'])) ?></small></p>
                                <p><?= htmlspecialchars($tv['next_episode_to_air']['overview'] ?? 'Nessuna descrizione disponibile.') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Videos -->
                <?php if(isset($tv['videos']['results']) && !empty($tv['videos']['results'])): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-play-circle me-2"></i>Video</h3>
                        <div class="row">
                            <?php 
                            $videoCount = 0;
                            foreach($tv['videos']['results'] as $video): 
                                if($video['site'] == 'YouTube' && $videoCount < 2):
                                    $videoCount++;
                            ?>
                                <div class="col-md-6 mb-4">
                                    <div class="video-thumbnail">
                                        <img src="https://img.youtube.com/vi/<?= $video['key'] ?>/hqdefault.jpg" 
                                             alt="<?= htmlspecialchars($video['name']) ?>" class="img-fluid">
                                        <a href="https://www.youtube.com/watch?v=<?= $video['key'] ?>" 
                                           class="stretched-link" target="_blank" data-bs-toggle="tooltip" 
                                           title="Guarda '<?= htmlspecialchars($video['name']) ?>'">
                                            <i class="bi bi-play-circle-fill play-icon"></i>
                                        </a>
                                    </div>
                                    <h5><?= htmlspecialchars($video['name']) ?></h5>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Similar TV Shows -->
                <?php if(isset($tv['similar']['results']) && !empty($tv['similar']['results'])): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-collection-play me-2"></i>Serie TV Simili</h3>
                        <div class="row row-cols-2 row-cols-md-3 g-3">
                            <?php foreach(array_slice($tv['similar']['results'], 0, 6) as $similar): ?>
                                <div class="col">
                                    <a href="tv_details.php?id=<?= $similar['id'] ?>" class="text-decoration-none">
                                        <div class="similar-card">
                                            <img src="<?= !empty($similar['poster_path']) ? 
                                                'https://image.tmdb.org/t/p/w200' . $similar['poster_path'] : 
                                                'https://via.placeholder.com/200x300?text=No+Image' ?>" 
                                                     class="card-img-top" alt="<?= htmlspecialchars($similar['name']) ?>">
                                            <div class="card-body p-2">
                                                <h6 class="card-title mb-0"><?= htmlspecialchars($similar['name']) ?></h6>
                                                <small class="text-muted">
                                                    <?= isset($similar['first_air_date']) ? date('Y', strtotime($similar['first_air_date'])) : 'N/A' ?>
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
            
            <div class="col-lg-4">
                <!-- Details -->
                <div class="info-card">
                    <h3 class="mb-4"><i class="bi bi-info-circle me-2"></i>Dettagli</h3>
                    <ul class="list-unstyled">
                        <?php if(isset($tv['original_name']) && $tv['original_name'] != $tv['name']): ?>
                            <li class="mb-2"><strong>Titolo originale:</strong> <?= htmlspecialchars($tv['original_name']) ?></li>
                        <?php endif; ?>
                        <li class="mb-2"><strong>Stato:</strong> <?= htmlspecialchars($tv['status'] ?? 'Sconosciuto') ?></li>
                        <li class="mb-2"><strong>Tipo:</strong> <?= htmlspecialchars($tv['type'] ?? 'Sconosciuto') ?></li>
                        <li class="mb-2"><strong>Lingua originale:</strong> <?= strtoupper($tv['original_language'] ?? '') ?></li>
                        <li class="mb-2"><strong>Durata episodi:</strong> <?= $tv['episode_run_time'][0] ?? '--' ?> minuti</li>
                        <li class="mb-2"><strong>Reti:</strong> 
                            <?php if(isset($tv['networks']) && !empty($tv['networks'])): ?>
                                <?= implode(', ', array_column($tv['networks'], 'name')) ?>
                            <?php else: ?>
                                Sconosciuto
                            <?php endif; ?>
                        </li>
                        <?php if(isset($tv['homepage']) && !empty($tv['homepage'])): ?>
                            <li class="mt-3">
                                <a href="<?= htmlspecialchars($tv['homepage']) ?>" target="_blank" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-globe me-2"></i>Sito Ufficiale
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Seasons -->
                <?php if(isset($tv['seasons']) && !empty($tv['seasons'])): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-collection me-2"></i>Stagioni</h3>
                        <div class="row g-3">
                            <?php foreach(array_slice($tv['seasons'], 0, 3) as $season): ?>
                                <div class="col-12">
                                    <div class="season-card">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <img src="<?= !empty($season['poster_path']) ? 
                                                    "https://image.tmdb.org/t/p/w200" . $season['poster_path'] : 
                                                    "https://via.placeholder.com/200x300?text=Immagine+non+disponibile" ?>" 
                                                         class="season-img" alt="<?= htmlspecialchars($season['name']) ?>">
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="mt-0"><?= htmlspecialchars($season['name']) ?></h5>
                                                <p class="mb-1"><small class="text-muted"><?= $season['episode_count'] ?> episodi</small></p>
                                                <?php if(!empty($season['air_date'])): ?>
                                                    <p class="mb-1"><small><?= date('Y', strtotime($season['air_date'])) ?></small></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if(count($tv['seasons']) > 3): ?>
                            <a href="#" class="btn btn-outline-secondary w-100 mt-3">Vedi tutte le stagioni</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Cast -->
                <?php if(isset($tv['credits']['cast']) && !empty($tv['credits']['cast'])): ?>
                    <div class="info-card">
                        <h3 class="mb-4"><i class="bi bi-people me-2"></i>Cast Principale</h3>
                        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-2 g-3">
                            <?php foreach(array_slice($tv['credits']['cast'], 0, 6) as $person): ?>
                                <div class="col">
                                    <div class="cast-card">
                                        <img src="<?= !empty($person['profile_path']) ? 
                                            "https://image.tmdb.org/t/p/w200" . $person['profile_path'] : 
                                            "https://via.placeholder.com/200x300?text=Immagine+non+disponibile" ?>" 
                                                 class="cast-img" alt="<?= htmlspecialchars($person['name']) ?>">
                                        <div class="p-2">
                                            <h6 class="mb-0"><?= htmlspecialchars($person['name']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($person['character']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if(count($tv['credits']['cast']) > 6): ?>
                            <a href="#" class="btn btn-outline-secondary w-100 mt-3">Vedi tutto il cast</a>
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
        // Abilita i tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>