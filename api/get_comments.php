<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Check if post_id was provided
if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid post ID'
    ]);
    exit;
}

$postId = (int)$_GET['post_id'];

// Get comments for the post
$stmt = $conn->prepare("
    SELECT 
        c.id, c.user_id, c.post_id, c.content, c.created_at,
        u.username, IFNULL(u.profile_picture, 'assets/images/default_profile.png') as profile_picture
    FROM 
        comments c
    JOIN 
        users u ON c.user_id = u.id
    WHERE 
        c.post_id = ?
    ORDER BY 
        c.created_at ASC
");

$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    // Format the date for display
    $row['created_at'] = date('M d, Y H:i', strtotime($row['created_at']));
    
    // Escape HTML in content
    $row['content'] = htmlspecialchars($row['content']);
    
    // Add to comments array
    $comments[] = $row;
}

echo json_encode([
    'success' => true,
    'comments' => $comments
]);