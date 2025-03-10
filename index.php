<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to home if logged in
if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

// Check for guest cookie
$guestPreferences = getGuestPreferences();

// Handle guest mode
if (isset($_POST['guest_mode'])) {
    // Set default preferences
    setGuestCookie([
        'theme' => isset($_POST['theme']) ? $_POST['theme'] : 'light',
        'language' => isset($_POST['language']) ? $_POST['language'] : 'en',
        'notification' => 'off',
        'ignored_posts' => []
    ]);
    
    // Redirect to home as guest
    header('Location: home.php?mode=guest');
    exit;
}

include_once 'views/header.php';
?>

  <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

     <!-- Custom Design CSS -->
     <link rel="stylesheet" href="assets/css/custom-design.css">

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center"><?php echo SITE_NAME; ?></h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <p>Please login or register to continue</p>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="login.php" class="btn btn-primary btn-lg">Login</a>
                        <a href="register.php" class="btn btn-secondary btn-lg">Register</a>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center mb-3">
                        <p>Or continue as guest</p>
                    </div>
                    
                    <form method="post" action="">
                        <select class="form-select" name="theme" id="theme">
                            <option value="light" <?php echo ($guestPreferences['theme'] == 'light') ? 'selected' : ''; ?>>Light</option>
                            <option value="dark" <?php echo ($guestPreferences['theme'] == 'dark') ? 'selected' : ''; ?>>Dark</option>
                        </select>
                        
                        
                        <div class="d-grid">
                            <button type="submit" name="guest_mode" class="btn btn-info btn-lg">Continue as Guest</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'views/footer.php'; ?>



