/**
 * Page Transition Functionality
 * 
 * This script handles showing and hiding the loading spinner
 * when navigating between pages of the website.
 * 
 * It works with adaptive-spinner.js to adjust spinner animation
 * based on network connection speed.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality with simplified icon change
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const siteHeader = document.querySelector('.site-header');
    
    if (mobileMenuToggle && siteHeader) {
        mobileMenuToggle.addEventListener('click', function() {
            // Toggle menu open state
            siteHeader.classList.toggle('menu-open');
            
            // Simple direct icon swap without animation
            const icon = mobileMenuToggle.querySelector('i');
            if (icon) {
                // Simply replace the icon class without fancy animations
                if (siteHeader.classList.contains('menu-open')) {
                    // Replace icon without transition effects
                    icon.className = 'fas fa-times';
                } else {
                    // Replace icon without transition effects
                    icon.className = 'fas fa-bars';
                }
            }
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!siteHeader.contains(event.target) && siteHeader.classList.contains('menu-open')) {
                siteHeader.classList.remove('menu-open');
                
                // Reset to hamburger icon without animation when menu closes
                const icon = mobileMenuToggle.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-bars';
                }
            }
        });
    }
    
    // Get the overlay element
    const overlay = document.getElementById('page-transition-overlay');
    
    // If overlay doesn't exist, exit early
    if (!overlay) return;
    
    // Function to hide spinner
    function hideSpinner() {
        if (overlay.classList.contains('active')) {
            overlay.classList.remove('active');
        }
    }
    
    // Wait until the theme is properly applied before hiding the spinner
    // This ensures the theme transition doesn't happen under the spinner
    function safeHideSpinner() {
        // Check if theme is being switched from localStorage
        const storedTheme = localStorage.getItem('videoType');
        
        if (storedTheme === 'facebook') {
            // For Facebook theme, make sure theme classes are applied before hiding spinner
            document.documentElement.classList.add('facebook-theme');
            document.body.classList.add('facebook-theme');
            
            // After ensuring theme is applied, hide spinner with a slight delay
            // This delay ensures the theme is visible before spinner disappears
            setTimeout(hideSpinner, 200);
        } else {
            // For YouTube theme (default), we can hide right away
            document.documentElement.classList.remove('facebook-theme');
            document.body.classList.remove('facebook-theme');
            hideSpinner();
        }
    }
    
    // Apply the safe hiding approach
    safeHideSpinner();
    
    // Always hide spinner after content is fully loaded
    window.addEventListener('load', safeHideSpinner);
    
    // Force hide spinner after a longer timeout (fallback) to ensure theme is applied
    setTimeout(hideSpinner, 1500);
    
    // Always show overlay on page refresh/navigation
    window.addEventListener('beforeunload', function() {
        overlay.classList.add('active');
    });
    
    // Force spinner to be active even if browser load indicator stops
    function ensureSpinnerActive() {
        // If we're still loading and spinner is not visible, make sure it shows
        if (document.readyState !== 'complete' && overlay && !overlay.classList.contains('active')) {
            overlay.classList.add('active');
        }
    }
    
    // Check spinner status every 200ms to ensure it continues spinning
    setInterval(ensureSpinnerActive, 200);
    
    // Find all links that are internal to our site (excluding download links)
    const links = document.querySelectorAll('a[href^="/"]:not([target]):not([data-no-spinner]), a[href^="./"]:not([target]):not([data-no-spinner]), a[href^="../"]:not([target]):not([data-no-spinner]), a[href$=".php"]:not([target]):not([data-no-spinner])');
    
    // Add click event listeners to all internal links
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            // Skip if modifier keys are pressed (for opening in new tab, etc.)
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;
            
            // Get the href attribute
            const href = this.getAttribute('href');
            
            // Skip AJAX requests or links with # (anchor links) 
            // or download.php links which should be opened without spinner
            if (href.includes('#') || 
                this.hasAttribute('data-ajax') || 
                href.startsWith('download.php') || 
                this.hasAttribute('data-no-spinner')) return;
            
            // Prevent default behavior
            e.preventDefault();
            
            // Show the overlay immediately with full opacity
            overlay.classList.add('active');
            
            // Before navigating, make sure theme preferences are applied
            // and stored in cookie to ensure they persist across page loads
            const storedTheme = localStorage.getItem('videoType');
            if (storedTheme) {
                document.cookie = "preferred_video_type=" + storedTheme + "; path=/; max-age=31536000";
            }
            
            // Apply theme classes immediately before navigation
            if (storedTheme === 'facebook') {
                document.documentElement.classList.add('facebook-theme');
                document.body.classList.add('facebook-theme');
            } else {
                document.documentElement.classList.remove('facebook-theme');
                document.body.classList.remove('facebook-theme');
            }
            
            // Allow a longer delay for the animation to be visible before navigating
            // Also ensures the white background is fully visible
            setTimeout(() => {
                window.location.href = href;
            }, 80); // Slightly longer delay for smoother transition
        });
    });
    
    // Handle back/forward browser buttons (History API)
    window.addEventListener('popstate', function() {
        overlay.classList.add('active');
        // Use our safer hiding method that respects theme
        setTimeout(safeHideSpinner, 500);
    });
    
    // Special mobile back button handling
    let lastPopStateTime = 0;
    window.addEventListener('pageshow', function(event) {
        // Use our safer hiding method that respects theme
        safeHideSpinner();
        
        // If the page was loaded from cache (mobile back button)
        if (event.persisted) {
            // Additional timeout to ensure the spinner gets hidden with theme applied
            setTimeout(safeHideSpinner, 200);
        }
    });
    
    // Update the spinner when network conditions change
    function updateSpinnerForNetworkChanges() {
        // Call the adaptive spinner update if available
        if (window.adaptiveSpinner && typeof window.adaptiveSpinner.update === 'function') {
            window.adaptiveSpinner.update();
        }
        
        // Also ensure spinner is active during page loading
        ensureSpinnerActive();
    }
    

    
    // Also update spinner animation when the connection type changes, if the API is available
    if (navigator.connection) {
        navigator.connection.addEventListener('change', updateSpinnerForNetworkChanges);
    }
    
    // Initial update
    updateSpinnerForNetworkChanges();
});