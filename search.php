<?php

require_once 'bootstrap.php';

use App\Services\TMDBService;
use App\Services\DatabaseService;

// Initialize services
$config = require 'config/app.php';
$db = new DatabaseService($config);
$tmdb = new TMDBService($config, $db);

// Get search parameters
$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'movie'; // movie or tv
$page = $_GET['page'] ?? 1;

$results = [];
$totalResults = 0;
$totalPages = 0;
$error = null;

// Perform search if query is provided
if (!empty($query)) {
    try {
        if ($type === 'tv') {
            $searchResults = $tmdb->searchTVShows($query, $page);
        } else {
            $searchResults = $tmdb->searchMovies($query, $page);
        }
        
        $results = $searchResults['results'] ?? [];
        $totalResults = $searchResults['total_results'] ?? 0;
        $totalPages = $searchResults['total_pages'] ?? 0;
        
        // Log search for audit
        if (config('logging.audit_actions')) {
            $db->insert("INSERT INTO audit_log (user_id, action, ip_address, payload) VALUES (?, 'search', ?, ?)", [
                $_SESSION['user_id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? '',
                json_encode(['query' => $query, 'type' => $type, 'page' => $page])
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
        $error = "Failed to perform search. Please try again.";
    }
}

$pageTitle = 'Search - Movie Reviews';
include 'templates/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl font-bold text-gray-900 mb-8">Search Movies & TV Shows</h1>
        
        <!-- Search Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <form method="GET" action="search.php" class="space-y-4">
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <label for="q" class="block text-sm font-medium text-gray-700 mb-2">Search Query</label>
                        <input type="text" 
                               id="q" 
                               name="q" 
                               value="<?= isVulnerable('reflected_xss') ? $query : htmlspecialchars($query) ?>" 
                               placeholder="Enter movie or TV show title..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>
                    
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select id="type" 
                                name="type" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900">
                            <option value="movie" <?= $type === 'movie' ? 'selected' : '' ?>>Movies</option>
                            <option value="tv" <?= $type === 'tv' ? 'selected' : '' ?>>TV Shows</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Search Results Header -->
        <?php if (!empty($query)): ?>
            <div class="mb-6">
                <?php if (isVulnerable('reflected_xss')): ?>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        Search results for: <?= $query ?>
                    </h2>
                <?php else: ?>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        Search results for: <?= htmlspecialchars($query) ?>
                    </h2>
                <?php endif; ?>
                
                <?php if ($totalResults > 0): ?>
                    <p class="text-gray-600 mt-2">
                        Found <?= number_format($totalResults) ?> results
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Search Results -->
        <?php if (!empty($results)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($results as $item): ?>
                    <?php
                    $itemId = $item['id'];
                    $itemTitle = $type === 'tv' ? ($item['name'] ?? $item['original_name']) : ($item['title'] ?? $item['original_title']);
                    $itemDate = $type === 'tv' ? $item['first_air_date'] ?? '' : $item['release_date'] ?? '';
                    $itemOverview = $item['overview'] ?? '';
                    $posterPath = $item['poster_path'] ?? '';
                    $rating = $item['vote_average'] ?? 0;
                    ?>
                    
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <a href="details.php?type=<?= $type ?>&id=<?= $itemId ?>" class="block">
                            <div class="aspect-w-2 aspect-h-3">
                                <img src="<?= $tmdb->getImageUrl($posterPath) ?>" 
                                     alt="<?= htmlspecialchars($itemTitle) ?>"
                                     class="w-full h-80 object-cover"
                                     loading="lazy"
                                     onerror="this.src='/assets/images/no-poster.png'">
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2"><?= htmlspecialchars($itemTitle) ?></h3>
                                
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm text-gray-600">
                                        <?= $itemDate ? date('Y', strtotime($itemDate)) : 'TBA' ?>
                                    </span>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="text-sm text-gray-600"><?= number_format($rating, 1) ?></span>
                                    </div>
                                </div>
                                
                                <p class="text-sm text-gray-600 line-clamp-3"><?= htmlspecialchars(substr($itemOverview, 0, 100)) ?><?= strlen($itemOverview) > 100 ? '...' : '' ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?q=<?= urlencode($query) ?>&type=<?= $type ?>&page=<?= $page - 1 ?>" 
                               class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <span class="px-3 py-2 bg-blue-500 text-white rounded-md">
                            Page <?= $page ?> of <?= min($totalPages, 500) ?>
                        </span>
                        
                        <?php if ($page < min($totalPages, 500)): ?>
                            <a href="?q=<?= urlencode($query) ?>&type=<?= $type ?>&page=<?= $page + 1 ?>" 
                               class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
            
        <?php elseif (!empty($query) && !$error): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No results found</h3>
                <p class="mt-1 text-sm text-gray-500">Try adjusting your search terms or search type.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'templates/footer.php'; ?>