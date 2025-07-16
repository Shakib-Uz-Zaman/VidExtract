/**
 * Platform Selector for VidExtract
 * Handles platform button selection without theme changes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Update active class on platform buttons based on current selection
    function updateActivePlatformButtons() {
        // Get current platform from localStorage
        const currentPlatform = localStorage.getItem('videoType') || 'youtube';
        
        // Remove 'active' class from all platform buttons
        document.querySelectorAll('.platform-option').forEach(function(button) {
            button.classList.remove('active');
        });
        
        // Handle special case for X (Twitter)
        let platformSelector = `.platform-option.${currentPlatform}-platform`;
        if (currentPlatform === 'twitter') {
            platformSelector = '.platform-option.x-platform';
        }
        
        // Add 'active' class to the current platform's button
        const activeButton = document.querySelector(platformSelector);
        if (activeButton) {
            activeButton.classList.add('active');
            
            // Also check the radio button
            const radioButton = activeButton.querySelector('input[type="radio"]');
            if (radioButton) {
                radioButton.checked = true;
            }
        }
    }
    
    // Call this function on page load
    updateActivePlatformButtons();
    
    // Apply with slight delay to ensure all elements are ready
    setTimeout(function() {
        updateActivePlatformButtons();
        
        // Also restore selector position if that function exists
        if (typeof window.restoreSelectorScrollPosition === 'function') {
            window.restoreSelectorScrollPosition();
        }
    }, 100);
    
    // Listen for platform changes (when user switches between platforms)
    const videoTypeRadios = document.querySelectorAll('input[name="video_type"]');
    if (videoTypeRadios.length > 0) {
        videoTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                // Remove active class from all platform buttons
                document.querySelectorAll('.platform-option').forEach(function(button) {
                    button.classList.remove('active');
                });
                
                // Add active class to the parent button of this radio
                const parentButton = radio.closest('.platform-option');
                if (parentButton) {
                    parentButton.classList.add('active');
                }
                
                // Store in localStorage
                localStorage.setItem('videoType', radio.value);
                
                // Set cookie for server-side persistence
                document.cookie = "preferred_video_type=" + radio.value + "; path=/; max-age=31536000";
            });
        });
    }
    
    // Check if platform changes via localStorage events from other pages
    window.addEventListener('storage', function(event) {
        if (event.key === 'videoType') {
            // Update platform buttons active state
            updateActivePlatformButtons();
        }
    });
});