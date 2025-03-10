<?php
// forgot_password.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to home if logged in
if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$errors = [];
$email = '';
$success = false;
$resetLink = ''; 

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid form submission.');
        header('Location: forgot_password.php');
        exit;
    }
    
    $email = $_POST['email'] ?? '';
    
    // Validate email
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // If no errors, generate reset token
    if (empty($errors)) {
        $result = generatePasswordResetToken($email);
        
        if ($result['success']) {
            $success = true;
         
            $resetLink = $result['reset_link'];
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
                    <h3 class="mb-0">Forgot Password</h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h4>Reset Instructions Sent</h4>
                            <p>We've sent password reset instructions to your email address. Please check your inbox and follow the instructions to reset your password.</p>
                            
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <p class="mb-4">Enter your email address below and we'll send you instructions to reset your password.</p>
                        
                        <form method="post" action="">
                            <?php echo csrfTokenField(); ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Send Reset Instructions</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Remember your password? <a href="login.php">Login here</a></p>
                    <p class="mt-2">Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'views/footer.php'; ?>
