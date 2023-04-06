<?php

require_once 'bootstrap.php';

use App\Services\TMDBService;
use App\Services\DatabaseService;
use App\Services\AuthService;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize services
$config = require 'config/app.php';
$db = new DatabaseService($config);
$tmdb = new TMDBService($config, $db);
$auth = new AuthService($db, $config);

$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

// Handle add/remove favorites
if ($_POST) {
    $action = $_POST['action'] ?? null;
    $type = $_POST['type'] ?? null;
    $id = $_POST['id'] ?? null;
    $movieId = $_POST['movie_id'] ?? null;
    $tvId = $_POST['tv_id'] ?? null;
    
    // Convert type/id format to movie_id/tv_id format
    if ($type && $id) {
        if ($type === 'movie') {
            $movieId = $id;
        } elseif ($type === 'tv') {
            $tvId = $id;
        }
    }
    
    // CSRF protection (if enabled)
    if (!isVulnerable('csrf')) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $error = "Invalid request.";
        }
    }
    
    if (!isset($error)) {
        try {
            if ($action === 'add') {
                if ($movieId) {
                    $db->execute("INSERT IGNORE INTO favorites (user_id, movie_id, created_at) VALUES (?, ?, NOW())", [$user['id'], $movieId]);
                } elseif ($tvId) {
                    $db->execute("INSERT IGNORE INTO favorites (user_id, tv_id, created_at) VALUES (?, ?, NOW())", [$user['id'], $tvId]);
                }
                $success = "Added to favorites!";
            } elseif ($action === 'remove') {
                if ($movieId) {
                    $db->execute("DELETE FROM favorites WHERE user_id = ? AND movie_id = ?", [$user['id'], $movieId]);
                } elseif ($tvId) {
                    $db->execute("DELETE FROM favorites WHERE user_id = ? AND tv_id = ?", [$user['id'], $tvId]);
                }
                $success = "Removed from favorites!";
            }
        } catch (Exception $e) {
            error_log("Favorites error: " . $e->getMessage());
            $error = "Failed to update favorites.";
        }
    }
}

// Fetch user's favorite movies and TV shows
$favoriteMovies = [];
$favoriteTVShows = [];

try {
    // Get favorite movies with details
    $favoriteMovies = $db->fetchAll("
        SELECT f.id as favorite_id, m.* 
        FROM favorites f 
        JOIN movies m ON f.movie_id = m.id 
        WHERE f.user_id = ? 
        ORDER BY f.created_at DESC
    ", [$user['id']]);
    
    // Get favorite TV shows with details
    $favoriteTVShows = $db->fetchAll("
        SELECT f.id as favorite_id, t.* 
        FROM favorites f 
        JOIN tv_shows t ON f.tv_id = t.id 
        WHERE f.user_id = ? 
        ORDER BY f.created_at DESC
    ", [$user['id']]);
    
} catch (Exception $e) {
    error_log("Favorites fetch error: " . $e->getMessage());
    $error = "Failed to load favorites.";
}

$pageTitle = 'My Favorites - Movie Reviews';
include 'templates/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">My Favorites</h1>
        <p class="text-gray-600">Your collection of favorite movies and TV shows</p>
    </div>

    <!-- Messages -->
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Favorite Movies Section -->
    <div class="mb-12">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
            🎬 Favorite Movies
            <span class="ml-2 text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                <?= count($favoriteMovies) ?>
            </span>
        </h2>
        
        <?php if (empty($favoriteMovies)): ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <div class="text-gray-400 text-6xl mb-4">🎬</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Favorite Movies Yet</h3>
                <p class="text-gray-600 mb-4">Start exploring and add movies to your favorites!</p>
                <a href="/" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Browse Movies
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <?php foreach ($favoriteMovies as $movie): ?>
                    <div class="relative group">
                        <a href="details.php?type=movie&id=<?= $movie['id'] ?>" class="block">
                            <?php if (!empty($movie['poster_path'])): ?>
                                <img src="<?= $tmdb->getImageUrl($movie['poster_path'], 'w342') ?>" 
                                     alt="<?= htmlspecialchars($movie['title']) ?>"
                                     class="w-full h-auto rounded-lg shadow-md transition-transform group-hover:scale-105">
                            <?php else: ?>
                                <div class="w-full aspect-[2/3] bg-gray-200 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-400">No Image</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-2">
                                <h3 class="font-medium text-gray-900 line-clamp-2 text-sm">
                                    <?= htmlspecialchars($movie['title']) ?>
                                </h3>
                                <?php if (!empty($movie['release_date'])): ?>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?= date('Y', strtotime($movie['release_date'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </a>
                        
                        <!-- Remove from favorites button -->
                        <form method="POST" class="absolute top-2 right-2">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="type" value="movie">
                            <input type="hidden" name="id" value="<?= $movie['id'] ?>">
                            <?php if (!isVulnerable('csrf')): ?>
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <?php endif; ?>
                            <button type="submit" 
                                    class="bg-red-600 hover:bg-red-700 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                    onclick="return confirm('Remove from favorites?')"
                                    title="Remove from favorites">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Favorite TV Shows Section -->
    <div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
            📺 Favorite TV Shows
            <span class="ml-2 text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                <?= count($favoriteTVShows) ?>
            </span>
        </h2>
        
        <?php if (empty($favoriteTVShows)): ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <div class="text-gray-400 text-6xl mb-4">📺</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Favorite TV Shows Yet</h3>
                <p class="text-gray-600 mb-4">Start exploring and add TV shows to your favorites!</p>
                <a href="search.php?type=tv" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Browse TV Shows
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <?php foreach ($favoriteTVShows as $show): ?>
                    <div class="relative group">
                        <a href="details.php?type=tv&id=<?= $show['id'] ?>" class="block">
                            <?php if (!empty($show['poster_path'])): ?>
                                <img src="<?= $tmdb->getImageUrl($show['poster_path'], 'w342') ?>" 
                                     alt="<?= htmlspecialchars($show['name']) ?>"
                                     class="w-full h-auto rounded-lg shadow-md transition-transform group-hover:scale-105">
                            <?php else: ?>
                                <div class="w-full aspect-[2/3] bg-gray-200 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-400">No Image</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-2">
                                <h3 class="font-medium text-gray-900 line-clamp-2 text-sm">
                                    <?= htmlspecialchars($show['name']) ?>
                                </h3>
                                <?php if (!empty($show['first_air_date'])): ?>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?= date('Y', strtotime($show['first_air_date'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </a>
                        
                        <!-- Remove from favorites button -->
                        <form method="POST" class="absolute top-2 right-2">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="type" value="tv">
                            <input type="hidden" name="id" value="<?= $show['id'] ?>">
                            <?php if (!isVulnerable('csrf')): ?>
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <?php endif; ?>
                            <button type="submit" 
                                    class="bg-red-600 hover:bg-red-700 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                    onclick="return confirm('Remove from favorites?')"
                                    title="Remove from favorites">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'templates/footer.php'; ?>