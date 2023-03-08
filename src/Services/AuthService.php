<?php

namespace App\Services;

class AuthService
{
    private $db;
    private $config;

    public function __construct(DatabaseService $db, $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Authenticate user login
     */
    public function login($username, $password, $rememberMe = false)
    {
        try {
            // Check for rate limiting
            if (!$this->checkRateLimit($username)) {
                return ['success' => false, 'message' => 'Too many failed attempts. Please try again later.'];
            }

            // Fetch user from database
            if (isVulnerable('sql_injection')) {
                // Vulnerable SQL injection implementation
                $sql = "SELECT * FROM users WHERE username = '{$username}'";
                $user = $this->db->fetchOne($sql, [], true);
            } else {
                // Secure implementation with prepared statements
                $user = $this->db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
            }

            if (!$user) {
                $this->recordFailedAttempt($username);
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            // Verify password
            $passwordValid = false;
            if (isVulnerable('auth_failures')) {
                // Vulnerable: Check plaintext password
                $passwordValid = ($password === $user['password_plain']);
            } else {
                // Secure: Check hashed password
                $passwordValid = password_verify($password, $user['password_hash']);
            }

            if (!$passwordValid) {
                $this->recordFailedAttempt($username, $user['id']);
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            // Check if user is locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return ['success' => false, 'message' => 'Account is temporarily locked.'];
            }

            // Login successful - create session
            $this->createSession($user);
            $this->resetFailedAttempts($user['id']);

            // Log successful login
            $this->logAction($user['id'], 'login_success', ['username' => $username]);

            return ['success' => true, 'user' => $user];

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login.'];
        }
    }

    /**
     * Create user session
     */
    private function createSession($user)
    {
        // Regenerate session ID for security
        if (!isVulnerable('auth_failures')) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();

        // Generate CSRF token
        if (!isVulnerable('csrf')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        if (isset($_SESSION['user_id'])) {
            $this->logAction($_SESSION['user_id'], 'logout');
        }

        session_destroy();
        
        // Clear session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }

    /**
     * Register new user
     */
    public function register($username, $email, $password, $confirmPassword)
    {
        try {
            // Validate input
            if (empty($username) || empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'All fields are required.'];
            }

            if ($password !== $confirmPassword) {
                return ['success' => false, 'message' => 'Passwords do not match.'];
            }

            if (!isVulnerable('auth_failures') && strlen($password) < $this->config['security']['password_min_length']) {
                return ['success' => false, 'message' => 'Password must be at least ' . $this->config['security']['password_min_length'] . ' characters long.'];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email address.'];
            }

            // Check if user already exists
            $existingUser = $this->db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            if ($existingUser) {
                return ['success' => false, 'message' => 'Username or email already exists.'];
            }

            // Hash password
            $passwordHash = null;
            $passwordPlain = null;

            if (isVulnerable('auth_failures')) {
                // Vulnerable: Store plaintext password
                $passwordPlain = $password;
            } else {
                // Secure: Hash password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            }

            // Insert new user
            $userId = $this->db->insert(
                "INSERT INTO users (username, email, password_hash, password_plain, role) VALUES (?, ?, ?, ?, 'user')",
                [$username, $email, $passwordHash, $passwordPlain]
            );

            $this->logAction($userId, 'user_register', ['username' => $username, 'email' => $email]);

            return ['success' => true, 'user_id' => $userId];

        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during registration.'];
        }
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get current user
     */
    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->fetchOne("SELECT id, username, email, role, created_at, updated_at FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }

    /**
     * Check rate limiting for login attempts
     */
    private function checkRateLimit($username)
    {
        if (isVulnerable('auth_failures')) {
            return true; // No rate limiting in vulnerable mode
        }

        $user = $this->db->fetchOne("SELECT failed_login_count, last_failed_login_at, locked_until FROM users WHERE username = ?", [$username]);
        
        if (!$user) {
            return true;
        }

        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return false;
        }

        // Check failed attempts in last hour
        if ($user['failed_login_count'] >= $this->config['security']['max_login_attempts']) {
            $lastAttempt = strtotime($user['last_failed_login_at']);
            if (time() - $lastAttempt < $this->config['security']['lockout_duration']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($username, $userId = null)
    {
        if (isVulnerable('auth_failures')) {
            return; // No tracking in vulnerable mode
        }

        if ($userId) {
            // Update existing user
            $this->db->execute(
                "UPDATE users SET failed_login_count = failed_login_count + 1, last_failed_login_at = NOW() WHERE id = ?",
                [$userId]
            );

            // Lock account if too many attempts
            $user = $this->db->fetchOne("SELECT failed_login_count FROM users WHERE id = ?", [$userId]);
            if ($user['failed_login_count'] >= $this->config['security']['max_login_attempts']) {
                $lockUntil = date('Y-m-d H:i:s', time() + $this->config['security']['lockout_duration']);
                $this->db->execute("UPDATE users SET locked_until = ? WHERE id = ?", [$lockUntil, $userId]);
            }
        }

        $this->logAction($userId, 'login_failed', ['username' => $username]);
    }

    /**
     * Reset failed login attempts
     */
    private function resetFailedAttempts($userId)
    {
        $this->db->execute(
            "UPDATE users SET failed_login_count = 0, last_failed_login_at = NULL, locked_until = NULL WHERE id = ?",
            [$userId]
        );
    }

    /**
     * Log user action
     */
    private function logAction($userId, $action, $payload = [])
    {
        if (!config('logging.audit_actions')) {
            return;
        }

        $this->db->insert(
            "INSERT INTO audit_log (user_id, action, ip_address, user_agent, payload) VALUES (?, ?, ?, ?, ?)",
            [
                $userId,
                $action,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode($payload)
            ]
        );
    }

    /**
     * Require login (redirect if not logged in)
     */
    public function requireLogin($redirectTo = '/login.php')
    {
        if (!$this->isLoggedIn()) {
            header("Location: {$redirectTo}");
            exit;
        }
    }

    /**
     * Require admin role
     */
    public function requireAdmin($redirectTo = '/')
    {
        $this->requireLogin();
        
        if ($_SESSION['user_role'] !== 'admin') {
            header("Location: {$redirectTo}");
            exit;
        }
    }

    /**
     * Update user's password
     */
    public function updatePassword($userId, $newPassword)
    {
        // Validate password strength (secure implementation)
        if (!$this->config['vulnerabilities']['auth_failures']) {
            if (strlen($newPassword) < $this->config['security']['password_min_length']) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least ' . $this->config['security']['password_min_length'] . ' characters long'
                ];
            }
            
            if (!preg_match('/[A-Z]/', $newPassword)) {
                return [
                    'success' => false,
                    'message' => 'Password must contain at least one uppercase letter'
                ];
            }
            
            if (!preg_match('/[a-z]/', $newPassword)) {
                return [
                    'success' => false,
                    'message' => 'Password must contain at least one lowercase letter'
                ];
            }
            
            if (!preg_match('/[0-9]/', $newPassword)) {
                return [
                    'success' => false,
                    'message' => 'Password must contain at least one number'
                ];
            }
        }

        try {
            if (isVulnerable('auth_failures')) {
                // Vulnerable: Store plaintext password
                $sql = "UPDATE users SET password_plain = ?, updated_at = NOW() WHERE id = ?";
                $this->db->execute($sql, [$newPassword, $userId]);
            } else {
                // Secure: Store hashed password
                $hashedPassword = $this->hashPassword($newPassword);
                $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
                $this->db->execute($sql, [$hashedPassword, $userId]);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Password update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update password'
            ];
        }
    }

    /**
     * Update user's email
     */
    public function updateEmail($userId, $newEmail)
    {
        // Validate email format
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }

        try {
            // Check if email is already taken
            $existingUser = $this->db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$newEmail, $userId]);
            
            if ($existingUser) {
                return [
                    'success' => false,
                    'message' => 'Email address is already taken'
                ];
            }
            
            // Update email
            $this->db->execute("UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?", [$newEmail, $userId]);
            
            // Update session if current user
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['user_email'] = $newEmail;
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Email update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update email'
            ];
        }
    }

    /**
     * Verify current user's password
     */
    public function verifyCurrentPassword($userId, $password)
    {
        $user = $this->db->fetchOne("SELECT password_hash, password_plain FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return false;
        }

        if ($this->config['vulnerabilities']['auth_failures']) {
            // Vulnerable: plaintext comparison
            return $password === $user['password_plain'];
        } else {
            // Secure: bcrypt verification
            return password_verify($password, $user['password_hash']);
        }
    }

    /**
     * Verify password against hash
     */
    public function verifyPassword($password, $hash)
    {
        if ($this->config['vulnerabilities']['auth_failures']) {
            // Vulnerable: plaintext comparison
            return $password === $hash;
        } else {
            // Secure: bcrypt verification
            return password_verify($password, $hash);
        }
    }
}