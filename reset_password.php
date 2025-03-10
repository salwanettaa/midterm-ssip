<?php
// reset_password.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to home if logged in
if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$errors = [];
$success = false;
$token = $_GET['token'] ?? '';
$userData = null;

// Validate token
if (empty($token)) {
    header('Location: forgot_password.php');
    exit;
}

$validation = validateResetToken($token);
if (!$validation['success']) {
    $errors[] = $validation['message'];
} else {
    $userData = $validation['user'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($errors)) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid form submission.');
        header('Location: reset_password.php?token=' . urlencode($token));
        exit;
    }
    
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // If no errors, reset password
    if (empty($errors)) {
        $result = resetPassword($token, $password);
        
        if ($result['success']) {
            $success = true;
            setFlashMessage('success', 'Your password has been reset successfully. You can now login with your new password.');
        } else {
            $errors[] = $result['message'];
        }
    }
}

include_once 'views/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Reset Password</h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h4>Password Reset Successful!</h4>
                            <p>Your password has been reset successfully. You can now <a href="login.php">login</a> with your new password.</p>
                        </div>
                    <?php elseif (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="text-center">
                            <a href="forgot_password.php" class="btn btn-primary">Request New Reset Link</a>
                        </div>
                    <?php else: ?>
                        <p class="mb-4">Hi <?php echo htmlspecialchars($userData['username']); ?>, please enter your new password below.</p>
                        
                        <form method="post" action="">
                            <?php echo csrfTokenField(); ?>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Password must be at least 8 characters long and include uppercase, lowercase, and numbers.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Remember your password? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'views/footer.php'; ?>