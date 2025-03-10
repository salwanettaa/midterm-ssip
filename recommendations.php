<?php
/**
 * Enhanced Recommendations System
 */
 ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuration and Database Connection
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}



// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}




if (!function_exists('createAnonymousNote')) {
    /**
     * Membuat catatan anonim baru
     * @param string $content Isi catatan
     * @param int $max_length Panjang maksimal catatan (default: 500 karakter)
     * @return int|bool ID catatan jika berhasil, false jika gagal
     */
    function createAnonymousNote($content, $max_length = 500) {
        global $conn;
        
        // Bersihkan dan validasi konten
        $content = trim($content);
        
        // Periksa panjang konten
        if (empty($content)) {
            return false;
        }
        
        // Potong konten jika melebihi panjang maksimal
        $content = mb_substr($content, 0, $max_length);
        
        // Hindari XSS dengan membersihkan input
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        
        // Pilih warna acak untuk catatan dengan palet warna yang aman
        $colors = [
            '#FFC3A0', // Soft Orange
            '#A0E7E5', // Soft Teal
            '#B4F8C8', // Soft Green
            '#FBE7C6', // Soft Yellow
            '#E7DBFF', // Soft Purple
            '#FFD1DC', // Soft Pink
            '#C7CEEA', // Soft Blue
            '#FFDAB9'  // Peach
        ];
        $color = $colors[array_rand($colors)];
        
        // Siapkan pernyataan SQL dengan prepared statement
        $stmt = $conn->prepare("INSERT INTO anonymous_notes (content, color, created_at) VALUES (?, ?, NOW())");
        
        if (!$stmt) {
            // Catat kesalahan jika persiapan pernyataan gagal
            error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            return false;
        }
        
        // Bind parameter
        $stmt->bind_param("ss", $content, $color);
        
        // Jalankan pernyataan
        try {
            if ($stmt->execute()) {
                $note_id = $conn->insert_id;
                $stmt->close();
                return $note_id;
            } else {
                // Catat kesalahan eksekusi
                error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                $stmt->close();
                return false;
            }
        } catch (Exception $e) {
            // Tangani pengecualian
            error_log("Exception in createAnonymousNote: " . $e->getMessage());
            return false;
        }
    }
}

// Do the same for other functions that might be declared multiple times
if (!function_exists('getRandomAnonymousNotes')) {
    function getRandomAnonymousNotes($limit = 10, $offset = 0) {
        // Existing implementation...
    }
}

// Error handling function
function logError($message) {
    // Log errors to a file
    error_log($message, 3, "error.log");
}



// Fallback functions if not defined elsewhere
if (!function_exists('ignorePost')) {
    function ignorePost($userId, $postId) {
        global $conn, $db, $pdo;
        
        try {
            if (isset($pdo)) {
                $stmt = $pdo->prepare("INSERT INTO ignored_posts (user_id, post_id) VALUES (?, ?)");
                return $stmt->execute([$userId, $postId]);
            } elseif (isset($conn)) {
                $stmt = $conn->prepare("INSERT INTO ignored_posts (user_id, post_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $userId, $postId);
                return $stmt->execute();
            } elseif (isset($db)) {
                return $db->query("INSERT INTO ignored_posts (user_id, post_id) VALUES (?, ?)", 
                                   [$userId, $postId]);
            }
        } catch (Exception $e) {
            error_log('Error ignoring post: ' . $e->getMessage());
            return false;
        }
        
        return false;
    }
}

if (!function_exists('toggleLike')) {
    function toggleLike($userId, $postId) {
        global $conn, $db, $pdo;
        
        try {
            if (isset($pdo)) {
                // Check if like exists
                $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                $stmt->execute([$userId, $postId]);
                $existingLike = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingLike) {
                    // Unlike
                    $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
                    $stmt->execute([$userId, $postId]);
                    return ['success' => true, 'action' => 'unliked'];
                } else {
                    // Like
                    $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $postId]);
                    return ['success' => true, 'action' => 'liked'];
                }
            } elseif (isset($conn)) {
                // mysqli version
                $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                $stmt->bind_param("ii", $userId, $postId);
                $stmt->execute();
                $result = $stmt->get_result();
                $existingLike = $result->fetch_assoc();

                if ($existingLike) {
                    // Unlike
                    $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
                    $stmt->bind_param("ii", $userId, $postId);
                    $stmt->execute();
                    return ['success' => true, 'action' => 'unliked'];
                } else {
                    // Like
                    $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $userId, $postId);
                    $stmt->execute();
                    return ['success' => true, 'action' => 'liked'];
                }
            } elseif (isset($db)) {
                // Custom DB wrapper version
                $existingLike = $db->query("SELECT id FROM likes WHERE user_id = ? AND post_id = ?", 
                                           [$userId, $postId])->fetch();

                if ($existingLike) {
                    // Unlike
                    $db->query("DELETE FROM likes WHERE user_id = ? AND post_id = ?", 
                               [$userId, $postId]);
                    return ['success' => true, 'action' => 'unliked'];
                } else {
                    // Like
                    $db->query("INSERT INTO likes (user_id, post_id) VALUES (?, ?)", 
                               [$userId, $postId]);
                    return ['success' => true, 'action' => 'liked'];
                }
            }
        } catch (Exception $e) {
            error_log('Error toggling like: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
        
        return ['success' => false, 'error' => 'No database connection'];
    }
}

// Fallback for getEnhancedRecommendations if not defined
if (!function_exists('getEnhancedRecommendations')) {
    function getEnhancedRecommendations($userId, $limit = 10) {
        global $conn, $db, $pdo;
        
        try {
            if (isset($pdo)) {
                $stmt = $pdo->prepare("
                    SELECT p.*, 
                           u.username, 
                           u.profile_picture,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.user_id != :userId
                    AND p.id NOT IN (SELECT post_id FROM ignored_posts WHERE user_id = :userId)
                    ORDER BY p.created_at DESC
                    LIMIT :limit
                ");
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif (isset($conn)) {
                $stmt = $conn->prepare("
                    SELECT p.*, 
                           u.username, 
                           u.profile_picture,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.user_id != ?
                    AND p.id NOT IN (SELECT post_id FROM ignored_posts WHERE user_id = ?)
                    ORDER BY p.created_at DESC
                    LIMIT ?
                ");
                $stmt->bind_param("iii", $userId, $userId, $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                return $result->fetch_all(MYSQLI_ASSOC);
            } elseif (isset($db)) {
                return $db->fetchAll("
                    SELECT p.*, 
                           u.username, 
                           u.profile_picture,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.user_id != ?
                    AND p.id NOT IN (SELECT post_id FROM ignored_posts WHERE user_id = ?)
                    ORDER BY p.created_at DESC
                    LIMIT ?
                ", [$userId, $userId, $limit]);
            }
        } catch (Exception $e) {
            error_log('Error getting recommendations: ' . $e->getMessage());
            return [];
        }
        
        return [];
    }
}

// Include header
include_once 'views/header.php';

// Fetch recommendations
$userId = $_SESSION['user_id'];
$recommendations = getEnhancedRecommendations($userId);

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleApiRequest();
    header('Content-Type: application/json');
    
    try {
        switch ($_SERVER['REQUEST_URI']) {
            case '/api/ignore_post.php':
                $data = json_decode(file_get_contents('php://input'), true);
                $postId = $data['post_id'];
                $response = ['success' => ignorePost($userId, $postId)];
                echo json_encode($response);
                break;
            
            case '/api/toggle_like.php':
                $data = json_decode(file_get_contents('php://input'), true);
                $postId = $data['post_id'];
                $response = toggleLike($userId, $postId);
                echo json_encode($response);
                break;
            
            case '/api/add_comment.php':
                $data = json_decode(file_get_contents('php://input'), true);
                $postId = $data['post_id'];
                $content = $data['content'];
                $response = addComment($userId, $postId, $content);
                echo json_encode($response);
                break;
        }
    } catch (Exception $e) {
        error_log('API Request Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommended Posts</title>
    
    
</head>
<body>
    <div class="container mt-4">
    <div class="row">
        <!-- Left sidebar -->
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; // Assuming you have a sidebar include ?>
        </div>
    <div class="container mt-5">
        <h1 class="mb-4">Recommended for You</h1>
        
        <?php if (empty($recommendations)): ?>
            <div class="alert alert-info">
                No recommendations available at the moment.
            </div>
        <?php else: ?>
            <?php foreach ($recommendations as $post): ?>
                <div class="card post-card mb-3" id="post-<?php echo $post['id']; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo htmlspecialchars($post['profile_picture'] ?? ''); ?>" 
                                 class="rounded-circle me-2" width="40" height="40" alt="Profile picture">
                            <a href="/profile.php?id=<?php echo $post['user_id']; ?>" class="fw-bold">
                                <?php echo htmlspecialchars($post['username'] ?? 'Unknown User'); ?>
                            </a>
                        </div>
                        <div class="post-actions">
                            <button class="btn btn-sm btn-outline-secondary ignore-btn" 
                                    data-post-id="<?php echo $post['id']; ?>">
                                <i class="bi bi-x-lg"></i> Hide
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <p class="card-text"><?php echo htmlspecialchars($post['content'] ?? ''); ?></p>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <button class="btn btn-sm btn-outline-primary like-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="bi bi-hand-thumbs-up"></i> 
                                    Like (<span class="like-count"><?php echo $post['like_count'] ?? 0; ?></span>)
                                </button>
                                <button class="btn btn-sm btn-outline-secondary comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="bi bi-chat"></i>
                                    Comments (<span class="comment-count"><?php echo $post['comment_count'] ?? 0; ?></span>)
                                </button>
                            </div>
                        </div>
                        
                        <!-- Comment section (initially hidden) -->
                        <div class="comments-section mt-3" id="comments-<?php echo $post['id']; ?>">
                            <div class="comments-container">
                                <!-- Comments will be loaded here -->
                            </div>
                            
                            <form class="comment-form mt-2" data-post-id="<?php echo $post['id']; ?>">
                                <div class="input-group">
                                    <input type="text" class="form-control comment-input" placeholder="Write a comment...">
                                    <button class="btn btn-primary" type="submit">Post</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ignore Post Functionality
            document.querySelectorAll('.ignore-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const postElement = document.getElementById(`post-${postId}`);
                    
                    fetch('/api/ignore_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ post_id: postId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            postElement.style.opacity = <?php
/**
 * Enhanced Recommendations System
 */

// Configuration and Database Connection
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fallback functions if not defined elsewhere
if (!function_exists('ignorePost')) {
    function ignorePost($userId, $postId) {
        global $conn, $db, $pdo;
        
        try {
            if (isset($pdo)) {
                $stmt = $pdo->prepare("INSERT INTO ignored_posts (user_id, post_id) VALUES (?, ?)");
                return $stmt->execute([$userId, $postId]);
            } elseif (isset($conn)) {
                $stmt = $conn->prepare("INSERT INTO ignored_posts (user_id, post_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $userId, $postId);
                return $stmt->execute();
            } elseif (isset($db)) {
                return $db->query("INSERT INTO ignored_posts (user_id, post_id) VALUES (?, ?)", 
                                   [$userId, $postId]);
            }
        } catch (Exception $e) {
            error_log('Error ignoring post: ' . $e->getMessage());
            return false;
        }
        
        return false;
    }
}

if (!function_exists('toggleLike')) {
    function toggleLike($userId, $postId) {
        global $conn, $db, $pdo;
        
        try {
            if (isset($pdo)) {
                // Check if like exists
                $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                $stmt->execute([$userId, $postId]);
                $existingLike = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingLike) {
                    // Unlike
                    $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
                    $stmt->execute([$userId, $postId]);
                    return ['success' => true, 'action' => 'unliked'];
                } else {
                    // Like
                    $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $postId]);
                    return ['success' => true, 'action' => 'liked'];
                }
            } elseif (isset($conn)) {
                // mysqli version
                $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                $stmt->bind_param("ii", $userId, $postId);
                $stmt->execute();
                $result = $stmt->get_result();
                $existingLike = $result->fetch_assoc();

                if ($existingLike) {
                    // Unlike
                    $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
                    $stmt->bind_param("ii", $userId, $postId);
                    $stmt->execute();
                    return ['success' => true, 'action' => 'unliked'];
                } else {
                    // Like
                    $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $userId, $postId);
                    $stmt->execute();
                    return ['success' => true, 'action' => 'liked'];
                }
            } elseif (isset($db)) {
                // Custom DB wrapper version
                $existingLike = $db->query("SELECT id FROM likes WHERE user_id = ? AND post_id = ?", 
                                           [$userId, $postId])->fetch();

                if ($existingLike) {
                    // Unlike
                    $db->query("DELETE FROM likes WHERE user_id = ? AND post_id = ?", 
                               [$userId, $postId]);
                    return ['success' => true, 'action' => 'unliked'];
                } else {
                    // Like
                    $db->query("INSERT INTO likes (user_id, post_id) VALUES (?, ?)", 
                               [$userId, $postId]);
                    return ['success' => true, 'action' => 'liked'];
                }
            }
        } catch (Exception $e) {
            error_log('Error toggling like: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
        
        return ['success' => false, 'error' => 'No database connection'];
    }
}

// Fallback for getEnhancedRecommendations if not defined
if (!function_exists('getEnhancedRecommendations')) {
    function getEnhancedRecommendations($userId, $limit = 10) {
        global $conn, $db, $pdo;
        
        try {
            if (isset($pdo)) {
                $stmt = $pdo->prepare("
                    SELECT p.*, 
                           u.username, 
                           u.profile_picture,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.user_id != :userId
                    AND p.id NOT IN (SELECT post_id FROM ignored_posts WHERE user_id = :userId)
                    ORDER BY p.created_at DESC
                    LIMIT :limit
                ");
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif (isset($conn)) {
                $stmt = $conn->prepare("
                    SELECT p.*, 
                           u.username, 
                           u.profile_picture,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.user_id != ?
                    AND p.id NOT IN (SELECT post_id FROM ignored_posts WHERE user_id = ?)
                    ORDER BY p.created_at DESC
                    LIMIT ?
                ");
                $stmt->bind_param("iii", $userId, $userId, $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                return $result->fetch_all(MYSQLI_ASSOC);
            } elseif (isset($db)) {
                return $db->fetchAll("
                    SELECT p.*, 
                           u.username, 
                           u.profile_picture,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.user_id != ?
                    AND p.id NOT IN (SELECT post_id FROM ignored_posts WHERE user_id = ?)
                    ORDER BY p.created_at DESC
                    LIMIT ?
                ", [$userId, $userId, $limit]);
            }
        } catch (Exception $e) {
            error_log('Error getting recommendations: ' . $e->getMessage());
            return [];
        }
        
        return [];
    }
}

// Include header
include_once 'views/header.php';

// Fetch recommendations
$userId = $_SESSION['user_id'];
$recommendations = getEnhancedRecommendations($userId);

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        switch ($_SERVER['REQUEST_URI']) {
            case '/api/ignore_post.php':
                $data = json_decode(file_get_contents('php://input'), true);
                $postId = $data['post_id'];
                $response = ['success' => ignorePost($userId, $postId)];
                echo json_encode($response);
                break;
            
            case '/api/toggle_like.php':
                $data = json_decode(file_get_contents('php://input'), true);
                $postId = $data['post_id'];
                $response = toggleLike($userId, $postId);
                echo json_encode($response);
                break;
            
            case '/api/add_comment.php':
                $data = json_decode(file_get_contents('php://input'), true);
                $postId = $data['post_id'];
                $content = $data['content'];
                $response = addComment($userId, $postId, $content);
                echo json_encode($response);
                break;
        }
    } catch (Exception $e) {
        error_log('API Request Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommended Posts</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        .post-card {
            margin-bottom: 1rem;
            transition: opacity 0.5s ease;
        }
        .comments-section {
            display: none;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Recommended for You</h1>
        
        <?php if (empty($recommendations)): ?>
            <div class="alert alert-info">
                No recommendations available at the moment.
            </div>
        <?php else: ?>
            <?php foreach ($recommendations as $post): ?>
                <div class="card post-card mb-3" id="post-<?php echo $post['id']; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo htmlspecialchars($post['profile_picture'] ?? ''); ?>" 
                                 class="rounded-circle me-2" width="40" height="40" alt="Profile picture">
                            <a href="/profile.php?id=<?php echo $post['user_id']; ?>" class="fw-bold">
                                <?php echo htmlspecialchars($post['username'] ?? 'Unknown User'); ?>
                            </a>
                        </div>
                        <div class="post-actions">
                            <button class="btn btn-sm btn-outline-secondary ignore-btn" 
                                    data-post-id="<?php echo $post['id']; ?>">
                                <i class="bi bi-x-lg"></i> Hide
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <p class="card-text"><?php echo htmlspecialchars($post['content'] ?? ''); ?></p>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <button class="btn btn-sm btn-outline-primary like-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="bi bi-hand-thumbs-up"></i> 
                                    Like (<span class="like-count"><?php echo $post['like_count'] ?? 0; ?></span>)
                                </button>
                                <button class="btn btn-sm btn-outline-secondary comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="bi bi-chat"></i>
                                    Comments (<span class="comment-count"><?php echo $post['comment_count'] ?? 0; ?></span>)
                                </button>
                            </div>
                        </div>
                        
                        <!-- Comment section (initially hidden) -->
                        <div class="comments-section mt-3" id="comments-<?php echo $post['id']; ?>">
                            <div class="comments-container">
                                <!-- Comments will be loaded here -->
                            </div>
                            
                            <form class="comment-form mt-2" data-post-id="<?php echo $post['id']; ?>">
                                <div class="input-group">
                                    <input type="text" class="form-control comment-input" placeholder="Write a comment...">
                                    <button class="btn btn-primary" type="submit">Post</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ignore Post Functionality
            document.querySelectorAll('.ignore-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const postElement = document.getElementById(`post-${postId}`);
                    
                    fetch('/api/ignore_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ post_id: postId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            postElement.style.opacity = '0';
                            setTimeout(() => postElement.remove(), 500);
                        } else {
                            alert('Error hiding post. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            });

            // Like Post Functionality
            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const likeCount = this.querySelector('.like-count');
                    
                    fetch('/api/toggle_like.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ post_id: postId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.action === 'liked') {
                                this.classList.remove('btn-outline-primary');
                                this.classList.add('btn-primary');
                                likeCount.textContent = parseInt(likeCount.textContent) + 1;
                            } else {
                                this.classList.remove('btn-primary');
                                this.classList.add('btn-outline-primary');
                                likeCount.textContent = parseInt(likeCount.textContent) - 1;
                            }
                        } else {
                            alert('Error updating like. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            });

            // Comment Interactions
            document.querySelectorAll('.comment-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const commentSection = document.getElementById(`comments-${postId}`);
                    
                    // Toggle comment section visibility
                    commentSection.style.display = 
                        commentSection.style.display === 'none' ? 'block' : 'none';
                });
            });

            // Comment Form Submission
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const postId = this.getAttribute('data-post-id');
                    const commentInput = this.querySelector('.comment-input');
                    const commentContent = commentInput.value.trim();
                    const commentCount = document.querySelector(`#post-${postId} .comment-count`);
                    
                    if (!commentContent) return;
                    
                    fetch('/api/add_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            post_id: postId,
                            content: commentContent
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Clear input
                            commentInput.value = '';
                            
                            // Update comment count
                            commentCount.textContent = 
                                parseInt(commentCount.textContent) + 1;
                            
                            // TODO: Optionally, refresh comments or add new comment to list
                            alert('Comment added successfully!');
                        } else {
                            alert('Error adding comment. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            });
        });
    </script>
</body>
</html>

<?php include_once 'views/footer.php'; ?>
