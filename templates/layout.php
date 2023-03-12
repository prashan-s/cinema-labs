<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Shows Management') ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="nav-logo">Shows Management</h1>
                <ul class="nav-menu">
                    <li><a href="/" class="nav-link">Home</a></li>
                    <li><a href="/shows" class="nav-link">My Shows</a></li>
                    <li><a href="/trending" class="nav-link">Trending</a></li>
                    <li><a href="/movies" class="nav-link">Movies</a></li>
                    <li><a href="/tv-shows" class="nav-link">TV Shows</a></li>
                    <li><a href="/add-show" class="nav-link">Add Show</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="container">
            <?php if (isset($content)): ?>
                <?php if ($path === '/'): ?>
                    <!-- Hero Carousel Section -->
                    <section class="hero-carousel">
                        <div class="carousel-container">
                            <div class="carousel-track" id="carousel-track">
                                <?php foreach ($carousel_slides as $index => $slide): ?>
                                    <div class="carousel-slide <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="<?= htmlspecialchars($slide['image_url']) ?>" alt="<?= htmlspecialchars($slide['title']) ?>" loading="lazy">
                                        <div class="carousel-content">
                                            <h2><?= htmlspecialchars($slide['title']) ?></h2>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-btn carousel-prev" id="carousel-prev">❮</button>
                            <button class="carousel-btn carousel-next" id="carousel-next">❯</button>
                            <div class="carousel-indicators">
                                <?php foreach ($carousel_slides as $index => $slide): ?>
                                    <button class="indicator <?= $index === 0 ? 'active' : '' ?>" data-slide="<?= $index ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <!-- Quick Actions -->
                    <section class="quick-actions">
                        <h2>Quick Access</h2>
                        <div class="action-grid">
                            <a href="/shows" class="action-card">
                                <h3>My Shows</h3>
                                <p>Manage your personal collection</p>
                            </a>
                            <a href="/trending" class="action-card">
                                <h3>Trending</h3>
                                <p>Discover what's popular</p>
                            </a>
                            <a href="/add-show" class="action-card">
                                <h3>Add New</h3>
                                <p>Add shows to your collection</p>
                            </a>
                        </div>
                    </section>
                <?php elseif ($path === '/trending'): ?>
                    <section class="trending-section">
                        <h2>Trending Content</h2>
                        
                        <div class="trending-category">
                            <h3>Popular Movies</h3>
                            <div class="trending-grid">
                                <?php foreach (array_slice($trending_movies, 0, 6) as $movie): ?>
                                    <div class="trending-card">
                                        <img src="<?= htmlspecialchars($movie['image_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" loading="lazy">
                                        <div class="trending-info">
                                            <h4><?= htmlspecialchars($movie['title']) ?></h4>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="/movies" class="btn btn-secondary">View All Movies</a>
                        </div>

                        <div class="trending-category">
                            <h3>Popular TV Shows</h3>
                            <div class="trending-grid">
                                <?php foreach ($trending_shows as $show): ?>
                                    <div class="trending-card">
                                        <img src="<?= htmlspecialchars($show['image_url']) ?>" alt="<?= htmlspecialchars($show['title']) ?>" loading="lazy">
                                        <div class="trending-info">
                                            <h4><?= htmlspecialchars($show['title']) ?></h4>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="/tv-shows" class="btn btn-secondary">View All TV Shows</a>
                        </div>
                    </section>
                <?php elseif ($path === '/movies'): ?>
                    <section class="movies-section">
                        <h2>Trending Movies</h2>
                        <div class="trending-grid large-grid">
                            <?php foreach ($trending_movies as $movie): ?>
                                <div class="trending-card">
                                    <img src="<?= htmlspecialchars($movie['image_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" loading="lazy">
                                    <div class="trending-info">
                                        <h4><?= htmlspecialchars($movie['title']) ?></h4>
                                        <button class="btn btn-primary btn-sm add-to-collection" data-title="<?= htmlspecialchars($movie['title']) ?>" data-type="movie">Add to Collection</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php elseif ($path === '/tv-shows'): ?>
                    <section class="tv-shows-section">
                        <h2>Trending TV Shows</h2>
                        <div class="trending-grid large-grid">
                            <?php foreach ($trending_shows as $show): ?>
                                <div class="trending-card">
                                    <img src="<?= htmlspecialchars($show['image_url']) ?>" alt="<?= htmlspecialchars($show['title']) ?>" loading="lazy">
                                    <div class="trending-info">
                                        <h4><?= htmlspecialchars($show['title']) ?></h4>
                                        <button class="btn btn-primary btn-sm add-to-collection" data-title="<?= htmlspecialchars($show['title']) ?>" data-type="tv">Add to Collection</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php elseif ($path === '/shows'): ?>
                    <section class="shows-section">
                        <h2>Your Shows</h2>
                        <div id="shows-container" class="shows-grid">
                            <!-- Shows will be loaded here via JavaScript -->
                        </div>
                    </section>
                <?php elseif ($path === '/add-show'): ?>
                    <section class="add-show-section">
                        <h2>Add New Show</h2>
                        <form id="add-show-form" class="show-form">
                            <div class="form-group">
                                <label for="title">Title:</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="genre">Genre:</label>
                                <select id="genre" name="genre" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900">
                                    <option value="">Select Genre</option>
                                    <option value="action">Action</option>
                                    <option value="comedy">Comedy</option>
                                    <option value="drama">Drama</option>
                                    <option value="horror">Horror</option>
                                    <option value="sci-fi">Sci-Fi</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="year">Year:</label>
                                <input type="number" id="year" name="year" min="1900" max="2030" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description:</label>
                                <textarea id="description" name="description" rows="4"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Show</button>
                        </form>
                    </section>
                <?php else: ?>
                    <section class="error-section">
                        <h2><?= htmlspecialchars($content) ?></h2>
                        <a href="/" class="btn btn-primary">Go Home</a>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Shows Management. All rights reserved.</p>
        </div>
    </footer>

    <script src="/js/app.js"></script>
</body>
</html>