<?php // templates/footer.php ?>
    
    <footer class="bg-gray-800 text-white mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Shows</h3>
                    <p class="text-gray-300 text-sm">
                        Your ultimate destination for discovering and reviewing the latest movies and TV shows.
                    </p>
                    <div class="mt-4">
                        <span class="bg-blue-600 text-xs px-2 py-1 rounded">Discover Cinema</span>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Features</h3>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="search.php" class="hover:text-white">Advanced Search</a></li>
                        <li><a href="favorites.php" class="hover:text-white">Personal Favorites</a></li>
                        <li><a href="profile.php" class="hover:text-white">User Profiles</a></li>
                        <li><a href="review.php" class="hover:text-white">Write Reviews</a></li>
                        <li><a href="/" class="hover:text-white">Latest Releases</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Account</h3>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="profile.php" class="hover:text-white">My Profile</a></li>
                            <li><a href="favorites.php" class="hover:text-white">My Favorites</a></li>
                            <li><a href="logout.php" class="hover:text-white">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="hover:text-white">Sign In</a></li>
                            <li><a href="register.php" class="hover:text-white">Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>&copy; <?= date('Y') ?> Shows. A comprehensive movie discovery and review platform.</p>
                <p class="mt-2">Movie data provided by <a href="https://www.themoviedb.org/" class="text-blue-400 hover:text-blue-300">The Movie Database (TMDB)</a></p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>