<?php
/**
 * SNS-DSO - DevSecOps Microblogging App
 * Main Entry Point
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Include dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    jsonResponse([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ], 500);
}

// Simple routing
if ($path === '/' || $path === '/index.php') {
    // Home page - show app info
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SNS-DSO - DevSecOps Microblogging</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 800px;
                width: 100%;
                padding: 40px;
            }
            h1 {
                color: #667eea;
                font-size: 2.5rem;
                margin-bottom: 10px;
            }
            .subtitle {
                color: #666;
                font-size: 1.2rem;
                margin-bottom: 30px;
            }
            .status {
                background: #f0f9ff;
                border-left: 4px solid #0ea5e9;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .status h2 {
                color: #0369a1;
                margin-bottom: 15px;
            }
            .status-item {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #e0f2fe;
            }
            .status-item:last-child {
                border-bottom: none;
            }
            .status-label {
                font-weight: 600;
                color: #374151;
            }
            .status-value {
                color: #059669;
                font-weight: 500;
            }
            .status-value.error {
                color: #dc2626;
            }
            .endpoints {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .endpoints h2 {
                color: #92400e;
                margin-bottom: 15px;
            }
            .endpoint {
                background: white;
                padding: 10px;
                margin: 8px 0;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
                font-size: 0.9rem;
            }
            .method {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 4px;
                font-weight: bold;
                margin-right: 10px;
            }
            .method.get { background: #dbeafe; color: #1e40af; }
            .method.post { background: #d1fae5; color: #065f46; }
            .footer {
                margin-top: 30px;
                text-align: center;
                color: #6b7280;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚀 SNS-DSO</h1>
            <div class="subtitle">DevSecOps Microblogging Platform</div>
            
            <div class="status">
                <h2>📊 System Status</h2>
                <?php
                $dbStatus = 'Connected';
                $dbClass = '';
                try {
                    $stmt = $db->query("SELECT VERSION() as version");
                    $version = $stmt->fetch();
                    $dbVersion = $version['version'];
                } catch (Exception $e) {
                    $dbStatus = 'Connection Failed';
                    $dbClass = 'error';
                    $dbVersion = 'N/A';
                }
                ?>
                <div class="status-item">
                    <span class="status-label">PHP Version:</span>
                    <span class="status-value"><?php echo phpversion(); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Database Status:</span>
                    <span class="status-value <?php echo $dbClass; ?>"><?php echo $dbStatus; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">MariaDB Version:</span>
                    <span class="status-value"><?php echo $dbVersion; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Server Time:</span>
                    <span class="status-value"><?php echo date('Y-m-d H:i:s T'); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Client IP:</span>
                    <span class="status-value"><?php echo getClientIp(); ?></span>
                </div>
            </div>
            
            <div class="endpoints">
                <h2>🔌 Available Endpoints</h2>
                <div class="endpoint">
                    <span class="method get">GET</span> /api/health - Health check endpoint
                </div>
                <div class="endpoint">
                    <span class="method get">GET</span> /api/init - Initialize database tables
                </div>
                <div class="endpoint">
                    <span class="method get">GET</span> /api/posts - Get all posts
                </div>
                <div class="endpoint">
                    <span class="method post">POST</span> /api/posts - Create new post
                </div>
            </div>
            
            <div class="footer">
                <p>Powered by PHP <?php echo phpversion(); ?> • NGINX • MariaDB</p>
                <p>Running on Docker with DevSecOps principles</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// API Routes
if (strpos($path, '/api/') === 0) {
    
    // Health check endpoint
    if ($path === '/api/health') {
        jsonResponse([
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'database' => 'connected'
        ]);
    }
    
    // Initialize database
    if ($path === '/api/init' && $requestMethod === 'GET') {
        if ($database->initDatabase()) {
            jsonResponse([
                'status' => 'success',
                'message' => 'Database initialized successfully',
                'tables' => ['users', 'posts', 'likes', 'comments', 'follows']
            ]);
        } else {
            jsonResponse([
                'status' => 'error',
                'message' => 'Failed to initialize database'
            ], 500);
        }
    }
    
    // Get all posts
    if ($path === '/api/posts' && $requestMethod === 'GET') {
        try {
            $stmt = $db->query("
                SELECT p.*, u.username, u.display_name, u.avatar_url
                FROM posts p
                JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
                LIMIT 50
            ");
            $posts = $stmt->fetchAll();
            
            jsonResponse([
                'status' => 'success',
                'count' => count($posts),
                'posts' => $posts
            ]);
        } catch (Exception $e) {
            jsonResponse([
                'status' => 'error',
                'message' => 'Failed to fetch posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Create new post
    if ($path === '/api/posts' && $requestMethod === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['content']) || empty($data['user_id'])) {
            jsonResponse([
                'status' => 'error',
                'message' => 'Missing required fields: content, user_id'
            ], 400);
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO posts (user_id, content, image_url)
                VALUES (:user_id, :content, :image_url)
            ");
            
            $stmt->execute([
                'user_id' => $data['user_id'],
                'content' => $data['content'],
                'image_url' => $data['image_url'] ?? null
            ]);
            
            jsonResponse([
                'status' => 'success',
                'message' => 'Post created successfully',
                'post_id' => $db->lastInsertId()
            ], 201);
        } catch (Exception $e) {
            jsonResponse([
                'status' => 'error',
                'message' => 'Failed to create post',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

// 404 for unknown routes
jsonResponse([
    'status' => 'error',
    'message' => 'Endpoint not found',
    'path' => $path
], 404);
