<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TMDBService
{
    private $client;
    private $config;
    private $baseUrl;
    private $imageBaseUrl;
    private $cookieName;
    private $db;

    public function __construct($config, $db = null)
    {
        $this->config = $config;
        $this->client = new Client();
        $this->baseUrl = $config['tmdb']['base_url'];
        $this->imageBaseUrl = $config['tmdb']['image_base_url'];
        $this->cookieName = $config['tmdb']['cookie_name'];
        $this->db = $db;
        
        // Set bearer token in cookie if not already set
        $this->initializeTokenCookie();
    }

    /**
     * Initialize bearer token in cookie
     */
    private function initializeTokenCookie()
    {
        if (!isset($_COOKIE[$this->cookieName]) && !empty($this->config['tmdb']['bearer_token'])) {
            setcookie(
                $this->cookieName,
                $this->config['tmdb']['bearer_token'],
                [
                    'expires' => time() + (365 * 24 * 60 * 60), // 1 year
                    'path' => '/',
                    'secure' => false, // Set to true in production with HTTPS
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
            $_COOKIE[$this->cookieName] = $this->config['tmdb']['bearer_token'];
        }
    }

    /**
     * Get bearer token from cookie
     */
    private function getBearerToken()
    {
        return $_COOKIE[$this->cookieName] ?? $this->config['tmdb']['bearer_token'];
    }

    /**
     * Generate cache key for API requests
     */
    private function generateCacheKey($endpoint, $params = [])
    {
        ksort($params); // Sort params for consistent keys
        return md5($endpoint . '?' . http_build_query($params));
    }

    /**
     * Check if cache is enabled
     */
    public function isCacheEnabled()
    {
        return $this->config['tmdb']['cache']['enabled'] && $this->db !== null;
    }

    /**
     * Get data from cache
     */
    private function getFromCache($cacheKey, $cacheType)
    {
        if (!$this->isCacheEnabled()) {
            return null;
        }

        try {
            $stmt = $this->db->getPdo()->prepare(
                "SELECT data FROM tmdb_cache WHERE cache_key = ? AND cache_type = ? AND expires_at > NOW()"
            );
            $stmt->execute([$cacheKey, $cacheType]);
            $result = $stmt->fetch();

            if ($result) {
                return json_decode($result['data'], true);
            }
        } catch (\Exception $e) {
            error_log("Cache read error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Store data in cache
     */
    private function storeInCache($cacheKey, $cacheType, $data, $hoursToCache = 24)
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        try {
            $expiresAt = new \DateTime();
            $expiresAt->add(new \DateInterval('PT' . $hoursToCache . 'H'));

            $stmt = $this->db->getPdo()->prepare(
                "INSERT INTO tmdb_cache (cache_key, cache_type, data, expires_at) 
                 VALUES (?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE 
                 data = VALUES(data), 
                 expires_at = VALUES(expires_at), 
                 updated_at = CURRENT_TIMESTAMP"
            );
            
            $stmt->execute([
                $cacheKey,
                $cacheType,
                json_encode($data),
                $expiresAt->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Cache write error: " . $e->getMessage());
        }
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpiredCache()
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        try {
            $stmt = $this->db->getPdo()->prepare("DELETE FROM tmdb_cache WHERE expires_at <= NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            error_log("Cache cleanup error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Make API request to TMDB
     */
    private function makeRequest($endpoint, $params = [])
    {
        try {
            $url = $this->baseUrl . $endpoint;
            
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getBearerToken(),
                    'accept' => 'application/json',
                ],
                'query' => $params
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            error_log("TMDB API Error: " . $e->getMessage());
            return ['error' => 'Failed to fetch data from TMDB API'];
        }
    }

    /**
     * Make cached API request to TMDB
     */
    private function makeCachedRequest($endpoint, $params = [], $cacheType = 'general', $cacheHours = null)
    {
        // Generate cache key
        $cacheKey = $this->generateCacheKey($endpoint, $params);
        
        // Try to get from cache first
        $cachedData = $this->getFromCache($cacheKey, $cacheType);
        if ($cachedData !== null) {
            return $cachedData;
        }

        // If not in cache or cache disabled, make API request
        $data = $this->makeRequest($endpoint, $params);
        
        // Store in cache if successful
        if (!isset($data['error']) && $this->isCacheEnabled()) {
            if ($cacheHours === null) {
                // Use default cache hours from config based on cache type
                $cacheHours = $this->getCacheHoursForType($cacheType);
            }
            $this->storeInCache($cacheKey, $cacheType, $data, $cacheHours);
        }

        return $data;
    }

    /**
     * Get cache hours for specific cache type
     */
    private function getCacheHoursForType($cacheType)
    {
        $mapping = [
            'discover_movies' => $this->config['tmdb']['cache']['popular_content_cache_hours'],
            'discover_tv' => $this->config['tmdb']['cache']['popular_content_cache_hours'],
            'search_movies' => $this->config['tmdb']['cache']['search_cache_hours'],
            'search_tv' => $this->config['tmdb']['cache']['search_cache_hours'],
            'movie_details' => $this->config['tmdb']['cache']['details_cache_hours'],
            'tv_details' => $this->config['tmdb']['cache']['details_cache_hours'],
            'trending' => $this->config['tmdb']['cache']['trending_cache_hours'],
        ];

        return $mapping[$cacheType] ?? 24; // Default to 24 hours
    }

    /**
     * Discover popular movies
     */
    public function discoverMovies($page = 1, $params = [])
    {
        $defaultParams = [
            'include_adult' => false,
            'include_video' => false,
            'language' => 'en-US',
            'page' => $page,
            'sort_by' => 'popularity.desc'
        ];

        $queryParams = array_merge($defaultParams, $params);
        return $this->makeCachedRequest('/discover/movie', $queryParams, 'discover_movies');
    }

    /**
     * Discover popular TV shows
     */
    public function discoverTVShows($page = 1, $params = [])
    {
        $defaultParams = [
            'include_adult' => false,
            'include_null_first_air_dates' => false,
            'language' => 'en-US',
            'page' => $page,
            'sort_by' => 'popularity.desc'
        ];

        $queryParams = array_merge($defaultParams, $params);
        return $this->makeCachedRequest('/discover/tv', $queryParams, 'discover_tv');
    }

    /**
     * Search for movies
     */
    public function searchMovies($query, $page = 1)
    {
        return $this->makeCachedRequest('/search/movie', [
            'query' => $query,
            'include_adult' => false,
            'language' => 'en-US',
            'page' => $page
        ], 'search_movies');
    }

    /**
     * Search for TV shows
     */
    public function searchTVShows($query, $page = 1)
    {
        return $this->makeCachedRequest('/search/tv', [
            'query' => $query,
            'include_adult' => false,
            'language' => 'en-US',
            'page' => $page
        ], 'search_tv');
    }

    /**
     * Get movie details
     */
    public function getMovieDetails($movieId)
    {
        return $this->makeCachedRequest("/movie/{$movieId}", [
            'language' => 'en-US'
        ], 'movie_details');
    }

    /**
     * Get TV show details
     */
    public function getTVShowDetails($tvId)
    {
        return $this->makeCachedRequest("/tv/{$tvId}", [
            'language' => 'en-US'
        ], 'tv_details');
    }

    /**
     * Get trending movies/TV shows
     */
    public function getTrending($mediaType = 'movie', $timeWindow = 'day')
    {
        return $this->makeCachedRequest("/trending/{$mediaType}/{$timeWindow}", [], 'trending');
    }

    /**
     * Get full image URL
     */
    public function getImageUrl($imagePath, $size = 'w500')
    {
        if (empty($imagePath)) {
            return '/assets/images/no-poster.png'; // Fallback image
        }
        return "https://image.tmdb.org/t/p/{$size}{$imagePath}";
    }

    /**
     * Update bearer token in cookie
     */
    public function updateBearerToken($newToken)
    {
        setcookie(
            $this->cookieName,
            $newToken,
            [
                'expires' => time() + (365 * 24 * 60 * 60), // 1 year
                'path' => '/',
                'secure' => false, // Set to true in production with HTTPS
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
        $_COOKIE[$this->cookieName] = $newToken;
    }

    /**
     * Clear bearer token cookie
     */
    public function clearBearerToken()
    {
        setcookie($this->cookieName, '', [
            'expires' => time() - 3600,
            'path' => '/'
        ]);
        unset($_COOKIE[$this->cookieName]);
    }

    /**
     * Check if API token is valid
     */
    public function validateToken()
    {
        $result = $this->makeRequest('/authentication');
        return !isset($result['error']);
    }

    /**
     * Update sync status
     */
    private function updateSyncStatus($syncType, $status, $recordsProcessed = 0, $errorMessage = null)
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        try {
            $stmt = $this->db->getPdo()->prepare(
                "INSERT INTO tmdb_sync_status (sync_type, last_sync_at, status, records_processed, error_message) 
                 VALUES (?, NOW(), ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE 
                 last_sync_at = NOW(), 
                 status = VALUES(status), 
                 records_processed = VALUES(records_processed), 
                 error_message = VALUES(error_message), 
                 updated_at = CURRENT_TIMESTAMP"
            );
            
            $stmt->execute([$syncType, $status, $recordsProcessed, $errorMessage]);
        } catch (\Exception $e) {
            error_log("Sync status update error: " . $e->getMessage());
        }
    }

    /**
     * Check if sync is needed based on last sync time and interval
     */
    public function isSyncNeeded($syncType)
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }

        try {
            $stmt = $this->db->getPdo()->prepare(
                "SELECT last_sync_at FROM tmdb_sync_status WHERE sync_type = ? AND status = 'completed'"
            );
            $stmt->execute([$syncType]);
            $result = $stmt->fetch();

            if (!$result) {
                return true; // No sync record, needs sync
            }

            $lastSync = new \DateTime($result['last_sync_at']);
            $now = new \DateTime();
            $syncInterval = $this->config['tmdb']['cache']['sync_interval_hours'];
            
            $timeDiff = $now->getTimestamp() - $lastSync->getTimestamp();
            $hoursSinceSync = $timeDiff / 3600;

            return $hoursSinceSync >= $syncInterval;
        } catch (\Exception $e) {
            error_log("Sync check error: " . $e->getMessage());
            return true; // Default to sync needed if error
        }
    }

    /**
     * Sync popular movies to cache
     */
    public function syncPopularMovies($maxPages = 5)
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }

        $syncType = 'popular_movies';
        $this->updateSyncStatus($syncType, 'running');

        try {
            $totalProcessed = 0;
            
            for ($page = 1; $page <= $maxPages; $page++) {
                // Force fresh API call (bypass cache)
                $data = $this->makeRequest('/discover/movie', [
                    'include_adult' => false,
                    'include_video' => false,
                    'language' => 'en-US',
                    'page' => $page,
                    'sort_by' => 'popularity.desc'
                ]);

                if (isset($data['error'])) {
                    throw new \Exception("API Error: " . $data['error']);
                }

                // Cache this page
                $cacheKey = $this->generateCacheKey('/discover/movie', [
                    'include_adult' => false,
                    'include_video' => false,
                    'language' => 'en-US',
                    'page' => $page,
                    'sort_by' => 'popularity.desc'
                ]);

                $this->storeInCache($cacheKey, 'discover_movies', $data, $this->config['tmdb']['cache']['popular_content_cache_hours']);
                
                $totalProcessed += count($data['results'] ?? []);

                // Add delay to respect API rate limits
                usleep(100000); // 100ms delay
            }

            $this->updateSyncStatus($syncType, 'completed', $totalProcessed);
            return true;
        } catch (\Exception $e) {
            error_log("Popular movies sync error: " . $e->getMessage());
            $this->updateSyncStatus($syncType, 'failed', 0, $e->getMessage());
            return false;
        }
    }

    /**
     * Sync popular TV shows to cache
     */
    public function syncPopularTVShows($maxPages = 5)
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }

        $syncType = 'popular_tv_shows';
        $this->updateSyncStatus($syncType, 'running');

        try {
            $totalProcessed = 0;
            
            for ($page = 1; $page <= $maxPages; $page++) {
                // Force fresh API call (bypass cache)
                $data = $this->makeRequest('/discover/tv', [
                    'include_adult' => false,
                    'include_null_first_air_dates' => false,
                    'language' => 'en-US',
                    'page' => $page,
                    'sort_by' => 'popularity.desc'
                ]);

                if (isset($data['error'])) {
                    throw new \Exception("API Error: " . $data['error']);
                }

                // Cache this page
                $cacheKey = $this->generateCacheKey('/discover/tv', [
                    'include_adult' => false,
                    'include_null_first_air_dates' => false,
                    'language' => 'en-US',
                    'page' => $page,
                    'sort_by' => 'popularity.desc'
                ]);

                $this->storeInCache($cacheKey, 'discover_tv', $data, $this->config['tmdb']['cache']['popular_content_cache_hours']);
                
                $totalProcessed += count($data['results'] ?? []);

                // Add delay to respect API rate limits
                usleep(100000); // 100ms delay
            }

            $this->updateSyncStatus($syncType, 'completed', $totalProcessed);
            return true;
        } catch (\Exception $e) {
            error_log("Popular TV shows sync error: " . $e->getMessage());
            $this->updateSyncStatus($syncType, 'failed', 0, $e->getMessage());
            return false;
        }
    }

    /**
     * Sync trending content to cache
     */
    public function syncTrending()
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }

        $syncType = 'trending';
        $this->updateSyncStatus($syncType, 'running');

        try {
            $totalProcessed = 0;
            $mediaTypes = ['movie', 'tv'];
            $timeWindows = ['day', 'week'];

            foreach ($mediaTypes as $mediaType) {
                foreach ($timeWindows as $timeWindow) {
                    // Force fresh API call (bypass cache)
                    $data = $this->makeRequest("/trending/{$mediaType}/{$timeWindow}");

                    if (isset($data['error'])) {
                        throw new \Exception("API Error: " . $data['error']);
                    }

                    // Cache this data
                    $cacheKey = $this->generateCacheKey("/trending/{$mediaType}/{$timeWindow}", []);
                    $this->storeInCache($cacheKey, 'trending', $data, $this->config['tmdb']['cache']['trending_cache_hours']);
                    
                    $totalProcessed += count($data['results'] ?? []);

                    // Add delay to respect API rate limits
                    usleep(100000); // 100ms delay
                }
            }

            $this->updateSyncStatus($syncType, 'completed', $totalProcessed);
            return true;
        } catch (\Exception $e) {
            error_log("Trending sync error: " . $e->getMessage());
            $this->updateSyncStatus($syncType, 'failed', 0, $e->getMessage());
            return false;
        }
    }

    /**
     * Run full sync process
     */
    public function runFullSync()
    {
        $results = [];
        
        // Clean up expired cache first
        $expired = $this->clearExpiredCache();
        $results['expired_cleaned'] = $expired;

        // Sync popular movies
        if ($this->isSyncNeeded('popular_movies')) {
            $results['popular_movies'] = $this->syncPopularMovies();
        } else {
            $results['popular_movies'] = 'skipped - not needed';
        }

        // Sync popular TV shows
        if ($this->isSyncNeeded('popular_tv_shows')) {
            $results['popular_tv_shows'] = $this->syncPopularTVShows();
        } else {
            $results['popular_tv_shows'] = 'skipped - not needed';
        }

        // Sync trending content
        if ($this->isSyncNeeded('trending')) {
            $results['trending'] = $this->syncTrending();
        } else {
            $results['trending'] = 'skipped - not needed';
        }

        return $results;
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats()
    {
        if (!$this->isCacheEnabled()) {
            return null;
        }

        try {
            // Get sync status
            $stmt = $this->db->getPdo()->prepare(
                "SELECT sync_type, last_sync_at, status, records_processed, error_message 
                 FROM tmdb_sync_status 
                 ORDER BY last_sync_at DESC"
            );
            $stmt->execute();
            $syncStatus = $stmt->fetchAll();

            // Get cache statistics
            $stmt = $this->db->getPdo()->prepare(
                "SELECT cache_type, COUNT(*) as count, 
                 SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as active_count,
                 MIN(created_at) as oldest_entry,
                 MAX(updated_at) as newest_entry
                 FROM tmdb_cache 
                 GROUP BY cache_type"
            );
            $stmt->execute();
            $cacheStats = $stmt->fetchAll();

            return [
                'sync_status' => $syncStatus,
                'cache_stats' => $cacheStats
            ];
        } catch (\Exception $e) {
            error_log("Sync stats error: " . $e->getMessage());
            return null;
        }
    }
}