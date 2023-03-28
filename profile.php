<?php

require_once 'bootstrap.php';

use App\Services\DatabaseService;
use App\Services\AuthService;

// Initialize services
$config = require 'config/app.php';
$db = new DatabaseService($config);
$auth = new AuthService($db, $config);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
$error = null;
$success = null;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate current password if changing password
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $error = 'Current password is required to change password.';
        } elseif (!$auth->verifyCurrentPassword($user['id'], $currentPassword)) {
            $error = 'Current password is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            // Update password
            $result = $auth->updatePassword($user['id'], $newPassword);
            if ($result['success']) {
                $success = 'Password updated successfully!';
            } else {
                $error = $result['message'];
            }
        }
    }

    // Update email if provided and no password errors
    if (!$error && $email !== $user['email']) {
        $result = $auth->updateEmail($user['id'], $email);
        if ($result['success']) {
            $success = ($success ? $success . ' ' : '') . 'Email updated successfully!';
            $user['email'] = $email; // Update local user data
        } else {
            $error = $result['message'];
        }
    }
}

// Get user statistics
$reviewCount = $db->fetchOne("SELECT COUNT(*) as review_count FROM reviews WHERE user_id = ?", [$user['id']])['review_count'] ?? 0;
$favoriteCount = $db->fetchOne("SELECT COUNT(*) as favorite_count FROM favorites WHERE user_id = ?", [$user['id']])['favorite_count'] ?? 0;

$pageTitle = 'Profile - Movie Reviews';
include 'templates/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Profile Header -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-8">
                <div class="flex items-center">
                    <div class="bg-white rounded-full p-4">
                        <svg class="h-16 w-16 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-6 text-white">
                        <h1 class="text-3xl font-bold"><?= htmlspecialchars($user['username']) ?></h1>
                        <p class="text-blue-100"><?= htmlspecialchars($user['email']) ?></p>
                        <p class="text-sm text-blue-200 mt-1">
                            Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?= $reviewCount ?></div>
                        <div class="text-sm text-gray-600">Reviews Written</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?= $favoriteCount ?></div>
                        <div class="text-sm text-gray-600">Favorites</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">
                            <?= number_format(($reviewCount + $favoriteCount) * 10) ?>
                        </div>
                        <div class="text-sm text-gray-600">Points Earned</div>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="px-6 py-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Update Profile</h2>

                <!-- Error/Success Messages -->
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Username (readonly) -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" 
                                   id="username" 
                                   value="<?= htmlspecialchars($user['username']) ?>"
                                   readonly
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-500">
                            <p class="mt-1 text-xs text-gray-500">Username cannot be changed</p>
                        </div>
                    </div>

                    <!-- Password Change Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Leave password fields empty to keep current password
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8 bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Recent Activity</h2>
            </div>
            <div class="px-6 py-4">
                <?php
                // Get recent reviews
                $recentReviews = $db->fetchAll("
                    SELECT r.*, 
                           COALESCE(m.title, t.name) as title, 
                           COALESCE(m.poster_path, t.poster_path) as poster_path,
                           CASE WHEN m.id IS NOT NULL THEN 'movie' ELSE 'tv' END as type,
                           COALESCE(m.id, t.id) as content_id
                    FROM reviews r 
                    LEFT JOIN movies m ON r.movie_id = m.id 
                    LEFT JOIN tv_shows t ON r.tv_id = t.id
                    WHERE r.user_id = ? 
                    ORDER BY r.created_at DESC 
                    LIMIT 5
                ", [$user['id']]);
                ?>

                <?php if (empty($recentReviews)): ?>
                    <p class="text-gray-500 text-center py-8">No recent activity. Start by reviewing some movies!</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recentReviews as $review): ?>
                            <div class="flex items-start space-x-4 p-4 border border-gray-200 rounded-lg">
                                <a href="details.php?type=<?= $review['type'] ?>&id=<?= $review['content_id'] ?>" class="flex-shrink-0">
                                    <?php if ($review['poster_path']): ?>
                                        <img src="https://image.tmdb.org/t/p/w92<?= htmlspecialchars($review['poster_path']) ?>" 
                                             alt="<?= htmlspecialchars($review['title']) ?>"
                                             class="w-16 h-24 object-cover rounded hover:opacity-80 transition-opacity">
                                    <?php else: ?>
                                        <div class="w-16 h-24 bg-gray-200 rounded flex items-center justify-center hover:bg-gray-300 transition-colors">
                                            <span class="text-gray-400 text-xs">No Image</span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                
                                <div class="flex-1">
                                    <a href="details.php?type=<?= $review['type'] ?>&id=<?= $review['content_id'] ?>" class="hover:text-blue-600">
                                        <h3 class="font-medium text-gray-900"><?= htmlspecialchars($review['title']) ?></h3>
                                    </a>
                                    <div class="flex items-center mt-1">
                                        <div class="flex text-yellow-400">
                                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <svg class="w-4 h-4 <?= $i <= $review['rating'] ? 'fill-current' : 'text-gray-300' ?>" 
                                                     viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="ml-2 text-sm text-gray-500">
                                            <?= $review['rating'] ?>/10 • <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 mt-2 line-clamp-2">
                                        <?php if (isVulnerable('stored_xss')): ?>
                                            <?= $review['content'] ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($review['content']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>