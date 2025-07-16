const CACHE_NAME = 'vidextract-v5';
const CACHE_EXPIRY_TIME = 60 * 60 * 1000; // 1 hour in milliseconds
const CACHE_TIMESTAMP_KEY = 'cache_created_timestamp';

const RESOURCES_TO_PRELOAD = [
  '/',
  '/index.php',
  '/about/',
  '/help/',
  '/privacy/',
  '/offline/',
  '/css/style.css',
  '/css/input-style.css',
  '/js/script.js',
  '/js/adaptive-spinner.js',
  '/js/favicon-switcher.js',
  '/js/input-actions.js',
  '/js/page-transitions.js',
  '/js/scroll-indicators.js',
  '/js/spa-navigation.js',
  '/js/pwa-installer.js',
  '/vidextract-tab-icon.webp',
  '/vidextract-pwa-icon.webp',
  '/vidx-logo.webp',
  '/manifest.json'
];

// Function to check if cache has expired
function isCacheExpired() {
  // Service Worker doesn't have localStorage, use IndexedDB or simple cache check
  return caches.keys().then(cacheNames => {
    if (cacheNames.length === 0) {
      return true; // No caches, so expired
    }
    
    // Check cache timestamp from a special cache entry
    return caches.open(CACHE_NAME).then(cache => {
      return cache.match('/__cache_timestamp__').then(response => {
        if (!response) {
          return true; // No timestamp, so expired
        }
        
        return response.json().then(data => {
          const currentTime = Date.now();
          const timeDifference = currentTime - data.timestamp;
          return timeDifference > CACHE_EXPIRY_TIME;
        }).catch(() => true); // Error reading timestamp, so expired
      });
    });
  }).catch(() => true); // Error accessing cache, so expired
}

// Function to clear all caches
function clearAllCaches() {
  return caches.keys().then(cacheNames => {
    return Promise.all(
      cacheNames.map(cacheName => {
        return caches.delete(cacheName);
      })
    );
  });
}

// Function to set cache timestamp
function setCacheTimestamp() {
  return caches.open(CACHE_NAME).then(cache => {
    const timestamp = { timestamp: Date.now() };
    const response = new Response(JSON.stringify(timestamp), {
      headers: { 'Content-Type': 'application/json' }
    });
    return cache.put('/__cache_timestamp__', response);
  }).catch(error => {
    console.log('Error setting cache timestamp:', error);
  });
}

// Install event - preload key resources
self.addEventListener('install', event => {
  self.skipWaiting();
  
  event.waitUntil(
    isCacheExpired().then(expired => {
      if (expired) {
        console.log('Cache expired, clearing all caches...');
        return clearAllCaches().then(() => {
          return setCacheTimestamp().then(() => {
            return caches.open(CACHE_NAME);
          });
        });
      } else {
        return caches.open(CACHE_NAME);
      }
    }).then(cache => {
      return cache.addAll(RESOURCES_TO_PRELOAD);
    })
  );
});

// Activate event - clean up old caches and check expiry
self.addEventListener('activate', event => {
  event.waitUntil(
    isCacheExpired().then(expired => {
      if (expired) {
        console.log('Cache expired during activation, clearing all caches...');
        return clearAllCaches().then(() => {
          return setCacheTimestamp();
        });
      } else {
        // Regular cleanup of old caches
        return caches.keys().then(cacheNames => {
          return Promise.all(
            cacheNames.filter(cacheName => {
              return cacheName !== CACHE_NAME;
            }).map(cacheName => {
              return caches.delete(cacheName);
            })
          );
        });
      }
    }).then(() => {
      return self.clients.claim();
    })
  );
});

// Fetch event - serve from cache or network with cache update
self.addEventListener('fetch', event => {
  event.respondWith(
    isCacheExpired().then(expired => {
      if (expired) {
        console.log('Cache expired during fetch, clearing and fetching fresh...');
        return clearAllCaches().then(() => {
          return setCacheTimestamp().then(() => {
            // Fetch fresh from network
            return fetch(event.request.clone())
              .then(response => {
                if (response && response.status === 200 && response.type === 'basic') {
                  const responseToCache = response.clone();
                  caches.open(CACHE_NAME)
                    .then(cache => {
                      cache.put(event.request, responseToCache);
                    });
                }
                return response;
              })
              .catch(() => {
                if (event.request.mode === 'navigate') {
                  return caches.match('/offline.php');
                }
              });
          });
        });
      } else {
        // Normal cache-first strategy
        return caches.match(event.request)
          .then(response => {
            // Cache hit - return the response from the cached version
            if (response) {
              return response;
            }
            
            // Clone the request since it's a one-time use
            const fetchRequest = event.request.clone();
            
            return fetch(fetchRequest)
              .then(response => {
                // Check if we received a valid response
                if (!response || response.status !== 200 || response.type !== 'basic') {
                  return response;
                }
                
                // Clone the response since it's a one-time use
                const responseToCache = response.clone();
                
                caches.open(CACHE_NAME)
                  .then(cache => {
                    cache.put(event.request, responseToCache);
                  });
                  
                return response;
              })
              .catch(error => {
                // If fetch fails, e.g. if user is offline, try to serve the offline page
                if (event.request.mode === 'navigate') {
                  return caches.match('/offline.php');
                }
              });
          });
      }
    })
  );
});

// Periodic cache cleanup check
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'CACHE_CHECK') {
    isCacheExpired().then(expired => {
      if (expired) {
        console.log('Periodic cache check: Cache expired, clearing...');
        clearAllCaches().then(() => {
          return setCacheTimestamp();
        }).then(() => {
          event.ports[0].postMessage({ success: true, message: 'Cache cleared due to expiry' });
        });
      } else {
        event.ports[0].postMessage({ success: true, message: 'Cache is still valid' });
      }
    });
  }
});

// Set up a timer to check cache expiry every 30 minutes
function setupPeriodicCacheCheck() {
  setInterval(() => {
    isCacheExpired().then(expired => {
      if (expired) {
        console.log('Automatic periodic check: Cache expired, clearing...');
        clearAllCaches().then(() => {
          return setCacheTimestamp();
        });
      }
    });
  }, 30 * 60 * 1000); // Check every 30 minutes
}

// Start periodic check when service worker loads
setupPeriodicCacheCheck();