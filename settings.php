<?php
// settings.php - User settings page
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Initialize with default values first to ensure no null references
$userPreferences = [
    'theme' => 'light',
    'notification' => 'on',
    'language' => 'en'
];

// Get current user and check for null
$currentUser = getCurrentUser();
if (is_array($currentUser) && isset($currentUser['id'])) {
    // Try to get preferences, but keep defaults if not found
    $fetchedPreferences = getUserPreferences($currentUser['id']);
    if (is_array($fetchedPreferences)) {
        // Merge with defaults to ensure all keys exist
        $userPreferences = array_merge($userPreferences, $fetchedPreferences);
    }
}

// Initialize success and error messages
$profileSuccess = false;
$profileErrors = [];
$passwordSuccess = false;
$passwordErrors = [];
$preferencesSuccess = false;
$preferencesErrors = [];

// Handle profile update
if (isset($_POST['update_profile'])) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid form submission.');
        header('Location: settings.php');
        exit;
    }
    
    // Get username from form
    $username = $_POST['username'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $location = $_POST['location'] ?? '';
    $website = $_POST['website'] ?? '';
    
    // Validate username
    if (empty($username)) {
        $profileErrors[] = 'Username is required';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $profileErrors[] = 'Username must be between 3 and 20 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $profileErrors[] = 'Username can only contain letters, numbers, and underscores';
    } elseif ($username !== $currentUser['username'] && isUsernameTaken($username)) {
        $profileErrors[] = 'This username is already taken';
    }
    
    // Validate website URL if provided
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $profileErrors[] = 'Invalid website URL';
    }
    
    // Handle profile picture upload
    $profile_picture = $currentUser['profile_picture'] ?? '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
        $file = $_FILES['profile_picture'];
        $upload = uploadMedia($file, 'image');
        
        if ($upload['success']) {
            $profile_picture = $upload['path'];
        } else {
            $profileErrors[] = $upload['message'];
        }
    }
    
    // If no errors, update profile
    if (empty($profileErrors)) {
        $updateData = [
            'username' => $username,
            'bio' => $bio,
            'location' => $location,
            'website' => $website,
            'profile_picture' => $profile_picture
        ];
        
        $result = updateUserProfile($currentUser['id'], $updateData);
        
        if ($result['success']) {
            $profileSuccess = true;
        } else {
            $profileErrors[] = $result['message'];
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid form submission.');
        header('Location: settings.php');
        exit;
    }
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($current_password)) {
        $passwordErrors[] = 'Current password is required';
    }
    
    if (empty($new_password)) {
        $passwordErrors[] = 'New password is required';
    } elseif (strlen($new_password) < 8) {
        $passwordErrors[] = 'New password must be at least 8 characters long';
    } elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $passwordErrors[] = 'New password must contain at least one uppercase letter, one lowercase letter, and one number';
    }
    
    if ($new_password !== $confirm_password) {
        $passwordErrors[] = 'Passwords do not match';
    }
    
    // If no errors, change password
    if (empty($passwordErrors)) {
        $result = changeUserPassword($currentUser['id'], $current_password, $new_password);
        
        if ($result['success']) {
            $passwordSuccess = true;
        } else {
            $passwordErrors[] = $result['message'];
        }
    }
}

// Handle preferences update
if (isset($_POST['update_preferences'])) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid form submission.');
        header('Location: settings.php');
        exit;
    }
    
    $theme = $_POST['theme'] ?? 'light';
    $notification = $_POST['notification'] ?? 'on';
    $language = $_POST['language'] ?? 'en';
    
    // Validate theme
    if (!in_array($theme, ['light', 'dark'])) {
        $preferencesErrors[] = 'Invalid theme';
    }
    
    // Validate notification
    if (!in_array($notification, ['on', 'off'])) {
        $preferencesErrors[] = 'Invalid notification setting';
    }
    
    // Validate language
    if (!in_array($language, ['en', 'es', 'fr'])) {
        $preferencesErrors[] = 'Invalid language';
    }
    
    // If no errors, update preferences
    if (empty($preferencesErrors)) {
        $preferences = [
            'theme' => $theme,
            'notification' => $notification,
            'language' => $language
        ];
        
        $result = updateUserPreferences($currentUser['id'], $preferences);
        
        if ($result['success']) {
            $preferencesSuccess = true;
            
            // Refresh the page to apply new theme
            header('Location: settings.php?updated=1');
            exit;
        } else {
            $preferencesErrors[] = $result['message'];
        }
    }
}

// Show update message if redirected after update
if (isset($_GET['updated']) && $_GET['updated'] == 1) {
    setFlashMessage('success', 'Settings updated successfully!');
}

include_once 'views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Left sidebar -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Account Settings</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="#profile-settings" class="list-group-item list-group-item-action active" data-bs-toggle="list">Profile Information</a>
                        <a href="#password-settings" class="list-group-item list-group-item-action" data-bs-toggle="list">Change Password</a>
                        <a href="#preferences-settings" class="list-group-item list-group-item-action" data-bs-toggle="list">Preferences</a>
                        <a href="profile.php?id=<?php echo isset($currentUser['id']) ? $currentUser['id'] : ''; ?>" class="list-group-item list-group-item-action">View Public Profile</a>
                        <a href="home.php" class="list-group-item list-group-item-action">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Profile Settings -->
                <div class="tab-pane fade show active" id="profile-settings">
                    <div class="card">
                        <div class="card-header">
                            <h5>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($profileSuccess): ?>
                                <div class="alert alert-success">Profile updated successfully!</div>
                            <?php endif; ?>
                            
                            <?php if (!empty($profileErrors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($profileErrors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="" enctype="multipart/form-data">
                                <?php echo csrfTokenField(); ?>
                                
                                <div class="mb-3 text-center">
                                    <img src="<?php echo isset($currentUser['profile_picture']) ? $currentUser['profile_picture'] : 'assets/img/default-avatar.png'; ?>" class="img-fluid rounded-circle mb-3" alt="Profile picture" style="max-width: 150px;">
                                    <div class="mb-3">
                                        <label for="profile_picture" class="form-label">Profile Picture</label>
                                        <input class="form-control" type="file" id="profile_picture" name="profile_picture" accept="image/*">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($currentUser['username']) ? htmlspecialchars($currentUser['username']) : ''; ?>">
                                    <div class="form-text">Choose a unique username between 3-20 characters using only letters, numbers, and underscores.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo isset($currentUser['email']) ? htmlspecialchars($currentUser['email']) : ''; ?>" disabled>
                                    <div class="form-text">Email cannot be changed.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo isset($currentUser['bio']) ? htmlspecialchars($currentUser['bio']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?php echo isset($currentUser['location']) ? htmlspecialchars($currentUser['location']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo isset($currentUser['website']) ? htmlspecialchars($currentUser['website']) : ''; ?>">
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="update_profile" class="btn btn-primary">Save Profile</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Password Settings -->
                <div class="tab-pane fade" id="password-settings">
                    <div class="card">
                        <div class="card-header">
                            <h5>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($passwordSuccess): ?>
                                <div class="alert alert-success">Password changed successfully!</div>
                            <?php endif; ?>
                            
                            <?php if (!empty($passwordErrors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($passwordErrors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="">
                                <?php echo csrfTokenField(); ?>
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">Password must be at least 8 characters long and include uppercase, lowercase, and numbers.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Preferences Settings -->
                <div class="tab-pane fade" id="preferences-settings">
                    <div class="card">
                        <div class="card-header">
                            <h5>Preferences</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($preferencesSuccess): ?>
                                <div class="alert alert-success">Preferences updated successfully!</div>
                            <?php endif; ?>
                            
                            <?php if (!empty($preferencesErrors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($preferencesErrors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="">
                                <?php echo csrfTokenField(); ?>
                                
                                <div class="mb-3">
                                    <label for="theme" class="form-label">Theme</label>
                                    <select class="form-select" id="theme" name="theme">
                                        <option value="light" <?php echo ($userPreferences['theme'] === 'light') ? 'selected' : ''; ?>>Light</option>
                                        <option value="dark" <?php echo ($userPreferences['theme'] === 'dark') ? 'selected' : ''; ?>>Dark</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notification" class="form-label">Notifications</label>
                                    <select class="form-select" id="notification" name="notification">
                                        <option value="on" <?php echo ($userPreferences['notification'] === 'on') ? 'selected' : ''; ?>>On</option>
                                        <option value="off" <?php echo ($userPreferences['notification'] === 'off') ? 'selected' : ''; ?>>Off</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="language" class="form-label">Language</label>
                                    <select class="form-select" id="language" name="language">
                                        <option value="en" <?php echo ($userPreferences['language'] === 'en') ? 'selected' : ''; ?>>English</option>
                                        <option value="es" <?php echo ($userPreferences['language'] === 'es') ? 'selected' : ''; ?>>Spanish</option>
                                        <option value="fr" <?php echo ($userPreferences['language'] === 'fr') ? 'selected' : ''; ?>>French</option>
                                    </select>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="update_preferences" class="btn btn-primary">Save Preferences</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Check if a username is already taken by another user
 * 
 * @param string $username The username to check
 * @return bool True if username is taken, false otherwise
 */
function isUsernameTaken($username) {
    global $db;
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $_SESSION['user_id'] ?? 0]);
    $count = $stmt->fetchColumn();
    
    return $count > 0;
}
?>