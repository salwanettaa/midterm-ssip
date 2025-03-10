<?php
// views/header.php
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME;

// Determine theme
$theme = 'light';
if (isLoggedIn()) {
    $userPrefs = getUserPreferences($_SESSION['user_id']);
    $theme = $userPrefs['theme'] ?? 'light';
} elseif (isset($_COOKIE['user_preferences'])) {
    $guestPrefs = json_decode($_COOKIE['user_preferences'], true);
    $theme = $guestPrefs['theme'] ?? 'light';
}
$currentUser = null;
$ignoredPosts = [];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/custom-design.css">
    
    <!-- Dropdown Fix Styles -->
    <style>
    /* Fix for dropdown menus */
    .dropdown-menu.force-show {
      display: block !important;
      visibility: visible !important;
      opacity: 1 !important;
      position: absolute !important;
      right: 0 !important;
      left: auto !important;
      z-index: 2000 !important;
      transform: translate3d(0px, 40px, 0px) !important;
      top: 0 !important;
      border-radius: 8px !important;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2) !important;
      background-color: white !important;
    }
    
    /* Dark theme support */
    [data-bs-theme="dark"] .dropdown-menu.force-show {
      background-color: #343a40 !important;
    }
    
    /* Fix dropdown items */
    .dropdown-menu.force-show .dropdown-item {
      padding: 8px 20px !important;
      display: block !important;
      clear: both !important;
      font-weight: 400 !important;
      color: #212529 !important;
      text-align: inherit !important;
      white-space: nowrap !important;
      background-color: transparent !important;
      border: 0 !important;
    }
    
    /* Dark theme support for items */
    [data-bs-theme="dark"] .dropdown-menu.force-show .dropdown-item {
      color: #f8f9fa !important;
    }
    
    .dropdown-menu.force-show .dropdown-item:hover {
      background-color: #f8f9fa !important;
      color: #16181b !important;
    }
    
    [data-bs-theme="dark"] .dropdown-menu.force-show .dropdown-item:hover {
      background-color: #495057 !important;
      color: #f8f9fa !important;
    }
    
    /* Make sure dropdown dividers are visible */
    .dropdown-menu.force-show .dropdown-divider {
      height: 0;
      margin: 0.5rem 0;
      overflow: hidden;
      border-top: 1px solid #e9ecef;
    }
    
    [data-bs-theme="dark"] .dropdown-menu.force-show .dropdown-divider {
      border-top-color: #495057;
    }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="home.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php $user = getCurrentUser(); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo $user['profile_picture']; ?>" class="rounded-circle me-1" width="24" height="24" alt="Profile">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="profile.php?id=<?php echo $user['id']; ?>">My Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <div class="container mt-3">
        <?php echo displayFlashMessage(); ?>
    </div>

    <div class="container mt-4">
        <!-- Main content will go here -->

    <!-- jQuery (placed before Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- React (if needed) -->
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
    
    <!-- Single unified dropdown fix script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded - implementing dropdown fix');
        
        // Only target navbar dropdown
        const navbarDropdownToggle = document.querySelector('.navbar .dropdown-toggle');
        
        if (navbarDropdownToggle) {
            console.log('Found navbar dropdown, applying custom handling');
            
            // Add click handler
            navbarDropdownToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const menu = this.nextElementSibling;
                console.log('Toggling dropdown menu', menu);
                
                // Toggle custom show class
                menu.classList.toggle('force-show');
            });
            
            // Close when clicking outside
            document.addEventListener('click', function(e) {
                const menu = document.querySelector('.navbar .dropdown-menu.force-show');
                if (menu && !menu.contains(e.target) && !navbarDropdownToggle.contains(e.target)) {
                    menu.classList.remove('force-show');
                }
            });
        } else {
            console.log('Navbar dropdown not found!');
            console.log('Available dropdowns:', document.querySelectorAll('.dropdown-toggle').length);
        }
        
        // Leave other Bootstrap components like tabs working normally
        console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    });
    </script>
</body>
</html>