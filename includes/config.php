<?php
require_once 'session.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost:3306');
define('DB_USER', 'qxpanuto_halo');
define('DB_PASS', 'p@bS_WR8rcsf2AX');
define('DB_NAME', 'qxpanuto_mid');

// Site configuration
define('SITE_NAME', 'Social Media Platform');
define('SITE_URL', 'midterm.talksy.biz.id');
define('UPLOAD_PATH', 'assets/uploads/');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Tambahkan ini di awal file config.php
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan timezone Anda

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>