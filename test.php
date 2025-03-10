<?php
// test.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a log file for debugging
file_put_contents('debug.log', date('[Y-m-d H:i:s] ') . "Test file accessed\n", FILE_APPEND);
file_put_contents('debug.log', date('[Y-m-d H:i:s] ') . "Session data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Set JSON response headers
header('Content-Type: application/json');

// Return a simple response
echo json_encode([
    'status' => 'success',
    'message' => 'Test successful',
    'time' => date('Y-m-d H:i:s'),
    'session_active' => session_status() === PHP_SESSION_ACTIVE,
    'session_id' => session_id(),
    'session_data' => $_SESSION
]);
exit;