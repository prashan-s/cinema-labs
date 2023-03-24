<?php

require_once 'bootstrap.php';

use App\Services\DatabaseService;
use App\Services\AuthService;

// Initialize services
$config = require 'config/app.php';
$db = new DatabaseService($config);
$auth = new AuthService($db, $config);

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = null;
$success = null;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $result = $auth->register($username, $email, $password, $confirmPassword);
    
    if ($result['success']) {
        $success = 'Registration successful! You can now log in.';
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Register - Movie Reviews';
include 'templates/header.php';
?>

<main class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Create your account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Join our movie community
            </p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?= htmlspecialchars($success) ?>
                <div class="mt-2">
                    <a href="login.php" class="font-medium text-green-700 hover:text-green-600">
                        Click here to login →
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form class="mt-8 space-y-6" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input id="username" 
                           name="username" 
                           type="text" 
                           required 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                           placeholder="Choose a username">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                           placeholder="your@email.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           required 
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                           placeholder="Password">
                    <?php if (!isVulnerable('auth_failures')): ?>
                        <p class="mt-1 text-xs text-gray-500">
                            Must be at least <?= config('security.password_min_length', 8) ?> characters long
                        </p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input id="confirm_password" 
                           name="confirm_password" 
                           type="password" 
                           required 
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                           placeholder="Confirm password">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-green-500 group-hover:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                        </svg>
                    </span>
                    Create Account
                </button>
            </div>

            <div class="text-center">
                <span class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Sign in
                    </a>
                </span>
            </div>
        </form>
    </div>
</main>

<?php include 'templates/footer.php'; ?>