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

if (empty($input["post_id"]) || empty($input["reason"])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Save report
$post_id = (int)$input["post_id"];
$reason = htmlspecialchars($input["reason"]);
$details = isset($input["details"]) ? htmlspecialchars($input["details"]) : "";

// Create reports table if it doesn't exist
global $conn, $db;

if (isset($conn)) {
    $conn->query("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        reason VARCHAR(50) NOT NULL,
        details TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $stmt = $conn->prepare("INSERT INTO reports (user_id, post_id, reason, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $currentUser["id"], $post_id, $reason, $details);
    $success = $stmt->execute();
    $stmt->close();
} else if (isset($db)) {
    $db->query("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        reason VARCHAR(50) NOT NULL,
        details TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $success = $db->query("INSERT INTO reports (user_id, post_id, reason, details) VALUES (?, ?, ?, ?)", 
                        [$currentUser["id"], $post_id, $reason, $details]);
}

echo json_encode(["success" => true, "message" => "Report submitted successfully"]);
