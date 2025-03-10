<?php
// login.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to home if logged in
if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$errors = [];
$email = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid form submission.');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If no errors, try to login
    if (empty($errors)) {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // Redirect to home
            header('Location: home.php');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

include_once 'views/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Login</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                    <?php echo csrfTokenField(); ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Forgot your password? <a href="forgot_password.php">Reset here</a></p>
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                        <p>Or <a href="index.php">continue as guest</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'views/footer.php'; ?>

