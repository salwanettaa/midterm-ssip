<?php
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Check if logged in
if (!isLoggedIn()) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

// Get current user
$currentUser = getCurrentUser();

// Get POST data
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, TRUE);

if (empty($input["post_id"]) || empty($input["content_type"]) || empty($input["preference"])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$post_id = (int)$input["post_id"];
$content_type = htmlspecialchars($input["content_type"]);
$preference = htmlspecialchars($input["preference"]);

// Create content_preferences table if it doesn't exist
global $conn, $db;

if (isset($conn)) {
    $conn->query("CREATE TABLE IF NOT EXISTS content_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content_type VARCHAR(50) NOT NULL,
        preference VARCHAR(20) NOT NULL,
        reference_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $stmt = $conn->prepare("INSERT INTO content_preferences (user_id, content_type, preference, reference_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $currentUser["id"], $content_type, $preference, $post_id);
    $success = $stmt->execute();
    $stmt->close();
    
    // If preference is "less", also add to ignored posts
    if ($preference == "less") {
        ignorePost($currentUser["id"], $post_id);
    }
} else if (isset($db)) {
    $db->query("CREATE TABLE IF NOT EXISTS content_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content_type VARCHAR(50) NOT NULL,
        preference VARCHAR(20) NOT NULL,
        reference_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $success = $db->query("INSERT INTO content_preferences (user_id, content_type, preference, reference_id) VALUES (?, ?, ?, ?)", 
                        [$currentUser["id"], $content_type, $preference, $post_id]);
    
    // If preference is "less", also add to ignored posts
    if ($preference == "less") {
        ignorePost($currentUser["id"], $post_id);
    }
}

echo json_encode(["success" => true, "message" => "Preference updated successfully"]);
