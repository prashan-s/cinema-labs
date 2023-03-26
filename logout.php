<?php

require_once 'bootstrap.php';

use App\Services\DatabaseService;
use App\Services\AuthService;

// Initialize services
$config = require 'config/app.php';
$db = new DatabaseService($config);
$auth = new AuthService($db, $config);

// Perform logout
$auth->logout();

// Redirect to home page
header('Location: /');
exit;