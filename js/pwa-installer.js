/**
 * PWA Installer for VidExtract
 * Handles service worker registration and install prompts
 */

document.addEventListener('DOMContentLoaded', () => {
    // Register service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered with scope:', registration.scope);
            })
            .catch(error => {
                console.error('Service Worker registration failed:', error);
            });
    }

    // Variables for install prompt
    let deferredPrompt;
    const installButton = document.getElementById('pwa-install-button');
    const installContainer = document.getElementById('pwa-install-container');

    // Hide install button if not needed or already shown
    if (installContainer) {
        installContainer.style.display = 'none';
    }
    
    // Check if user has dismissed the banner before
    const isPWABannerDismissed = localStorage.getItem('pwa_banner_dismissed');
    if (isPWABannerDismissed === 'true') {
        return; // Don't show the banner if user dismissed it
    }

    // Listen for the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        
        // Show the install container if it exists
        if (installButton && installContainer) {
            installContainer.style.display = 'flex';
            
            // Install button click handler
            installButton.addEventListener('click', () => {
                // Hide the install banner
                installContainer.style.display = 'none';
                
                // Show the prompt
                deferredPrompt.prompt();
                
                // Wait for the user to respond to the prompt
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                        localStorage.setItem('pwa_banner_dismissed', 'true');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            });
            

        }
    });

    // If the app is already installed, hide the install button
    window.addEventListener('appinstalled', (evt) => {
        if (installContainer) {
            installContainer.style.display = 'none';
        }
        localStorage.setItem('pwa_banner_dismissed', 'true');
        deferredPrompt = null;
        console.log('VidExtract has been installed');
    });

    // Cache management functions
    function checkCacheStatus() {
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            const messageChannel = new MessageChannel();
            messageChannel.port1.onmessage = function(event) {
                console.log('Cache status:', event.data.message);
            };
            navigator.serviceWorker.controller.postMessage(
                { type: 'CACHE_CHECK' }, 
                [messageChannel.port2]
            );
        }
    }

    // Set up periodic cache status check (every 15 minutes)
    function setupClientSideCacheCheck() {
        setInterval(() => {
            checkCacheStatus();
        }, 15 * 60 * 1000); // Check every 15 minutes
    }

    // Start cache monitoring if service worker is available
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(() => {
            // Initial cache check
            checkCacheStatus();
            
            // Start periodic checks
            setupClientSideCacheCheck();
            
            console.log('Cache monitoring started - automatic cleanup every hour');
        });
    }
});