<?php

require_once 'bootstrap.php';

use App\Services\TMDBService;
use App\Services\DatabaseService;

// Initialize services
$config = require 'config/app.php';
$db = new DatabaseService($config);
$tmdb = new TMDBService($config, $db);

// Get parameters
$type = $_GET['type'] ?? 'movie';
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: /');
    exit;
}

// Fetch details from TMDB
$details = null;
$reviews = [];
$error = null;

try {
    if ($type === 'tv') {
        $details = $tmdb->getTVShowDetails($id);
    } else {
        $details = $tmdb->getMovieDetails($id);
    }
    
    // Cache movie/TV show in database
    if ($details && !isset($details['error'])) {
        if ($type === 'tv') {
            $db->execute("INSERT IGNORE INTO tv_shows (id, name, original_name, overview, first_air_date, poster_path, backdrop_path, vote_average, vote_count, popularity, genre_ids, origin_country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $details['id'],
                $details['name'] ?? '',
                $details['original_name'] ?? '',
                $details['overview'] ?? '',
                $details['first_air_date'] ?? null,
                $details['poster_path'] ?? null,
                $details['backdrop_path'] ?? null,
                $details['vote_average'] ?? 0,
                $details['vote_count'] ?? 0,
                $details['popularity'] ?? 0,
                json_encode(array_column($details['genres'] ?? [], 'id')),
                json_encode($details['origin_country'] ?? [])
            ]);
        } else {
            $db->execute("INSERT IGNORE INTO movies (id, title, original_title, overview, release_date, poster_path, backdrop_path, vote_average, vote_count, popularity, genre_ids) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $details['id'],
                $details['title'] ?? '',
                $details['original_title'] ?? '',
                $details['overview'] ?? '',
                $details['release_date'] ?? null,
                $details['poster_path'] ?? null,
                $details['backdrop_path'] ?? null,
                $details['vote_average'] ?? 0,
                $details['vote_count'] ?? 0,
                $details['popularity'] ?? 0,
                json_encode(array_column($details['genres'] ?? [], 'id'))
            ]);
        }
    }
    
    // Fetch reviews from database
    if ($type === 'tv') {
        $reviews = $db->fetchAll("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.tv_id = ? ORDER BY r.created_at DESC", [$id]);
    } else {
        $reviews = $db->fetchAll("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.movie_id = ? ORDER BY r.created_at DESC", [$id]);
    }
    
} catch (Exception $e) {
    error_log("Details error: " . $e->getMessage());
    $error = "Failed to load details.";
}

if (!$details || isset($details['error'])) {
    include 'templates/header.php';
    echo '<div class="container mx-auto px-4 py-8"><div class="text-center"><h1 class="text-2xl font-bold text-gray-900 mb-4">Content Not Found</h1><p class="text-gray-600">The requested content could not be found.</p><a href="/" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Go Home</a></div></div>';
    include 'templates/footer.php';
    exit;
}

$title = $type === 'tv' ? ($details['name'] ?? $details['original_name']) : ($details['title'] ?? $details['original_title']);
$pageTitle = $title . ' - Movie Reviews';
include 'templates/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <!-- Hero Section -->
    <div class="relative bg-gray-900 rounded-lg overflow-hidden mb-8">
        <?php if (!empty($details['backdrop_path'])): ?>
            <div class="absolute inset-0">
                <img src="<?= $tmdb->getImageUrl($details['backdrop_path'], 'w1280') ?>" 
                     alt="<?= htmlspecialchars($title) ?> backdrop"
                     class="w-full h-full object-cover opacity-30">
            </div>
        <?php endif; ?>
        
        <div class="relative p-8 md:p-12">
            <div class="flex flex-col md:flex-row space-y-6 md:space-y-0 md:space-x-8">
                <div class="flex-shrink-0">
                    <img src="<?= $tmdb->getImageUrl($details['poster_path']) ?>" 
                         alt="<?= htmlspecialchars($title) ?> poster"
                         class="w-64 h-96 object-cover rounded-lg shadow-lg mx-auto md:mx-0">
                </div>
                
                <div class="flex-1 text-white">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4"><?= htmlspecialchars($title) ?></h1>
                    
                    <div class="flex flex-wrap items-center space-x-4 mb-4 text-sm">
                        <span class="bg-yellow-600 px-2 py-1 rounded">
                            ⭐ <?= number_format($details['vote_average'] ?? 0, 1) ?>
                        </span>
                        <span>
                            <?php 
                            $date = $type === 'tv' ? $details['first_air_date'] ?? '' : $details['release_date'] ?? '';
                            echo $date ? date('Y', strtotime($date)) : 'TBA';
                            ?>
                        </span>
                        <?php if (!empty($details['genres'])): ?>
                            <span><?= implode(', ', array_column($details['genres'], 'name')) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-lg leading-relaxed mb-6"><?= htmlspecialchars($details['overview'] ?? 'No overview available.') ?></p>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-4">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" action="favorites.php" class="inline">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="type" value="<?= $type ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <?php if (!isVulnerable('csrf')): ?>
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <?php endif; ?>
                                <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                                    ❤️ Add to Favorites
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                                Login to Add Favorites
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">User Reviews</h2>
            
            <!-- Add Review Form -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h3 class="text-lg font-semibold mb-4">Write a Review</h3>
                    
                    <form method="POST" action="review.php" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="type" value="<?= $type ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <?php if (!isVulnerable('csrf')): ?>
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                            <select id="rating" name="rating" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900">
                                <option value="">Select rating</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?>/10</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Review</label>
                            <textarea id="content" 
                                      name="content" 
                                      rows="4" 
                                      required
                                      placeholder="Share your thoughts about this <?= $type === 'tv' ? 'TV show' : 'movie' ?>..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Submit Review
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="bg-gray-100 p-6 rounded-lg mb-6">
                    <p class="text-gray-600">
                        <a href="login.php" class="text-blue-600 hover:underline">Login</a> to write a review.
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Existing Reviews -->
            <?php if (!empty($reviews)): ?>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        <?= strtoupper(substr($review['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($review['username']) ?></p>
                                        <p class="text-sm text-gray-500"><?= date('M j, Y', strtotime($review['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm font-medium">
                                        <?= $review['rating'] ?>/10
                                    </span>
                                </div>
                            </div>
                            
                            <div class="text-gray-700">
                                <?php if (isVulnerable('stored_xss')): ?>
                                    <?= $review['content'] ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($review['content']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <p>No reviews yet. Be the first to write one!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold mb-4">Details</h3>
                
                <dl class="space-y-3 text-sm">
                    <?php if ($type === 'tv'): ?>
                        <div>
                            <dt class="font-medium text-gray-900">First Air Date</dt>
                            <dd class="text-gray-600"><?= $details['first_air_date'] ? date('M j, Y', strtotime($details['first_air_date'])) : 'TBA' ?></dd>
                        </div>
                        <?php if (!empty($details['number_of_seasons'])): ?>
                            <div>
                                <dt class="font-medium text-gray-900">Seasons</dt>
                                <dd class="text-gray-600"><?= $details['number_of_seasons'] ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($details['number_of_episodes'])): ?>
                            <div>
                                <dt class="font-medium text-gray-900">Episodes</dt>
                                <dd class="text-gray-600"><?= $details['number_of_episodes'] ?></dd>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div>
                            <dt class="font-medium text-gray-900">Release Date</dt>
                            <dd class="text-gray-600"><?= $details['release_date'] ? date('M j, Y', strtotime($details['release_date'])) : 'TBA' ?></dd>
                        </div>
                        <?php if (!empty($details['runtime'])): ?>
                            <div>
                                <dt class="font-medium text-gray-900">Runtime</dt>
                                <dd class="text-gray-600"><?= $details['runtime'] ?> minutes</dd>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div>
                        <dt class="font-medium text-gray-900">Rating</dt>
                        <dd class="text-gray-600"><?= number_format($details['vote_average'] ?? 0, 1) ?>/10 (<?= number_format($details['vote_count'] ?? 0) ?> votes)</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>