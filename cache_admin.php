<?php

require_once 'bootstrap.php';

use App\Services\TMDBService;
use App\Services\DatabaseService;
use App\Services\AuthService;

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize services
$config = require 'config/app.php';
$db = new DatabaseService($config);
$tmdb = new TMDBService($config, $db);
$auth = new AuthService($db, $config);

// Check if user is admin
$user = $auth->getCurrentUser();
if (!$user || $user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Handle sync actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'sync_movies':
            $result = $tmdb->syncPopularMovies();
            $message = $result ? 'Popular movies synced successfully!' : 'Failed to sync popular movies.';
            $messageType = $result ? 'success' : 'error';
            break;
            
        case 'sync_tv':
            $result = $tmdb->syncPopularTVShows();
            $message = $result ? 'Popular TV shows synced successfully!' : 'Failed to sync popular TV shows.';
            $messageType = $result ? 'success' : 'error';
            break;
            
        case 'sync_trending':
            $result = $tmdb->syncTrending();
            $message = $result ? 'Trending content synced successfully!' : 'Failed to sync trending content.';
            $messageType = $result ? 'success' : 'error';
            break;
            
        case 'full_sync':
            $results = $tmdb->runFullSync();
            $successful = 0;
            $total = 0;
            foreach ($results as $key => $result) {
                if ($key !== 'expired_cleaned') {
                    $total++;
                    if ($result === true) $successful++;
                }
            }
            $message = "Full sync completed: {$successful}/{$total} successful. Cleaned {$results['expired_cleaned']} expired entries.";
            $messageType = $successful === $total ? 'success' : 'warning';
            break;
            
        case 'clear_cache':
            $cleared = $tmdb->clearExpiredCache();
            $message = "Cleared {$cleared} expired cache entries.";
            $messageType = 'info';
            break;
    }
}

// Get cache statistics
$stats = $tmdb->getSyncStats();

// Get cache contents statistics
$cacheContents = null;
$cacheTotals = null;
$errorMessage = null;

if ($tmdb->isCacheEnabled()) {
    try {
        // First check if the tmdb_cache table exists
        $stmt = $db->getPdo()->prepare("SHOW TABLES LIKE 'tmdb_cache'");
        $stmt->execute();
        $tableExists = $stmt->fetch();
        
        if (!$tableExists) {
            $errorMessage = "Cache table 'tmdb_cache' does not exist. Please run database migration.";
        } else {
            // Check if cache table has any data
            $stmt = $db->getPdo()->prepare("SELECT COUNT(*) as count FROM tmdb_cache");
            $stmt->execute();
            $totalCacheCount = $stmt->fetch();
            
            if ($totalCacheCount['count'] > 0) {
                // Get detailed cache statistics
                $stmt = $db->getPdo()->prepare("
                    SELECT 
                        cache_type,
                        COUNT(*) as total_entries,
                        SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as active_entries,
                        SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired_entries,
                        MIN(created_at) as oldest_cache,
                        MAX(updated_at) as newest_cache,
                        AVG(TIMESTAMPDIFF(HOUR, created_at, expires_at)) as avg_cache_hours
                    FROM tmdb_cache 
                    GROUP BY cache_type
                    ORDER BY cache_type
                ");
                $stmt->execute();
                $cacheContents = $stmt->fetchAll();
                
                // Get total cache size
                $stmt = $db->getPdo()->prepare("
                    SELECT 
                        COUNT(*) as total_cache_entries,
                        SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as total_active,
                        SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as total_expired,
                        SUM(LENGTH(data)) as total_data_size
                    FROM tmdb_cache
                ");
                $stmt->execute();
                $cacheTotals = $stmt->fetch();
            } else {
                $totalCacheCount = ['count' => 0];
            }
        }
        
    } catch (Exception $e) {
        $errorMessage = "Database error: " . $e->getMessage();
        error_log("Cache contents stats error: " . $e->getMessage());
        $cacheContents = null;
        $cacheTotals = null;
        $totalCacheCount = null;
    }
} else {
    $totalCacheCount = ['count' => 0];
    $errorMessage = "Cache is disabled in configuration.";
}

include 'templates/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                <svg class="w-8 h-8 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7l16 0M4 7l1-3h14l1 3M9 11l0 4M15 11l0 4"></path>
                </svg>
                TMDB Cache Management
            </h1>
            
            <?php if ($message): ?>
                <div class="mt-4 p-4 rounded-lg <?php 
                    echo $messageType === 'error' ? 'bg-red-50 border border-red-200 text-red-800' : 
                        ($messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 
                        ($messageType === 'warning' ? 'bg-yellow-50 border border-yellow-200 text-yellow-800' : 
                        'bg-blue-50 border border-blue-200 text-blue-800')); 
                ?>">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Cache Configuration -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Cache Configuration
                </h2>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Cache Enabled:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $config['tmdb']['cache']['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $config['tmdb']['cache']['enabled'] ? 'Yes' : 'No'; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Sync Interval:</span>
                            <span class="text-sm text-gray-900"><?php echo $config['tmdb']['cache']['sync_interval_hours']; ?> hours</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Popular Content Cache:</span>
                            <span class="text-sm text-gray-900"><?php echo $config['tmdb']['cache']['popular_content_cache_hours']; ?> hours</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Search Cache:</span>
                            <span class="text-sm text-gray-900"><?php echo $config['tmdb']['cache']['search_cache_hours']; ?> hours</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Details Cache:</span>
                            <span class="text-sm text-gray-900"><?php echo $config['tmdb']['cache']['details_cache_hours']; ?> hours</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Trending Cache:</span>
                            <span class="text-sm text-gray-900"><?php echo $config['tmdb']['cache']['trending_cache_hours']; ?> hours</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Sync Actions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Manual Sync Actions
                </h2>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <form method="post">
                        <input type="hidden" name="action" value="sync_movies">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 110 2h-1v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4zM6 6v14h12V6H6z"></path>
                            </svg>
                            Sync Movies
                        </button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="action" value="sync_tv">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Sync TV Shows
                        </button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="action" value="sync_trending">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path>
                            </svg>
                            Sync Trending
                        </button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="action" value="full_sync">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Full Sync
                        </button>
                    </form>
                </div>
                <div class="max-w-md">
                    <form method="post">
                        <input type="hidden" name="action" value="clear_cache">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Clear Expired Cache
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cache Contents Statistics -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Cache Contents Statistics
                    <?php if ($errorMessage): ?>
                        <span class="ml-2 text-sm text-red-600">(Error)</span>
                    <?php elseif (!$tmdb->isCacheEnabled()): ?>
                        <span class="ml-2 text-sm text-red-600">(Cache Disabled)</span>
                    <?php elseif (isset($totalCacheCount) && $totalCacheCount['count'] == 0): ?>
                        <span class="ml-2 text-sm text-yellow-600">(No Cache Data)</span>
                    <?php endif; ?>
                </h2>
            </div>
            <div class="px-6 py-4">
                <?php if ($errorMessage): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Cache Error</h3>
                        <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($errorMessage); ?></p>
                        <?php if (strpos($errorMessage, 'does not exist') !== false): ?>
                            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <h4 class="text-sm font-medium text-yellow-800">Setup Required</h4>
                                <p class="mt-1 text-sm text-yellow-700">The cache tables need to be created. Run the database migration:</p>
                                <div class="mt-2 bg-gray-800 text-gray-100 text-xs p-2 rounded font-mono">
                                    php database/migrate.php
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif (!$tmdb->isCacheEnabled()): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Cache is Disabled</h3>
                        <p class="mt-1 text-sm text-gray-500">Enable cache in configuration to see statistics.</p>
                    </div>
                <?php elseif (isset($totalCacheCount) && $totalCacheCount['count'] == 0): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No Cache Data Available</h3>
                        <p class="mt-1 text-sm text-gray-500">Cache is enabled but no data has been cached yet. Try syncing content or browsing the site to populate the cache.</p>
                        <div class="mt-4">
                            <form method="post" class="inline">
                                <input type="hidden" name="action" value="full_sync">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Populate Cache Now
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($cacheContents && count($cacheContents) > 0): ?>
                <!-- Cache Overview Cards -->
                <?php if (isset($cacheTotals)): ?>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7l16 0M4 7l1-3h14l1 3M9 11l0 4M15 11l0 4"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-blue-600">Total Entries</p>
                                <p class="text-2xl font-semibold text-blue-900"><?php echo number_format($cacheTotals['total_cache_entries']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-600">Active Entries</p>
                                <p class="text-2xl font-semibold text-green-900"><?php echo number_format($cacheTotals['total_active']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-600">Expired Entries</p>
                                <p class="text-2xl font-semibold text-red-900"><?php echo number_format($cacheTotals['total_expired']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-purple-600">Cache Size</p>
                                <p class="text-2xl font-semibold text-purple-900"><?php echo $cacheTotals['total_data_size'] ? number_format($cacheTotals['total_data_size'] / 1024 / 1024, 2) . ' MB' : '0 MB'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Detailed Cache Contents Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cache Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expired</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cache Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Cache Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age Range</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($cacheContents as $cache): ?>
                                <?php 
                                    $activeRate = $cache['total_entries'] > 0 ? ($cache['active_entries'] / $cache['total_entries']) * 100 : 0;
                                    $rateColor = $activeRate >= 80 ? 'bg-green-100 text-green-800' : 
                                               ($activeRate >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-3 w-3 rounded-full <?php 
                                                echo $cache['cache_type'] === 'discover_movies' ? 'bg-blue-400' :
                                                    ($cache['cache_type'] === 'discover_tv' ? 'bg-indigo-400' :
                                                    ($cache['cache_type'] === 'search_movies' ? 'bg-cyan-400' :
                                                    ($cache['cache_type'] === 'search_tv' ? 'bg-teal-400' :
                                                    ($cache['cache_type'] === 'movie_details' ? 'bg-purple-400' :
                                                    ($cache['cache_type'] === 'tv_details' ? 'bg-pink-400' :
                                                    ($cache['cache_type'] === 'trending' ? 'bg-orange-400' : 'bg-gray-400'))))));
                                            ?>"></div>
                                            <span class="ml-3 text-sm font-medium text-gray-900">
                                                <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($cache['cache_type']))); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo number_format($cache['total_entries']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">
                                        <?php echo number_format($cache['active_entries']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                        <?php echo number_format($cache['expired_entries']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $rateColor; ?>">
                                                <?php echo number_format($activeRate, 1); ?>%
                                            </span>
                                            <div class="ml-3 w-16 bg-gray-200 rounded-full h-2">
                                                <div class="h-2 rounded-full <?php echo $activeRate >= 80 ? 'bg-green-500' : ($activeRate >= 50 ? 'bg-yellow-500' : 'bg-red-500'); ?>" 
                                                     style="width: <?php echo $activeRate; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?php echo $cache['avg_cache_hours'] ? round($cache['avg_cache_hours'], 1) . 'h' : 'N/A'; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="text-xs">
                                            <div>Oldest: <?php echo date('M j, H:i', strtotime($cache['oldest_cache'])); ?></div>
                                            <div>Newest: <?php echo date('M j, H:i', strtotime($cache['newest_cache'])); ?></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($stats): ?>
            <!-- Sync Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Sync Status
                    </h2>
                </div>
                <div class="px-6 py-4">
                    <?php if (!empty($stats['sync_status'])): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sync Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Sync</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Records</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error Message</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($stats['sync_status'] as $sync): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($sync['sync_type']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?php echo htmlspecialchars($sync['last_sync_at']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                                    echo $sync['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                        ($sync['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($sync['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?php echo htmlspecialchars($sync['records_processed']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <?php if ($sync['error_message']): ?>
                                                    <span class="text-red-600 text-xs"><?php echo htmlspecialchars($sync['error_message']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">None</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No sync history available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cache Statistics -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7l16 0M4 7l1-3h14l1 3M9 11l0 4M15 11l0 4"></path>
                        </svg>
                        Cache Statistics
                    </h2>
                </div>
                <div class="px-6 py-4">
                    <?php if (!empty($stats['cache_stats'])): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cache Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Entries</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Entries</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Oldest Entry</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Newest Entry</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($stats['cache_stats'] as $cache): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($cache['cache_type']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?php echo htmlspecialchars($cache['count']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <div class="flex items-center">
                                                    <span class="font-medium"><?php echo htmlspecialchars($cache['active_count']); ?></span>
                                                    <span class="ml-2 text-xs text-gray-400">
                                                        (<?php echo round(($cache['active_count'] / $cache['count']) * 100, 1); ?>%)
                                                    </span>
                                                </div>
                                                <div class="mt-1 w-20 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo round(($cache['active_count'] / $cache['count']) * 100, 1); ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?php echo htmlspecialchars($cache['oldest_entry']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?php echo htmlspecialchars($cache['newest_entry']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7l16 0M4 7l1-3h14l1 3M9 11l0 4M15 11l0 4"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No cache data available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Automatic Sync Setup
                </h2>
            </div>
            <div class="px-6 py-4">
                <div class="space-y-6">
                    <div>
                        <h3 class="text-base font-medium text-gray-900 mb-3">Cron Job Setup</h3>
                        <p class="text-sm text-gray-600 mb-3">To set up automatic syncing, add the following cron job to your server:</p>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                            <div class="text-gray-700 mb-2"># Run TMDB sync every 6 hours</div>
                            <div class="text-gray-900">0 */6 * * * cd <?php echo __DIR__; ?> && php sync_tmdb_cache.php > /dev/null 2>&1</div>
                            <div class="text-gray-700 mt-3 mb-2"># Or run with verbose output and log to file</div>
                            <div class="text-gray-900">0 */6 * * * cd <?php echo __DIR__; ?> && php sync_tmdb_cache.php --verbose >> logs/sync.log 2>&1</div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-base font-medium text-gray-900 mb-3">Manual Sync Commands</h3>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                            <div class="text-gray-700 mb-2"># Navigate to project directory</div>
                            <div class="text-gray-900 mb-3">cd <?php echo __DIR__; ?></div>
                            
                            <div class="text-gray-700 mb-2"># Run sync manually</div>
                            <div class="text-gray-900 mb-3">php sync_tmdb_cache.php --verbose</div>
                            
                            <div class="text-gray-700 mb-2"># Force sync (ignore intervals)</div>
                            <div class="text-gray-900">php sync_tmdb_cache.php --force --verbose</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'templates/footer.php'; ?>