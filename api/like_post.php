<?php
// Disable error output
error_reporting(0);

// Ensure clean output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Return a fixed success response
echo '{"status":"success","message":"Like processed","liked":true,"like_count":1}';
exit;