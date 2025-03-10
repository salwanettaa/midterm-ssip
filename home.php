<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent header issues
ob_start();
?>

<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';
// Check if user is logged in or guest
$isGuest = !isLoggedIn();
$currentUser = null;
$ignoredPosts = []; // Initialize as an empty array by default
$posts=[];



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

// Handle new post submission
$postMessage = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$isGuest) {
    if (isset($_POST['post_content'])) {
        $content = $_POST['post_content'];
        $media_type = 'none';
        $media_path = null;
        
        // Check if there's a file upload
        if (isset($_FILES['post_media']) && $_FILES['post_media']['size'] > 0) {
            $file = $_FILES['post_media'];
            $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Determine media type
            if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                $media_type = 'image';
            } elseif (in_array($file_type, ['mp4', 'avi', 'mov'])) {
                $media_type = 'video';
            }
            
            // Upload media
            if ($media_type != 'none') {
                $upload = uploadMedia($file, $media_type);
                if ($upload['success']) {
                    $media_path = $upload['path'];
                } else {
                    $postMessage = $upload['message'];
                }
            }
        }
        
        // Create post if content exists
        if (!empty($content) || $media_path) {
            $post_id = createPost($currentUser['id'], $content, $media_type, $media_path);
            if ($post_id) {
                $postMessage = 'Post created successfully!';
                // Refresh page to show new post
                header('Location: home.php?posted=1');
                exit;
            } else {
                $postMessage = 'Failed to create post.';
            }
        } else {
            $postMessage = 'Post content is required.';
        }
    }
}

// Get posts
$posts = getPosts(20, 0);

include_once 'views/header.php';
?>



<link href="assets/css/style.css" rel="stylesheet">
<div class="container mt-4">
    <div class="row">
        <!-- Left sidebar -->
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; // Assuming you have a sidebar include ?>
        </div>
        
        <!-- Main content -->
        <div class="col-md-6">
            <?php if (!$isGuest): ?>
                <!-- Create post card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Create a Post</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($postMessage)): ?>
                            <div class="alert alert-info"><?php echo $postMessage; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <textarea class="form-control" name="post_content" rows="3" placeholder="What's on your mind?"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="post_media" class="form-label">Add Image/Video</label>
                                <input class="form-control" type="file" id="post_media" name="post_media">
                                <div class="form-text">Supported formats: JPG, PNG, GIF, MP4, AVI, MOV</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Post</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Posts feed -->
            <h4 class="mb-3">Latest Posts</h4>
            
            <?php if (empty($posts)): ?>
                <div class="alert alert-info">No posts to display.</div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php 
                    // Skip ignored posts
                    if (in_array($post['id'], $ignoredPosts)) continue;
                    
                    // Check if current user has already liked this post
                    $liked = false;
                    if (!$isGuest) {
                        $likeStmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                        $likeStmt->bind_param("ii", $currentUser['id'], $post['id']);
                        $likeStmt->execute();
                        $likeResult = $likeStmt->get_result();
                        $liked = ($likeResult->num_rows > 0);
                        $likeStmt->close();
                    }
                    $btnClass = $liked ? 'btn-primary active' : 'btn-outline-primary';
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
                                    <button class="btn btn-sm <?php echo $btnClass; ?> like-btn" 
                                            data-post-id="<?php echo $post['id']; ?>"
                                            data-ajax-url="ajax_handler.php">
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
            
            <!-- Load more button -->
            <div class="d-grid gap-2 mb-4">
                <button class="btn btn-outline-primary load-more-btn">Load More</button>
            </div>
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
                    <h5>Suggested Users</h5>
                </div>
                <div class="card-body">
                <?php
                // Get random users from the database including their profile picture
                $sql = "SELECT id, username, profile_picture FROM users ORDER BY RAND() LIMIT 3";
                $result = $conn->query($sql);

                // Display each random user
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Set default profile picture path
                        $profilePic = "assets/images/default_profile.png";
                        
                        // If user has a profile picture, use it instead
                        if (!empty($row['profile_picture'])) {
                            $profilePic = $row['profile_picture'];
                        }
                        
                        echo '<div class="d-flex align-items-center mb-2">
                            <img src="' . $profilePic . '" class="rounded-circle me-2 border border-2" width="32" height="32" alt="Profile picture">
                            <div>
                                <a href="profile.php?id=' . $row['id'] . '" class="text-decoration-none">' . htmlspecialchars($row["username"]) . '</a>
                            </div>
                        </div>';
                    }
                } else {
                    // Fallback if no users found
                    echo '<div class="alert alert-info">No suggested users available.</div>';
                }
                ?>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include_once 'views/footer.php'; ?>