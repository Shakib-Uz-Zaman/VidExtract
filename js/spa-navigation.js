/**
 * URL Cleaner for VidExtract
 *
 * This enhanced script hides page names in the browser URL bar
 * when navigating between different sections of the website.
 * It provides a seamless SPA-like experience while maintaining traditional navigation.
 */

// Make the current URL cleaner by replacing it with root URL
document.addEventListener('DOMContentLoaded', function() {
    // Only proceed if History API is available
    if (!window.history || !window.history.replaceState) {
        console.warn('SPA Navigation not supported in this browser');
        return; // Exit if browser doesn't support history API
    }

    // Get current location info
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop();
    const rootUrl = window.location.origin + '/';
    
    // Save the actual page for back button functionality
    if (currentPage && 
        currentPage !== '' && 
        currentPage !== 'index.php' && 
        currentPage !== '/') {
        // Store the current page information in history state
        window.history.replaceState({page: currentPage}, document.title, rootUrl);
        console.log('URL cleaner: URL hidden for ' + currentPage);
        
        // Add an entry to session storage to keep track of current page
        sessionStorage.setItem('currentPage', currentPage);
    } else {
        // We're on the home page
        sessionStorage.setItem('currentPage', 'index.php');
    }

    // Add special click handler to all internal navigation links
    document.body.addEventListener('click', function(e) {
        // Find if we clicked on an internal link 
        let target = e.target;
        while (target && target !== document) {
            if (target.tagName === 'A') {
                const href = target.getAttribute('href');
                
                // Only process internal links that aren't downloads or external
                if (href && 
                    !href.includes('://') && 
                    !href.includes('download.php') && 
                    !target.hasAttribute('download')) {
                    
                    // Remember which page we're navigating to for URL cleanup
                    let destinationPage = href;
                    
                    // Handle both relative and root-relative links
                    if (destinationPage.startsWith('/')) {
                        destinationPage = destinationPage.substring(1);
                    }
                    
                    // Store the destination for cleanup after page load
                    sessionStorage.setItem('navigatingTo', destinationPage);
                    sessionStorage.setItem('cleanUrlAfterLoad', 'true');
                }
                break;
            }
            target = target.parentNode;
        }
    });
    
    // Set up a handler for browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.page) {
            // If we have a saved page, navigate to it
            window.location.href = event.state.page;
        } else {
            // Default to home if no state is available
            window.location.href = 'index.php';
        }
    });
});

// Clean URL after page load
window.addEventListener('load', function() {
    if (sessionStorage.getItem('cleanUrlAfterLoad') === 'true') {
        // Clear the flag
        sessionStorage.removeItem('cleanUrlAfterLoad');
        
        // Get the page we navigated to
        const navigatedTo = sessionStorage.getItem('navigatingTo') || window.location.pathname.split('/').pop();
        sessionStorage.removeItem('navigatingTo');
        
        // Update current page tracking
        sessionStorage.setItem('currentPage', navigatedTo);
        
        // Clean the URL
        if (window.history && window.history.replaceState) {
            const rootUrl = window.location.origin + '/';
            window.history.replaceState(
                {page: navigatedTo}, 
                document.title, 
                rootUrl
            );
            console.log('URL cleaned after navigation to: ' + navigatedTo);
        }
    }
});
