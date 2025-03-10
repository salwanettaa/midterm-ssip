$(document).ready(function() {
    console.log('Comment script loaded!');
    
    // Test AJAX connection
    $.ajax({
        url: 'ajax_handler.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'test'
        },
        success: function(response) {
            console.log('AJAX test response:', response);
        },
        error: function(xhr, status, error) {
            console.error('AJAX test failed:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
        }
    });
    
    // Prevent other click events from interfering
    $(document).on('click', function(e) {
        // Prevent default only for specific elements
        if ($(e.target).closest('.comment-btn, .comment-section, .like-btn, .ignore-btn').length) {
            e.stopPropagation();
        }
    });
    
    // Comment button click handler - show/hide comments and load them
    $(document).on('click', '.comment-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Comment button clicked');
        const postId = $(this).data('post-id');
        console.log('Post ID:', postId);
        
        const commentSection = $(`#comments-${postId}`);
        console.log('Comment section found:', commentSection.length);
        
        // Toggle visibility
        if (commentSection.is(':visible')) {
            commentSection.hide();
            console.log('Hiding comment section');
        } else {
            console.log('Showing comment section');
            commentSection.show();
            
            // If comments haven't been loaded yet, load them
            if (commentSection.find('.comments-container').is(':empty')) {
                console.log('Loading comments for post:', postId);
                loadComments(postId);
            }
        }
        
        // Prevent the event from propagating up
        return false;
    });
    
    // Comment form submission
    $(document).on('submit', '.comment-form', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Comment form submitted');
        
        const postId = $(this).data('post-id');
        const commentInput = $(this).find('.comment-input');
        const comment = commentInput.val().trim();
        
        console.log('Submitting comment for post:', postId);
        console.log('Comment:', comment);
        
        if (comment) {
            addComment(postId, comment);
            commentInput.val(''); // Clear input after submission
        }
        
        return false;
    });
    
    // Function to load comments
    function loadComments(postId) {
        const commentContainer = $(`#comments-${postId} .comments-container`);
        commentContainer.html('<p class="text-center"><i class="bi bi-hourglass"></i> Loading comments...</p>');
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'load_comments',
                post_id: postId
            },
            success: function(response) {
                console.log('Load comments response:', response);
                
                if (response.status === 'success') {
                    // Clear container
                    commentContainer.empty();
                    
                    if (response.comments.length === 0) {
                        commentContainer.html('<p class="text-muted">No comments yet</p>');
                        return;
                    }
                    
                    // Add each comment to the container
                    $.each(response.comments, function(i, comment) {
                        commentContainer.append(`
                            <div class="comment mb-2">
                                <div class="d-flex">
                                    <img src="${comment.profile_picture}" class="rounded-circle me-2" width="24" height="24" alt="Profile">
                                    <div>
                                        <div><strong>${comment.username}</strong> <small class="text-muted">${comment.formatted_date}</small></div>
                                        <div>${comment.content}</div>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    commentContainer.html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading comments:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                commentContainer.html('<div class="alert alert-danger">Error loading comments</div>');
            }
        });
    }
    
    // Function to add a comment
    function addComment(postId, comment) {
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'add_comment',
                post_id: postId,
                comment: comment
            },
            success: function(response) {
                console.log('Add comment response:', response);
                
                if (response.status === 'success') {
                    // Add the new comment to the top of the comments
                    const commentContainer = $(`#comments-${postId} .comments-container`);
                    
                    // If there was a "no comments yet" message, remove it
                    if (commentContainer.find('.text-muted').length > 0) {
                        commentContainer.empty();
                    }
                    
                    commentContainer.prepend(`
                        <div class="comment mb-2">
                            <div class="d-flex">
                                <img src="${response.comment.profile_picture}" class="rounded-circle me-2" width="24" height="24" alt="Profile">
                                <div>
                                    <div><strong>${response.comment.username}</strong> <small class="text-muted">${response.comment.formatted_date}</small></div>
                                    <div>${response.comment.content}</div>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    // Update comment count
                    const commentCountElement = $(`button.comment-btn[data-post-id="${postId}"] .comment-count`);
                    const newCount = parseInt(commentCountElement.text()) + 1;
                    commentCountElement.text(newCount);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error adding comment:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                alert('Error adding comment. Please try again.');
            }
        });
    }
    
    // Handle ignore button clicks
    $(document).on('click', '.ignore-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const postId = $(this).data('post-id');
        const postCard = $(`#post-${postId}`);
        
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
                    // Hide the post
                    postCard.slideUp(300, function() {
                        $(this).remove();
                    });
                }
            }
        });
        
        return false;
    });
    
    // Handle like button clicks with proper database connection
    $(document).on('click', '.like-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = $(this);
        const postId = btn.data('post-id');
        
        console.log('Like button clicked for post ID:', postId);
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'like_post',
                post_id: postId
            },
            success: function(response) {
                console.log('Like response:', response);
                
                if (response.status === 'success') {
                    // Update like count
                    btn.find('.like-count').text(response.like_count);
                    
                    // Toggle button style
                    if (response.liked) {
                        btn.removeClass('btn-outline-primary').addClass('btn-primary active');
                    } else {
                        btn.removeClass('btn-primary active').addClass('btn-outline-primary');
                    }
                } else {
                    // Show error message
                    console.error('Error liking post:', response.message);
                    if (response.message.includes('login')) {
                        // If it's a login-related error, redirect to login page
                        window.location.href = 'login.php';
                    } else {
                        alert(response.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                console.error('Response:', xhr.responseText);
            }
        });
        
        return false;
    });
});