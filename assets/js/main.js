// assets/js/main.js
$(document).ready(function() {
    // Like button functionality
    $(document).on('click', '.like-btn', function() {
        const postId = $(this).data('post-id');
        const button = $(this);
        
        // Disable button during request to prevent double-clicks
        button.prop('disabled', true);
        
        $.ajax({
            url: 'post.php',
            type: 'POST',
            data: {
                action: 'like',
                post_id: postId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Like response:', response); // For debugging
                if (response.success) {
                    // Update like count with the exact count from server
                    const likeCount = button.find('.like-count');
                    likeCount.text(response.like_count);
                    
                    // Update button appearance based on the new state
                    if (response.action === 'liked') {
                        button.removeClass('btn-outline-primary').addClass('btn-primary active');
                    } else {
                        button.removeClass('btn-primary active').addClass('btn-outline-primary');
                    }
                } else {
                    // If not logged in or error
                    if (response.message === 'Please login to perform this action') {
                        window.location.href = 'login.php';
                    } else {
                        alert(response.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                // Re-enable button after request completes
                button.prop('disabled', false);
            }
        });
    });
    
    // Comment button functionality
    $(document).on('click', '.comment-btn', function() {
        const postId = $(this).data('post-id');
        const commentSection = $(`#comments-${postId}`);
        
        // Toggle comment section
        if (commentSection.is(':visible')) {
            commentSection.slideUp();
        } else {
            // Load comments
            $.ajax({
                url: 'post.php',
                type: 'POST',
                data: {
                    action: 'get_comments',
                    post_id: postId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const commentsContainer = commentSection.find('.comments-container');
                        commentsContainer.empty();
                        
                        if (response.comments.length === 0) {
                            commentsContainer.html('<p class="text-muted">No comments yet.</p>');
                        } else {
                            // Display comments
                            $.each(response.comments, function(i, comment) {
                                const commentHtml = `
                                    <div class="comment" id="comment-${comment.id}">
                                        <div class="d-flex">
                                            <img src="${comment.profile_picture}" class="rounded-circle me-2" width="24" height="24" alt="Profile">
                                            <div>
                                                <div>
                                                    <a href="profile.php?id=${comment.user_id}" class="fw-bold text-decoration-none">${comment.username}</a>
                                                    <small class="text-muted ms-2">${formatTimestamp(comment.created_at)}</small>
                                                </div>
                                                <p class="mb-0">${comment.content}</p>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                commentsContainer.append(commentHtml);
                            });
                        }
                        
                        // Show comment section
                        commentSection.slideDown();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert('An error occurred while loading comments.');
                }
            });
        }
    });
    
    // Comment form submission
    $(document).on('submit', '.comment-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const postId = form.data('post-id');
        const input = form.find('.comment-input');
        const content = input.val().trim();
        
        if (content === '') {
            return;
        }
        
        // Disable the form during submission
        const submitButton = form.find('button[type="submit"]');
        submitButton.prop('disabled', true);
        
        $.ajax({
            url: 'post.php',
            type: 'POST',
            data: {
                action: 'comment',
                post_id: postId,
                content: content
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Clear input
                    input.val('');
                    
                    // Add new comment to the list
                    const commentsContainer = $(`#comments-${postId} .comments-container`);
                    
                    // Remove "No comments yet" message if it exists
                    if (commentsContainer.find('.text-muted').length > 0) {
                        commentsContainer.empty();
                    }
                    
                    // Add the new comment
                    const commentHtml = `
                        <div class="comment" id="comment-${response.comment.id}">
                            <div class="d-flex">
                                <img src="${response.comment.profile_picture}" class="rounded-circle me-2" width="24" height="24" alt="Profile">
                                <div>
                                    <div>
                                        <a href="profile.php?id=${response.comment.user_id}" class="fw-bold text-decoration-none">${response.comment.username}</a>
                                        <small class="text-muted ms-2">${formatTimestamp(response.comment.created_at)}</small>
                                    </div>
                                    <p class="mb-0">${response.comment.content}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    commentsContainer.append(commentHtml);
                    
                    // Update comment count
                    const commentBtn = $(`.comment-btn[data-post-id="${postId}"]`);
                    const commentCount = commentBtn.find('.comment-count');
                    commentCount.text(response.comment_count);
                } else {
                    // If not logged in or error
                    if (response.message === 'Please login to perform this action') {
                        window.location.href = 'login.php';
                    } else {
                        alert(response.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                // Re-enable the submit button
                submitButton.prop('disabled', false);
            }
        });
    });
    
    // Ignore post functionality
    $(document).on('click', '.ignore-btn', function() {
        const postId = $(this).data('post-id');
        const postCard = $(`#post-${postId}`);
        
        if (confirm('Are you sure you want to hide this post?')) {
            $.ajax({
                url: 'post.php',
                type: 'POST',
                data: {
                    action: 'ignore',
                    post_id: postId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Hide the post with animation
                        postCard.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });
    
    // Load more posts functionality
    $(document).on('click', '.load-more-btn', function() {
        const button = $(this);
        const offset = $('.post-card').length;
        
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
        
        $.ajax({
            url: 'post.php',
            type: 'POST',
            data: {
                action: 'load_more',
                offset: offset
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.posts.length > 0) {
                        $.each(response.posts, function(i, post) {
                            // Append the new posts to the feed
                            const postHtml = createPostHtml(post);
                            $('.post-card').last().after(postHtml);
                        });
                        
                        button.html('Load More');
                    } else {
                        button.html('No more posts');
                        setTimeout(function() {
                            button.remove();
                        }, 2000);
                    }
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});

// Helper function to create post HTML
function createPostHtml(post) {
    const likedClass = post.liked ? 'btn-primary active' : 'btn-outline-primary';
    
    let mediaHtml = '';
    if (post.media_type === 'image') {
        mediaHtml = `<img src="${post.media_path}" class="img-fluid rounded mb-3" alt="Post image">`;
    } else if (post.media_type === 'video') {
        mediaHtml = `
            <video class="img-fluid rounded mb-3" controls>
                <source src="${post.media_path}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        `;
    }
    
    return `
        <div class="card mb-3 post-card" id="post-${post.id}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <img src="${post.profile_picture}" class="rounded-circle me-2" width="32" height="32" alt="Profile picture">
                    <a href="profile.php?id=${post.user_id}">${post.username}</a>
                </div>
                <small class="text-muted">${formatTimestamp(post.created_at)}</small>
            </div>
            
            <div class="card-body">
                ${post.content ? `<p class="card-text">${post.content}</p>` : ''}
                ${mediaHtml}
                
                <div class="d-flex justify-content-between">
                    <div>
                        <button class="btn btn-sm ${likedClass} like-btn" data-post-id="${post.id}">
                            <i class="bi bi-hand-thumbs-up"></i> 
                            Like (<span class="like-count">${post.like_count}</span>)
                        </button>
                        <button class="btn btn-sm btn-outline-secondary comment-btn" data-post-id="${post.id}">
                            <i class="bi bi-chat"></i>
                            Comments (<span class="comment-count">${post.comment_count}</span>)
                        </button>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-danger ignore-btn" data-post-id="${post.id}">
                            <i class="bi bi-eye-slash"></i> Hide
                        </button>
                    </div>
                </div>
                
                <div class="comment-section mt-3" style="display: none;" id="comments-${post.id}">
                    <hr>
                    <h6>Comments</h6>
                    
                    <div class="comments-container">
                        <!-- Comments will be loaded here -->
                    </div>
                    
                    <form class="comment-form mt-2" data-post-id="${post.id}">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" placeholder="Write a comment...">
                            <button class="btn btn-primary" type="submit">Post</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
}

// Global helper function for timestamp formatting
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString();
}