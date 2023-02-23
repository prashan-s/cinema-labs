CREATE DATABASE IF NOT EXISTS cinemalabs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cinemalabs;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NULL, -- NULL for vulnerable plaintext storage
    password_plain VARCHAR(255) NULL, -- For vulnerable implementation
    role ENUM('user', 'admin') DEFAULT 'user',
    failed_login_count INT DEFAULT 0,
    last_failed_login_at TIMESTAMP NULL,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Movies table (cached from TMDB)
CREATE TABLE IF NOT EXISTS movies (
    id INT PRIMARY KEY, -- TMDB ID
    title VARCHAR(255) NOT NULL,
    original_title VARCHAR(255),
    overview TEXT,
    release_date DATE,
    poster_path VARCHAR(255),
    backdrop_path VARCHAR(255),
    vote_average DECIMAL(3,1),
    vote_count INT,
    popularity DECIMAL(8,3),
    genre_ids JSON,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TV Shows table (cached from TMDB)
CREATE TABLE IF NOT EXISTS tv_shows (
    id INT PRIMARY KEY, -- TMDB ID
    name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    overview TEXT,
    first_air_date DATE,
    poster_path VARCHAR(255),
    backdrop_path VARCHAR(255),
    vote_average DECIMAL(3,1),
    vote_count INT,
    popularity DECIMAL(8,3),
    genre_ids JSON,
    origin_country JSON,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TMDB API Cache table
CREATE TABLE IF NOT EXISTS tmdb_cache (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cache_key VARCHAR(255) UNIQUE NOT NULL,
    cache_type ENUM('discover_movies', 'discover_tv', 'search_movies', 'search_tv', 'movie_details', 'tv_details', 'trending') NOT NULL,
    data JSON NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cache_key (cache_key),
    INDEX idx_cache_type (cache_type),
    INDEX idx_expires_at (expires_at)
);

-- Sync tracking table
CREATE TABLE IF NOT EXISTS tmdb_sync_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sync_type VARCHAR(50) NOT NULL,
    last_sync_at TIMESTAMP NOT NULL,
    status ENUM('running', 'completed', 'failed') DEFAULT 'completed',
    error_message TEXT NULL,
    records_processed INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sync_type (sync_type),
    INDEX idx_sync_type (sync_type),
    INDEX idx_last_sync_at (last_sync_at)
);

-- Reviews table (for stored XSS demonstration)
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT NULL, -- For movies
    tv_id INT NULL, -- For TV shows
    content TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (tv_id) REFERENCES tv_shows(id) ON DELETE CASCADE
);

-- Favorites table (for CSRF demonstration)
CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT NULL, -- For movies
    tv_id INT NULL, -- For TV shows
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (tv_id) REFERENCES tv_shows(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_movie (user_id, movie_id),
    UNIQUE KEY unique_user_tv (user_id, tv_id)
);

-- Audit log table (for security monitoring)
CREATE TABLE IF NOT EXISTS audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Admin settings table (for vulnerability toggles)
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Indexes for performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_reviews_user_id ON reviews(user_id);
CREATE INDEX idx_reviews_movie_id ON reviews(movie_id);
CREATE INDEX idx_reviews_tv_id ON reviews(tv_id);
CREATE INDEX idx_favorites_user_id ON favorites(user_id);
CREATE INDEX idx_audit_log_user_id ON audit_log(user_id);
CREATE INDEX idx_audit_log_action ON audit_log(action);
CREATE INDEX idx_audit_log_created_at ON audit_log(created_at);

-- Insert default admin user (vulnerable password for demo)
INSERT IGNORE INTO users (username, email, password_hash, password_plain, role) VALUES 
('admin', 'admin@cinemalabs.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', 'admin'),
('student', 'student@cinemalabs.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', 'user'),
('testuser', 'test@cinemalabs.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', 'user');

-- Insert vulnerability settings
INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES 
('vuln_auth_failures', 'true'),
('vuln_reflected_xss', 'true'),
('vuln_stored_xss', 'true'),
('vuln_csrf', 'true'),
('vuln_command_injection', 'true'),
('vuln_sql_injection', 'true'),
('vuln_insecure_design', 'true');

-- Sample review with potential XSS (for demonstration purposes)
INSERT IGNORE INTO reviews (user_id, movie_id, content, rating) VALUES 
(2, 1311031, 'Great movie! <script>console.log("XSS Demo")</script>', 9),
(3, 755898, 'Not bad, but could be better. <img src="x" onerror="console.log(\'Stored XSS\')">', 6);