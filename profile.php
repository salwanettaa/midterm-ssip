<?php
// profile.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in or guest
$isGuest = !isLoggedIn();
$currentUser = null;
$ignoredPosts = [];

if ($isGuest) {
    $guestPreferences = getGuestPreferences();
    $ignoredPosts = $guestPreferences['ignored_posts'] ?? [];
} else {
    $currentUser = getCurrentUser();
    $ignoredPosts = array_column(getIgnoredPosts($currentUser['id']), 'post_id');
}

// Get profile user ID
$profile_id = $_GET['id'] ?? ($isGuest ? 0 : $currentUser['id']);

// Get profile user data
$profileUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$profile_id]);

if (!$profileUser) {
    // Profile not found, redirect to home
    header('Location: home.php');
    exit;
}

// Check if it's the current user's profile
$isOwnProfile = !$isGuest && $currentUser['id'] == $profile_id;

// Handle profile update
$updateMessage = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $isOwnProfile) {
    if (isset($_POST['update_profile'])) {
        $bio = $_POST['bio'] ?? '';
        
        // Update bio
        $db->query("UPDATE users SET bio = ? WHERE id = ?", [$bio, $currentUser['id']]);
        
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            $file = $_FILES['profile_picture'];
            $upload = uploadMedia($file, 'image');
            
            if ($upload['success']) {
                $db->query("UPDATE users SET profile_picture = ? WHERE id = ?", [$upload['path'], $currentUser['id']]);
                $updateMessage = 'Profile updated successfully!';
                // Refresh to show changes
                header('Location: profile.php?id=' . $currentUser['id'] . '&updated=1');
                exit;
            } else {
                $updateMessage = $upload['message'];
            }
        } else {
            $updateMessage = 'Profile updated successfully!';
            // Refresh to show changes
            header('Location: profile.php?id=' . $currentUser['id'] . '&updated=1');
            exit;
        }
    }
}

// Show update message if redirected after update
if (isset($_GET['updated']) && $_GET['updated'] == 1) {
    $updateMessage = 'Profile updated successfully!';
}

// Get user posts
$posts = getUserPosts($profile_id, 10, 0);

// Get user stats
$stats = [
    'posts_count' => $db->rowCount("SELECT * FROM posts WHERE user_id = ?", [$profile_id]),
    'likes_received' => $db->rowCount("SELECT * FROM likes WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)", [$profile_id]),
    'comments_received' => $db->rowCount("SELECT * FROM comments WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)", [$profile_id]),
    'joined_date' => date('F Y', strtotime($profileUser['created_at']))
];

include_once 'views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Left sidebar -->
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; // Assuming you have a sidebar include ?>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <!-- Profile card -->
            <div class="card mb-4">
                <div class="card-body">
                    <?php if (!empty($updateMessage)): ?>
                        <div class="alert alert-success"><?php echo $updateMessage; ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img src="<?php echo $profileUser['profile_picture']; ?>" class="img-fluid rounded-circle mb-3" alt="Profile picture" style="max-width: 150px;">
                            
                            <?php if ($isOwnProfile): ?>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    Edit Profile
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-9">
                            <h3><?php echo htmlspecialchars($profileUser['username']); ?></h3>
                            
                            <div class="mb-3">
                                <?php if (!empty($profileUser['bio'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars($profileUser['bio'])); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">No bio provided.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="stat-box text-center border rounded p-2">
                                        <h5><?php echo $stats['posts_count']; ?></h5>
                                        <small class="text-muted">Posts</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="stat-box text-center border rounded p-2">
                                        <h5><?php echo $stats['likes_received']; ?></h5>
                                        <small class="text-muted">Likes</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="stat-box text-center border rounded p-2">
                                        <h5><?php echo $stats['comments_received']; ?></h5>
                                        <small class="text-muted">Comments</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="stat-box text-center border rounded p-2">
                                        <h5><?php echo $stats['joined_date']; ?></h5>
                                        <small class="text-muted">Joined</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User posts -->
            <h4 class="mb-3"><?php echo $isOwnProfile ? 'Your Posts' : htmlspecialchars($profileUser['username']) . "'s Posts"; ?></h4>
            
            <?php if (empty($posts)): ?>
                <div class="alert alert-info">No posts to display.</div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php 
                    // Skip ignored posts
                    if (in_array($post['id'], $ignoredPosts)) continue; 
                    ?>
                    <div class="card mb-3 post-card" id="post-<?php echo $post['id']; ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <img src="<?php echo $profileUser['profile_picture']; ?>" class="rounded-circle me-2" width="32" height="32" alt="Profile picture">
                                <a href="profile.php?id=<?php echo $profileUser['id']; ?>"><?php echo htmlspecialchars($profileUser['username']); ?></a>
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
                                <?php if ($isOwnProfile): ?>
                                    <div>
                                        <button class="btn btn-sm btn-outline-danger delete-post-btn" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div>
                                        <button class="btn btn-sm btn-outline-danger ignore-btn" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="bi bi-eye-slash"></i> Hide
                                        </button>
                                    </div>
                                <?php endif; ?>
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
                
                <!-- Load more button -->
                <div class="d-grid gap-2 mb-4">
                    <button class="btn btn-outline-primary load-more-btn" data-user-id="<?php echo $profile_id; ?>">Load More</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<?php if ($isOwnProfile): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input class="form-control" type="file" id="profile_picture" name="profile_picture" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($profileUser['bio'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include_once 'views/footer.php'; ?>