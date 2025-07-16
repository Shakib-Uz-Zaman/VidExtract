/**
 * Material Design Adaptive Spinner for VidExtract
 * 
 * This module controls the Google Material Design style circular indeterminate progress indicator.
 * It adjusts animation speeds based on network conditions when possible.
 */

// Create a global adaptiveSpinner object for compatibility with page-transitions.js
window.adaptiveSpinner = (function() {
    // Default animation durations (in milliseconds)
    const DEFAULT_ROTATE_DURATION = 2000; // 2 seconds
    const DEFAULT_DASH_DURATION = 1500; // 1.5 seconds
    
    // Get spinner elements (will be initialized when DOM is ready)
    let spinnerElement = null;
    let spinnerCircleElement = null;
    
    /**
     * Get the current network connection type or estimate it
     * @returns {string} The connection type ('4g', '3g', etc)
     */
    function getConnectionType() {
        // Use the Network Information API if available
        if (navigator.connection && navigator.connection.effectiveType) {
            return navigator.connection.effectiveType;
        }
        
        // Default to a reasonable fallback for modern devices
        return '4g';
    }
    
    /**
     * Calculate appropriate animation speeds based on connection type
     * @returns {Object} Object with animation durations
     */
    function calculateAnimationSpeeds() {
        const connectionType = getConnectionType();
        let rotateDuration = DEFAULT_ROTATE_DURATION;
        let dashDuration = DEFAULT_DASH_DURATION;
        
        // Adjust animation speed based on connection type
        switch (connectionType) {
            case 'slow':
            case '2g':
                // Slower connection: slower animation to match perceived loading time
                rotateDuration = 2500; // 2.5 seconds
                dashDuration = 2000; // 2 seconds
                break;
                
            case '3g':
                // Medium connection: slightly slower than default
                rotateDuration = 2200; // 2.2 seconds
                dashDuration = 1800; // 1.8 seconds
                break;
                
            case '4g':
            case 'wifi':
                // Fast connection: default animation speed
                rotateDuration = 2000; // 2 seconds
                dashDuration = 1500; // 1.5 seconds
                break;
                
            default:
                // Unknown or other: use default values
                rotateDuration = DEFAULT_ROTATE_DURATION;
                dashDuration = DEFAULT_DASH_DURATION;
        }
        
        return {
            rotateDuration,
            dashDuration
        };
    }
    
    /**
     * Apply the calculated animation speeds to the spinner
     */
    function updateSpinnerSpeed() {
        // Find spinner elements if not already found
        if (!spinnerElement) {
            spinnerElement = document.querySelector('.material-spinner');
        }
        
        if (!spinnerCircleElement) {
            spinnerCircleElement = document.querySelector('.material-spinner-circle');
        }
        
        // Exit if elements aren't found
        if (!spinnerElement || !spinnerCircleElement) {
            return;
        }
        
        // Calculate speeds based on network conditions
        const speeds = calculateAnimationSpeeds();
        
        // Apply animation durations to spinner elements
        spinnerElement.style.animationDuration = speeds.rotateDuration + 'ms';
        spinnerCircleElement.style.animationDuration = speeds.dashDuration + 'ms';
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        updateSpinnerSpeed();
        
        // Add network change event listeners if API is available
        if (navigator.connection) {
            navigator.connection.addEventListener('change', updateSpinnerSpeed);
        }
        

    });
    
    // Return public API
    return {
        update: updateSpinnerSpeed,
        getConnectionType: getConnectionType
    };
})();