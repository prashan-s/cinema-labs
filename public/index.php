<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Load configuration and data
$config = require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../data.php';

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove query parameters and normalize path
$path = rtrim($path, '/') ?: '/';

// Basic routing
switch ($path) {
    case '/':
        $pageTitle = 'Home - Shows Management';
        $content = 'Welcome to Shows Management System';
        break;
    case '/shows':
        $pageTitle = 'Shows - Shows Management';
        $content = 'Shows List';
        break;
    case '/add-show':
        $pageTitle = 'Add Show - Shows Management';
        $content = 'Add New Show';
        break;
    case '/trending':
        $pageTitle = 'Trending - Shows Management';
        $content = 'Trending Content';
        break;
    case '/movies':
        $pageTitle = 'Movies - Shows Management';
        $content = 'Trending Movies';
        break;
    case '/tv-shows':
        $pageTitle = 'TV Shows - Shows Management';
        $content = 'Trending TV Shows';
        break;
    default:
        http_response_code(404);
        $pageTitle = '404 - Page Not Found';
        $content = 'Page not found';
        break;
}

// Include the main template
include __DIR__ . '/../templates/layout.php';