<?php
// views/footer.php
?>

<footer class="bg-light py-4 mt-5 border-top">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0 text-dark">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>


    
<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<!-- Custom JS -->
<script src="assets/js/main.js"></script>
<script src="assets/js/social-interactions.js"></script>
<script src="assets/js/like_handler.js"></script>

<script>
// Apply theme to Bootstrap components that might be dynamically created
document.addEventListener('DOMContentLoaded', function() {
    // Check current theme
    const htmlElement = document.documentElement;
    const currentTheme = htmlElement.getAttribute('data-bs-theme');
    
    // Add theme class to body for custom styling
    document.body.classList.add(`theme-${currentTheme}`);
    
    // For any theme toggle buttons
    const themeToggles = document.querySelectorAll('.theme-toggle');
    themeToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            // Update theme preference via AJAX (for logged in users)
            if (document.body.classList.contains('logged-in')) {
                fetch('update_theme.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `theme=${newTheme}&csrf_token=${document.querySelector('input[name="csrf_token"]').value}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh page to apply theme
                        window.location.reload();
                    }
                });
            } else {
                // For guest users, update cookie and reload
                const preferences = getCookie('user_preferences');
                if (preferences) {
                    const prefsObj = JSON.parse(preferences);
                    prefsObj.theme = newTheme;
                    setCookie('user_preferences', JSON.stringify(prefsObj), 30);
                    window.location.reload();
                }
            }
        });
    });
    
    // Cookie helper functions
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }
    
    function getCookie(name) {
        const decodedCookie = decodeURIComponent(document.cookie);
        const cookies = decodedCookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            let cookie = cookies[i].trim();
            if (cookie.indexOf(name + "=") === 0) {
                return cookie.substring(name.length + 1);
            }
        }
        return null;
    }
});
</script>

</body>
</html>
