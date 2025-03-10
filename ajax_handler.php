<?php
session_start();

// Enable strict error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log important debugging info
error_log('AJAX handler called with: ' . print_r($_POST, true));
error_log('Session data: ' . print_r($_SESSION, true));

// Prevent direct access if needed
if (!defined('IN_APP')) {
    define('IN_APP', true);
}

// Start output buffering to prevent header issues
ob_start();

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Unknown action'
];

// Remove this check temporarily for debugging
// It might be preventing AJAX requests from being processed
/*
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}
*/

// Check for logged-in status
$isGuest = true; // Default to guest
$currentUser = null;

// More robust login check
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    $isGuest = false;
    $currentUser = getCurrentUser();
    
    // Fallback if getCurrentUser fails
    if (!$currentUser) {
        // Try direct database query as fallback
        $userId = (int)$_SESSION['user_id'];
        $userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult && $userResult->num_rows > 0) {
            $currentUser = $userResult->fetch_assoc();
        } else {
            $response['message'] = 'User authentication failed';
            echo json_encode($response);
            exit;
        }
    }
}

error_log('User status: ' . ($isGuest ? 'Guest' : 'Logged in as ' . ($currentUser['username'] ?? 'unknown')));

/**
 * Get the current like count for a post
 * @param int $post_id Post ID
 * @return int Number of likes
 */
function getLikeCount($post_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return (int)$row['count'];
    }
    
    return 0;
}

// Get the action from POST or GET
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Log the action for debugging
error_log('Processing action: ' . $action);

// Handle different actions
try {
    switch($action) {
        case 'like_post':
            // Debug info
            error_log('Like post action triggered');
            error_log('User status: ' . ($isGuest ? 'Guest' : 'Logged in user ID: ' . $currentUser['id']));
            
            // Validate required parameters
            if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
                throw new Exception('Invalid post ID');
            }
            
            // Prevent guests from liking
            if ($isGuest) {
                error_log('Guest tried to like post');
                throw new Exception('Please login to like posts');
            }
            
            $post_id = (int)$_POST['post_id'];
            $user_id = $currentUser['id'];
            
            // Check if user already liked this post
            $checkStmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
            $checkStmt->bind_param("ii", $user_id, $post_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // User already liked the post, unlike it
                $unlikeStmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
                $unlikeStmt->bind_param("ii", $user_id, $post_id);
                $unlikeStmt->execute();
                
                // Update post like count
                $updateStmt = $conn->prepare("UPDATE posts SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?");
                $updateStmt->bind_param("i", $post_id);
                $updateStmt->execute();
                
                $liked = false;
            } else {
                // User hasn't liked the post, like it
                $likeStmt = $conn->prepare("INSERT INTO likes (user_id, post_id, created_at) VALUES (?, ?, NOW())");
                $likeStmt->bind_param("ii", $user_id, $post_id);
                $likeStmt->execute();
                
                // Update post like count
                $updateStmt = $conn->prepare("UPDATE posts SET like_count = like_count + 1 WHERE id = ?");
                $updateStmt->bind_param("i", $post_id);
                $updateStmt->execute();
                
                $liked = true;
            }
            
            // Get updated like count
            $likeCount = getLikeCount($post_id);
            
            $response = [
                'status' => 'success',
                'message' => $liked ? 'Post liked' : 'Post unliked',
                'liked' => $liked,
                'like_count' => $likeCount
            ];
            break;
        case 'ignore_post':
            // Validate required parameters
            if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
                throw new Exception('Invalid post ID');
            }
            
            $post_id = (int)$_POST['post_id'];
            
            // For guests
            if ($isGuest) {
                // Use the existing functions if they exist
                // Access session directly if needed
                if (!isset($_SESSION['guest_preferences'])) {
                    $_SESSION['guest_preferences'] = [];
                }
                
                if (!isset($_SESSION['guest_preferences']['ignored_posts'])) {
                    $_SESSION['guest_preferences']['ignored_posts'] = [];
                }
                
                $ignoredPosts = $_SESSION['guest_preferences']['ignored_posts'];
                
                if (!in_array($post_id, $ignoredPosts)) {
                    $ignoredPosts[] = $post_id;
                    $_SESSION['guest_preferences']['ignored_posts'] = $ignoredPosts;
                }
                
                $response = [
                    'status' => 'success',
                    'message' => 'Post ignored',
                    'ignored_posts' => $ignoredPosts
                ];
            } 
            // For logged-in users
            else {
                // Check if post is already ignored
                $checkStmt = $conn->prepare("SELECT id FROM ignored_posts WHERE user_id = ? AND post_id = ?");
                $checkStmt->bind_param("ii", $currentUser['id'], $post_id);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                
                if ($result->num_rows == 0) {
                    // Insert into ignored posts
                    $insertStmt = $conn->prepare("INSERT INTO ignored_posts (user_id, post_id, created_at) VALUES (?, ?, NOW())");
                    $insertStmt->bind_param("ii", $currentUser['id'], $post_id);
                    $insertStmt->execute();
                }
                
                $response = [
                    'status' => 'success',
                    'message' => 'Post ignored'
                ];
            }
            break;
        
        case 'load_comments':
            // Validate required parameters
            if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
                throw new Exception('Invalid post ID');
            }
            
            $post_id = (int)$_POST['post_id'];
            
            error_log('Loading comments for post ID: ' . $post_id);
            
            // Fetch comments for the post
            $commentsStmt = $conn->prepare("
                SELECT 
                    c.id, 
                    c.content, 
                    c.created_at, 
                    u.username, 
                    COALESCE(u.profile_picture, 'assets/images/default_profile.png') as profile_picture
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ?
                ORDER BY c.created_at DESC
                LIMIT 50
            ");
            
            if (!$commentsStmt) {
                error_log("MySQL Error: " . $conn->error);
                throw new Exception("Database error: " . $conn->error);
            }
            
            $commentsStmt->bind_param("i", $post_id);
            
            if (!$commentsStmt->execute()) {
                error_log("MySQL Execute Error: " . $commentsStmt->error);
                throw new Exception("Database execution error: " . $commentsStmt->error);
            }
            
            $commentsResult = $commentsStmt->get_result();
            
            if (!$commentsResult) {
                error_log("MySQL Result Error: " . $commentsStmt->error);
                throw new Exception("Database result error: " . $commentsStmt->error);
            }
            
            $comments = [];
            while ($comment = $commentsResult->fetch_assoc()) {
                // Ensure content is properly escaped for JSON
                $comment['content'] = htmlspecialchars($comment['content']);
                $comment['formatted_date'] = date('M d, Y H:i', strtotime($comment['created_at']));
                $comments[] = $comment;
            }
            
            error_log('Loaded ' . count($comments) . ' comments');
            
            $response = [
                'status' => 'success',
                'comments' => $comments
            ];
            break;
        
        case 'add_comment':
            // Prevent guests from commenting
            if ($isGuest) {
                throw new Exception('Please login to comment');
            }
            
            // Validate required parameters
            if (!isset($_POST['post_id']) || !isset($_POST['comment']) || 
                !is_numeric($_POST['post_id']) || trim($_POST['comment']) === '') {
                throw new Exception('Invalid comment or post ID');
            }
            
            $post_id = (int)$_POST['post_id'];
            $comment_content = trim($_POST['comment']);
            
            error_log('Adding comment for post ID: ' . $post_id);
            error_log('Comment content: ' . $comment_content);
            
            // Verify post exists
            $checkPostStmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
            $checkPostStmt->bind_param("i", $post_id);
            $checkPostStmt->execute();
            $checkPostResult = $checkPostStmt->get_result();
            
            if ($checkPostResult->num_rows === 0) {
                throw new Exception('Post not found');
            }
            
            // Insert comment
            $insertCommentStmt = $conn->prepare("
                INSERT INTO comments (user_id, post_id, content, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            if (!$insertCommentStmt) {
                error_log("MySQL Error: " . $conn->error);
                throw new Exception("Database error: " . $conn->error);
            }
            
            $insertCommentStmt->bind_param("iis", $currentUser['id'], $post_id, $comment_content);
            
            if (!$insertCommentStmt->execute()) {
                error_log("MySQL Execute Error: " . $insertCommentStmt->error);
                throw new Exception("Database execution error: " . $insertCommentStmt->error);
            }
            
            $comment_id = $conn->insert_id;
            error_log('Comment created with ID: ' . $comment_id);
            
            // Update comment count
            $updateCommentCountStmt = $conn->prepare("
                UPDATE posts 
                SET comment_count = comment_count + 1 
                WHERE id = ?
            ");
            $updateCommentCountStmt->bind_param("i", $post_id);
            $updateCommentCountStmt->execute();
            
            // Get profile picture with fallback
            $profilePic = !empty($currentUser['profile_picture']) 
                ? $currentUser['profile_picture'] 
                : 'assets/images/default_profile.png';
            
            $response = [
                'status' => 'success',
                'message' => 'Comment added',
                'comment' => [
                    'id' => $comment_id,
                    'content' => htmlspecialchars($comment_content),
                    'username' => $currentUser['username'],
                    'profile_picture' => $profilePic,
                    'formatted_date' => date('M d, Y H:i')
                ]
            ];
            break;
        
        case 'load_more_posts':
            // Validate offset
            $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            $limit = 20;
            
            // Prepare query to get posts with additional user-specific data
            if ($isGuest) {
                // For guests, no need to check likes
                $postsStmt = $conn->prepare("
                    SELECT 
                        p.id, 
                        p.user_id, 
                        p.content, 
                        p.media_type, 
                        p.media_path, 
                        p.like_count, 
                        p.comment_count, 
                        p.created_at,
                        u.username, 
                        COALESCE(u.profile_picture, 'assets/images/default_profile.png') as profile_picture,
                        0 as liked
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    ORDER BY p.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                
                $postsStmt->bind_param("ii", $limit, $offset);
            } else {
                // For logged-in users, check likes and ignored posts
                $postsStmt = $conn->prepare("
                    SELECT 
                        p.id, 
                        p.user_id, 
                        p.content, 
                        p.media_type, 
                        p.media_path, 
                        p.like_count, 
                        p.comment_count, 
                        p.created_at,
                        u.username, 
                        COALESCE(u.profile_picture, 'assets/images/default_profile.png') as profile_picture,
                        CASE WHEN l.id IS NOT NULL THEN 1 ELSE 0 END as liked
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    LEFT JOIN likes l ON l.post_id = p.id AND l.user_id = ?
                    WHERE p.id NOT IN (
                        SELECT post_id FROM ignored_posts WHERE user_id = ?
                    )
                    ORDER BY p.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                
                $postsStmt->bind_param("iiii", $currentUser['id'], $currentUser['id'], $limit, $offset);
            }
            
            $postsStmt->execute();
            $postsResult = $postsStmt->get_result();
            
            $posts = [];
            while ($post = $postsResult->fetch_assoc()) {
                // Convert liked to boolean
                $post['liked'] = (bool)$post['liked'];
                $posts[] = $post;
            }
            
            $response = [
                'status' => 'success',
                'posts' => $posts
            ];
            break;
            
        case 'test':
            // Simple test action for debugging
            $response = [
                'status' => 'success',
                'message' => 'AJAX connection working',
                'timestamp' => time(),
                'user_status' => $isGuest ? 'Guest' : 'Logged in as ' . ($currentUser['username'] ?? 'unknown')
            ];
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    // Log the error
    error_log('AJAX Handler Error: ' . $e->getMessage());
    error_log('AJAX Handler Error Trace: ' . $e->getTraceAsString());
    
    // Prepare error response
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Clear any previous output
ob_clean();

// Send JSON response
echo json_encode($response);
exit;