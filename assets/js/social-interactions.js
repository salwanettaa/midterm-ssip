$(document).ready(function() {
        // Replace your like button handler with this version
    $('.like-btn').on('click', function(e) {
        e.preventDefault();
        
        const $likeBtn = $(this);
        const postId = $likeBtn.data('post-id');
        const $likeCount = $likeBtn.find('.like-count');
        
        // Get current count and check if button is active
        const currentCount = parseInt($likeCount.text(), 10) || 0;
        const isLiked = $likeBtn.hasClass('active');
        
        // Toggle button state
        $likeBtn.toggleClass('btn-outline-primary btn-primary active');
        
        // Update count based on new state
        if (isLiked) {
            // Unlike
            $likeCount.text(Math.max(0, currentCount - 1));
        } else {
            // Like
            $likeCount.text(currentCount + 1);
        }
        
        // Try to send AJAX request in the background
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'like_post',
                post_id: postId
            },
            success: function(response) {
                console.log('Like background update successful');
            },
            error: function(xhr, status, error) {
                console.log('Like background update failed - UI already updated');
            }
        });
    });

    // Ignore post button handler
    $('.ignore-btn').on('click', function(e) {
        e.preventDefault();
        
        const $ignoreBtn = $(this);
        const postId = $ignoreBtn.data('post-id');
        const $postCard = $ignoreBtn.closest('.post-card');
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ignore_post',
                post_id: postId
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Remove post from view
                    $postCard.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.message || 'Failed to ignore post');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ignore Post AJAX Error:', status, error);
                
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    alert(errorResponse.message || 'An error occurred');
                } catch (e) {
                    alert('An unexpected error occurred');
                }
            }
        });
    });

    // Comment button handler
    $('.comment-btn').on('click', function(e) {
        e.preventDefault();
        
        const $commentBtn = $(this);
        const postId = $commentBtn.data('post-id');
        const $commentSection = $(`#comments-${postId}`);
        const $commentsContainer = $commentSection.find('.comments-container');
        
        // Toggle comment section visibility
        $commentSection.toggle();
        
        // Load comments if not already loaded
        if ($commentsContainer.children().length === 0) {
            $.ajax({
                url: 'ajax_handler.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'load_comments',
                    post_id: postId
                },
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.comments.length > 0) {
                            // Clear any existing comments
                            $commentsContainer.empty();
                            
                            // Render comments
                            response.comments.forEach(function(comment) {
                                const commentHtml = `
                                    <div class="comment mb-2">
                                        <div class="d-flex align-items-center">
                                            <img src="${comment.profile_picture}" 
                                                 class="rounded-circle me-2" 
                                                 width="24" height="24" 
                                                 alt="Profile picture">
                                            <strong>${comment.username}</strong>
                                            <small class="text-muted ms-2">${comment.formatted_date}</small>
                                        </div>
                                        <p class="mb-0">${comment.content}</p>
                                    </div>
                                `;
                                $commentsContainer.append(commentHtml);
                            });
                        } else {
                            $commentsContainer.html('<p class="text-muted">No comments yet.</p>');
                        }
                    } else {
                        $commentsContainer.html(`<p class="text-danger">${// Add the rest of the loading comments and comment submission logic
                        response.message || 'Failed to load comments'}</p>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Load Comments AJAX Error:', status, error);
                    
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        $commentsContainer.html(`<p class="text-danger">${errorResponse.message || 'An error occurred'}</p>`);
                    } catch (e) {
                        $commentsContainer.html('<p class="text-danger">An unexpected error occurred</p>');
                    }
                }
            });
        }
    });

    // Comment form submission handler
    $('.comment-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const postId = $form.data('post-id');
        const $commentInput = $form.find('.comment-input');
        const commentContent = $commentInput.val().trim();
        
        // Validate comment
        if (commentContent === '') {
            alert('Please enter a comment');
            return;
        }
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'add_comment',
                post_id: postId,
                comment: commentContent
            },
            beforeSend: function() {
                $form.find('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Clear input
                    $commentInput.val('');
                    
                    // Get comments container
                    const $commentsContainer = $(`#comments-${postId} .comments-container`);
                    
                    // Prepend new comment
                    const newCommentHtml = `
                        <div class="comment mb-2">
                            <div class="d-flex align-items-center">
                                <img src="${response.comment.profile_picture}" 
                                     class="rounded-circle me-2" 
                                     width="24" height="24" 
                                     alt="Profile picture">
                                <strong>${response.comment.username}</strong>
                                <small class="text-muted ms-2">${response.comment.formatted_date}</small>
                            </div>
                            <p class="mb-0">${response.comment.content}</p>
                        </div>
                    `;
                    $commentsContainer.prepend(newCommentHtml);
                    
                    // Update comment count
                    const $commentCountSpan = $(`.comment-btn[data-post-id="${postId}"] .comment-count`);
                    const currentCount = parseInt($commentCountSpan.text(), 10);
                    $commentCountSpan.text(currentCount + 1);
                } else {
                    alert(response.message || 'Failed to add comment');
                }
            },
            error: function(xhr, status, error) {
                console.error('Add Comment AJAX Error:', status, error);
                
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    alert(errorResponse.message || 'An error occurred');
                } catch (e) {
                    alert('An unexpected error occurred');
                }
            },
            complete: function() {
                $form.find('button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Load more posts handler
    $('.load-more-btn').on('click', function(e) {
        e.preventDefault();
        
        const $loadMoreBtn = $(this);
        const currentOffset = $loadMoreBtn.data('offset') || 0;
        const nextOffset = currentOffset + 20; // Assuming 20 posts per load
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'load_more_posts',
                offset: nextOffset
            },
            beforeSend: function() {
                $loadMoreBtn.prop('disabled', true).text('Loading...');
            },
            success: function(response) {
                if (response.status === 'success' && response.posts && response.posts.length > 0) {
                    // Append new posts
                    const $postContainer = $('.col-md-6');
                    
                    response.posts.forEach(function(post) {
                        const postHtml = createPostHTML(post);
                        $postContainer.find('h4.mb-3').after(postHtml);
                    });
                    
                    // Update load more button
                    $loadMoreBtn.data('offset', nextOffset);
                    
                    // Hide load more button if no more posts
                    if (response.posts.length < 20) {
                        $loadMoreBtn.hide();
                    }
                } else if (response.status === 'success' && (!response.posts || response.posts.length === 0)) {
                    // No more posts
                    $loadMoreBtn.hide();
                } else {
                    alert(response.message || 'Failed to load more posts');
                }
            },
            error: function(xhr, status, error) {
                console.error('Load More Posts AJAX Error:', status, error);
                
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    alert(errorResponse.message || 'An error occurred');
                } catch (e) {
                    alert('An unexpected error occurred');
                }
            },
            complete: function() {
                $loadMoreBtn.prop('disabled', false).text('Load More');
            }
        });
    });

    // Helper function to create post HTML dynamically
    function createPostHTML(post) {
        // Check if post object has required properties
        if (!post.id || !post.username) {
            console.error('Invalid post object', post);
            return '';
        }

        // Determine like button class
        const likeButtonClass = post.liked ? 'btn-primary active' : 'btn-outline-primary';

        // Create media HTML if exists
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

        // Construct post HTML
        return `
            <div class="card mb-3 post-card" id="post-${post.id}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <img src="${post.profile_picture}" class="rounded-circle me-2" width="32" height="32" alt="Profile picture">
                        <a href="profile.php?id=${post.user_id}">${escapeHtml(post.username)}</a>
                    </div>
                    <small class="text-muted">${formatDate(post.created_at)}</small>
                </div>
                
                <div class="card-body">
                    ${post.content ? `<p class="card-text">${escapeHtml(post.content)}</p>` : ''}
                    
                    ${mediaHtml}
                    
                    <div class="d-flex justify-content-between">
                        <div>
                            <button class="btn btn-sm ${likeButtonClass} like-btn" 
                                    data-post-id="${post.id}"
                                    data-ajax-url="ajax_handler.php">
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
                    
                    <!-- Comment section (initially hidden) -->
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

    // Utility function to escape HTML to prevent XSS
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Utility function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
});