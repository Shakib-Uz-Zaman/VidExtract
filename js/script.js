/**
 * YouTube Video Info Extractor
 * 
 * JavaScript functionality for copying text to clipboard
 * and handling user interactions.
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize UI state
    const resultsContainer = document.getElementById('results-container');
    const clearButton = document.getElementById('clear-button');
    const extractButton = document.getElementById('extract-button');
    
    // Make sure extract button is shown at start when there are no results
    if (!resultsContainer.querySelector('.results')) {
        if (clearButton) clearButton.style.display = 'none';
        if (extractButton) extractButton.style.display = 'block';
        if (clearButton) clearButton.style.zIndex = '1'; 
        if (extractButton) extractButton.style.zIndex = '2';
    }
    
    // Initialize platform selection buttons based on current mode
    const platformOptions = document.querySelectorAll('.platform-option');
    
    // Set platform from localStorage if available
    const savedPlatform = localStorage.getItem('videoType');
    if (savedPlatform) {
        // Find the right platform option
        const platformSelector = savedPlatform === 'twitter' ? 
            '.platform-option.x-platform' : 
            `.platform-option.${savedPlatform}-platform`;
            
        const platformOption = document.querySelector(platformSelector);
        if (platformOption) {
            // Remove active class from all options
            platformOptions.forEach(opt => {
                opt.classList.remove('active');
            });
            
            // Add active class to the saved platform
            platformOption.classList.add('active');
            
            // Check the radio button
            const radioButton = platformOption.querySelector('input[type="radio"]');
            if (radioButton) {
                radioButton.checked = true;
            }
        }
    }
    
    // Save scroll position of the platforms selector
    function saveSelectorScrollPosition() {
        const platformSelector = document.querySelector('.video-platforms-selector');
        if (platformSelector) {
            localStorage.setItem('platformSelectorScrollLeft', platformSelector.scrollLeft);
        }
    }
    
    // Expose the function to the global scope so it can be called from other scripts
    window.saveSelectorScrollPosition = saveSelectorScrollPosition;
    
    // Restore scroll position of the platforms selector
    function restoreSelectorScrollPosition() {
        const platformSelector = document.querySelector('.video-platforms-selector');
        const savedScrollLeft = localStorage.getItem('platformSelectorScrollLeft');
        
        if (platformSelector && savedScrollLeft !== null) {
            // Try to set the scroll position immediately
            platformSelector.scrollLeft = parseInt(savedScrollLeft, 10);
            
            // Also use setTimeout with multiple delays to ensure it works reliably across browsers
            setTimeout(() => {
                platformSelector.scrollLeft = parseInt(savedScrollLeft, 10);
            }, 100);
            
            setTimeout(() => {
                platformSelector.scrollLeft = parseInt(savedScrollLeft, 10);
            }, 500);
        }
    }
    
    // Listen for scroll events on the platforms selector to save position
    const platformSelector = document.querySelector('.video-platforms-selector');
    if (platformSelector) {
        platformSelector.addEventListener('scroll', function() {
            saveSelectorScrollPosition();
        });
    }
    
    // Restore the scroll position when the page loads
    restoreSelectorScrollPosition();
    
    // Setup click event listeners for platform options
    platformOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove active class from all platform options
            platformOptions.forEach(opt => {
                opt.classList.remove('active');
            });
            
            // Add active class to selected option
            this.classList.add('active');
            
            // Get the selected platform value
            const radioButton = this.querySelector('input[type="radio"]');
            if (radioButton) {
                // Ensure this remembered for refresh
                localStorage.setItem('videoType', radioButton.value);
                
                // Set cookie for server-side persistence
                document.cookie = "preferred_video_type=" + radioButton.value + "; path=/; max-age=31536000";
                
                // Save the scroll position immediately
                saveSelectorScrollPosition();
            }
        });
    });
});

// Add animation keyframes for toast messages
(function() {
    // Create a style element for keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translate3d(0, -20px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }
    `;
    if (document.head) {
        document.head.appendChild(style);
    } else if (document.documentElement) {
        document.documentElement.appendChild(style);
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission with AJAX
    const videoForm = document.getElementById('video-form');
    const extractButton = document.getElementById('extract-button');
    const clearButton = document.getElementById('clear-button');
    // Loading spinner element has been removed
    const buttonText = extractButton.querySelector('.button-text');
    const resultsContainer = document.getElementById('results-container');
    const errorContainer = document.getElementById('error-container');
    const errorMessage = document.getElementById('error-message');
    const videoUrl = document.getElementById('video_url');
    const videoTypeRadios = document.querySelectorAll('input[name="video_type"]');
    
    // Handle regular form submission (without AJAX)
    // This handles the case when JavaScript is disabled or the AJAX request isn't used
    if (videoForm) {
        videoForm.addEventListener('submit', function() {
            // If there's already content in the results, show clear button and hide extract button
            setTimeout(function() {
                if (resultsContainer && resultsContainer.innerHTML.trim() !== '') {
                    if (clearButton) clearButton.style.display = '';
                    if (extractButton) extractButton.style.display = 'none';
                }
            }, 100);
        });
    }
    
    // DOM elements for recent extractions
    const youtubeExtractionsContainer = document.getElementById('youtube-extractions');
    const facebookExtractionsContainer = document.getElementById('facebook-extractions');
    const instagramExtractionsContainer = document.getElementById('instagram-extractions');
    const twitterExtractionsContainer = document.getElementById('twitter-extractions');
    
    // Variables to hold temporary state when switching between modes
    let youtubeState = {
        inputValue: '',
        resultsHtml: '',
        errorMessage: '',
        errorVisible: false
    };
    
    let facebookState = {
        inputValue: '',
        resultsHtml: '',
        errorMessage: '',
        errorVisible: false
    };
    
    let instagramState = {
        inputValue: '',
        resultsHtml: '',
        errorMessage: '',
        errorVisible: false
    };
    
    let twitterState = {
        inputValue: '',
        resultsHtml: '',
        errorMessage: '',
        errorVisible: false
    };
    
    // Arrays to store recent extractions (limit to 10 for each type)
    let recentExtractions = {
        youtube: [], // will contain {url, title, thumbnailUrl, timestamp, videoId}
        facebook: [], // will contain {url, title, thumbnailUrl, timestamp, videoId}
        instagram: [], // will contain {url, title, thumbnailUrl, timestamp, videoId}
        twitter: [] // will contain {url, title, thumbnailUrl, timestamp, videoId}
    };
    
    // Load saved extractions from localStorage if available
    function loadSavedExtractions() {
        const savedExtractions = localStorage.getItem('recentExtractions');
        if (savedExtractions) {
            try {
                recentExtractions = JSON.parse(savedExtractions);
            } catch (e) {
                console.error('Error parsing saved extractions:', e);
                // If there's an error, just use the empty arrays
            }
        }
    }
    
    // Call this function when the page loads
    loadSavedExtractions();
    
    // Function to save current state based on mode (only for session)
    function saveCurrentState(mode) {
        // Get current state
        const currentInputValue = videoUrl.value || '';
        const currentResultsHtml = resultsContainer ? resultsContainer.innerHTML : '';
        const currentErrorMessage = errorMessage ? errorMessage.textContent : '';
        const currentErrorVisible = errorContainer ? (errorContainer.style.display !== 'none') : false;
        
        // Save to appropriate state object (in-memory only)
        if (mode === 'youtube') {
            youtubeState = {
                inputValue: currentInputValue,
                resultsHtml: currentResultsHtml,
                errorMessage: currentErrorMessage,
                errorVisible: currentErrorVisible
            };
        } else if (mode === 'facebook') {
            facebookState = {
                inputValue: currentInputValue,
                resultsHtml: currentResultsHtml,
                errorMessage: currentErrorMessage,
                errorVisible: currentErrorVisible
            };
        } else if (mode === 'instagram') {
            instagramState = {
                inputValue: currentInputValue,
                resultsHtml: currentResultsHtml,
                errorMessage: currentErrorMessage,
                errorVisible: currentErrorVisible
            };
        } else if (mode === 'twitter') {
            twitterState = {
                inputValue: currentInputValue,
                resultsHtml: currentResultsHtml,
                errorMessage: currentErrorMessage,
                errorVisible: currentErrorVisible
            };
        }
    }
    
    // Expose the save state function to the window object for other scripts to access
    window.saveCurrentState = saveCurrentState;
    
    // Function to restore state based on mode (in-memory only)
    function restoreState(mode) {
        let state;
        if (mode === 'youtube') {
            state = youtubeState;
        } else if (mode === 'facebook') {
            state = facebookState;
        } else if (mode === 'instagram') {
            state = instagramState;
        } else if (mode === 'twitter') {
            state = twitterState;
        }
        
        // Restore input value
        if (videoUrl) {
            videoUrl.value = state.inputValue;
            
            // Check if the textarea needs to be resized based on content
            // If textarea has content that would need more height, auto-resize it
            if (state.inputValue && state.inputValue.trim() !== '') {
                // Use setTimeout to ensure it happens after the value is fully set
                setTimeout(() => {
                    // Check if autoResizeTextarea function exists in window object (created in input-actions.js)
                    if (typeof window.autoResizeTextarea === 'function') {
                        // Call the auto-resize function to set proper height based on content
                        window.autoResizeTextarea();
                    } else {
                        // Fallback if function doesn't exist - manually calculate height
                        const content = state.inputValue;
                        if (content.includes('\n') || content.length > 60) {
                            // Calculate appropriate height based on content
                            const tempEl = document.createElement('textarea');
                            tempEl.style.position = 'absolute';
                            tempEl.style.left = '-9999px';
                            tempEl.style.width = videoUrl.offsetWidth + 'px';
                            tempEl.style.height = 'auto';
                            tempEl.value = content;
                            document.body.appendChild(tempEl);
                            const scrollHeight = Math.min(tempEl.scrollHeight, 120);
                            document.body.removeChild(tempEl);
                            
                            requestAnimationFrame(() => {
                                videoUrl.style.height = Math.max(50, scrollHeight) + 'px';
                            });
                        } else {
                            // Reset to default height if content is short
                            requestAnimationFrame(() => {
                                videoUrl.style.height = '50px';
                            });
                        }
                    }
                }, 10);
            } else {
                // If there's no content, just reset height to default
                requestAnimationFrame(() => {
                    videoUrl.style.height = '50px';
                });
            }
        }
        
        // Restore results
        if (resultsContainer) {
            resultsContainer.innerHTML = state.resultsHtml;
            
            // Reinitialize event listeners for restored content
            if (state.resultsHtml) {
                initializeCopyButtons();
                initializeTagListeners();
                initializeThumbnailListeners();
                initializeDropdownListeners();
            }
        }
        
        // Restore error state
        if (errorContainer && errorMessage) {
            errorMessage.textContent = state.errorMessage;
            errorContainer.style.display = state.errorVisible ? 'block' : 'none';
        }
        
        // Update button state (extract vs clear button)
        updateButtonStateBasedOnResults(state.resultsHtml);
    }
    
    // Helper function to update button state based on whether results exist
    function updateButtonStateBasedOnResults(resultsHtml) {
        if (clearButton && extractButton) {
            if (resultsHtml && resultsHtml.trim() !== '') {
                // If there are results, show clear button and hide extract button
                clearButton.style.display = 'block';
                extractButton.style.display = 'none';
                // Update z-index to ensure clear button is on top when extract button is hidden
                clearButton.style.zIndex = '2';
                extractButton.style.zIndex = '1';
                console.log('Showing clear button, hiding extract button');
            } else {
                // If there are no results, hide clear button and show extract button
                clearButton.style.display = 'none';
                extractButton.style.display = 'block';
                // Update z-index to ensure extract button is on top when clear button is hidden
                clearButton.style.zIndex = '1';
                extractButton.style.zIndex = '2';
                console.log('Showing extract button, hiding clear button');
            }
        }
    }
    
    // Function to add current extraction to history
    function addToRecentExtractions(mode) {
        // Get the necessary information
        const url = videoUrl.value;
        const titleElement = document.getElementById('video-title');
        const thumbnailElement = document.getElementById('main-thumbnail');
        
        if (!url || !titleElement || !thumbnailElement) return;
        
        // Extract video ID to check for duplicates
        const videoId = extractVideoId(url, mode);
        
        // Create extraction object with formatted date and time in AM/PM format
        const now = new Date();
        // Format: May 1, 2025 12:30 PM
        const formattedDateTime = now.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }) + ' ' + now.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        // Get thumbnail URL
        const thumbnailUrl = thumbnailElement.src;
        

        
        const extraction = {
            url: url,
            title: titleElement.textContent.trim(),
            thumbnailUrl: thumbnailUrl,
            timestamp: formattedDateTime,
            videoId: videoId
        };
        
        // Check if this video/channel is already in the recent extractions
        let duplicateIndex = -1;
        
        // Check if video ID contains platform prefix (yt_, fb_, etc)
        if (videoId.includes('channel_')) {
            // For channels, check if the channel ID matches or if URLs are similar
            // This handles cases where same channel might be added with different URL formats
            duplicateIndex = recentExtractions[mode].findIndex(item => {
                // Match by videoId (exact channel match)
                if (item.videoId && item.videoId === videoId) {
                    return true;
                }
                
                // Match by URL pattern for channels
                // Extract the channel name after channel_
                const channelNamePos = videoId.indexOf('channel_') + 8;
                const channelName = videoId.substring(channelNamePos);
                
                if (item.videoId && item.videoId.includes('channel_') &&
                    (item.url.includes('@' + channelName) ||
                     url.includes('@' + item.videoId.substring(item.videoId.indexOf('channel_') + 8)))) {
                    return true;
                }
                
                return false;
            });
        } else {
            // For regular videos, just check the videoId as before
            // Only look within the same platform's extraction list
            duplicateIndex = recentExtractions[mode].findIndex(item => item.videoId === videoId);
        }
        
        // If it's a duplicate, remove it from its current position
        if (duplicateIndex !== -1) {
            recentExtractions[mode].splice(duplicateIndex, 1);
        }
        
        // Add to appropriate array (at the beginning)
        recentExtractions[mode].unshift(extraction);
        
        // Limit to maximum 10 extractions per type
        if (recentExtractions[mode].length > 10) {
            recentExtractions[mode] = recentExtractions[mode].slice(0, 10);
        }
        
        // Save to localStorage
        saveExtractionsToLocalStorage();
        
        // Update the UI
        updateRecentExtractionsUI(mode);
    }
    
    // Function to save extractions to localStorage
    function saveExtractionsToLocalStorage() {
        try {
            localStorage.setItem('recentExtractions', JSON.stringify(recentExtractions));
        } catch (e) {
            console.error('Error saving extractions to localStorage:', e);
        }
    }
    
    // Function to extract video ID (simple version for demonstration)
    function extractVideoId(url, mode) {
        // Add platform prefix to the IDs to ensure they don't conflict between platforms
        const platformPrefixes = {
            'youtube': 'yt_',
            'facebook': 'fb_',
            'instagram': 'ig_',
            'twitter': 'tw_'
        };
        
        // Get the platform prefix or use 'unknown_' if mode is not recognized
        const prefix = platformPrefixes[mode] || 'unknown_';
        
        if (mode === 'youtube') {
            // Standard YouTube video ID pattern
            const standardPattern = /(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
            let matches = url.match(standardPattern);
            if (matches) return prefix + matches[1];
            
            // YouTube Shorts pattern
            const shortsPattern = /(?:youtube\.com\/shorts\/|youtube\.com\/(?:.+)\/shorts\/)([a-zA-Z0-9_-]{11})/;
            matches = url.match(shortsPattern);
            if (matches) return prefix + matches[1];
            
            // YouTube Live pattern
            const livePattern = /youtube\.com\/live\/([a-zA-Z0-9_-]{11})/;
            matches = url.match(livePattern);
            if (matches) return prefix + matches[1];
            
            // YouTube Post pattern
            const postPattern = /youtube\.com\/post\/([A-Za-z0-9_\-]+)(?:\?[^\/]*)?/;
            matches = url.match(postPattern);
            if (matches) return prefix + matches[1];
            
            // YouTube Channel pattern (@username format)
            const channelPattern = /youtube\.com\/@([A-Za-z0-9_\-]+)(?:\/|\?|$)/;
            matches = url.match(channelPattern);
            if (matches) return prefix + 'channel_' + matches[1]; // Add prefix to distinguish from video IDs
            
            // YouTube Channel pattern (channel/ID format)
            const channelIdPattern = /youtube\.com\/channel\/([A-Za-z0-9_\-]+)(?:\/|\?|$)/;
            matches = url.match(channelIdPattern);
            if (matches) return prefix + 'channel_' + matches[1]; // Add prefix to distinguish from video IDs
            
            return prefix + 'unknown';
        } else if (mode === 'facebook') {
            // Facebook video ID patterns for different URL formats
            
            // Standard Facebook video pattern
            const standardPattern = /facebook\.com\/.*?\/videos\/(?:vb\.\d+\/)?(\d+)/;
            let matches = url.match(standardPattern);
            if (matches) return prefix + matches[1];
            
            // Facebook watch pattern
            const watchPattern = /facebook\.com\/watch\/?\?v=(\d+)/;
            matches = url.match(watchPattern);
            if (matches) return prefix + matches[1];
            
            // Facebook story pattern
            const storyPattern = /facebook\.com\/story\.php\?story_fbid=(\d+)/;
            matches = url.match(storyPattern);
            if (matches) return prefix + matches[1];
            
            // Facebook reel pattern
            const reelPattern = /facebook\.com\/reel\/(\d+)/;
            matches = url.match(reelPattern);
            if (matches) return prefix + matches[1];
            
            // Mobile Facebook watch pattern
            const mobileWatchPattern = /m\.facebook\.com\/watch\/?\?v=(\d+)/;
            matches = url.match(mobileWatchPattern);
            if (matches) return prefix + matches[1];
            
            // Mobile Facebook reel pattern
            const mobileReelPattern = /m\.facebook\.com\/reel\/(\d+)/;
            matches = url.match(mobileReelPattern);
            if (matches) return prefix + matches[1];
            
            // Facebook short link (fb.watch) pattern
            const fbWatchPattern = /fb\.watch\/([^\/\?]+)/;
            matches = url.match(fbWatchPattern);
            if (matches) return prefix + matches[1];
            
            // If unable to extract a specific ID, use the entire URL as a hash
            if (!matches) {
                // Create a simple hash of the URL to ensure uniqueness
                let hash = 0;
                for (let i = 0; i < url.length; i++) {
                    const char = url.charCodeAt(i);
                    hash = ((hash << 5) - hash) + char;
                    hash = hash & hash; // Convert to 32bit integer
                }
                return prefix + 'hash_' + Math.abs(hash).toString(16);
            }
            
            return prefix + 'unknown';
        } else if (mode === 'instagram') {
            // Instagram Post pattern
            const postPattern = /instagram\.com\/p\/([A-Za-z0-9_\-]+)/;
            let matches = url.match(postPattern);
            if (matches) return prefix + matches[1];
            
            // Instagram Reel pattern
            const reelPattern = /instagram\.com\/reel\/([A-Za-z0-9_\-]+)/;
            matches = url.match(reelPattern);
            if (matches) return prefix + matches[1];
            
            // Profile pattern
            const profilePattern = /instagram\.com\/([A-Za-z0-9_\.]+)\/?$/;
            matches = url.match(profilePattern);
            if (matches) return prefix + 'profile_' + matches[1];
            
            return prefix + 'unknown';
        } else if (mode === 'twitter') {
            // Twitter/X tweet pattern
            const tweetPattern = /twitter\.com\/(?:#!\/)?(\w+)\/status(?:es)?\/(\d+)/;
            let matches = url.match(tweetPattern);
            if (matches) return prefix + matches[2]; // Tweet ID
            
            // X.com tweet pattern
            const xComPattern = /x\.com\/(?:#!\/)?(\w+)\/status(?:es)?\/(\d+)/;
            matches = url.match(xComPattern);
            if (matches) return prefix + matches[2]; // Tweet ID
            
            // Profile pattern
            const profilePattern = /(?:twitter\.com|x\.com)\/([A-Za-z0-9_]+)\/?$/;
            matches = url.match(profilePattern);
            if (matches) return prefix + 'profile_' + matches[1];
            
            return prefix + 'unknown';
        } else {
            return prefix + 'unknown';
        }
    }
    
    // Function to update the recent extractions UI
    function updateRecentExtractionsUI(activeMode) {
        // Get the appropriate container
        const youtubeContainer = youtubeExtractionsContainer;
        const facebookContainer = facebookExtractionsContainer;
        const instagramContainer = instagramExtractionsContainer;
        const twitterContainer = twitterExtractionsContainer;
        // Show/hide the appropriate list
        youtubeContainer.style.display = activeMode === 'youtube' ? 'block' : 'none';
        facebookContainer.style.display = activeMode === 'facebook' ? 'block' : 'none';
        instagramContainer.style.display = activeMode === 'instagram' ? 'block' : 'none';
        twitterContainer.style.display = activeMode === 'twitter' ? 'block' : 'none';
        
        // Update all platform extractions
        updateExtractionList('youtube', youtubeContainer);
        updateExtractionList('facebook', facebookContainer);
        updateExtractionList('instagram', instagramContainer);
        updateExtractionList('twitter', twitterContainer);
    }
    
    // Helper function to update a single extraction list
    function updateExtractionList(mode, container) {
        // Clear existing extractions except for the no-extractions message
        const noExtractionsMessage = container.querySelector('.no-extractions-message');
        container.innerHTML = '';
        
        // Check if we have any extractions
        if (recentExtractions[mode].length === 0) {
            // Re-add the no-extractions message
            container.appendChild(noExtractionsMessage || 
                createNoExtractionsMessage(
                    mode === 'youtube' ? 'No recent YouTube extractions' : 
                    mode === 'facebook' ? 'No recent Facebook extractions' :
                    mode === 'instagram' ? 'No recent Instagram extractions' :
                    'No recent X extractions'
                ));
            return;
        }
        
        // Determine device type based on screen width for responsive display
        function getDeviceLimitForExtractions() {
            // Get the current window width
            const windowWidth = window.innerWidth;
            
            // Set different limits based on device size
            if (windowWidth < 576) { // Mobile phones
                return 3;
            } else if (windowWidth < 992) { // Tablets
                return 4;
            } else { // Desktops and larger screens
                return 5;
            }
        }
        
        // Get the device-specific limit
        const deviceLimit = getDeviceLimitForExtractions();
        
        // Determine if we need to show the 'Show More' button
        const hasMoreThanLimit = recentExtractions[mode].length > deviceLimit;
        
        // Track if we're showing all items
        let showingAll = false;
        
        // Function to render the extraction items with smooth animation on new items only
        const renderExtractions = (limit = null) => {
            // First, remove items but preserve Show More/Less button if it exists
            const showMoreButton = container.querySelector('.show-more-button');
            if (showMoreButton) {
                showMoreButton.remove(); // Temporarily remove it
            }
            
            // Get existing items to preserve them when showing more
            const existingItems = Array.from(container.querySelectorAll('.extraction-item'));
            const existingItemsData = [];
            
            // If we're showing more (not less) and have existing items, keep them
            if (limit === null && existingItems.length > 0) {
                // Store info about existing items to keep them
                existingItems.forEach(item => {
                    existingItemsData.push({
                        url: item.dataset.url,
                        index: parseInt(item.dataset.index)
                    });
                    item.remove(); // Remove temporarily to reorder
                });
                
                // Build the new list with old and new items
                renderWithPreservedItems();
            } else if (limit !== null && existingItems.length > limit) {
                // We're showing less - animate out the items that will be hidden from bottom to top
                const itemsToRemove = existingItems.slice(limit);
                const itemsToKeep = existingItems.slice(0, limit);
                
                // Add special upward hiding class to items that will be removed
                itemsToRemove.forEach(item => {
                    item.classList.add('hiding-upward'); // Use upward animation class instead
                });
                
                // Wait for animation then remove
                setTimeout(() => {
                    itemsToRemove.forEach(item => item.remove());
                    
                    // If we need to add the Show More button back
                    if (showMoreButton) {
                        container.appendChild(showMoreButton);
                    }
                }, 300);
                
                // Keep these items without animation
                itemsToKeep.forEach(item => {
                    // Keep them as is, no animation needed
                });
            } else {
                // Just show the items with default behavior (first time)
                renderWithoutPreserving();
            }
            
            // Function to render keeping existing items and only animating new ones
            function renderWithPreservedItems() {
                const existingUrls = existingItemsData.map(item => item.url);
                const allExtractions = recentExtractions[mode];
                
                // First add back existing items without animation
                existingItemsData.forEach(itemData => {
                    const extractionData = allExtractions.find(e => e.url === itemData.url);
                    if (extractionData) {
                        addExtractionItem(extractionData, false); // no animation
                    }
                });
                
                // Then add new items with animation
                const newExtractions = allExtractions.filter(extraction => 
                    !existingUrls.includes(extraction.url));
                
                newExtractions.forEach((extraction, index) => {
                    addExtractionItem(extraction, true, index); // with animation
                });
                
                // Add the Show More/Less button back at the end if it existed
                if (showMoreButton) {
                    container.appendChild(showMoreButton);
                }
            }
            
            // Function for default rendering (used for initial load)
            function renderWithoutPreserving() {
                const extractionsToShow = limit ? 
                    recentExtractions[mode].slice(0, limit) : 
                    recentExtractions[mode];
                
                extractionsToShow.forEach((extraction, index) => {
                    addExtractionItem(extraction, false); // no animation for initial items
                });
                
                // Add the Show More/Less button back at the end if it existed
                if (showMoreButton) {
                    container.appendChild(showMoreButton);
                }
            }
            
            // Helper function to create and add an extraction item
            function addExtractionItem(extraction, animate = false, animationIndex = 0) {
                const extractionItem = document.createElement('div');
                extractionItem.className = animate ? 'extraction-item hidden' : 'extraction-item';
                extractionItem.dataset.url = extraction.url;
                extractionItem.dataset.index = recentExtractions[mode].indexOf(extraction);
                
                // Create the thumbnail image element
                const imgElement = document.createElement('img');
                imgElement.className = 'thumbnail';
                imgElement.alt = extraction.title;
                
                // For Instagram thumbnails, route through download.php directly to handle access issues
                if (mode === 'instagram' && extraction.thumbnailUrl) {
                    imgElement.src = 'download.php?url=' + encodeURIComponent(extraction.thumbnailUrl) + '&display=true';
                } else {
                    imgElement.src = extraction.thumbnailUrl;
                }
                
                // Add error handler to use fallback when image can't be loaded
                imgElement.onerror = function() {
                    console.log('Failed to load thumbnail, using fallback:', extraction.thumbnailUrl);
                    
                    // Special handling for Instagram thumbnails
                    if (mode === 'instagram' && extraction.thumbnailUrl) {
                        // Redirect through our download.php script with display=true to handle the authentication/referer
                        this.src = 'download.php?url=' + encodeURIComponent(extraction.thumbnailUrl) + '&display=true';
                    } else {
                        // Regular fallback for other platforms
                        this.src = './favicon/thumbnail-fallback.svg';
                    }
                    
                    // Prevent infinite error loop
                    this.onerror = null;
                };
                
                // Create the info container
                const infoDiv = document.createElement('div');
                infoDiv.className = 'info';
                
                // Add title
                const titleElement = document.createElement('h4');
                titleElement.className = 'title';
                titleElement.textContent = extraction.title;
                
                // Add timestamp
                const timestampElement = document.createElement('div');
                timestampElement.className = 'timestamp';
                timestampElement.textContent = extraction.timestamp;
                
                // Append elements
                infoDiv.appendChild(titleElement);
                infoDiv.appendChild(timestampElement);
                
                extractionItem.appendChild(imgElement);
                extractionItem.appendChild(infoDiv);
                
                // Add click event listener
                extractionItem.addEventListener('click', function() {
                    // Fill the input field with this URL
                    videoUrl.value = extraction.url;
                    
                    // Force browser reflow to recognize the new content
                    void videoUrl.offsetHeight;
                    
                    // Use setTimeout to ensure DOM has updated
                    setTimeout(function() {
                        // Trigger the auto-resize function with force parameter to ensure resize happens
                        if (typeof window.resizeVideoUrlInput === 'function') {
                            window.resizeVideoUrlInput(true); // Force resize regardless of content length
                        } else {
                            // Fallback: Manually trigger input event to resize textarea
                            videoUrl.dispatchEvent(new Event('input'));
                        }
                        
                        // Submit the form
                        videoForm.dispatchEvent(new Event('submit'));
                    }, 50); // Small delay to allow DOM to update
                });
                
                container.appendChild(extractionItem);
                
                // Apply animation if needed
                if (animate) {
                    setTimeout(() => {
                        extractionItem.classList.remove('hidden');
                    }, 50 * animationIndex);
                }
            }
        };
        
        // Initial render with device-specific limit
        renderExtractions(hasMoreThanLimit ? deviceLimit : null);
        
        // Make sure the panel has the collapsed class by default
        const extractionsPanel = document.getElementById('recent-extractions-panel');
        if (extractionsPanel && !extractionsPanel.classList.contains('collapsed') && !extractionsPanel.classList.contains('expanded')) {
            extractionsPanel.classList.add('collapsed');
        }
        
        // Function to create and append the show more/less button
        function createShowMoreButton() {
            // Create the button
            const showMoreButton = document.createElement('button');
            showMoreButton.className = showingAll ? 'show-more-button show-less' : 'show-more-button';
            
            // Create text span
            const textSpan = document.createElement('span');
            textSpan.textContent = showingAll ? 'Show Less' : 'Show More';
            showMoreButton.appendChild(textSpan);
            
            // Create icon element
            const icon = document.createElement('i');
            icon.className = 'fas fa-chevron-down';
            showMoreButton.appendChild(icon);
            
            // Add event listener
            showMoreButton.addEventListener('click', function() {
                if (!showingAll) {
                    // Show all extractions
                    renderExtractions();
                    textSpan.textContent = 'Show Less';
                    showingAll = true;
                    this.classList.add('show-less');
                    
                    // Expand the panel with smooth animation
                    if (extractionsPanel) {
                        // Apply transition class first for animation
                        extractionsPanel.style.transition = 'all 0.4s ease-in-out';
                        extractionsPanel.classList.remove('collapsed');
                        extractionsPanel.classList.add('expanded');
                    }
                } else {
                    // Show only device-specific limit of extractions
                    renderExtractions(deviceLimit);
                    textSpan.textContent = 'Show More';
                    showingAll = false;
                    this.classList.remove('show-less');
                    
                    // Collapse the panel with smooth animation
                    if (extractionsPanel) {
                        // Apply transition class first for animation
                        extractionsPanel.style.transition = 'all 0.4s ease-in-out';
                        extractionsPanel.classList.remove('expanded');
                        extractionsPanel.classList.add('collapsed');
                    }
                }
                
                // Remove and re-add the button to keep it at the bottom
                const oldButton = container.querySelector('.show-more-button');
                if (oldButton) {
                    oldButton.remove();
                }
                container.appendChild(showMoreButton);
            });
            
            return showMoreButton;
        }
        
        // If we have more extractions than the device limit, add a "Show More" button
        if (hasMoreThanLimit) {
            const showMoreButton = createShowMoreButton();
            container.appendChild(showMoreButton);
        } else {
            // If no "Show More" button is needed, ensure the panel is in collapsed state
            if (extractionsPanel) {
                extractionsPanel.classList.remove('expanded');
                extractionsPanel.classList.add('collapsed');
            }
        }
        
        // Add event listener for window resize to update the list when screen size changes
        // But use it only once for the entire application
        if (!window.resizeListenerAdded) {
            window.addEventListener('resize', function() {
                // Only update if we're not showing all items
                if (!showingAll) {
                    // Determine the current mode based on theme classes
                    let currentMode = 'youtube'; // Default
                    if (document.documentElement.classList.contains('facebook-theme')) {
                        currentMode = 'facebook';
                    } else if (document.documentElement.classList.contains('instagram-theme')) {
                        currentMode = 'instagram';
                    } else if (document.documentElement.classList.contains('twitter-theme')) {
                        currentMode = 'twitter';
                    }
                    updateRecentExtractionsUI(currentMode);
                }
            });
            window.resizeListenerAdded = true;
        }
    }
    
    // Helper function to create a "no extractions" message
    function createNoExtractionsMessage(text) {
        const message = document.createElement('div');
        message.className = 'no-extractions-message';
        message.textContent = text;
        return message;
    }
    
    // Listen for changes to the video type radio buttons
    videoTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Save current state before switching
            // Determine the current mode based on localStorage
            const currentMode = localStorage.getItem('videoType') || 'youtube';
            saveCurrentState(currentMode);
            
            // Update the platform selector UI
            const platformOptions = document.querySelectorAll('.platform-option');
            platformOptions.forEach(option => {
                const optionRadio = option.querySelector('input[type="radio"]');
                if (optionRadio && optionRadio.value === this.value) {
                    option.classList.add('active');
                    // Remove inline style to let CSS classes handle the background color
                    option.style.removeProperty('background-color');
                    option.style.color = 'white';
                } else {
                    option.classList.remove('active');
                    option.style.backgroundColor = '#f0f0f0';
                    option.style.color = '#333';
                }
            });
            
            if (this.value === 'youtube') {
                // Set placeholder
                videoUrl.placeholder = 'Enter Youtube URL';
                
                // Save preference to localStorage and cookie
                localStorage.setItem('videoType', 'youtube');
                document.cookie = "preferred_video_type=youtube; path=/; max-age=31536000";
                
                // Restore YouTube state
                restoreState('youtube');
                
                // Update recent extractions UI
                updateRecentExtractionsUI('youtube');
                
            } else if (this.value === 'facebook') {
                // Set placeholder
                videoUrl.placeholder = 'Enter Facebook URL';
                
                // Save preference to localStorage and cookie
                localStorage.setItem('videoType', 'facebook');
                document.cookie = "preferred_video_type=facebook; path=/; max-age=31536000";
                
                // Restore Facebook state
                restoreState('facebook');
                
                // Update recent extractions UI
                updateRecentExtractionsUI('facebook');
            
            } else if (this.value === 'instagram') {
                // Set placeholder
                videoUrl.placeholder = 'Enter Instagram URL';
                
                // Save preference to localStorage and cookie
                localStorage.setItem('videoType', 'instagram');
                document.cookie = "preferred_video_type=instagram; path=/; max-age=31536000";
                
                // Restore Instagram state
                restoreState('instagram');
                
                // Update recent extractions UI
                updateRecentExtractionsUI('instagram');
                
            } else if (this.value === 'twitter') {
                // Set placeholder
                videoUrl.placeholder = 'Enter Twitter/X URL';
                
                // Save preference to localStorage and cookie
                localStorage.setItem('videoType', 'twitter');
                document.cookie = "preferred_video_type=twitter; path=/; max-age=31536000";
                
                // Restore Twitter state
                restoreState('twitter');
                
                // Update recent extractions UI
                updateRecentExtractionsUI('twitter');
            }
        });
    });
    
    /* 
     * No need to set initial selector class as it's now added 
     * server-side in PHP based on current selection 
     */
    
    if (videoForm) {
        videoForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent standard form submission
            
            // Loading spinner was removed
            
            // Show spinner in extract button and hide icon
            const extractSpinner = document.getElementById('extract-spinner');
            const extractIcon = document.getElementById('extract-icon');
            if (extractSpinner) {
                extractSpinner.style.display = 'inline-block';
                extractButton.classList.add('is-loading');
                if (extractIcon) extractIcon.style.display = 'none';
            }
            
            // Create FormData object
            const formData = new FormData(videoForm);
            
            // Create and configure XMLHttpRequest object
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            // Handle response
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            // Hide error message if it was shown
                            errorContainer.style.display = 'none';
                            
                            // Update results container with new content
                            resultsContainer.innerHTML = response.html;
                            
                            // Reinitialize event listeners for new content
                            initializeCopyButtons();
                            initializeTagListeners();
                            initializeThumbnailListeners();
                            initializeDropdownListeners();
                            
                            // Save the current state after successful extraction
                            // Determine the current mode based on localStorage
                            const currentMode = localStorage.getItem('videoType') || 'youtube';
                            saveCurrentState(currentMode);
                            
                            // Add to recent extractions
                            addToRecentExtractions(currentMode);
                            
                            // Hide extract button and show clear button
                            const clearButton = document.getElementById('clear-button');
                            if (clearButton && extractButton) {
                                clearButton.style.display = 'block';
                                extractButton.style.display = 'none';
                                // Update z-index to ensure clear button is on top when extract button is hidden
                                clearButton.style.zIndex = '2';
                                extractButton.style.zIndex = '1';
                                console.log('After extraction: Showing clear button, hiding extract button');
                            }
                        } else {
                            // Show error message
                            errorMessage.textContent = response.error;
                            errorContainer.style.display = 'block';
                            resultsContainer.innerHTML = ''; // Clear results
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                        showToast('Error processing response from server', 'error');
                    }
                } else {
                    console.error('Request failed. Status:', xhr.status);
                    showToast('Error connecting to server', 'error');
                }
                
                // Loading spinner was removed
                
                // Hide extract button spinner and show icon
                if (extractSpinner) {
                    extractSpinner.style.display = 'none';
                    extractButton.classList.remove('is-loading');
                    const extractIcon = document.getElementById('extract-icon');
                    if (extractIcon) extractIcon.style.display = 'block';
                }
            };
            
            // Handle network errors
            xhr.onerror = function() {
                console.error('Network error occurred');
                showToast('Network error, please try again', 'error');
                // Loading spinner was removed
                
                // Hide extract button spinner and show icon on network error
                if (extractSpinner) {
                    extractSpinner.style.display = 'none';
                    extractButton.classList.remove('is-loading');
                    const extractIcon = document.getElementById('extract-icon');
                    if (extractIcon) extractIcon.style.display = 'block';
                }
            };
            
            // Send the request
            xhr.send(formData);
        });
    }
    
    // Initialize functionality for initial page load
    initializeCopyButtons();
    initializeTagListeners();
    initializeThumbnailListeners();
    initializeDropdownListeners();
    
    // Initialize recent extractions panel with the current mode
    // Determine the current mode based on localStorage
    const currentMode = localStorage.getItem('videoType') || 'youtube';
    updateRecentExtractionsUI(currentMode);
    
    // Do not save state on page load - let it start fresh
    
    // Check localStorage for platform preference and set placeholder
    const storedPlatform = localStorage.getItem('videoType') || 'youtube';
    
    // Set up a MutationObserver to update the placeholder when input field appears
    const observer = new MutationObserver(function(mutations) {
        const videoUrl = document.getElementById('video_url');
        if (videoUrl) {
            // Update placeholder based on stored platform
            if (storedPlatform === 'facebook') {
                videoUrl.placeholder = 'Enter Facebook URL';
            } else if (storedPlatform === 'instagram') {
                videoUrl.placeholder = 'Enter Instagram URL';
            } else if (storedPlatform === 'twitter') {
                videoUrl.placeholder = 'Enter Twitter/X URL';
            } else {
                videoUrl.placeholder = 'Enter Youtube URL';
            }
            
            // Disconnect once we've made the changes
            observer.disconnect();
        }
    });
    
    // Start observing the document with configured parameters
    observer.observe(document.documentElement, { childList: true, subtree: true });
    
    // Function to initialize copy buttons
    function initializeCopyButtons() {
        const copyButtons = document.querySelectorAll('.copy-btn');
        
        copyButtons.forEach(button => {
            // Remove existing event listeners (if any)
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', function() {
                // Get the target element ID from data attribute
                const targetId = this.getAttribute('data-clipboard-target').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (!targetElement) {
                    alert('Element not found!');
                    return;
                }
                
                let textToCopy;
                
                // Handle different element types
                if (targetId === 'video-tags') {
                    // For tags, collect all tag texts
                    const tags = targetElement.querySelectorAll('.tag');
                    const tagTexts = Array.from(tags).map(tag => tag.textContent.trim());
                    textToCopy = tagTexts.join(', ');
                } else {
                    // For other elements, just get the text content
                    textToCopy = targetElement.textContent.trim();
                }
                
                // Copy to clipboard
                copyToClipboard(textToCopy, this);
            });
        });
    }
    
    // Function to initialize tag listeners
    function initializeTagListeners() {
        const tags = document.querySelectorAll('.tag');
        tags.forEach(tag => {
            tag.addEventListener('click', function() {
                // Get tag text from data attribute for more reliable copying
                const tagText = this.getAttribute('data-tag') || this.textContent.trim();
                
                // Remove active and copied classes from all tags
                document.querySelectorAll('.tag').forEach(t => {
                    t.classList.remove('active', 'copied');
                });
                
                // Add active class to clicked tag
                this.classList.add('active');
                
                // Copy to clipboard with modern API
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(tagText)
                        .then(() => {
                            // Show tooltip with "Copied!" message
                            this.classList.add('copied');
                            
                            // Remove copied class after animation completes
                            setTimeout(() => {
                                this.classList.remove('copied');
                                // Keep active state a bit longer
                                setTimeout(() => {
                                    this.classList.remove('active');
                                }, 500);
                            }, 1500);
                        })
                        .catch(err => {
                            console.error('Failed to copy text: ', err);
                            legacyCopyTag(tagText, this);
                        });
                } else {
                    // Fallback for browsers without clipboard API
                    legacyCopyTag(tagText, this);
                }
            });
        });
    }
    
    // Legacy copy method for tags
    function legacyCopyTag(text, tagElement) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'absolute';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        
        textarea.select();
        let success = false;
        
        try {
            success = document.execCommand('copy');
            if (success) {
                // Show active state for visual feedback
                tagElement.classList.add('active');
                setTimeout(() => {
                    tagElement.classList.remove('active');
                }, 1500);
            } else {
                alert('Failed to copy tag');
            }
        } catch (err) {
            console.error('Error copying tag:', err);
            alert('Failed to copy tag');
        }
        
        document.body.removeChild(textarea);
    }
    
    /**
     * Copy text to clipboard
     * 
     * @param {string} text - Text to copy
     * @param {Element} button - Button that was clicked
     */
    function copyToClipboard(text, button) {
        // Try using the Clipboard API first (more modern)
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    // Change button text temporarily
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    
                    // Restore original button text after delay
                    setTimeout(() => {
                        button.innerHTML = originalText;
                    }, 2000);
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
                    // Change button text temporarily
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    
                    // Restore original button text after delay
                    setTimeout(() => {
                        button.innerHTML = originalText;
                    }, 2000);
                } else {
                    alert('Failed to copy text');
                }
            } catch (err) {
                console.error('Failed to copy text: ', err);
                alert('Failed to copy text: ' + err);
            }
            
            // Clean up
            document.body.removeChild(textarea);
        }
    }
    
    /**
     * Show a toast notification
     * 
     * @param {string} message - Message to display
     * @param {string} type - Type of toast (success/error)
     */
    // Make function available globally for other scripts to access
    window.showToast = function(message, type) {
        // Check if a toast container exists, create one if it doesn't
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.classList.add('toast-container');
            document.body.appendChild(toastContainer);
            
            // Add styles for the toast container
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '15px'; // Slightly higher position
            toastContainer.style.left = '50%'; // Center horizontally
            toastContainer.style.transform = 'translateX(-50%)'; // Perfect centering
            toastContainer.style.zIndex = '1000';
            toastContainer.style.display = 'flex';
            toastContainer.style.flexDirection = 'column';
            toastContainer.style.alignItems = 'center';
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.classList.add('toast');
        toast.classList.add(type === 'error' ? 'toast-error' : 'toast-success');
        
        // Style the toast with modern look
        // Determine the current theme based on theme classes
        let currentTheme = 'youtube'; // Default
        if (document.documentElement.classList.contains('facebook-theme')) {
            currentTheme = 'facebook';
        } else if (document.documentElement.classList.contains('instagram-theme')) {
            currentTheme = 'instagram';
        } else if (document.documentElement.classList.contains('twitter-theme')) {
            currentTheme = 'twitter';
        } else if (document.documentElement.classList.contains('')) {
            currentTheme = '';
        }
        
        // Set primary color based on theme
        let primaryColor;
        switch (currentTheme) {
            case 'facebook':
                primaryColor = '#1877f2';
                break;
            case 'instagram':
                primaryColor = '#c13584';
                break;
            case 'twitter':
                primaryColor = '#1da1f2';
                break;
            case '':
                primaryColor = '#25f4ee';
                break;
            default: // youtube
                primaryColor = '#ff0000';
                break;
        }
        
        if (type === 'success') {
            toast.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            toast.style.color = primaryColor;
            toast.style.borderLeft = `4px solid ${primaryColor}`;
        } else {
            toast.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            toast.style.color = '#cc0000';
            toast.style.borderLeft = '4px solid #cc0000';
        }
        
        // More compact, smaller toast
        toast.style.padding = '8px 16px';
        toast.style.borderRadius = '3px';
        toast.style.marginTop = '8px';
        toast.style.boxShadow = '0 3px 8px rgba(0,0,0,0.12)';
        toast.style.minWidth = '180px';
        toast.style.maxWidth = '220px';
        toast.style.textAlign = 'center';
        toast.style.fontWeight = '500';
        toast.style.fontSize = '14px';
        toast.style.transition = 'all 0.3s ease';
        toast.style.animation = 'fadeInDown 0.5s';
        
        // Set the message
        toast.innerHTML = message;
        
        // Add the toast to the container
        toastContainer.appendChild(toast);
        
        // Remove the toast after a delay
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                toastContainer.removeChild(toast);
                // Remove container if empty
                if (toastContainer.children.length === 0) {
                    document.body.removeChild(toastContainer);
                }
            }, 300);
        }, 3000);
    }
    
    // Function to initialize thumbnail listeners
    function initializeThumbnailListeners() {
        const mainThumbnail = document.getElementById('main-thumbnail');
        if (mainThumbnail) {
            // Remove existing event listeners (if any)
            const newThumbnail = mainThumbnail.cloneNode(true);
            mainThumbnail.parentNode.replaceChild(newThumbnail, mainThumbnail);
            
            newThumbnail.addEventListener('error', function() {
                // Try fallback thumbnail if the current resolution fails
                if (this.src.includes('maxresdefault')) {
                    const videoId = this.src.match(/\/vi\/([^\/]+)\/maxresdefault/)[1];
                    this.src = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
                } else if (this.src.includes('sddefault')) {
                    const videoId = this.src.match(/\/vi\/([^\/]+)\/sddefault/)[1];
                    this.src = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
                } else if (this.src.includes('cdninstagram.com') || this.src.includes('fbcdn.net')) {
                    // Instagram thumbnails might have CORS issues, use proxy
                    console.log('Instagram image failed to load, using proxy:', this.src);
                    const originalUrl = encodeURIComponent(this.src);
                    this.src = `download.php?url=${originalUrl}&display=true`;
                }
            });
        }
    }
    
    // Function to directly download a file from a URL
    function downloadFile(url, filename) {
        // Create a temporary invisible anchor tag
        const link = document.createElement('a');
        link.href = url;
        link.download = filename || 'download';
        link.style.display = 'none';
        
        // Add to document, trigger click and remove
        document.body.appendChild(link);
        link.click();
        
        // Remove after a short delay to ensure download starts
        setTimeout(() => {
            document.body.removeChild(link);
        }, 100);
        
        // Show a success toast
        if (typeof window.showToast === 'function') {
            window.showToast('<i class="fas fa-check-circle" style="margin-right:6px;"></i>Download started', 'success');
        }
    }
    
    // Function to initialize dropdown listeners
    function initializeDropdownListeners() {
        // Get all dropdown toggle buttons (both for profile and banner images)
        const dropdownToggleButtons = document.querySelectorAll('.dropdown-toggle');
        
        if (dropdownToggleButtons.length === 0) return;
        
        // First, remove existing global click handler to prevent duplicates
        document.removeEventListener('click', handleOutsideClick);
        
        // Add click handler for closing dropdowns when clicking outside
        document.addEventListener('click', handleOutsideClick);
        
        // Process each dropdown toggle button
        dropdownToggleButtons.forEach(dropdownToggle => {
            // Remove existing event listeners (if any) by cloning and replacing
            const newDropdownToggle = dropdownToggle.cloneNode(true);
            dropdownToggle.parentNode.replaceChild(newDropdownToggle, dropdownToggle);
            
            // Add event listeners to dropdown menu items
            const dropdownMenu = newDropdownToggle.nextElementSibling;
            if (dropdownMenu) {
                // Ensure the menu has the highest z-index
                dropdownMenu.style.zIndex = '999999';
                
                // Handle direct download links
                const directDownloadLinks = dropdownMenu.querySelectorAll('.direct-download-link');
                directDownloadLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = this.getAttribute('data-url');
                        const filename = this.getAttribute('data-filename');
                        
                        if (url) {
                            downloadFile(url, filename);
                        }
                        
                        // Close the dropdown
                        setTimeout(() => {
                            dropdownMenu.style.display = 'none';
                            newDropdownToggle.classList.remove('active');
                            const chevron = newDropdownToggle.querySelector('.fa-chevron-down');
                            if (chevron) {
                                chevron.style.transform = 'rotate(0deg)';
                            }
                        }, 100);
                    });
                });
                
                // Handle any regular links (includes the links we added for banner options)
                const regularLinks = dropdownMenu.querySelectorAll('a:not(.direct-download-link)');
                regularLinks.forEach(item => {
                    item.addEventListener('click', function(e) {
                        // After clicking an item, close the dropdown
                        setTimeout(() => {
                            dropdownMenu.style.display = 'none';
                            newDropdownToggle.classList.remove('active');
                            const chevron = newDropdownToggle.querySelector('.fa-chevron-down');
                            if (chevron) {
                                chevron.style.transform = 'rotate(0deg)';
                            }
                        }, 100);
                    });
                });
            }
            
            // For all devices (especially touch devices), toggle on click
            newDropdownToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Stop event from bubbling up
                
                // Close all other dropdowns first
                document.querySelectorAll('.dropdown-toggle').forEach(otherToggle => {
                    if (otherToggle !== this) {
                        const otherMenu = otherToggle.nextElementSibling;
                        if (otherMenu && otherMenu.style.display === 'block') {
                            otherMenu.style.display = 'none';
                            otherToggle.classList.remove('active');
                            const otherChevron = otherToggle.querySelector('.fa-chevron-down');
                            if (otherChevron) {
                                otherChevron.style.transform = 'rotate(0deg)';
                            }
                        }
                    }
                });
                
                const dropdownMenu = this.nextElementSibling;
                
                // Toggle the dropdown visibility
                if (dropdownMenu.style.display === 'block') {
                    dropdownMenu.style.display = 'none';
                    this.classList.remove('active');
                    
                    // Reset the chevron rotation
                    const chevron = this.querySelector('.fa-chevron-down');
                    if (chevron) {
                        chevron.style.transform = 'rotate(0deg)';
                    }
                } else {
                    dropdownMenu.style.display = 'block';
                    this.classList.add('active');
                    
                    // Ensure extremely high z-index to overcome all other elements
                    dropdownMenu.style.zIndex = '99999';
                    
                    // Rotate the chevron when open
                    const chevron = this.querySelector('.fa-chevron-down');
                    if (chevron) {
                        chevron.style.transform = 'rotate(180deg)';
                    }
                    
                    // Always position dropdown above the button
                    dropdownMenu.style.top = 'auto';
                    dropdownMenu.style.bottom = '100%';
                    dropdownMenu.style.marginTop = '0';
                    dropdownMenu.style.marginBottom = '0.5rem';
                }
            });
        });
    }
    
    // Handle outside clicks to close all dropdowns
    function handleOutsideClick(e) {
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            if (!toggle.contains(e.target)) {
                const dropdownMenu = toggle.nextElementSibling;
                if (dropdownMenu && dropdownMenu.style.display === 'block') {
                    dropdownMenu.style.display = 'none';
                    toggle.classList.remove('active');
                    
                    // Reset the chevron rotation
                    const chevron = toggle.querySelector('.fa-chevron-down');
                    if (chevron) {
                        chevron.style.transform = 'rotate(0deg)';
                    }
                }
            }
        });
    }
});
