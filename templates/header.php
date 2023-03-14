<?php // templates/header.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Shows - Movie Reviews') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Enhanced dropdown styling */
        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
            padding-right: 40px !important;
            min-height: 42px;
            background-color: white !important;
            border: 2px solid #d1d5db !important;
            color: #374151 !important;
            font-size: 14px;
            min-width: 120px;
        }
        
        select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            outline: none !important;
        }
        
        select option {
            background-color: white !important;
            color: #374151 !important;
            padding: 8px 12px;
            font-size: 14px;
        }
        
        select option:hover {
            background-color: #f3f4f6 !important;
        }
        
        select option:checked {
            background-color: #3b82f6 !important;
            color: white !important;
        }
    </style>
</head>
<body class="bg-gray-50">

    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-blue-600">Shows</a>
                </div>
                
                <nav class="flex items-center space-x-6">
                    <a href="/" class="text-gray-700 hover:text-blue-600">Home</a>
                    <a href="search.php" class="text-gray-700 hover:text-blue-600">Search</a>
                    <a href="favorites.php" class="text-gray-700 hover:text-blue-600">Favorites</a>
                    
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['username'])): ?>
                        <!-- User is logged in -->
                        <div class="flex items-center space-x-4">
                            <div class="text-sm text-gray-600">
                                Welcome, <span class="font-medium text-gray-900"><?= htmlspecialchars($_SESSION['username']) ?></span>
                            </div>
                            <a href="profile.php" class="text-gray-700 hover:text-blue-600">Profile</a>
                            <a href="logout.php" 
                               class="text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-md text-sm"
                               onclick="return confirm('Are you sure you want to logout?')">
                                Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- User is not logged in -->
                        <div class="flex items-center space-x-3">
                            <a href="login.php" class="text-gray-700 hover:text-blue-600">Login</a>
                            <a href="register.php" 
                               class="text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md text-sm font-medium">
                                Sign Up
                            </a>
                        </div>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>