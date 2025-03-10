<?php
// trending.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in or guest
$isGuest = !isLoggedIn();
$currentUser = null;
$ignoredPosts = []; // Initialize as an empty array by default'
$posts = [];

if ($isGuest) {
    // For guests, try to get preferences safely
    $guestPreferences = getGuestPreferences() ?: [];
    $ignoredPosts = isset($guestPreferences['ignored_posts']) && is_array($guestPreferences['ignored_posts']) 
        ? $guestPreferences['ignored_posts'] 
        : [];
} else {
    // For logged-in users, get current user and ignored posts
    $currentUser = getCurrentUser();
    
    if ($currentUser) {
        // Safely get ignored posts
        $userIgnoredPosts = getIgnoredPosts($currentUser['id']);
        $ignoredPosts = is_array($userIgnoredPosts) 
            ? array_column($userIgnoredPosts, 'post_id') 
            : [];
    }
}

// Modify your post display loop to use safe checking
$displayedPosts = [];
foreach ($posts as $post) {
    // Ensure $post['id'] exists and $ignoredPosts is an array before checking
    if (isset($post['id']) && is_array($ignoredPosts) && !in_array($post['id'], $ignoredPosts)) {
        $displayedPosts[] = $post;
    }
}

// Replace original $posts with filtered posts
$posts = $displayedPosts;

// Fetch liked posts with error handling
try {
    // Prepare a query to get all posts the user has liked, ordered by the like timestamp
    $stmt = $conn->prepare("
        SELECT p.*, 
               u.username, 
               u.profile_picture, 
               l.created_at AS liked_at,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN likes l ON p.id = l.post_id
        JOIN users u ON p.user_id = u.id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $likedPosts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    // Log the error
    error_log('Error fetching liked posts: ' . $e->getMessage());
    $likedPosts = []; // Ensure $likedPosts is always an array
}
// Get trending posts
$posts = getTrendingPosts(20);

include_once 'views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Left sidebar -->
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; // Assuming you have a sidebar include ?>
        </div>
        
        <!-- Main content -->
        <div class="col-md-6">
            <!-- Trending posts feed -->
            <h4 class="mb-3">Trending Posts</h4>
            
            <?php if (empty($posts)): ?>
                <div class="alert alert-info">No trending posts to display.</div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php 
                    // Skip ignored posts
                    if (in_array($post['id'], $ignoredPosts)) continue; 
                    ?>
                    <div class="card mb-3 post-card" id="post-<?php echo $post['id']; ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <img src="<?php echo $post['profile_picture']; ?>" class="rounded-circle me-2" width="32" height="32" alt="Profile picture">
                                <a href="profile.php?id=<?php echo $post['user_id']; ?>"><?php echo htmlspecialchars($post['username']); ?></a>
                            </div>
                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></small>
                        </div>
                        
                        <div class="card-body">
                            <?php if (!empty($post['content'])): ?>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($post['media_type'] == 'image'): ?>
                                <img src="<?php echo $post['media_path']; ?>" class="img-fluid rounded mb-3" alt="Post image">
                            <?php elseif ($post['media_type'] == 'video'): ?>
                                <video class="img-fluid rounded mb-3" controls>
                                    <source src="<?php echo $post['media_path']; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button class="btn btn-sm btn-outline-primary like-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="bi bi-hand-thumbs-up"></i> 
                                        Like (<span class="like-count"><?php echo $post['like_count']; ?></span>)
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="bi bi-chat"></i>
                                        Comments (<span class="comment-count"><?php echo $post['comment_count']; ?></span>)
                                    </button>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-danger ignore-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="bi bi-eye-slash"></i> Hide
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Comment section (initially hidden) -->
                            <div class="comment-section mt-3" style="display: none;" id="comments-<?php echo $post['id']; ?>">
                                <hr>
                                <h6>Comments</h6>
                                
                                <div class="comments-container">
                                    <!-- Comments will be loaded here -->
                                </div>
                                
                                <?php if (!$isGuest): ?>
                                    <form class="comment-form mt-2" data-post-id="<?php echo $post['id']; ?>">
                                        <div class="input-group">
                                            <input type="text" class="form-control comment-input" placeholder="Write a comment...">
                                            <button class="btn btn-primary" type="submit">Post</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info mt-2">
                                        <small>Please <a href="login.php">login</a> to comment</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Right sidebar -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Trending Tags</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-1">
                        <a href="#" class="badge bg-primary text-decoration-none">#trending</a>
                        <a href="#" class="badge bg-primary text-decoration-none">#popular</a>
                        <a href="#" class="badge bg-primary text-decoration-none">#viral</a>
                        <a href="#" class="badge bg-primary text-decoration-none">#news</a>
                        <a href="#" class="badge bg-primary text-decoration-none">#tech</a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>About Trending</h5>
                </div>
                <div class="card-body">
                    <p>Trending posts are determined by the number of likes and comments they receive. The most popular content rises to the top!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'views/footer.php'; ?>

