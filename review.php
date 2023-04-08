<?php
require_once 'bootstrap.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new App\Services\DatabaseService($config);
$auth = new App\Services\AuthService($db, $config);

// CSRF Protection
if (!isVulnerable('csrf')) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'add':
        handleAddReview($db, $user_id);
        break;
    case 'edit':
        handleEditReview($db, $user_id);
        break;
    case 'delete':
        handleDeleteReview($db, $user_id);
        break;
    default:
        $_SESSION['error'] = 'Invalid action';
        header('Location: index.php');
        exit;
}

function handleAddReview($db, $user_id) {
    $required = ['type', 'id', 'rating', 'content'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error'] = 'All fields are required';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            return;
        }
    }

    $type = $_POST['type']; // 'movie' or 'tv'
    $tmdb_id = (int)$_POST['id'];
    $rating = (int)$_POST['rating'];
    $content = trim($_POST['content']);

    // Validate rating
    if ($rating < 1 || $rating > 10) {
        $_SESSION['error'] = 'Rating must be between 1 and 10';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        return;
    }

    // Check if user already reviewed this item
    $column = $type === 'tv' ? 'tv_id' : 'movie_id';
    $existing = $db->fetchOne("SELECT id FROM reviews WHERE user_id = ? AND $column = ?", [$user_id, $tmdb_id]);
    
    if ($existing) {
        $_SESSION['error'] = 'You have already reviewed this ' . $type;
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        return;
    }

    try {
        // Insert review
        if ($type === 'tv') {
            if (isVulnerable('sql_injection')) {
                // VULNERABLE: Direct SQL injection
                $escapedContent = addslashes($content); // Basic escaping only
                $sql = "INSERT INTO reviews (user_id, tv_id, rating, content, created_at) VALUES ($user_id, $tmdb_id, $rating, '$escapedContent', NOW())";
                $db->executeVulnerable($sql);
            } else {
                // SECURE: Use prepared statements
                $db->execute(
                    "INSERT INTO reviews (user_id, tv_id, rating, content, created_at) VALUES (?, ?, ?, ?, NOW())",
                    [$user_id, $tmdb_id, $rating, $content]
                );
            }
        } else {
            if (isVulnerable('sql_injection')) {
                // VULNERABLE: Direct SQL injection
                $escapedContent = addslashes($content); // Basic escaping only
                $sql = "INSERT INTO reviews (user_id, movie_id, rating, content, created_at) VALUES ($user_id, $tmdb_id, $rating, '$escapedContent', NOW())";
                $db->executeVulnerable($sql);
            } else {
                // SECURE: Use prepared statements
                $db->execute(
                    "INSERT INTO reviews (user_id, movie_id, rating, content, created_at) VALUES (?, ?, ?, ?, NOW())",
                    [$user_id, $tmdb_id, $rating, $content]
                );
            }
        }

        $_SESSION['success'] = 'Review added successfully!';
    } catch (Exception $e) {
        error_log("Review addition failed: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to add review. Please try again.';
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
}

function handleEditReview($db, $user_id) {
    $review_id = (int)($_POST['review_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if (!$review_id || !$rating || !$content) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        return;
    }

    // Validate rating
    if ($rating < 1 || $rating > 10) {
        $_SESSION['error'] = 'Rating must be between 1 and 10';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        return;
    }

    // Verify ownership
    $review = $db->fetchOne("SELECT id FROM reviews WHERE id = ? AND user_id = ?", [$review_id, $user_id]);
    if (!$review) {
        $_SESSION['error'] = 'Review not found or you do not have permission to edit it';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        return;
    }

    try {
        if (isVulnerable('sql_injection')) {
            // VULNERABLE: Direct SQL injection
            $escapedContent = addslashes($content);
            $sql = "UPDATE reviews SET rating = $rating, content = '$escapedContent', updated_at = NOW() WHERE id = $review_id AND user_id = $user_id";
            $db->executeVulnerable($sql);
        } else {
            // SECURE: Use prepared statements
            $db->execute(
                "UPDATE reviews SET rating = ?, content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?",
                [$rating, $content, $review_id, $user_id]
            );
        }

        $_SESSION['success'] = 'Review updated successfully!';
    } catch (Exception $e) {
        error_log("Review update failed: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to update review. Please try again.';
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
}

function handleDeleteReview($db, $user_id) {
    $review_id = (int)($_POST['review_id'] ?? 0);

    if (!$review_id) {
        $_SESSION['error'] = 'Invalid review ID';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        return;
    }

    // Verify ownership
    $review = $db->fetchOne("SELECT id FROM reviews WHERE id = ? AND user_id = ?", [$review_id, $user_id]);
    if (!$review) {
        $_SESSION['error'] = 'Review not found or you do not have permission to delete it';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        return;
    }

    try {
        if (isVulnerable('sql_injection')) {
            // VULNERABLE: Direct SQL injection
            $sql = "DELETE FROM reviews WHERE id = $review_id AND user_id = $user_id";
            $db->executeVulnerable($sql);
        } else {
            // SECURE: Use prepared statements
            $db->execute("DELETE FROM reviews WHERE id = ? AND user_id = ?", [$review_id, $user_id]);
        }
        $_SESSION['success'] = 'Review deleted successfully!';
    } catch (Exception $e) {
        error_log("Review deletion failed: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to delete review. Please try again.';
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
}
?>