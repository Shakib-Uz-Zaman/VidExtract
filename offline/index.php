<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Offline - VidExtract</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/input-style.css">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#ff0000">
    <link rel="apple-touch-icon" href="/vidextract-tab-icon.png">
    <link rel="preload" href="/vidextract-tab-icon.webp" as="image" type="image/webp">
    <link rel="preload" href="/vidx-logo.webp" as="image" type="image/webp">
    <style>
        .offline-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 70vh;
            text-align: center;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .offline-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        .offline-title {
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .offline-message {
            margin-bottom: 20px;
            color: #666;
        }
        
        .reload-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .reload-button:hover {
            background-color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo">
                <a href="/">
                    <picture>
                        <source srcset="/vidx-logo.webp" type="image/webp">
                        <img src="/vidx-logo.png" alt="VidX" class="logo-image" width="96" height="32">
                    </picture>
                </a>
            </div>
        </div>
    </header>
    
    <main>
        <div class="offline-container">
            <div class="offline-icon">📶</div>
            <h1 class="offline-title">You're Offline</h1>
            <p class="offline-message">
                It looks like you've lost your internet connection. 
                Some features of VidExtract require an active internet connection to work properly.
            </p>
            <button class="reload-button" onclick="window.location.reload()">Try Again</button>
        </div>
    </main>
    
    <!-- Simple Footer -->
    <footer class="simple-footer">
        <div class="footer-nav">
            <a href="/">Home</a> |
            <a href="/about/">About</a> |
            <a href="/help/">Help</a> |
            <a href="/privacy/">Privacy</a>
        </div>
        <div class="footer-copyright">
            © <span id="current-year"></span> VidExtract. All rights reserved.
        </div>
    </footer>

    <script>
        // Auto-update current year
        document.getElementById('current-year').textContent = new Date().getFullYear();
    </script>

</body>
</html>