<?php
/**
 * Post Management Class
 * Handles post CRUD operations, likes, and comments
 */

class Post {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create a new post
     */
    public function create($userId, $content, $imageUrl = null) {
        if (empty($content) || strlen($content) > 280) {
            throw new Exception('Content must be between 1 and 280 characters');
        }
        
        $stmt = $this->db->prepare('
            INSERT INTO posts (user_id, content, image_url) 
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$userId, $content, $imageUrl]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get a single post by ID
     */
    public function getById($postId, $currentUserId = null) {
        $stmt = $this->db->prepare('
            SELECT p.*, 
                   u.username, u.display_name, u.avatar_url,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        
        if (!$post) {
            return null;
        }
        
        // Check if current user has liked this post
        if ($currentUserId) {
            $post['is_liked_by_user'] = $this->isLikedByUser($postId, $currentUserId);
        }
        
        return $post;
    }
    
    /**
     * Get all posts (timeline/feed)
     */
    public function getAll($limit = 50, $offset = 0, $currentUserId = null) {
        $sql = '
            SELECT p.*, 
                   u.username, u.display_name, u.avatar_url,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
            LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset);
        
        $stmt = $this->db->query($sql);
        $posts = $stmt->fetchAll();
        
        // Add like status for current user
        if ($currentUserId) {
            foreach ($posts as &$post) {
                $post['is_liked_by_user'] = $this->isLikedByUser($post['id'], $currentUserId);
            }
        }
        
        return $posts;
    }
    
    /**
     * Get posts by user
     */
    public function getByUser($userId, $limit = 50, $currentUserId = null) {
        $sql = '
            SELECT p.*, 
                   u.username, u.display_name, u.avatar_url,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ' . intval($userId) . '
            ORDER BY p.created_at DESC
            LIMIT ' . intval($limit);
        
        $stmt = $this->db->query($sql);
        $posts = $stmt->fetchAll();
        
        if ($currentUserId) {
            foreach ($posts as &$post) {
                $post['is_liked_by_user'] = $this->isLikedByUser($post['id'], $currentUserId);
            }
        }
        
        return $posts;
    }
    
    /**
     * Update a post (only by owner)
     */
    public function update($postId, $userId, $content, $imageUrl = null) {
        // Verify ownership
        if (!$this->isOwner($postId, $userId)) {
            throw new Exception('You can only edit your own posts');
        }
        
        if (empty($content) || strlen($content) > 280) {
            throw new Exception('Content must be between 1 and 280 characters');
        }
        
        $stmt = $this->db->prepare('
            UPDATE posts 
            SET content = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?
        ');
        $stmt->execute([$content, $imageUrl, $postId, $userId]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Delete a post (only by owner)
     */
    public function delete($postId, $userId) {
        // Verify ownership
        if (!$this->isOwner($postId, $userId)) {
            throw new Exception('You can only delete your own posts');
        }
        
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
        $stmt->execute([$postId, $userId]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Check if user is owner of post
     */
    public function isOwner($postId, $userId) {
        $stmt = $this->db->prepare('SELECT user_id FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        
        return $post && $post['user_id'] == $userId;
    }
    
    /**
     * Like a post
     */
    public function like($postId, $userId) {
        try {
            $stmt = $this->db->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)');
            $stmt->execute([$userId, $postId]);
            return true;
        } catch (PDOException $e) {
            // Duplicate key - already liked
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }
    
    /**
     * Unlike a post
     */
    public function unlike($postId, $userId) {
        $stmt = $this->db->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?');
        $stmt->execute([$userId, $postId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Check if user has liked a post
     */
    public function isLikedByUser($postId, $userId) {
        $stmt = $this->db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
        $stmt->execute([$userId, $postId]);
        return (bool) $stmt->fetch();
    }
    
    /**
     * Get likes for a post
     */
    public function getLikes($postId) {
        $stmt = $this->db->prepare('
            SELECT l.*, u.username, u.display_name, u.avatar_url
            FROM likes l
            JOIN users u ON l.user_id = u.id
            WHERE l.post_id = ?
            ORDER BY l.created_at DESC
        ');
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add a comment to a post
     */
    public function addComment($postId, $userId, $content) {
        if (empty($content) || strlen($content) > 280) {
            throw new Exception('Comment must be between 1 and 280 characters');
        }
        
        $stmt = $this->db->prepare('
            INSERT INTO comments (user_id, post_id, content) 
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$userId, $postId, $content]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get comments for a post
     */
    public function getComments($postId) {
        $stmt = $this->db->prepare('
            SELECT c.*, u.username, u.display_name, u.avatar_url
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ');
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Delete a comment (only by comment owner)
     */
    public function deleteComment($commentId, $userId) {
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
        $stmt->execute([$commentId, $userId]);
        return $stmt->rowCount() > 0;
    }
}
