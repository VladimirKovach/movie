<?php
require_once 'config.php';

if (!isset($_GET['query']) || empty(trim($_GET['query']))) {
    header("Location: index.html");
    exit();
}

$query = trim($_GET['query']);
$type = isset($_GET['type']) && in_array($_GET['type'], ['movie', 'tv']) ? $_GET['type'] : 'movie';
$page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;

$data = fetchFromApi("search/$type", [
    'query' => $query,
    'page' => $page
]);

$totalResults = $data['total_results'] ?? 0;
$totalPages = $data['total_pages'] ?? 1;
$currentPage = $page;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Risultati per "<?= htmlspecialchars($query) ?>" | FindFilm</title>
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

        .search-card {
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        /* Card layout: force button to bottom and clamp overview text */
        .result-card {
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid var(--secondary-color);
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(142, 22, 22, 0.15);
        }

        .card-img {
            height: 400px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }

        .card-body {
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            flex: 1;
        }

        .card-text {
            /* clamp to 3 lines and add ellipsis */
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .badge-rating {
            background-color: var(--primary-color);
            color: var(--light-color);
        }

        .btn-details {
            margin-top: auto; /* push to bottom */
            border-radius: 50px;
        }

        .search-input {
            border-radius: 50px;
            padding: 12px 20px;
            border: 2px solid var(--secondary-color);
            background: var(--light-color);
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(142, 22, 22, 0.25);
        }

        .search-btn {
            border-radius: 50px;
            padding: 12px 30px;
            background-color: var(--primary-color);
            border: none;
            color: var(--light-color);
        }

        .search-btn:hover {
            background-color: var(--dark-color);
        }

        .back-btn {
            border-radius: 50px;
            padding: 10px 25px;
            background-color: var(--primary-color);
            color: var(--light-color);
            border: none;
        }

        .back-btn:hover {
            background-color: var(--dark-color);
            color: var(--secondary-color);
        }

        .pagination .page-link {
            color: var(--primary-color);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        footer {
            background-color: var(--dark-color);
            color: var(--secondary-color);
            padding: 3rem 0;
            margin-top: 5rem;
        }
    </style>
</head>
<body>
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
                    <li class="nav-item"><a class="nav-link active" href="#">Ricerca</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="search-card">
            <form action="search.php" method="GET" class="d-flex flex-column flex-md-row align-items-center">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                <input type="text" name="query" class="form-control search-input me-md-2 mb-2 mb-md-0"
                       value="<?= htmlspecialchars($query) ?>" placeholder="Inserisci il titolo..." required>
                <button type="submit" class="btn search-btn">
                    <i class="bi bi-search me-2"></i>Cerca
                </button>
            </form>
        </div>

        <?php if($totalResults === 0): ?>
            <div class="text-center py-5">
                <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 3rem;"></i>
                <h2 class="mt-3">Nessun risultato trovato per "<?= htmlspecialchars($query) ?>"</h2>
                <p class="text-muted mt-2">Prova con un termine di ricerca diverso</p>
                <a href="index.html" class="btn btn-primary mt-3">
                    <i class="bi bi-arrow-left me-2"></i>Torna alla Home
                </a>
            </div>
        <?php else: ?>
            <h2 class="mb-4 text-center">
                <?= $type === 'movie' ? 'Film' : 'Serie TV' ?> trovati per "<?= htmlspecialchars($query) ?>"
                <span class="badge bg-secondary ms-2"><?= $totalResults ?> risultati</span>
            </h2>

            <?php if($totalPages > 1): ?>
            <nav aria-label="Paginazione" class="mb-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item<?= $currentPage <= 1 ? ' disabled' : '' ?>">
                        <a class="page-link" href="?query=<?= urlencode($query) ?>&type=<?= $type ?>&page=<?= max(1, $currentPage-1) ?>">&laquo;</a>
                    </li>
                    <?php
                    $start = max(1, $currentPage - 2);
                    $end = min($totalPages, $currentPage + 2);
                    if($start > 1): ?>
                    <li class="page-item"><a class="page-link" href="?query=<?= urlencode($query) ?>&type=<?= $type ?>&page=1">1</a></li>
                    <?php if($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for($i=$start; $i<=$end; $i++): ?>
                    <li class="page-item<?= $i==$currentPage?' active':'' ?>">
                        <a class="page-link" href="?query=<?= urlencode($query) ?>&type=<?= $type ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if($end<$totalPages): ?>
                    <?php if($end<$totalPages-1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?query=<?= urlencode($query) ?>&type=<?= $type ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
                    <?php endif; ?>
                    <li class="page-item<?= $currentPage >= $totalPages ? ' disabled' : '' ?>">
                        <a class="page-link" href="?query=<?= urlencode($query) ?>&type=<?= $type ?>&page=<?= min($totalPages, $currentPage+1) ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($data['results'] as $item): 
                    $poster = !empty($item['poster_path'])
                        ? "https://image.tmdb.org/t/p/w500{$item['poster_path']}"
                        : "https://via.placeholder.com/500x750?text=Immagine+non+disponibile";
                    $title = htmlspecialchars($item['title'] ?? $item['name'] ?? 'Titolo sconosciuto');
                    $date = $item['release_date'] ?? $item['first_air_date'] ?? null;
                    $overview = !empty($item['overview']) ? htmlspecialchars($item['overview']) : 'Nessuna descrizione disponibile.';
                    $link = ($type==='tv')
                        ? "tv_details.php?id={$item['id']}"
                        : "movie_details.php?id={$item['id']}";
                ?>
                <div class="col">
                    <div class="result-card">
                    <img
                        src="<?= $poster ?>"
                        onerror="this.onerror=null; this.src='image_not_found.jpg';"
                        class="card-img-top"
                        alt="<?= $title ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= $title ?></h5>
                            <small class="text-muted mb-2"><?= $date ? date('Y',strtotime($date)) : 'N/A' ?></small>
                            <p class="card-text"><?= $overview ?></p>
                            <a href="<?= $link ?>" class="btn btn-outline-primary btn-details w-100">
                                <i class="bi bi-info-circle me-2"></i>Dettagli
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-center mt-5">
                <a href="javascript:history.back()" class="btn back-btn">
                    <i class="bi bi-arrow-left me-2"></i>Torna indietro
                </a>
            </div>
        <?php endif; ?>
    </div>

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
</body>
</html>