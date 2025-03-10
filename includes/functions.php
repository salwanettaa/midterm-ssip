<?php
require_once 'db.php';
/**
 * Get preferences for guest users stored in session
 * @return array|null Guest preferences array or null if not set
 */
function getGuestPreferences() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    return isset($_SESSION['guest_preferences']) ? $_SESSION['guest_preferences'] : null;
}

/**
 * Update preferences for guest users
 * @param array $preferences Associative array of preferences to update
 * @return bool Success status
 */
function updateGuestPreferences($preferences) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['guest_preferences'])) {
        $_SESSION['guest_preferences'] = [];
    }
    
    // Merge new preferences with existing ones
    $_SESSION['guest_preferences'] = array_merge($_SESSION['guest_preferences'], $preferences);
    
    return true;
}

/**
 * Get user interests
 * 
 * @param int $user_id User ID
 * @return array Array of user interests
 */
function getUserInterests($user_id) {
    global $conn;
    
    // Check if the user_interests table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'user_interests'";
    $result = $conn->query($tableCheckQuery);
    
    if ($result->num_rows == 0) {
        // Create the table if it doesn't exist
        $createTableQuery = "CREATE TABLE user_interests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            interest VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_interest (user_id, interest)
        )";
        $conn->query($createTableQuery);
        
        // Return empty array if table was just created
        return [];
    }
    
    // Prepare statement to fetch user interests
    $stmt = $conn->prepare("
        SELECT interest 
        FROM user_interests 
        WHERE user_id = ?
    ");
    
    if (!$stmt) {
        // Log error if statement preparation fails
        error_log("Prepare failed in getUserInterests: (" . $conn->errno . ") " . $conn->error);
        return [];
    }
    
    // Bind user ID parameter
    $stmt->bind_param("i", $user_id);
    
    try {
        // Execute the statement
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch interests
        $interests = [];
        while ($row = $result->fetch_assoc()) {
            $interests[] = $row['interest'];
        }
        
        $stmt->close();
        return $interests;
    } catch (Exception $e) {
        // Log any exceptions
        error_log("Exception in getUserInterests: " . $e->getMessage());
        return [];
    }
}

/**
 * Add user interests
 * 
 * @param int $user_id User ID
 * @param array $interests Array of interests to add
 * @return bool Success status
 */
function addUserInterests($user_id, $interests) {
    global $conn;
    
    // Ensure user_interests table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'user_interests'";
    $result = $conn->query($tableCheckQuery);
    
    if ($result->num_rows == 0) {
        // Create the table if it doesn't exist
        $createTableQuery = "CREATE TABLE user_interests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            interest VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_interest (user_id, interest)
        )";
        $conn->query($createTableQuery);
    }
    
    // Prepare statement for inserting interests
    $stmt = $conn->prepare("
        INSERT IGNORE INTO user_interests (user_id, interest) 
        VALUES (?, ?)
    ");
    
    if (!$stmt) {
        error_log("Prepare failed in addUserInterests: (" . $conn->errno . ") " . $conn->error);
        return false;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert each interest
        foreach ($interests as $interest) {
            // Sanitize and validate interest
            $interest = trim(htmlspecialchars($interest, ENT_QUOTES, 'UTF-8'));
            
            if (!empty($interest)) {
                $stmt->bind_param("is", $user_id, $interest);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Exception in addUserInterests: " . $e->getMessage());
        return false;
    }
}
/**
 * Get floating anonymous notes for display
 * @param int $limit Number of notes to retrieve (default: 10)
 * @return array Array of anonymous notes
 */
function getFloatingNotes($limit = 10) {
    global $conn;
    
    // Ensure database connection exists
    if (!$conn) {
        // If no connection, attempt to establish one
        connectDatabase();
    }
    
    // Prepare the SQL statement to get random notes
    $stmt = $conn->prepare("
        SELECT id, content, color, created_at 
        FROM anonymous_notes 
        ORDER BY RAND() 
        LIMIT ?
    ");
    
    if (!$stmt) {
        // Log error if statement preparation fails
        error_log("Prepare failed in getFloatingNotes: (" . $conn->errno . ") " . $conn->error);
        return [];
    }
    
    // Bind the limit parameter
    $stmt->bind_param("i", $limit);
    
    try {
        // Execute the statement
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch notes
        $notes = [];
        while ($row = $result->fetch_assoc()) {
            // Truncate long content
            $row['short_content'] = mb_strlen($row['content']) > 100 
                ? mb_substr($row['content'], 0, 100) . '...' 
                : $row['content'];
            
            $notes[] = $row;
        }
        
        $stmt->close();
        return $notes;
    } catch (Exception $e) {
        // Log any exceptions
        error_log("Exception in getFloatingNotes: " . $e->getMessage());
        return [];
    }
}
// Tambahkan fungsi-fungsi ini ke file includes/functions.php

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

/**
 * Mengambil catatan anonim secara acak
 * @param int $limit Jumlah catatan yang akan diambil (default: 10, max: 50)
 * @param int $offset Offset untuk pagination (opsional)
 * @return array Catatan yang diambil
 */
function getRandomAnonymousNotes($limit = 10, $offset = 0) {
    global $conn;
    
    // Validasi dan batasi limit
    $limit = min(max(1, (int)$limit), 50);
    $offset = max(0, (int)$offset);
    
    $notes = [];
    
    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("
        SELECT 
            id, 
            content, 
            color, 
            created_at 
        FROM anonymous_notes 
        ORDER BY RAND() 
        LIMIT ? 
        OFFSET ?
    ");
    
    if (!$stmt) {
        // Catat kesalahan jika persiapan pernyataan gagal
        error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        return $notes;
    }
    
    // Bind parameter
    $stmt->bind_param("ii", $limit, $offset);
    
    try {
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Ambil data dengan pemrosesan tambahan
        while ($row = $result->fetch_assoc()) {
            // Potong konten yang terlalu panjang
            $row['content'] = mb_strlen($row['content']) > 300 
                ? mb_substr($row['content'], 0, 300) . '...' 
                : $row['content'];
            
            // Konversi waktu ke format yang lebih mudah dibaca
            $row['formatted_date'] = date('d M Y H:i', strtotime($row['created_at']));
            
            $notes[] = $row;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Tangani pengecualian
        error_log("Exception in getRandomAnonymousNotes: " . $e->getMessage());
    }
    
    return $notes;
}

/**
 * Menghitung total jumlah catatan anonim
 * @return int Jumlah total catatan
 */
function countAnonymousNotes() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM anonymous_notes");
    
    if (!$stmt) {
        error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        return 0;
    }
    
    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)$row['total'];
    } catch (Exception $e) {
        error_log("Exception in countAnonymousNotes: " . $e->getMessage());
        return 0;
    }
}

/**
 * Menghapus catatan anonim yang sudah lama
 * @param int $days Jumlah hari untuk menyimpan catatan (default: 30)
 * @return int Jumlah catatan yang dihapus
 */
function cleanupOldAnonymousNotes($days = 30) {
    global $conn;
    
    $stmt = $conn->prepare("
        DELETE FROM anonymous_notes 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        return 0;
    }
    
    // Bind parameter
    $stmt->bind_param("i", $days);
    
    try {
        $stmt->execute();
        $deleted_count = $stmt->affected_rows;
        $stmt->close();
        
        return $deleted_count;
    } catch (Exception $e) {
        error_log("Exception in cleanupOldAnonymousNotes: " . $e->getMessage());
        return 0;
    }
}
// User functions
function registerUser($username, $email, $password) {
    global $db;
    
    // Check if username or email already exists
    $check = $db->fetch("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $email]);
    if ($check) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $db->query("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())", 
        [$username, $email, $password_hash]);
    
    return ['success' => true, 'message' => 'Registration successful'];
}

function clearUserInterests($user_id) {
    global $conn; // Assuming you're using a global database connection

    // Prepare SQL statement to delete user's interests
    $stmt = $conn->prepare("DELETE FROM user_interests WHERE user_id = ?");
    
    try {
        // Execute the statement
        $stmt->bind_param("i", $user_id);
        $result = $stmt->execute();
        
        // Check if deletion was successful
        if ($result) {
            return true; // Successfully cleared interests
        } else {
            return false; // Failed to clear interests
        }
    } catch (Exception $e) {
        // Log the error or handle it appropriately
        error_log("Error clearing user interests: " . $e->getMessage());
        return false;
    }
}
/**
 * Get enhanced recommendations based on content similarity and user likes
 * 
 * @param int $userId User ID
 * @param int $limit Number of posts to return
 * @param int $offset Offset for pagination
 * @param string $type Recommendation type: 'mixed', 'content', or 'users'
 * @return array Array of recommended posts
 */
function getEnhancedRecommendations($userId, $limit = 10, $offset = 0, $type = 'mixed') {
    global $conn;
    
    // Get the type from GET parameter if set
    if (isset($_GET['recommendation_type']) && in_array($_GET['recommendation_type'], ['mixed', 'content', 'users'])) {
        $type = $_GET['recommendation_type'];
    }
    
    // Get user's liked posts to analyze content
    $likedPostsQuery = "SELECT p.id, p.content, p.user_id 
                        FROM posts p 
                        JOIN likes l ON p.id = l.post_id 
                        WHERE l.user_id = ?";
    $stmt = $conn->prepare($likedPostsQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $likedPosts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Extract important phrases and words from liked posts
    $likedContent = '';
    $likedUserIds = [];
    foreach ($likedPosts as $post) {
        $likedContent .= ' ' . $post['content'];
        $likedUserIds[] = $post['user_id'];
    }

    // Clean and prepare the content for matching
    $likedContent = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $likedContent);
    $likedContent = preg_replace('/\s+/', ' ', $likedContent);
    $likedContent = trim($likedContent);
    
    // Get user interests if available
    $userInterests = getUserInterests($userId);
    $interestTerms = !empty($userInterests) ? implode('|', array_map(function($interest) {
        return preg_quote($interest, '/');
    }, $userInterests)) : '';
    
    // Build a simpler, more reliable query
    $query = "SELECT p.*, u.username, u.profile_picture, 
              (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
              (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
              (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
              'general_content' as content_type,
              'Based on content you like' as recommendation_reason
              FROM posts p
              JOIN users u ON p.user_id = u.id
              WHERE p.id NOT IN (SELECT post_id FROM likes WHERE user_id = ?)
              AND p.user_id != ?";

    // Add ignored posts condition
    $ignoredPostsQuery = "SELECT post_id FROM ignored_posts WHERE user_id = ?";
    $stmt = $conn->prepare($ignoredPostsQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $ignoredResult = $stmt->get_result();
    $stmt->close();
    $ignoredPosts = [];
    while ($row = $ignoredResult->fetch_assoc()) {
        $ignoredPosts[] = $row['post_id'];
    }
    
    if (!empty($ignoredPosts)) {
        $ignoredPostsStr = implode(',', $ignoredPosts);
        $query .= " AND p.id NOT IN ($ignoredPostsStr)";
    }
    
    // Add basic filtering based on type
    if ($type == 'content' && !empty($likedContent)) {
        // Simple content matching
        $keywords = extractKeywords($likedContent, 5);
        if (!empty($keywords)) {
            $keywordConditions = [];
            foreach ($keywords as $keyword) {
                $keywordConditions[] = "p.content LIKE '%$keyword%'";
            }
            $query .= " AND (" . implode(" OR ", $keywordConditions) . ")";
        }
    } elseif ($type == 'users' && !empty($likedUserIds)) {
        // Similar users
        $likedUsersStr = implode(',', $likedUserIds);
        $query .= " AND p.user_id IN ($likedUsersStr)";
    }
    
    // Add ordering
    $query .= " ORDER BY p.created_at DESC";
    
    // Add pagination
    $query .= " LIMIT ? OFFSET ?";
    
    try {
        // Prepare statement with consistent parameters
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiii", $userId, $userId, $userId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $posts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $posts;
    } catch (Exception $e) {
        // Log the error and return empty array
        error_log("Error in getEnhancedRecommendations: " . $e->getMessage());
        return [];
    }
}
/**
 * Get total count of recommendations for pagination
 * 
 * @param int $userId User ID
 * @param string $type Recommendation type
 * @return int Total count of available recommendations
 */
function getTotalRecommendationCount($userId, $type = 'mixed') {
    global $conn;
    
    // Get the type from GET parameter if set
    if (isset($_GET['recommendation_type']) && in_array($_GET['recommendation_type'], ['mixed', 'content', 'users'])) {
        $type = $_GET['recommendation_type'];
    }
    
    // Basic query to count available posts
    $query = "SELECT COUNT(*) as total FROM posts p
              WHERE p.id NOT IN (SELECT post_id FROM likes WHERE user_id = ?)
              AND p.user_id != ?";
    
    // Get ignored posts
    $ignoredPostsQuery = "SELECT post_id FROM ignored_posts WHERE user_id = ?";
    $stmt = $conn->prepare($ignoredPostsQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $ignoredResult = $stmt->get_result();
    $stmt->close();
    $ignoredPosts = [];
    while ($row = $ignoredResult->fetch_assoc()) {
        $ignoredPosts[] = $row['post_id'];
    }
    
    if (!empty($ignoredPosts)) {
        $ignoredPostsStr = implode(',', $ignoredPosts);
        $query .= " AND p.id NOT IN ($ignoredPostsStr)";
    }
    
    // Execute the query
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['total'];
}

/**
 * Get similar users based on interests
 * 
 * @param int $userId User ID
 * @param int $limit Number of users to return
 * @return array Array of similar users
 */
function getSimilarUsers($userId, $limit = 5) {
    global $conn;
    
    // Check if the user_interests table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'user_interests'";
    $result = $conn->query($tableCheckQuery);
    
    if ($result->num_rows == 0) {
        // Table doesn't exist yet, return empty array
        return [];
    }
    
    $query = "SELECT u.id, u.username, u.profile_picture, 
             COUNT(ui2.interest) as common_interests
             FROM users u
             JOIN user_interests ui1 ON u.id != ?
             JOIN user_interests ui2 ON ui1.interest = ui2.interest AND ui2.user_id = ?
             WHERE ui1.user_id = u.id
             GROUP BY u.id
             ORDER BY common_interests DESC
             LIMIT ?";
             
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Extract important keywords from text
 * 
 * @param string $text Input text
 * @param int $limit Number of keywords to extract
 * @return array Array of keywords
 */
function extractKeywords($text, $limit = 10) {
    // Split text into words
    $words = preg_split('/\s+/', strtolower($text));
    
    // Remove common words (stopwords)
    $stopwords = ['a', 'about', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 
                 'has', 'have', 'i', 'in', 'is', 'it', 'of', 'on', 'that', 'the', 'this', 
                 'to', 'was', 'were', 'will', 'with'];
    
    $filteredWords = array_filter($words, function($word) use ($stopwords) {
        return strlen($word) > 3 && !in_array($word, $stopwords);
    });
    
    // Count word frequency
    $wordCount = array_count_values($filteredWords);
    
    // Sort by frequency
    arsort($wordCount);
    
    // Return top keywords
    return array_slice(array_keys($wordCount), 0, $limit);
}

/**
 * Get posts ignored by a user
 * 
 * @param int $userId User ID
 * @return array Array of ignored post IDs
 */
function getIgnoredPosts($userId) {
    global $db;
    
    // Validate input
    $userId = (int)$userId;
    
    // Check if the ignored_posts table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'ignored_posts'";
    $result = $db->query($tableCheckQuery);
    
    if (empty($result)) {
        // Create the table if it doesn't exist
        $createTableQuery = "CREATE TABLE ignored_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (user_id, post_id)
        )";
        $db->query($createTableQuery);
    }
    
    // Get all posts ignored by this specific user
    $ignoredPosts = $db->fetchAll("SELECT post_id FROM ignored_posts WHERE user_id = ?", [$userId]);
    
    // Return just the post IDs in a simple array format for easier use
    $postIds = [];
    foreach ($ignoredPosts as $post) {
        $postIds[] = $post['post_id'];
    }
    
    return $postIds;
}

/**
 * Ignore/hide a post for a specific user
 * 
 * @param int $userId User ID who wants to hide the post
 * @param int $postId Post ID to hide
 * @return array Result with success status and message
 */
function ignorePost($userId, $postId) {
    global $db;
    
    // Validate inputs
    $userId = (int)$userId;
    $postId = (int)$postId;
    
    // Ensure user exists
    $user = $db->fetch("SELECT id FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid user'];
    }
    
    // Ensure post exists
    $post = $db->fetch("SELECT id FROM posts WHERE id = ?", [$postId]);
    if (!$post) {
        return ['success' => false, 'message' => 'Invalid post'];
    }
    
    // Check if already ignored
    $check = $db->fetch("SELECT id FROM ignored_posts WHERE user_id = ? AND post_id = ?", [$userId, $postId]);
    if ($check) {
        return ['success' => false, 'message' => 'Post already hidden'];
    }
    
    // Add to ignored posts
    try {
        $db->query("INSERT INTO ignored_posts (user_id, post_id) VALUES (?, ?)", [$userId, $postId]);
        return ['success' => true, 'message' => 'Post hidden successfully'];
    } catch (Exception $e) {
        error_log("Error hiding post: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error hiding post. Please try again later.'];
    }
}

/**
 * Unhide a previously ignored post
 * 
 * @param int $userId User ID
 * @param int $postId Post ID to unhide
 * @return array Result with success status and message
 */
function unhidePost($userId, $postId) {
    global $db;
    
    // Validate inputs
    $userId = (int)$userId;
    $postId = (int)$postId;
    
    // Check if post is ignored
    $check = $db->fetch("SELECT id FROM ignored_posts WHERE user_id = ? AND post_id = ?", [$userId, $postId]);
    if (!$check) {
        return ['success' => false, 'message' => 'Post is not hidden'];
    }
    
    // Remove from ignored posts
    try {
        $db->query("DELETE FROM ignored_posts WHERE user_id = ? AND post_id = ?", [$userId, $postId]);
        return ['success' => true, 'message' => 'Post unhidden successfully'];
    } catch (Exception $e) {
        error_log("Error unhiding post: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error unhiding post. Please try again later.'];
    }
}

/**
 * Filter out ignored posts from a list of posts for a specific user
 * 
 * @param array $posts Array of posts
 * @param int $userId User ID
 * @return array Filtered posts
 */
function filterIgnoredPosts($posts, $userId) {
    if (empty($posts) || empty($userId)) {
        return $posts;
    }
    
    $ignoredPostIds = getIgnoredPosts($userId);
    
    if (empty($ignoredPostIds)) {
        return $posts;
    }
    
    // Filter out posts that are in the ignored list
    return array_filter($posts, function($post) use ($ignoredPostIds) {
        return !in_array($post['id'], $ignoredPostIds);
    });
}
/**
* Generate a reset token and stores it in the database
 * 
 * @param string $email User email
 * @return array Result with success status and message
 */
function generatePasswordResetToken($email) {
    global $db;
    
    // Check if email exists
    $user = $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
    if (!$user) {
        return ['success' => false, 'message' => 'Email not found in our records'];
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Check if a token already exists
    $existingToken = $db->fetch("SELECT * FROM password_reset WHERE user_id = ?", [$user['id']]);
    
    if ($existingToken) {
        // Update existing token
        $db->query("UPDATE password_reset SET token = ?, expires = ?, email = ? WHERE user_id = ?", 
                  [$token, $expires, $email, $user['id']]);
    } else {
        // Create new token
        try {
            $db->query("INSERT INTO password_reset (user_id, email, token, expires, created_at) VALUES (?, ?, ?, ?, NOW())", 
                      [$user['id'], $email, $token, $expires]);
        } catch (Exception $e) {
            error_log("Database error in generatePasswordResetToken: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating password reset token. Please try again later.'];
        }
    }
    
    // Create the reset link
    $resetLink = SITE_URL . '/reset_password.php?token=' . $token;
    
    // Email subject
    $subject = "Password Reset Request";
    
    // Email message
    $message = "
    <html>
    <head>
        <title>Password Reset</title>
    </head>
    <body>
        <h2>Password Reset Request</h2>
        <p>Hello {$user['username']},</p>
        <p>You have requested to reset your password. Please click the link below to reset your password:</p>
        <p><a href=\"{$resetLink}\">{$resetLink}</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request this password reset, please ignore this email.</p>
        <p>Regards,<br>Your Website Team</p>
    </body>
    </html>
    ";
    
    // Set content-type header for sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    
    // Send email - with error checking
    $mailSent = false;
    try {
        $mailSent = mail($email, $subject, $message, $headers);
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        // Continue anyway since we'll show the link for demonstration
    }
    
    // For development/testing, always return the link
    return [
        'success' => true, 
        'message' => 'Password reset instructions have been sent to your email',
        'mail_sent' => $mailSent,
        'reset_link' => $resetLink
    ];
}
/**
 * Validates a reset token
 * 
 * @param string $token Reset token
 * @return array Result with user data if valid
 */
function validateResetToken($token) {
    global $db;
    
    // Get token data - dengan debugging
    $query = "SELECT pr.*, u.email, u.username FROM password_reset pr 
              JOIN users u ON pr.user_id = u.id
              WHERE pr.token = ? AND pr.expires > NOW()";
    
    try {
        $tokenData = $db->fetch($query, [$token]);
        
        if (!$tokenData) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
        
        // Validasi data yang diambil
        if (!isset($tokenData['user_id'])) {
            error_log("User ID missing in token data");
            return ['success' => false, 'message' => 'Invalid token structure'];
        }
        
        return ['success' => true, 'user' => $tokenData];
    } catch (Exception $e) {
        error_log("Error validating token: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error validating token'];
    }
}
/**
 * Reset user password
 * 
 * @param string $token Reset token
 * @param string $password New password
 * @return array Result with success status and message
 */
function resetPassword($token, $password) {
    global $db;
    
    // Validate token
    $validation = validateResetToken($token);
    if (!$validation['success']) {
        return $validation;
    }
    
    $userId = $validation['user']['user_id'];
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password
    $db->query("UPDATE users SET password = ? WHERE id = ?", [$password_hash, $userId]);
    
    // Delete used token
    $db->query("DELETE FROM password_reset WHERE user_id = ?", [$userId]);
    
    return ['success' => true, 'message' => 'Password has been reset successfully'];
}


function loginUser($email, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // Add any other user data you need
            
            // Update last login time
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            
            return ['success' => true];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid email or password'];
}

function logoutUser() {
    
    destroySession();
    
    // Keep your existing cookie code
    if (isset($_COOKIE['user_preferences'])) {
        setcookie('user_preferences', '', time() - 3600, '/');
    }
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}
function getUserById($userId) {
    global $conn; // Assuming you have a database connection established

    // Prepare a SQL statement to fetch user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc(); // Returns the user details as an associative array
    }

    return null; // Return null if no user found
}
function isLoggedIn() {
    // This should check if a user session exists
    return isset($_SESSION['user_id']);
}


function getCurrentUser() {
    // This should retrieve the current user's details from the session or database
    if (isset($_SESSION['user_id'])) {
        // Fetch user details based on the session user ID
        return getUserById($_SESSION['user_id']);
    }
    return null;
}

// Guest functions
function setGuestCookie($preferences) {
    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
    setcookie('user_preferences', json_encode($preferences), $expiry, '/');
}



// Post functions
function createPost($user_id, $content, $media_type = null, $media_path = null) {
    global $db;
    
    $db->query("INSERT INTO posts (user_id, content, media_type, media_path, created_at) 
                VALUES (?, ?, ?, ?, NOW())", 
                [$user_id, $content, $media_type, $media_path]);
    
    return $db->lastInsertId();
}

function getPosts($limit = 10, $offset = 0) {
    global $db;
     $limit = (int)$limit;
    $offset = (int)$offset;
    
    return $db->fetchAll("SELECT p.*, u.username, u.profile_picture,
                         (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                         (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                         FROM posts p
                         JOIN users u ON p.user_id = u.id
                         ORDER BY p.created_at DESC
                         LIMIT $limit OFFSET $offset");
}

function getUserPosts($user_id, $limit = 10, $offset = 0) {
    global $db;

    $user_id = (int)$user_id;
     $limit = (int)$limit;
    $offset = (int)$offset;
    
    return $db->fetchAll("SELECT p.*, u.username, u.profile_picture,
                         (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                         (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                         FROM posts p
                         JOIN users u ON p.user_id = u.id
                        WHERE p.user_id = $user_id
                         ORDER BY p.created_at DESC
                         LIMIT $limit OFFSET $offset");
}

function getTrendingPosts($limit = 10) {
    global $db;

     $limit = (int)$limit;
  
    
    return $db->fetchAll("SELECT p.*, u.username, u.profile_picture,
                         (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                         (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                         FROM posts p
                         JOIN users u ON p.user_id = u.id
                         LEFT JOIN likes l ON p.id = l.post_id
                         GROUP BY p.id
                         ORDER BY COUNT(l.id) DESC, p.created_at DESC
                         LIMIT $limit");
}



// Interaction functions

/**
 * Get list of posts ignored by a user
 * 
 * @param int $user_id User ID
 * @return array Array of ignored post IDs
 */

// Utility functions
function uploadMedia($file, $type) {
    $target_dir = UPLOAD_PATH . $type . 's/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $filename = time() . '_' . basename($file['name']);
    $target_file = $target_dir . $filename;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    if ($type == 'image') {
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            return ['success' => false, 'message' => 'File is not an image.'];
        }
        
        // Check file size (5MB max)
        if ($file['size'] > 5000000) {
            return ['success' => false, 'message' => 'File is too large. Max 5MB.'];
        }
        
        // Allow certain file formats
        if ($file_type != "jpg" && $file_type != "png" && $file_type != "jpeg" && $file_type != "gif") {
            return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
        }
    } elseif ($type == 'video') {
        // Check file size (50MB max)
        if ($file['size'] > 50000000) {
            return ['success' => false, 'message' => 'File is too large. Max 50MB.'];
        }
        
        // Allow certain file formats
        if ($file_type != "mp4" && $file_type != "avi" && $file_type != "mov") {
            return ['success' => false, 'message' => 'Only MP4, AVI & MOV files are allowed.'];
        }
    } else {
        return ['success' => false, 'message' => 'Invalid media type.'];
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'path' => $target_file];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}







/**
 * Update user profile
 * 
 * @param int $user_id User ID
 * @param array $data Profile data
 * @return array Result with success status and message
 */
function updateUserProfile($user_id, $data) {
    global $db;
    
    // Get allowed fields
    $allowedFields = ['username', 'bio', 'location', 'website', 'profile_picture'];
    $updateData = [];
    
    // Filter data
    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $updateData[$key] = $value;
        }
    }
    
    // If no valid data, return error
    if (empty($updateData)) {
        return ['success' => false, 'message' => 'No valid data provided'];
    }
    
    // Build query
    $sql = "UPDATE users SET ";
    $params = [];
    
    foreach ($updateData as $key => $value) {
        $sql .= "$key = ?, ";
        $params[] = $value;
    }
    
    // Remove trailing comma and space
    $sql = rtrim($sql, ', ');
    
    // Add WHERE clause
    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    
    // Execute query
    $db->query($sql, $params);
    
    return ['success' => true, 'message' => 'Profile updated successfully'];
}

/**
 * Change user password
 * 
 * @param int $user_id User ID
 * @param string $current_password Current password
 * @param string $new_password New password
 * @return array Result with success status and message
 */
function changeUserPassword($user_id, $current_password, $new_password) {
    global $db;
    
    // Get user
    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user_id]);
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Hash new password
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $db->query("UPDATE users SET password = ? WHERE id = ?", [$password_hash, $user_id]);
    
    return ['success' => true, 'message' => 'Password changed successfully'];
}

/**
 * Update user preferences
 * 
 * @param int $user_id User ID
 * @param array $preferences User preferences
 * @return array Result with success status and message
 */
function updateUserPreferences($user_id, $preferences) {
    global $db;
    
    // Check if preferences exist
    $existingPrefs = $db->fetch("SELECT * FROM user_preferences WHERE user_id = ?", [$user_id]);
    
    if ($existingPrefs) {
        // Update existing preferences
        $db->query("UPDATE user_preferences SET theme = ?, notification = ?, updated_at = NOW() WHERE user_id = ?", 
                  [$preferences['theme'], $preferences['notification'], $user_id]);
    } else {
        // Create new preferences
        $db->query("INSERT INTO user_preferences (user_id, theme, notification, created_at) VALUES (?, ?, ?, NOW())", 
                  [$user_id, $preferences['theme'], $preferences['notification']]);
    }
    
    return ['success' => true, 'message' => 'Preferences updated successfully'];}

/**
 * Get user preferences
 * 
 * @param int $user_id User ID
 * @return array User preferences
 */
function getUserPreferences($user_id) {
    global $db;
    
    $prefs = $db->fetch("SELECT * FROM user_preferences WHERE user_id = ?", [$user_id]);
    
    if (!$prefs) {
        // Return default preferences
        return [
            'theme' => 'light',
            'notification' => 'on',
            'language' => 'en'
        ];
    }
    
    return $prefs;
}

// Add this function to includes/functions.php
function deletePost($user_id, $post_id) {
    global $db;
    
    // Check if post exists and belongs to user
    $post = $db->fetch("SELECT * FROM posts WHERE id = ? AND user_id = ?", [$post_id, $user_id]);
    
    if (!$post) {
        return ['success' => false, 'message' => 'Post not found or you do not have permission to delete it'];
    }
    
    // Delete associated files if any
    if ($post['media_path'] && file_exists($post['media_path'])) {
        unlink($post['media_path']);
    }
    
    // Delete post and all associated data (comments, likes, etc. will be deleted by foreign key constraints)
    $db->query("DELETE FROM posts WHERE id = ?", [$post_id]);
    
    return ['success' => true, 'message' => 'Post deleted successfully'];
}


?>