<?php
// Ensure user authentication status is determined
$isGuest = !isLoggedIn();
$currentUser = null;

if (!$isGuest) {
    $currentUser = getCurrentUser();
}
?>

<link href="assets/css/style.css" rel="stylesheet">


<!-- Library React (tambahkan ini di header atau sebelum penutup body) -->
<script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>

<script src="assets/js/floating-posts.js"></script>

<div class="card mb-3">
    <div class="card-header">
        <?php if ($isGuest): ?>
            <h5>Welcome, Guest!</h5>
        <?php else: ?>
            <h5>Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</h5>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="d-grid gap-2">
            <a href="home.php" class="btn btn-outline-primary">Home</a>
            <?php if (!$isGuest): ?>
                <a href="profile.php?id=<?php echo $currentUser['id']; ?>" class="btn btn-outline-primary">My Profile</a>
            <?php endif; ?>
            <a href="trending.php" class="btn btn-outline-primary">Trending</a>
            <a href="liked_post.php" class="btn btn-outline-primary">Your Likes</a>
            <?php if (!$isGuest): ?>
                <a href="recommendations.php" class="btn btn-outline-primary">For You</a>
                <a href="logout.php" class="btn btn-outline-danger mt-3">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-success mt-3">Login</a>
                <a href="register.php" class="btn btn-outline-info">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
  console.log('Checking if floating-posts.js loaded correctly...');
  if (document.getElementById('floating-posts-overlay')) {
    console.log('Floating posts container found!');
  } else {
    console.log('Floating posts container NOT found!');
  }
</script>
<!-- Container untuk React -->
<div id="anon-posts-root"></div>





