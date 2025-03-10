<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get current user safely
$currentUser = getCurrentUser();
if (!$currentUser) {
    // Redirect or handle the case where user data can't be retrieved
    header('Location: login.php');
    exit;
}

$userId = $currentUser['id'];

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

include_once 'views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Left sidebar -->
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; ?>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <h1 class="mb-4">Posts You've Liked</h1>
            
            <?php if (empty($likedPosts)): ?>
                <div class="alert alert-info">
                    You haven't liked any posts yet. Start exploring and like some posts!
                </div>
            <?php else: ?>
                <?php foreach ($likedPosts as $post): ?>
                    <div class="card mb-3 post-card" id="post-<?php echo $post['id'] ?? ''; ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($post['profile_picture'] ?? 'assets/images/default_profile.png'); ?>" 
                                     class="rounded-circle me-2" width="32" height="32" alt="Profile picture">
                                <a href="profile.php?id=<?php echo $post['user_id'] ?? ''; ?>" class="fw-bold">
                                    <?php echo htmlspecialchars($post['username'] ?? 'Unknown User'); ?>
                                </a>
                            </div>
                            <small class="text-muted">
                                Liked on <?php echo date('M d, Y H:i', strtotime($post['liked_at'] ?? 'now')); ?>
                            </small>
                        </div>
                        
                        <div class="card-body">
                            <?php if (!empty($post['content'])): ?>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'] ?? '')); ?></p>
                            <?php endif; ?>
                            
                            <?php if (isset($post['media_type']) && $post['media_type'] == 'image'): ?>
                                <img src="<?php echo htmlspecialchars($post['media_path'] ?? ''); ?>" 
                                     class="img-fluid rounded mb-3" alt="Post image">
                            <?php elseif (isset($post['media_type']) && $post['media_type'] == 'video'): ?>
                                <video class="img-fluid rounded mb-3" controls>
                                    <source src="<?php echo htmlspecialchars($post['media_path'] ?? ''); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button class="btn btn-sm btn-primary like-btn active" data-post-id="<?php echo $post['id'] ?? ''; ?>">
                                        <i class="bi bi-hand-thumbs-up"></i> 
                                        Like (<span class="like-count"><?php echo $post['like_count'] ?? 0; ?></span>)
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary comment-btn" data-post-id="<?php echo $post['id'] ?? ''; ?>">
                                        <i class="bi bi-chat"></i>
                                        Comments (<span class="comment-count"><?php echo $post['comment_count'] ?? 0; ?></span>)
                                    </button>
                                </div>
                                <div>
                                    <a href="post.php?id=<?php echo $post['id'] ?? ''; ?>" class="btn btn-sm btn-outline-primary">
                                        View Post
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'views/footer.php'; ?>