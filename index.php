<?php

require_once 'bootstrap.php';

use App\Services\TMDBService;
use App\Services\DatabaseService;

// Initialize services
$config = require 'config/app.php';
$db = new DatabaseService($config);
$tmdb = new TMDBService($config, $db);

// Get current page and type
$page = $_GET['page'] ?? 1;
$type = $_GET['type'] ?? 'movie'; // movie or tv
$view = $_GET['view'] ?? 'discover'; // discover, trending, popular

// Fetch data based on type and view
$data = [];
$title = 'Movie Reviews - Discover Cinema';

try {
    switch ($view) {
        case 'trending':
            $data = $tmdb->getTrending($type);
            $title = 'Trending ' . ucfirst($type) . 's';
            break;
        case 'popular':
        case 'discover':
        default:
            if ($type === 'tv') {
                $data = $tmdb->discoverTVShows($page);
                $title = 'Popular TV Shows';
            } else {
                $data = $tmdb->discoverMovies($page);
                $title = 'Popular Movies';
            }
            break;
    }
} catch (Exception $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $data = ['results' => [], 'error' => 'Failed to load content'];
}

// Include header
include 'templates/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($title) ?></h1>
        
        <!-- Navigation Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="?type=movie&view=discover" 
                   class="<?= $type === 'movie' && $view === 'discover' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Popular Movies
                </a>
                <a href="?type=tv&view=discover" 
                   class="<?= $type === 'tv' && $view === 'discover' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Popular TV Shows
                </a>
                <a href="?type=movie&view=trending" 
                   class="<?= $type === 'movie' && $view === 'trending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Trending Movies
                </a>
                <a href="?type=tv&view=trending" 
                   class="<?= $type === 'tv' && $view === 'trending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Trending TV Shows
                </a>
                <a href="search.php" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Search
                </a>
            </nav>
        </div>
    </div>

    <?php if (isset($data['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <strong>Error:</strong> <?= htmlspecialchars($data['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Grid of movies/TV shows -->
    <?php if (isset($data['results']) && !empty($data['results'])): ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            <?php foreach ($data['results'] as $item): ?>
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
        <?php if (isset($data['total_pages']) && $data['total_pages'] > 1): ?>
            <div class="mt-8 flex justify-center">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?type=<?= $type ?>&view=<?= $view ?>&page=<?= $page - 1 ?>" 
                           class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="px-3 py-2 bg-blue-500 text-white rounded-md">
                        Page <?= $page ?> of <?= min($data['total_pages'], 500) ?>
                    </span>
                    
                    <?php if ($page < min($data['total_pages'], 500)): ?>
                        <a href="?type=<?= $type ?>&view=<?= $view ?>&page=<?= $page + 1 ?>" 
                           class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="text-center py-12">
            <p class="text-gray-500 text-lg">No content available at the moment.</p>
        </div>
    <?php endif; ?>
</main>

<?php include 'templates/footer.php'; ?>