<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Check if post_id was provided
if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid post ID'
    ]);
    exit;
}

$postId = (int)$_POST['post_id'];

// Check if user is logged in or guest
$isGuest = !isLoggedIn();

if ($isGuest) {
    // For guests, we store in cookie preferences
    $guestPreferences = getGuestPreferences() ?: [];
    
    // Initialize ignored_posts array if it doesn't exist
    if (!isset($guestPreferences['ignored_posts']) || !is_array($guestPreferences['ignored_posts'])) {
        $guestPreferences['ignored_posts'] = [];
    }
    
    // Add postId to ignored_posts if not already there
    if (!in_array($postId, $guestPreferences['ignored_posts'])) {
        $guestPreferences['ignored_posts'][] = $postId;
    }
    
    // Save updated preferences
    saveGuestPreferences($guestPreferences);
    
    echo json_encode([
        'success' => true
    ]);
} else {
    // For logged-in users, we store in database
    $currentUser = getCurrentUser();
    
    if (!$currentUser) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    // Check if already ignored
    $checkStmt = $conn->prepare("SELECT id FROM ignored_posts WHERE user_id = ? AND post_id = ?");
    $checkStmt->bind_param("ii", $currentUser['id'], $postId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        // Not ignored yet, so add to ignored posts
        $addStmt = $conn->prepare("INSERT INTO ignored_posts (user_id, post_id, created_at) VALUES (?, ?, NOW())");
        $addStmt->bind_param("ii", $currentUser['id'], $postId);
        
        if ($addStmt->execute()) {
            echo json_encode([
                'success' => true
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to ignore post'
            ]);
        }
    } else {
        // Already ignored
        echo json_encode([
            'success' => true
        ]);
    }
}