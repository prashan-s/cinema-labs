<?php
// templates/detail.php
// This template expects a $content variable containing the show/movie data

if (!isset($content) || !$content) {
    include 'templates/404.php';
    return;
}
?>

<div class="detail-page">
    <!-- Hero Section with Backdrop -->
    <div class="detail-hero" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(18,18,18,0.8)), url('<?= htmlspecialchars($content['image_url']) ?>');">
        <div class="detail-hero-content">
            <div class="detail-poster">
                <img src="<?= htmlspecialchars($content['poster_url']) ?>" alt="<?= htmlspecialchars($content['title']) ?> Poster">
            </div>
            <div class="detail-info">
                <h1 class="detail-title"><?= htmlspecialchars($content['title']) ?></h1>
                
                <div class="detail-meta">
                    <span class="detail-year"><?= htmlspecialchars($content['year']) ?></span>
                    <span class="detail-genre"><?= htmlspecialchars($content['genre']) ?></span>
                    <span class="detail-duration"><?= htmlspecialchars($content['duration']) ?></span>
                    <span class="detail-rating">★ <?= htmlspecialchars($content['rating']) ?></span>
                </div>
                
                <p class="detail-description"><?= htmlspecialchars($content['description']) ?></p>
                
                <div class="detail-actions">
                    <button class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="white">
                            <path d="M320-200v-560l440 280-440 280Z"/>
                        </svg>
                        Play
                    </button>
                    <button class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="white">
                            <path d="M200-120v-680h160v680H200Zm400 0v-680h160v680H600Z"/>
                        </svg>
                        Add to List
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Details -->
    <div class="detail-content">
        <div class="detail-section">
            <h3>Cast</h3>
            <div class="cast-list">
                <?php foreach ($content['cast'] as $actor): ?>
                    <span class="cast-member"><?= htmlspecialchars($actor) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="detail-section">
            <h3><?= $content['type'] === 'movie' ? 'Director' : 'Creator' ?></h3>
            <p class="director-name">
                <?= htmlspecialchars($content['type'] === 'movie' ? $content['director'] : $content['creator']) ?>
            </p>
        </div>
        
        <?php if ($content['type'] === 'movie'): ?>
            <div class="detail-section">
                <h3>Type</h3>
                <p>Feature Film</p>
            </div>
        <?php else: ?>
            <div class="detail-section">
                <h3>Type</h3>
                <p>TV Series</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Back Button -->
    <div class="detail-navigation">
        <a href="/" class="btn btn-back">
            <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="white">
                <path d="M400-240 160-480l240-240 56 56-184 184 184 184-56 56Z"/>
            </svg>
            Back to Home
        </a>
    </div>
</div>