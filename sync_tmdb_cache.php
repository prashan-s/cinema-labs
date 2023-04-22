<?php
/**
 * TMDB Cache Sync Script
 * 
 * This script syncs popular content from TMDB API to local cache.
 * Can be run manually or scheduled as a cron job.
 * 
 * Usage:
 * php sync_tmdb_cache.php [--force] [--verbose]
 * 
 * Options:
 * --force   : Force sync even if not needed based on interval
 * --verbose : Enable verbose output
 */

require_once __DIR__ . '/bootstrap.php';

use App\Services\DatabaseService;
use App\Services\TMDBService;

// Parse command line arguments
$options = getopt('', ['force', 'verbose', 'help']);
$verbose = isset($options['verbose']);
$force = isset($options['force']);

if (isset($options['help'])) {
    echo "TMDB Cache Sync Script\n\n";
    echo "Usage: php sync_tmdb_cache.php [--force] [--verbose] [--help]\n\n";
    echo "Options:\n";
    echo "  --force     Force sync even if not needed based on interval\n";
    echo "  --verbose   Enable verbose output\n";
    echo "  --help      Show this help message\n\n";
    exit(0);
}

function log_message($message, $verbose = false) {
    global $options;
    if (!$verbose || isset($options['verbose'])) {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    }
}

try {
    log_message("Starting TMDB cache sync...");

    // Load configuration
    $config = require __DIR__ . '/config/app.php';
    
    // Check if cache is enabled
    if (!$config['tmdb']['cache']['enabled']) {
        log_message("TMDB cache is disabled in configuration. Exiting.");
        exit(0);
    }
    log_message($config['tmdb']['cache']['enabled']);
    // Initialize services
    log_message("Initializing services...", true);
    $db = new DatabaseService($config);
    $tmdb = new TMDBService($config, $db);

    // Validate API token
    log_message("Validating TMDB API token...", true);
    if (!$tmdb->validateToken()) {
        log_message("ERROR: TMDB API token is invalid!");
        exit(1);
    }
    log_message("API token is valid.", true);

    // Get current sync stats
    if ($verbose) {
        log_message("Current sync statistics:", true);
        $stats = $tmdb->getSyncStats();
        if ($stats) {
            foreach ($stats['sync_status'] as $sync) {
                log_message("  {$sync['sync_type']}: {$sync['status']} at {$sync['last_sync_at']} ({$sync['records_processed']} records)", true);
            }
        }
    }

    // Run sync
    log_message("Running cache sync...");
    $startTime = microtime(true);
    
    if ($force) {
        log_message("Force mode enabled - ignoring sync intervals", true);
        
        // Manual sync each component
        log_message("Syncing popular movies...", true);
        $movieResult = $tmdb->syncPopularMovies();
        log_message("Popular movies sync: " . ($movieResult ? "SUCCESS" : "FAILED"));
        
        log_message("Syncing popular TV shows...", true);
        $tvResult = $tmdb->syncPopularTVShows();
        log_message("Popular TV shows sync: " . ($tvResult ? "SUCCESS" : "FAILED"));
        
        log_message("Syncing trending content...", true);
        $trendingResult = $tmdb->syncTrending();
        log_message("Trending content sync: " . ($trendingResult ? "SUCCESS" : "FAILED"));
        
        // Clean expired cache
        log_message("Cleaning expired cache...", true);
        $expired = $tmdb->clearExpiredCache();
        log_message("Cleaned {$expired} expired cache entries");
        
        $results = [
            'popular_movies' => $movieResult,
            'popular_tv_shows' => $tvResult,
            'trending' => $trendingResult,
            'expired_cleaned' => $expired
        ];
    } else {
        $results = $tmdb->runFullSync();
    }

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    // Report results
    log_message("Sync completed in {$duration} seconds");
    foreach ($results as $component => $result) {
        if ($result === true) {
            log_message("✓ {$component}: SUCCESS");
        } elseif ($result === false) {
            log_message("✗ {$component}: FAILED");
        } else {
            log_message("- {$component}: {$result}");
        }
    }

    // Final stats
    if ($verbose) {
        log_message("Final sync statistics:", true);
        $stats = $tmdb->getSyncStats();
        if ($stats) {
            foreach ($stats['cache_stats'] as $cache) {
                log_message("  {$cache['cache_type']}: {$cache['active_count']}/{$cache['count']} active entries", true);
            }
        }
    }

    log_message("TMDB cache sync completed successfully!");
    exit(0);

} catch (Exception $e) {
    log_message("ERROR: " . $e->getMessage());
    log_message("Sync failed!");
    exit(1);
}