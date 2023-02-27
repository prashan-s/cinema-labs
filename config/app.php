<?php
return [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3307',
        'database' => $_ENV['DB_DATABASE'] ?? 'cinemalabs',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? 'root',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    'app' => [
        'name' => 'Movie Reviews - Cinema Discovery Platform',
        'debug' => $_ENV['APP_DEBUG'] ?? true,
        'timezone' => 'UTC',
        'url' => 'http://localhost:8000',
    ],
    
    // TMDB API Configuration
    'tmdb' => [
        'api_key' => $_ENV['TMDB_API_KEY'] ?? '',
        'bearer_token' => $_ENV['TMDB_BEARER_TOKEN'] ?? 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYjIyOWY1ZGVjMGIxY2UyMDFkNTJjYjcyOTUwYWQxZCIsIm5iZiI6MTc1ODM1NjA5NS43NDEsInN1YiI6IjY4Y2U2MjdmYTZjOWI4ODIxYzc0OTBiOCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.p3RE4AmuoNKSYB2Ucckb4oaFC629UYhwArui5zkqMt0',
        'base_url' => 'https://api.themoviedb.org/3',
        'image_base_url' => 'https://image.tmdb.org/t/p/w500',
        'use_cookie_storage' => true,
        'cookie_name' => 'tmdb_token',
        'cache' => [
            'enabled' => $_ENV['TMDB_CACHE_ENABLED'] ?? true,
            'sync_interval_hours' => $_ENV['TMDB_SYNC_INTERVAL_HOURS'] ?? 24,
            'popular_content_cache_hours' => $_ENV['TMDB_POPULAR_CACHE_HOURS'] ?? 6,
            'search_cache_hours' => $_ENV['TMDB_SEARCH_CACHE_HOURS'] ?? 2,
            'details_cache_hours' => $_ENV['TMDB_DETAILS_CACHE_HOURS'] ?? 168, // 7 days
            'trending_cache_hours' => $_ENV['TMDB_TRENDING_CACHE_HOURS'] ?? 1
        ]
    ],
    
    // Security Configuration
    'security' => [
        'session_lifetime' => 3600, // 1 hour
        'csrf_token_name' => 'csrf_token',
        'max_login_attempts' => 5,
        'lockout_duration' => 300, // 5 minutes
        'password_min_length' => 8,
    ],
    
    // Vulnerability Flags
    'vulnerabilities' => [
        'auth_failures' => $_ENV['VULN_AUTH_FAILURES'] ?? true,
        'reflected_xss' => $_ENV['VULN_REFLECTED_XSS'] ?? true,
        'stored_xss' => $_ENV['VULN_STORED_XSS'] ?? true,
        'csrf' => $_ENV['VULN_CSRF'] ?? true,
        'command_injection' => $_ENV['VULN_COMMAND_INJECTION'] ?? true,
        'sql_injection' => $_ENV['VULN_SQL_INJECTION'] ?? true,
        'insecure_design' => $_ENV['VULN_INSECURE_DESIGN'] ?? true,
    ],
    
    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'log_file' => __DIR__ . '/../logs/app.log',
        'log_level' => 'info',
        'audit_actions' => true,
    ]
];