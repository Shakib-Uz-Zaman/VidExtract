/**
 * Input Actions - JavaScript functionality for the custom input form
 */

// Define the auto-resize function globally so it can be called from anywhere
// Create this function outside DOMContentLoaded to make it globally accessible
window.resizeVideoUrlInput = function(force = false) {
    const videoUrlInput = document.getElementById('video_url');
    if (!videoUrlInput) return;
    
    // Save the current scroll position
    const scrollPos = videoUrlInput.scrollTop;
    
    // Clone the textarea and use it for calculation to get accurate height
    const clone = document.createElement('textarea');
    clone.style.visibility = 'hidden';
    clone.style.position = 'absolute';
    clone.style.top = '-9999px';
    clone.style.left = '-9999px';
    clone.style.width = videoUrlInput.offsetWidth + 'px';
    clone.style.height = 'auto';
    clone.style.minHeight = '50px';
    clone.style.padding = window.getComputedStyle(videoUrlInput).padding;
    clone.style.lineHeight = window.getComputedStyle(videoUrlInput).lineHeight;
    clone.style.font = window.getComputedStyle(videoUrlInput).font;
    clone.value = videoUrlInput.value;
    
    if (document.body) {
        document.body.appendChild(clone);
    } else {
        // If document.body is not available, use a different approach
        document.documentElement.appendChild(clone);
    }
    
    // Get the content
    const content = videoUrlInput.value.trim();
    
    // Check if content contains a line break or is too long for one line
    const needsMultiline = content.includes('\n') || content.length > 60 || force;
    
    // Default height is 50px
    let newHeight = 50;
    
    // Only increase height if needed
    if (needsMultiline) {
        // Calculate based on content, but max 120px
        newHeight = Math.min(clone.scrollHeight, 120);
        
        // Ensure minimum height (50px)
        newHeight = Math.max(50, newHeight);
    }
    
    // Apply the calculated height
    videoUrlInput.style.height = newHeight + 'px';
    
    // Remove the clone
    if (document.body && document.body.contains(clone)) {
        document.body.removeChild(clone);
    } else if (document.documentElement && document.documentElement.contains(clone)) {
        document.documentElement.removeChild(clone);
    }
    
    // Smooth scroll handling
    if (videoUrlInput.scrollHeight > 120) {
        // Use requestAnimationFrame for smoother animation of scroll position
        requestAnimationFrame(() => {
            videoUrlInput.scrollTop = scrollPos;
        });
    }
    
    // Reset any background color that might have been applied
    videoUrlInput.style.backgroundColor = 'transparent';
    videoUrlInput.style.background = 'none';
    videoUrlInput.style.webkitBackgroundClip = 'text';
};

document.addEventListener('DOMContentLoaded', function() {
    // Get references to form elements
    const videoUrlInput = document.getElementById('video_url');
    const copyButton = document.getElementById('copy-button');
    const pasteButton = document.getElementById('paste-button');
    const eraseButton = document.getElementById('erase-button');
    const clearButton = document.getElementById('clear-button');
    const extractButton = document.getElementById('extract-button');
    
    // Force transparent background on input field always
    if (videoUrlInput) {
        // Initial cleanup
        videoUrlInput.style.backgroundColor = 'transparent';
        videoUrlInput.style.background = 'none';
        
        // Call on initial load
        window.resizeVideoUrlInput();
        
        // Listen for the input event which happens after paste
        videoUrlInput.addEventListener('input', function() {
            window.resizeVideoUrlInput();
        });
        
        // Listen for paste event directly
        videoUrlInput.addEventListener('paste', function() {
            // Clean background immediately
            videoUrlInput.style.backgroundColor = 'transparent';
            videoUrlInput.style.background = 'none';
            
            // Use setTimeout to catch background colors applied after paste
            // and also resize the textarea
            setTimeout(function() {
                videoUrlInput.style.backgroundColor = 'transparent';
                videoUrlInput.style.background = 'none';
                videoUrlInput.style.webkitBackgroundClip = 'text';
                window.resizeVideoUrlInput();
            }, 10);
        });
    }
    
    // Apply themed class to action buttons
    const actionButtons = [copyButton, pasteButton, eraseButton];
    actionButtons.forEach(button => {
        if (button) button.classList.add('themed');
    });
    
    // Copy button - copies input content to clipboard
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const text = videoUrlInput.value.trim();
            
            if (text === '') {
                // Visual feedback for empty input
                showButtonFeedback(copyButton, false);
                
                // Show message in placeholder
                const originalPlaceholder = videoUrlInput.placeholder;
                videoUrlInput.placeholder = "Nothing to copy";
                
                // Restore placeholder after delay
                setTimeout(() => {
                    // Get the current platform from localStorage
                    const currentPlatform = localStorage.getItem('videoType') || 'youtube';
                    if (currentPlatform === 'facebook') {
                        videoUrlInput.placeholder = 'Enter Facebook URL';
                    } else if (currentPlatform === 'instagram') {
                        videoUrlInput.placeholder = 'Enter Instagram URL';
                    } else if (currentPlatform === 'twitter') {
                        videoUrlInput.placeholder = 'Enter Twitter/X URL';
                    } else {
                        // Default to YouTube
                        videoUrlInput.placeholder = 'Enter Youtube URL';
                    }
                }, 2000);
                
                return;
            }
            
            // Try using the Clipboard API first (more modern)
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        // Success feedback
                        showButtonFeedback(copyButton, true);
                    })
                    .catch(err => {
                        console.error('Failed to copy text: ', err);
                        // Fallback to the legacy method
                        legacyCopyToClipboard();
                    });
            } else {
                // Fallback for browsers that don't support Clipboard API
                legacyCopyToClipboard();
            }
            
            // Legacy copy method
            function legacyCopyToClipboard() {
                // Create a temporary textarea element
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                
                // Select and copy the text
                textarea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        // Success feedback
                        showButtonFeedback(copyButton, true);
                    } else {
                        // Error feedback
                        showButtonFeedback(copyButton, false);
                    }
                } catch (err) {
                    console.error('Failed to copy using legacy method: ', err);
                    showButtonFeedback(copyButton, false);
                }
                
                // Remove the temporary element
                document.body.removeChild(textarea);
            }
        });
    }
    
    // Paste button - pastes clipboard content to input with enhanced cross-browser/device compatibility
    if (pasteButton) {
        pasteButton.addEventListener('click', function() {
            // Function to update input with clipboard text
            function updateInputWithText(text) {
                if (text && text.trim() !== '') {
                    videoUrlInput.value = text.trim();
                    // Visual feedback
                    showButtonFeedback(pasteButton, true);
                    
                    // Clear any background color immediately
                    videoUrlInput.style.backgroundColor = 'transparent';
                    videoUrlInput.style.background = 'none';
                    
                    // Clean background again with delay to catch all browsers
                    setTimeout(function() {
                        videoUrlInput.style.backgroundColor = 'transparent';
                        videoUrlInput.style.background = 'none';
                    }, 0);
                    
                    // Ensure the input event is triggered
                    videoUrlInput.dispatchEvent(new Event('input'));
                    
                    return true;
                }
                return false;
            }
            
            // Function to handle paste failure with appropriate message
            function handlePasteFailure(errorType) {
                console.log('Paste failed: ' + (errorType || 'unknown error'));
                
                // Visual feedback animation to indicate action was recognized
                // Pass 'false' to indicate failure - don't change icon to checkmark
                showButtonFeedback(pasteButton, false);
                
                // Transform the videoUrlInput placeholder temporarily to guide the user
                const originalPlaceholder = videoUrlInput.placeholder;
                
                // Different error message based on error type
                let errorMessage;
                if (errorType === 'permission') {
                    errorMessage = "Paste permission denied. Paste manually or use Ctrl+V/⌘+V";
                } else if (errorType === 'empty') {
                    errorMessage = "Clipboard is empty. Copy a URL first";
                } else {
                    errorMessage = "Browser cannot paste. Please paste manually";
                }
                
                videoUrlInput.placeholder = errorMessage;
                
                // Focus the input to make it easier for the user to paste manually
                videoUrlInput.focus();
                
                // Reset the placeholder after a delay
                setTimeout(() => {
                    // Get the current platform from localStorage
                    const currentPlatform = localStorage.getItem('videoType') || 'youtube';
                    if (currentPlatform === 'facebook') {
                        videoUrlInput.placeholder = 'Enter Facebook URL';
                    } else if (currentPlatform === 'instagram') {
                        videoUrlInput.placeholder = 'Enter Instagram URL';
                    } else if (currentPlatform === 'twitter') {
                        videoUrlInput.placeholder = 'Enter Twitter/X URL';
                    } else {
                        // Default to YouTube
                        videoUrlInput.placeholder = 'Enter Youtube URL';
                    }
                }, 3000);
            }
            
            // Try multiple paste methods in sequence for maximum compatibility
            
            // Check if it's likely a mobile device and check specific browser info
            const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            
            // Check if we're in a WebView (which often has even tighter clipboard restrictions)
            const isWebView = /(WebView|wv)|(Version\/.+Chrome\/.+Mobile)/i.test(navigator.userAgent);
            const isAndroidWebView = /Android.*(wv|.+Chrome\/.+Version\/|WebView)/i.test(navigator.userAgent);
            const isIOSWebView = isIOS && /Safari\//.test(navigator.userAgent) === false;
            
            // METHOD 0: For mobile devices, let's try a more direct approach first
            if (isMobileDevice) {
                // Special handling for WebViews which have the strictest clipboard permissions
                if (isWebView || isAndroidWebView || isIOSWebView) {
                    // For WebViews, we need to be extremely clear and focus on native input methods
                    videoUrlInput.focus();
                    videoUrlInput.value = '';
                    
                    // Very clear instructions for WebView users
                    if (isAndroidWebView) {
                        videoUrlInput.placeholder = "Long-press & select 'Paste'";
                    } else if (isIOSWebView) {
                        videoUrlInput.placeholder = "Double-tap & select 'Paste'";
                    } else {
                        videoUrlInput.placeholder = "Tap & use keyboard to paste";
                    }
                    
                    // Show feedback that button was pressed
                    showButtonFeedback(pasteButton, false);
                    
                    // Restore placeholder after a delay
                    setTimeout(() => {
                        if (videoUrlInput.value.trim() === '') {
                            // Get the current platform from localStorage
                            const currentPlatform = localStorage.getItem('videoType') || 'youtube';
                            if (currentPlatform === 'facebook') {
                                videoUrlInput.placeholder = 'Enter Facebook URL';
                            } else if (currentPlatform === 'instagram') {
                                videoUrlInput.placeholder = 'Enter Instagram URL';
                            } else if (currentPlatform === 'twitter') {
                                videoUrlInput.placeholder = 'Enter Twitter/X URL';
                            } else {
                                // Default to YouTube
                                videoUrlInput.placeholder = 'Enter Youtube URL';
                            }
                        }
                    }, 6000); // Extra long delay for WebView users
                }
                // Special handling for iOS Safari which has stricter clipboard permissions
                else if (isIOS && isSafari) {
                    // For iOS Safari, we need to focus and make it very clear to the user
                    // that they need to use the native paste functionality
                    videoUrlInput.focus();
                    
                    // Special message for iOS Safari users
                    videoUrlInput.placeholder = "Tap in box → Tap 'Paste'";
                    
                    // Clear any existing value to ensure paste option shows
                    videoUrlInput.value = '';
                    
                    // Show feedback that button was pressed
                    showButtonFeedback(pasteButton, false);
                    
                    // Restore placeholder after a delay
                    setTimeout(() => {
                        if (videoUrlInput.value.trim() === '') {
                            // Get the current platform from localStorage
                            const currentPlatform = localStorage.getItem('videoType') || 'youtube';
                            if (currentPlatform === 'facebook') {
                                videoUrlInput.placeholder = 'Enter Facebook URL';
                            } else if (currentPlatform === 'instagram') {
                                videoUrlInput.placeholder = 'Enter Instagram URL';
                            } else if (currentPlatform === 'twitter') {
                                videoUrlInput.placeholder = 'Enter Twitter/X URL';
                            } else {
                                // Default to YouTube
                                videoUrlInput.placeholder = 'Enter Youtube URL';
                            }
                        }
                    }, 5000); // Longer delay for iOS users to notice
                } else {
                    // For other mobile browsers, focus the input directly as most modern mobile browsers
                    // show a paste option when focusing an empty input field
                    videoUrlInput.focus();
                    videoUrlInput.value = ''; // Clear it to ensure paste menu shows up
                    videoUrlInput.placeholder = "Tap & hold here to paste";
                    
                    // Add visual feedback to show the button was clicked
                    showButtonFeedback(pasteButton, false);
                    
                    // Set a timeout to restore the placeholder
                    setTimeout(() => {
                        if (videoUrlInput.value.trim() === '') {
                            // Get the current platform from localStorage
                            const currentPlatform = localStorage.getItem('videoType') || 'youtube';
                            if (currentPlatform === 'facebook') {
                                videoUrlInput.placeholder = 'Enter Facebook URL';
                            } else if (currentPlatform === 'instagram') {
                                videoUrlInput.placeholder = 'Enter Instagram URL';
                            } else if (currentPlatform === 'twitter') {
                                videoUrlInput.placeholder = 'Enter Twitter/X URL';
                            } else {
                                // Default to YouTube
                                videoUrlInput.placeholder = 'Enter Youtube URL';
                            }
                        }
                    }, 3000);
                }
                
                // Register a one-time input event to detect when the user pastes (for all mobile devices)
                const inputHandler = function() {
                    // User has pasted something, show visual feedback
                    if (videoUrlInput.value.trim() !== '') {
                        showButtonFeedback(pasteButton, true);
                    }
                    // Remove the event listener
                    videoUrlInput.removeEventListener('input', inputHandler);
                };
                
                videoUrlInput.addEventListener('input', inputHandler);
                
                // Continue with other methods as fallback
            }
            
            // METHOD 1: Modern Clipboard API (most modern browsers)
            if (navigator.clipboard && navigator.clipboard.readText) {
                navigator.clipboard.readText()
                    .then(text => {
                        if (updateInputWithText(text)) {
                            return; // Success
                        } else {
                            handlePasteFailure('empty');
                        }
                    })
                    .catch(err => {
                        // If permission denied or other error, try fallback methods
                        console.log('Clipboard API error:', err);
                        
                        // METHOD 2: execCommand fallback (older browsers)
                        try {
                            // Create a temporary textarea element for better paste support
                            const tempTextArea = document.createElement('textarea');
                            tempTextArea.setAttribute('readonly', '');
                            tempTextArea.style.position = 'fixed';
                            tempTextArea.style.top = '0';
                            tempTextArea.style.left = '0';
                            tempTextArea.style.opacity = '0';
                            tempTextArea.style.pointerEvents = 'none';
                            tempTextArea.style.zIndex = '-1';
                            document.body.appendChild(tempTextArea);
                            
                            // Focus the temporary element and try to paste
                            tempTextArea.focus();
                            
                            // Use execCommand for older browser compatibility
                            const successful = document.execCommand('paste');
                            
                            if (successful) {
                                // Get the pasted text from the temporary element
                                const pastedText = tempTextArea.value;
                                
                                // Remove the temporary element
                                document.body.removeChild(tempTextArea);
                                
                                if (updateInputWithText(pastedText)) {
                                    return; // Success
                                } else {
                                    handlePasteFailure('empty');
                                }
                            } else {
                                // Remove the temporary element
                                document.body.removeChild(tempTextArea);
                                
                                // METHOD 3: Event-based paste as last resort
                                videoUrlInput.focus();
                                
                                // Add one-time paste event listener
                                const pasteHandler = function(e) {
                                    // Get pasted text from clipboard event if available
                                    let clipboardData = e.clipboardData || window.clipboardData;
                                    let pastedText = clipboardData ? clipboardData.getData('text') : '';
                                    
                                    if (updateInputWithText(pastedText)) {
                                        // Success - do nothing else
                                    } else {
                                        handlePasteFailure('empty');
                                    }
                                    
                                    // Clean up event listener
                                    document.removeEventListener('paste', pasteHandler);
                                };
                                
                                // Listen for paste event
                                document.addEventListener('paste', pasteHandler, { once: true });
                                
                                // Alert user to use keyboard shortcut (only for desktop)
                                // For mobile devices, provide a more appropriate instruction
                                if (window.innerWidth >= 768) {
                                    // Desktop devices
                                    handlePasteFailure('manual');
                                    
                                    // Simulate keyboard paste with visual cue
                                    let shortcutHint = navigator.platform.indexOf('Mac') !== -1 ? '⌘+V' : 'Ctrl+V';
                                    videoUrlInput.placeholder = `Press ${shortcutHint} to paste`;
                                } else {
                                    // Mobile devices - show long press instruction
                                    handlePasteFailure('mobile');
                                    videoUrlInput.placeholder = "Tap & hold here to paste manually";
                                    
                                    // Focus and select any text to help reveal paste menu on mobile
                                    videoUrlInput.focus();
                                    videoUrlInput.select();
                                }
                            }
                        } catch (error) {
                            console.error('execCommand paste fallback failed:', error);
                            handlePasteFailure('permission');
                        }
                    });
            } else {
                // METHOD 2 directly if Clipboard API not available
                try {
                    // Create a temporary textarea element for better paste support
                    const tempTextArea = document.createElement('textarea');
                    tempTextArea.setAttribute('readonly', '');
                    tempTextArea.style.position = 'fixed';
                    tempTextArea.style.top = '0';
                    tempTextArea.style.left = '0';
                    tempTextArea.style.opacity = '0';
                    document.body.appendChild(tempTextArea);
                    
                    // Focus the temporary element and try to paste
                    tempTextArea.focus();
                    
                    // Use execCommand for older browser compatibility
                    const successful = document.execCommand('paste');
                    
                    // Get the pasted text from the temporary element
                    const pastedText = tempTextArea.value;
                    
                    // Remove the temporary element
                    document.body.removeChild(tempTextArea);
                    
                    if (successful && updateInputWithText(pastedText)) {
                        return; // Success
                    } else {
                        handlePasteFailure('compatibility');
                    }
                } catch (error) {
                    console.error('Legacy paste method failed:', error);
                    handlePasteFailure('browser');
                }
            }
        });
    }
    
    // Erase button - erases text from the input field
    if (eraseButton) {
        eraseButton.addEventListener('click', function() {
            if (videoUrlInput.value.trim() !== '') {
                // Clear the input field
                videoUrlInput.value = '';
                
                // Reset the textarea height to default (50px) with smooth transition
                requestAnimationFrame(() => {
                    videoUrlInput.style.height = '50px';
                });
                
                // No focus on input (as requested)
                // videoUrlInput.blur(); // Remove focus if needed
                
                // Visual feedback
                showButtonFeedback(eraseButton, true);
                
                // Update the current mode's state if needed
                const currentMode = localStorage.getItem('videoType') || 'youtube';
                if (typeof window.saveCurrentState === 'function') {
                    window.saveCurrentState(currentMode);
                }
            }
        });
    }
    
    // Clear button - clears the input field and results area and closes keyboard
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            // Clear input field
            videoUrlInput.value = '';
            
            // Reset the textarea height to default (50px) with smooth transition
            requestAnimationFrame(() => {
                videoUrlInput.style.height = '50px';
            });
            
            // Clear results container if it exists
            const resultsContainer = document.getElementById('results-container');
            if (resultsContainer) {
                resultsContainer.innerHTML = '';
            }
            
            // Hide error message if it's visible
            const errorContainer = document.getElementById('error-container');
            if (errorContainer) {
                errorContainer.style.display = 'none';
            }
            
            // Update the current mode's state
            const currentMode = localStorage.getItem('videoType') || 'youtube';
            if (typeof window.saveCurrentState === 'function') {
                window.saveCurrentState(currentMode);
            }
            
            // Remove focus to close virtual keyboard
            videoUrlInput.blur();
            
            // Visual feedback
            showButtonFeedback(clearButton, true);
            
            // Hide clear button and show extract button
            clearButton.style.display = 'none';
            extractButton.style.display = 'block';
            // Update z-index to ensure extract button is on top when clear button is hidden
            clearButton.style.zIndex = '1';
            extractButton.style.zIndex = '2';
            console.log('Clear button clicked: Showing extract button, hiding clear button');
        });
    }
    
    // Helper function for visual feedback when buttons are clicked
    /**
     * Show visual feedback for button click based on success or failure
     * 
     * @param {Element} button - The button that was clicked
     * @param {boolean} success - Whether the operation was successful (default: true)
     */
    function showButtonFeedback(button, success = true) {
        // Clear any existing timeout to prevent conflicts with multiple calls
        if (button._feedbackTimeout) {
            clearTimeout(button._feedbackTimeout);
            button._feedbackTimeout = null;
        }
        
        // Store the original icon color and class
        const iconElement = button.querySelector('i');
        const originalOpacity = iconElement.style.opacity;
        const originalIconClass = iconElement.className;
        
        // Use black button feedback color
        const primaryColor = '#000000'; // Black color for all buttons
        
        // Store original class on the button for reference if not already stored
        if (!button._originalIconClass) {
            button._originalIconClass = originalIconClass;
        }
        
        // Log button feedback for debugging
        console.log("Button feedback activated - will reset in 1500ms");
        
        if (success) {
            // Success feedback - change icon to check mark
            iconElement.className = 'fas fa-check';
            iconElement.style.opacity = '1';
            iconElement.style.setProperty('color', 'white', 'important');  // Force white color with !important
            
            // Force button to display as flex with important flag for centering
            button.style.setProperty('display', 'inline-flex', 'important');
            button.style.setProperty('justify-content', 'center', 'important');
            button.style.setProperty('align-items', 'center', 'important');
            
            // Make sure the icon stays centered
            iconElement.style.setProperty('position', 'absolute', 'important');
            iconElement.style.setProperty('top', '50%', 'important');
            iconElement.style.setProperty('left', '50%', 'important');
            iconElement.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
            
            // Apply theme-based background color to all buttons
            button.style.setProperty('background-color', primaryColor, 'important');
            button.style.setProperty('border-color', primaryColor, 'important');
            
            console.log('Applied background color to button: ' + primaryColor);
        } else {
            // Error feedback - change opacity only, don't change icon
            iconElement.style.opacity = '1';
        }
        
        // Set text color to white when showing success feedback
        if (success) {
            button.style.setProperty('color', 'white', 'important');
        }
        
        // Reset back to original after a delay
        button._feedbackTimeout = setTimeout(function() {
            // Restore original icon and style
            // Use stored original class to ensure correct restoration
            iconElement.className = button._originalIconClass || originalIconClass;
            iconElement.style.opacity = originalOpacity;
            iconElement.style.color = '';
            button.style.color = '';
            
            // Remove the !important inline styles
            button.style.removeProperty('background-color');
            button.style.removeProperty('border-color');
            button.style.removeProperty('display');
            button.style.removeProperty('justify-content');
            button.style.removeProperty('align-items');
            
            // Keep icon positioning intact by not removing these styles
            // This ensures the icon remains properly centered in the button
            
            // Determine the current theme and theme color
            let currentTheme = 'youtube'; // default
            let themeColor = '#ff0000'; // default youtube red
            let rgbaThemeColor = 'rgba(255, 0, 0, 0.6)'; // default
            
            if (document.documentElement.classList.contains('facebook-theme')) {
                currentTheme = 'facebook';
                themeColor = '#1877f2'; // Facebook blue
                rgbaThemeColor = 'rgba(24, 119, 242, 0.6)';
            } else if (document.documentElement.classList.contains('instagram-theme')) {
                currentTheme = 'instagram';
                themeColor = '#AB47BC'; // Instagram purple
                rgbaThemeColor = 'rgba(171, 71, 188, 0.6)';
            } else if (document.documentElement.classList.contains('twitter-theme')) {
                currentTheme = 'twitter';
                themeColor = '#000000'; // X/Twitter black
                rgbaThemeColor = 'rgba(0, 0, 0, 0.6)';
            } else if (document.documentElement.classList.contains('')) {
                currentTheme = '';
                themeColor = '#BB0835'; //  red
                rgbaThemeColor = 'rgba(187, 8, 53, 0.6)';
            }
            
            // Apply theme-based border style to all buttons
            if (button.classList.contains('themed')) {
                button.style.setProperty('border', '1px solid rgba(var(--primary-color-rgb), 0.3)');
                button.style.setProperty('background-color', 'rgba(var(--primary-color-rgb), 0.02)');
            } else {
                // Apply theme-based border to action buttons
                button.style.setProperty('border', '1px solid ' + rgbaThemeColor);
                button.style.setProperty('background-color', 'transparent');
            }
            
            button._feedbackTimeout = null;
            
            console.log("Button feedback reset - returning to normal state");
        }, 1500);
    }
    
    // Focus the input field when page loads
    videoUrlInput.focus();
});