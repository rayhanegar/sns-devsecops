<?php
/**
 * Authentication Class
 * Handles user authentication, registration, and session management
 */

class Auth {
    private $db;
    private $sessionLifetime = 86400; // 24 hours
    
    public function __construct($database) {
        $this->db = $database;
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
            session_start();
        }
    }
    
    /**
     * Register a new user
     */
    public function register($username, $email, $password, $displayName = null) {
        // Validate input
        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new Exception('Username must be between 3 and 50 characters');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        
        // Check if username or email already exists
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists');
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Insert user
        $stmt = $this->db->prepare('
            INSERT INTO users (username, email, password_hash, display_name) 
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([
            $username,
            $email,
            $passwordHash,
            $displayName ?: $username
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Login user
     */
    public function login($usernameOrEmail, $password) {
        // Find user by username or email
        $stmt = $this->db->prepare('
            SELECT id, username, email, password_hash, display_name, avatar_url 
            FROM users 
            WHERE username = ? OR email = ?
        ');
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Invalid credentials');
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid credentials');
        }
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['avatar_url'] = $user['avatar_url'];
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'display_name' => $user['display_name'],
            'avatar_url' => $user['avatar_url']
        ];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        session_destroy();
        return true;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time']) > $this->sessionLifetime) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'display_name' => $_SESSION['display_name'],
            'avatar_url' => $_SESSION['avatar_url'] ?? null
        ];
    }
    
    /**
     * Require authentication - throw exception if not authenticated
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            throw new Exception('Authentication required');
        }
        return $this->getCurrentUser();
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        $stmt = $this->db->prepare('
            SELECT id, username, email, display_name, bio, avatar_url, created_at 
            FROM users 
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}
