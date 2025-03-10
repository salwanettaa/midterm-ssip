<?php
// Start the session
session_start();

// Output session data
echo "<h1>Session Data</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check login status
echo "<h2>Login Status</h2>";
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    echo "Logged in as user ID: " . $_SESSION['user_id'];
} else {
    echo "NOT logged in (no user_id in session)";
}

// Force login for testing
echo "<h2>Force Login</h2>";
echo "<p>Click the link below to force set a test user ID in your session:</p>";
echo "<a href='session_check.php?force_login=1'>Force login as user ID 1</a>";

// Handle force login
if (isset($_GET['force_login']) && $_GET['force_login'] == 1) {
    $_SESSION['user_id'] = 1;
    echo "<p>Session updated! User ID set to 1.</p>";
    echo "<p><a href='session_check.php'>Refresh to see changes</a></p>";
}
?>