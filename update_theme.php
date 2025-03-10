<?php
// update_theme.php - AJAX handler for theme updates
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Only process AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    // Check if user is logged in
    if (!isLoggedIn()) {
        $response['message'] = 'You must be logged in to update preferences';
        echo json_encode($response);
        exit;
    }
    
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $response['message'] = 'Invalid form submission';
        echo json_encode($response);
        exit;
    }
    
    $theme = $_POST['theme'] ?? 'light';
    
    // Validate theme
    if (!in_array($theme, ['light', 'dark'])) {
        $response['message'] = 'Invalid theme';
        echo json_encode($response);
        exit;
    }
    
    // Get current preferences
    $currentUser = getCurrentUser();
    $userPreferences = getUserPreferences($currentUser['id']);
    
    // Update theme
    $userPreferences['theme'] = $theme;
    
    // Save preferences
    $result = updateUserPreferences($currentUser['id'], $userPreferences);
    
    $response = $result;
    echo json_encode($response);
    exit;
} else {
    // Redirect to home if accessed directly
    header('Location: home.php');
    exit;
}
?>