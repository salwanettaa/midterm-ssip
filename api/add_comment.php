<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to comment'
    ]);
    exit;
}

// Get the current user
$currentUser = getCurrentUser();

// Check if post_id and content were provided
if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid post ID'
    ]);
    exit;
}

if (!isset($_POST['content']) || trim($_POST['content']) === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Comment cannot be empty'
    ]);
    exit;
}

$postId = (int)$_POST['post_id'];
$content = trim($_POST['content']);

// Check if post exists
$postCheckStmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
$postCheckStmt->bind_param("i", $postId);
$postCheckStmt->execute();
$postResult = $postCheckStmt->get_result();

if ($postResult->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Post not found'
    ]);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Add the comment
    $commentStmt = $conn->prepare("INSERT INTO comments (user_id, post_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $commentStmt->bind_param("iis", $currentUser['id'], $postId, $content);
    $commentStmt->execute();
    $commentId = $conn->insert_id;
    
    // Get the new comment for returning
    $newCommentStmt = $conn->prepare("
        SELECT 
            c.id, c.user_id, c.post_id, c.content, c.created_at,
            u.username, IFNULL(u.profile_picture, 'assets/images/default_profile.png') as profile_picture
        FROM 
            comments c
        JOIN 
            users u ON c.user_id = u.id
        WHERE 
            c.id = ?
    ");
    $newCommentStmt->bind_param("i", $commentId);
    $newCommentStmt->execute();
    $commentResult = $newCommentStmt->get_result();
    $newComment = $commentResult->fetch_assoc();
    
    // Format the date for display
    $newComment['created_at'] = date('M d, Y H:i', strtotime($newComment['created_at']));
    
    // Update the comment count on the post
    $updateCountStmt = $conn->prepare("UPDATE posts SET comment_count = comment_count + 1 WHERE id = ?");
    $updateCountStmt->bind_param("i", $postId);
    $updateCountStmt->execute();
    
    // Get the new comment count
    $countStmt = $conn->prepare("SELECT comment_count FROM posts WHERE id = ?");
    $countStmt->bind_param("i", $postId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $commentCount = $countResult->fetch_assoc()['comment_count'];
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'comment' => $newComment,
        'comment_count' => $commentCount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}