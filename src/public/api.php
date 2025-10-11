<?php
/**
 * SNS-DSO API Router
 * Handles all API endpoints
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Include dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Post.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize database
try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $post = new Post($db);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => 'Database connection failed'], 500);
}

// Get request info
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix if present
$path = preg_replace('#^/api#', '', $path);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// ==================== HEALTH & INIT ====================

if ($path === '/health') {
    jsonResponse([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => '1.0.0',
        'database' => 'connected'
    ]);
}

if ($path === '/init' && $requestMethod === 'GET') {
    if ($database->initDatabase()) {
        jsonResponse([
            'status' => 'success',
            'message' => 'Database initialized successfully'
        ]);
    } else {
        jsonResponse(['status' => 'error', 'message' => 'Failed to initialize database'], 500);
    }
}

// ==================== AUTHENTICATION ====================

// Register
if ($path === '/auth/register' && $requestMethod === 'POST') {
    try {
        $userId = $auth->register(
            $input['username'] ?? '',
            $input['email'] ?? '',
            $input['password'] ?? '',
            $input['display_name'] ?? null
        );
        
        jsonResponse([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user_id' => $userId
        ], 201);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
}

// Login
if ($path === '/auth/login' && $requestMethod === 'POST') {
    try {
        $user = $auth->login(
            $input['username'] ?? '',
            $input['password'] ?? ''
        );
        
        jsonResponse([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user
        ]);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 401);
    }
}

// Logout
if ($path === '/auth/logout' && $requestMethod === 'POST') {
    $auth->logout();
    jsonResponse(['status' => 'success', 'message' => 'Logged out successfully']);
}

// Get current user
if ($path === '/auth/me' && $requestMethod === 'GET') {
    $user = $auth->getCurrentUser();
    if ($user) {
        jsonResponse(['status' => 'success', 'user' => $user]);
    } else {
        jsonResponse(['status' => 'error', 'message' => 'Not authenticated'], 401);
    }
}

// ==================== POSTS ====================

// Get all posts (timeline)
if ($path === '/posts' && $requestMethod === 'GET') {
    try {
        $currentUser = $auth->getCurrentUser();
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $posts = $post->getAll($limit, $offset, $currentUser['id'] ?? null);
        
        jsonResponse([
            'status' => 'success',
            'count' => count($posts),
            'posts' => $posts
        ]);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// Create post
if ($path === '/posts' && $requestMethod === 'POST') {
    try {
        $user = $auth->requireAuth();
        
        $postId = $post->create(
            $user['id'],
            $input['content'] ?? '',
            $input['image_url'] ?? null
        );
        
        jsonResponse([
            'status' => 'success',
            'message' => 'Post created successfully',
            'post_id' => $postId
        ], 201);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], $e->getMessage() === 'Authentication required' ? 401 : 400);
    }
}

// Get single post
if (preg_match('#^/posts/(\d+)$#', $path, $matches) && $requestMethod === 'GET') {
    try {
        $postId = $matches[1];
        $currentUser = $auth->getCurrentUser();
        
        $postData = $post->getById($postId, $currentUser['id'] ?? null);
        
        if ($postData) {
            jsonResponse(['status' => 'success', 'post' => $postData]);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Post not found'], 404);
        }
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// Update post
if (preg_match('#^/posts/(\d+)$#', $path, $matches) && $requestMethod === 'PUT') {
    try {
        $user = $auth->requireAuth();
        $postId = $matches[1];
        
        $success = $post->update(
            $postId,
            $user['id'],
            $input['content'] ?? '',
            $input['image_url'] ?? null
        );
        
        if ($success) {
            jsonResponse(['status' => 'success', 'message' => 'Post updated successfully']);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Failed to update post'], 400);
        }
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], $e->getMessage() === 'Authentication required' ? 401 : 403);
    }
}

// Delete post
if (preg_match('#^/posts/(\d+)$#', $path, $matches) && $requestMethod === 'DELETE') {
    try {
        $user = $auth->requireAuth();
        $postId = $matches[1];
        
        $success = $post->delete($postId, $user['id']);
        
        if ($success) {
            jsonResponse(['status' => 'success', 'message' => 'Post deleted successfully']);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Failed to delete post'], 400);
        }
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], $e->getMessage() === 'Authentication required' ? 401 : 403);
    }
}

// ==================== LIKES ====================

// Like a post
if (preg_match('#^/posts/(\d+)/like$#', $path, $matches) && $requestMethod === 'POST') {
    try {
        $user = $auth->requireAuth();
        $postId = $matches[1];
        
        $success = $post->like($postId, $user['id']);
        
        jsonResponse([
            'status' => 'success',
            'message' => $success ? 'Post liked' : 'Already liked',
            'liked' => $success
        ]);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], $e->getMessage() === 'Authentication required' ? 401 : 400);
    }
}

// Unlike a post
if (preg_match('#^/posts/(\d+)/unlike$#', $path, $matches) && $requestMethod === 'POST') {
    try {
        $user = $auth->requireAuth();
        $postId = $matches[1];
        
        $success = $post->unlike($postId, $user['id']);
        
        jsonResponse([
            'status' => 'success',
            'message' => $success ? 'Post unliked' : 'Not liked',
            'unliked' => $success
        ]);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], $e->getMessage() === 'Authentication required' ? 401 : 400);
    }
}

// Get likes for a post
if (preg_match('#^/posts/(\d+)/likes$#', $path, $matches) && $requestMethod === 'GET') {
    try {
        $postId = $matches[1];
        $likes = $post->getLikes($postId);
        
        jsonResponse([
            'status' => 'success',
            'count' => count($likes),
            'likes' => $likes
        ]);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ==================== COMMENTS ====================

// Add comment
if (preg_match('#^/posts/(\d+)/comments$#', $path, $matches) && $requestMethod === 'POST') {
    try {
        $user = $auth->requireAuth();
        $postId = $matches[1];
        
        $commentId = $post->addComment(
            $postId,
            $user['id'],
            $input['content'] ?? ''
        );
        
        jsonResponse([
            'status' => 'success',
            'message' => 'Comment added successfully',
            'comment_id' => $commentId
        ], 201);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], $e->getMessage() === 'Authentication required' ? 401 : 400);
    }
}

// Get comments for a post
if (preg_match('#^/posts/(\d+)/comments$#', $path, $matches) && $requestMethod === 'GET') {
    try {
        $postId = $matches[1];
        $comments = $post->getComments($postId);
        
        jsonResponse([
            'status' => 'success',
            'count' => count($comments),
            'comments' => $comments
        ]);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// Delete comment
if (preg_match('#^/comments/(\d+)$#', $path, $matches) && $requestMethod === 'DELETE') {
    try {
        $user = $auth->requireAuth();
        $commentId = $matches[1];
        
        $success = $post->deleteComment($commentId, $user['id']);
        
        if ($success) {
            jsonResponse(['status' => 'success', 'message' => 'Comment deleted successfully']);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Failed to delete comment'], 400);
        }
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], $e->getMessage() === 'Authentication required' ? 401 : 403);
    }
}

// ==================== USER PROFILE ====================

// Get user profile
if (preg_match('#^/users/(\d+)$#', $path, $matches) && $requestMethod === 'GET') {
    try {
        $userId = $matches[1];
        $currentUser = $auth->getCurrentUser();
        
        $userInfo = $auth->getUserById($userId);
        if ($userInfo) {
            $userPosts = $post->getByUser($userId, 50, $currentUser['id'] ?? null);
            jsonResponse([
                'status' => 'success',
                'user' => $userInfo,
                'posts' => $userPosts
            ]);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
        }
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// 404 for unknown routes
jsonResponse(['status' => 'error', 'message' => 'Endpoint not found', 'path' => $path], 404);
