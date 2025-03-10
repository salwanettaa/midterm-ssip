<?php
// post.php - AJAX handler for post actions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize response
$response = ['success' => false, 'message' => 'Invalid request'];

// Get action and post_id
$action = $_POST['action'] ?? '';
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

// Check if user is logged in for actions that require login
$isLoggedIn = isLoggedIn();
$currentUser = null;

if ($isLoggedIn) {
    $currentUser = getCurrentUser();
}

// Handle different actions
switch ($action) {
    case 'like':
        if (!$isLoggedIn) {
            $response = ['success' => false, 'message' => 'Please login to perform this action'];
        } else {
            $response = likePost($currentUser['id'], $post_id);
        }
        break;
        
    case 'comment':
        if (!$isLoggedIn) {
            $response = ['success' => false, 'message' => 'Please login to perform this action'];
        } else {
            $content = $_POST['content'] ?? '';
            if (empty($content)) {
                $response = ['success' => false, 'message' => 'Comment cannot be empty'];
            } else {
                $comment_id = addComment($currentUser['id'], $post_id, $content);
                if ($comment_id) {
                    $response = [
                        'success' => true, 
                        'comment' => [
                            'id' => $comment_id,
                            'user_id' => $currentUser['id'],
                            'content' => htmlspecialchars($content),
                            'username' => $currentUser['username'],
                            'profile_picture' => $currentUser['profile_picture'],
                            'created_at' => date('M d, Y H:i')
                        ]
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to add comment'];
                }
            }
        }
        break;
        
    case 'get_comments':
        $comments = getComments($post_id);
        $response = ['success' => true, 'comments' => $comments];
        break;
        
    case 'ignore':
        if ($isLoggedIn) {
            $response = ignorePost($currentUser['id'], $post_id);
        } else {
            // For guest users, store in cookie
            $guestPreferences = getGuestPreferences();
            $ignoredPosts = $guestPreferences['ignored_posts'] ?? [];
            $ignoredPosts[] = $post_id;
            $guestPreferences['ignored_posts'] = $ignoredPosts;
            setGuestCookie($guestPreferences);
            $response = ['success' => true, 'message' => 'Post hidden'];
        }
        break;
        
    default:
        $response = ['success' => false, 'message' => 'Invalid action'];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>