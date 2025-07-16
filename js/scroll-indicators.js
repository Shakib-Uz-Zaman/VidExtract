/**
 * Scroll Indicators for Video Platforms Selector
 * Shows scroll indicators when content is scrollable
 * Updated to not show fade effects on first and last buttons in primary state
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const selectorContainer = document.querySelector('.video-platforms-selector-container');
    const selector = document.querySelector('.video-platforms-selector');
    const leftIndicator = document.querySelector('.scroll-indicator-left');
    const rightIndicator = document.querySelector('.scroll-indicator-right');
    
    if (!selector || !leftIndicator || !rightIndicator || !selectorContainer) {
        return; // Exit if any element is missing
    }
    
    // Function to check scroll position and update indicators
    function updateScrollIndicators() {
        // Check if scrolling is possible (content width > visible width)
        const isScrollable = selector.scrollWidth > selectorContainer.clientWidth;
        
        if (isScrollable) {
            // Show left indicator if not at the beginning
            if (selector.scrollLeft > 20) { // Using a small threshold to ensure first button is fully visible
                leftIndicator.classList.add('active');
                // Manually control the left fade effect
                selectorContainer.style.setProperty('--left-fade-opacity', '1');
            } else {
                leftIndicator.classList.remove('active');
                // Manually hide the left fade effect
                selectorContainer.style.setProperty('--left-fade-opacity', '0');
            }
            
            // Show right indicator if not at the end
            const maxScrollLeft = selector.scrollWidth - selectorContainer.clientWidth;
            if (selector.scrollLeft < maxScrollLeft - 20) { // Using a small threshold to ensure last button is fully visible
                rightIndicator.classList.add('active');
                // Manually control the right fade effect
                selectorContainer.style.setProperty('--right-fade-opacity', '1');
            } else {
                rightIndicator.classList.remove('active');
                // Manually hide the right fade effect
                selectorContainer.style.setProperty('--right-fade-opacity', '0');
            }
        } else {
            // Hide both indicators if content is not scrollable
            leftIndicator.classList.remove('active');
            rightIndicator.classList.remove('active');
            // Manually hide both fade effects
            selectorContainer.style.setProperty('--left-fade-opacity', '0');
            selectorContainer.style.setProperty('--right-fade-opacity', '0');
        }
    }
    
    // Add click handlers for indicators
    leftIndicator.addEventListener('click', function() {
        // Scroll left by a fixed amount or to an item
        selector.scrollBy({
            left: -100,
            behavior: 'smooth'
        });
    });
    
    rightIndicator.addEventListener('click', function() {
        // Scroll right by a fixed amount or to an item
        selector.scrollBy({
            left: 100,
            behavior: 'smooth'
        });
    });
    
    // Listen for scroll events
    selector.addEventListener('scroll', updateScrollIndicators);
    
    // Listen for window resize
    window.addEventListener('resize', updateScrollIndicators);
    
    // Initial check
    setTimeout(updateScrollIndicators, 100);
});