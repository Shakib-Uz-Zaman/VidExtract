<?php
/**
 * About Page - Vidextract
 * 
 * Provides information about the tool, its capabilities, and the team behind it
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <!-- Primary Meta Tags -->
    <title>About - Vidextract</title>
    <meta name="title" content="About - Vidextract">
    <meta name="description" content="Learn about Vidextract - the advanced multimedia information extraction platform for YouTube, Facebook, Instagram, and Twitter/X content.">
    <meta name="keywords" content="video extractor, video data, YouTube extraction, Facebook video info, Instagram reel data, Twitter video data, about Vidextract, video metadata tool">
    <meta name="author" content="Vidextract">
    
    <!-- Search Engine Optimization Meta Tags -->
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta http-equiv="content-language" content="en">
    <meta name="language" content="English">
    <meta name="revisit-after" content="1 day">
    <meta name="rating" content="general">
    <meta name="application-name" content="Vidextract">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Structured Data Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "About Vidextract",
        "description": "Learn about Vidextract - the advanced multimedia information extraction platform for YouTube, Facebook, Instagram, and Twitter/X content.",
        "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'); ?>",
        "publisher": {
            "@type": "Organization",
            "name": "Vidextract",
            "logo": {
                "@type": "ImageObject",
                "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp"
            }
        }
    }
    </script>
    
    <!-- Enhanced Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="About - Vidextract">
    <meta property="og:description" content="Learn about Vidextract - the advanced multimedia information extraction platform for YouTube, Facebook, Instagram, and Twitter/X content.">
    <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/vidextract-tab-icon.webp'; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Vidextract">
    <meta property="og:locale" content="en_US">
    
    <!-- Enhanced Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@vidextract">
    <meta name="twitter:creator" content="@vidextract">
    <meta name="twitter:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta name="twitter:title" content="About - Vidextract">
    <meta name="twitter:description" content="Learn about Vidextract - the advanced multimedia information extraction platform for YouTube, Facebook, Instagram, and Twitter/X content.">
    <meta name="twitter:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/vidextract-tab-icon.webp'; ?>">
    
    <!-- Enhanced Favicon System -->
    <link rel="icon" href="/vidextract-tab-icon.webp" type="image/webp">
    <link rel="shortcut icon" href="/vidextract-tab-icon.webp" type="image/webp">
    <link rel="apple-touch-icon" href="/vidextract-tab-icon.webp">
    <link rel="apple-touch-icon" sizes="57x57" href="/vidextract-tab-icon.webp">
    <link rel="apple-touch-icon" sizes="72x72" href="/vidextract-tab-icon.webp">
    <link rel="apple-touch-icon" sizes="76x76" href="/vidextract-tab-icon.webp">
    <link rel="apple-touch-icon" sizes="114x114" href="/vidextract-tab-icon.webp">
    <link rel="apple-touch-icon" sizes="120x120" href="/vidextract-tab-icon.webp">
    <link rel="apple-touch-icon" sizes="144x144" href="/vidextract-tab-icon.webp">
    <link rel="apple-touch-icon" sizes="152x152" href="/vidextract-tab-icon.webp">
    <link rel="apple-touch-icon" sizes="180x180" href="/vidextract-tab-icon.webp">
    <link rel="icon" type="image/webp" sizes="192x192" href="/vidextract-tab-icon.webp">
    <link rel="icon" type="image/webp" sizes="32x32" href="/vidextract-tab-icon.webp">
    <link rel="icon" type="image/webp" sizes="96x96" href="/vidextract-tab-icon.webp">
    <link rel="icon" type="image/webp" sizes="16x16" href="/vidextract-tab-icon.webp">
    <link rel="mask-icon" href="/vidextract-tab-icon.webp" color="#4285f4">
    
    <!-- Performance & Security -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <!-- PWA Support -->
    <meta name="apple-touch-fullscreen" content="yes">
    <link rel="manifest" href="/manifest.json">
    
    <!-- Preload FontAwesome for faster icon loading -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    

    
    <!-- Tab favicon using the specially requested icon -->
    <link rel="icon" href="/vidextract-tab-icon.webp" type="image/webp">
    <link rel="shortcut icon" href="/vidextract-tab-icon.webp" type="image/webp">
    

    
    <!-- Apply DOM loaded class when ready, this makes HTML visible -->
    <script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            document.documentElement.classList.add('dom-loaded');
        });
        
        // Instantly make the page visible if it was already loaded 
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            document.documentElement.classList.add('dom-loaded');
        }
    })();
    </script>
    
    <!-- Open Graph Meta Tags for social sharing -->
    <meta property="og:title" content="About - VidExtract">
    <meta property="og:description" content="Learn about VidExtract - the advanced multimedia information extraction platform for YouTube, Facebook, Instagram, and Twitter/X content.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/vidextract-tab-icon.webp'; ?>">
    <meta property="og:site_name" content="VidExtract">
    


    <link rel="stylesheet" href="../css/style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional styles for About page */
        .about-section {
            background-color: #ffffff;
            border-radius: 0.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .about-section h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 1.25rem;
            border-bottom: 2px solid rgba(var(--primary-color-rgb), 0.2);
            padding-bottom: 0.75rem;
        }
        
        .about-section p {
            margin-bottom: 1.25rem;
            line-height: 1.7;
            color: #333;
        }
        
        .about-section ul {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .about-section li {
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .about-section li::before {
            content: "";
            position: absolute;
            left: -1.25rem;
            top: 0.5rem;
            width: 8px;
            height: 8px;
            background-color: var(--primary-color);
            border-radius: 50%;
        }
        

        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .feature-item {
            background-color: transparent;
            border-radius: 0.5rem;
            padding: 1.5rem;
            border: 1px solid #eee;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1a1a1a;
        }
        
        .feature-description {
            font-size: 0.95rem;
            color: #555;
            line-height: 1.6;
        }
        
        .team-section {
            text-align: center;
        }
        
        .team-info {
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Feature Highlight Section */
        .feature-highlight {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin: 2rem auto 3rem;
            max-width: 1200px;
            gap: 1.5rem;
        }
        
        .feature-item-large {
            background-color: transparent;
            border-radius: 12px;
            padding: 1.5rem;
            flex: 0 0 calc(33.33% - 1.5rem);
            margin-bottom: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid #eee;
        }
        
        .feature-icon-large {
            font-size: 2.2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .feature-item-large h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1a1a1a;
            position: relative;
            z-index: 1;
        }
        
        .feature-item-large p {
            font-size: 1rem;
            color: #555;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 992px) {
            .feature-item-large {
                flex: 0 0 calc(50% - 1.5rem);
            }
        }
        
        @media (max-width: 768px) {
            .feature-grid {
                grid-template-columns: 1fr;
            }
            
            .about-section {
                padding: 1.5rem;
            }
            
            .feature-item-large {
                flex: 0 0 100%;
            }
            

        }
    </style>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="VidExtract">
    <link rel="apple-touch-icon" href="/vidextract-tab-icon.png">
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
            
            <nav class="main-nav">
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/about/" class="active">About</a></li>
                    <li><a href="/privacy/">Privacy</a></li>
                    <li><a href="/help/">Help</a></li>
                </ul>
            </nav>
            
            <div class="header-right">
                <!-- PWA Install Banner in Header -->
                <div id="pwa-install-container" class="pwa-header-banner" style="display: none;">
                    <button id="pwa-install-button" class="pwa-open-app-btn">
                        <span>Open App</span>
                    </button>
                </div>
                
                <div class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>
    </header>
    <div class="container">
        <main>
            <div class="about-section">


                <h2>About Our Tool</h2>
                <p>VidExtract is a multimedia information extraction platform that helps users extract information from YouTube, Facebook, Instagram, and Twitter/X. This tool provides access to video thumbnails, titles, descriptions, and metadata tags without requiring an API key or registration.</p>
                
                <div class="privacy-callout">
                    <p>Our mission is to make multimedia content information more accessible for content creators, marketers, researchers, and casual users who need quick access to video assets and metadata across multiple platforms.</p>
                </div>
            </div>
            
            <div class="about-section">
                <h2>Key Features</h2>
                <p>VidExtract offers these key features:</p>
            </div>
            
            <div class="feature-highlight">
                <div class="feature-item-large">
                    <div class="feature-icon-large">
                        <i class="fas fa-image"></i>
                    </div>
                    <h3>High-Quality Thumbnails</h3>
                    <p>Download video thumbnails in multiple resolutions.</p>
                </div>
                
                <div class="feature-item-large">
                    <div class="feature-icon-large">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Metadata Tags</h3>
                    <p>Access hidden metadata tags from videos and posts.</p>
                </div>
                
                <div class="feature-item-large">
                    <div class="feature-icon-large">
                        <i class="fas fa-film"></i>
                    </div>
                    <h3>Multi-Platform Support</h3>
                    <p>Works with YouTube, Facebook, Instagram, and Twitter/X.</p>
                </div>
                
                <div class="feature-item-large">
                    <div class="feature-icon-large">
                        <i class="fas fa-align-left"></i>
                    </div>
                    <h3>Content Extraction</h3>
                    <p>Extract titles, descriptions, and post details.</p>
                </div>
                
                <div class="feature-item-large">
                    <div class="feature-icon-large">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>No API Required</h3>
                    <p>Works without API keys or authentication.</p>
                </div>
                
                <div class="feature-item-large">
                    <div class="feature-icon-large">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Responsive Design</h3>
                    <p>Optimized for all devices and screen sizes.</p>
                </div>
                
                <div class="feature-item-large">
                    <div class="feature-icon-large">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3>Direct Downloads</h3>
                    <p>Download thumbnails with a single click.</p>
                </div>
                
                <div class="feature-item-large">
                    <div class="feature-icon-large">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <h3>Platform Theming</h3>
                    <p>Enjoy platform-specific color themes.</p>
                </div>
            </div>
            
            <div class="about-section team-section">
                <h2>About SbX Group</h2>
                <p>SbX Group creates innovative tools for content creators and digital marketers. We focus on simplifying everyday tasks through intuitive web applications.</p>
                
                <div class="privacy-callout">
                    <p>Our goal is to create tools that are accessible, reliable, and genuinely useful in the digital landscape.</p>
                </div>
            </div>
            
            <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> <span class="back-text">Back to Home</span></a>
        </main>
        

    </div>
    

    

    

    <script src="../js/favicon-switcher.js"></script>
    <script src="../js/adaptive-spinner.js"></script>
    <script src="../js/page-transitions.js"></script>
    <script src="../js/spa-navigation.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check localStorage for theme preference and apply it
            const storedTheme = localStorage.getItem('videoType');
            if (storedTheme === 'facebook') {
                document.body.classList.add('facebook-theme');
            } else {
                document.body.classList.remove('facebook-theme');
            }
            
            // Setup network status monitoring
            if (typeof setupNetworkStatusMonitoring === 'function') {
                setupNetworkStatusMonitoring();
            }
        });
    </script>
    <!-- PWA Install Banner -->

    
    <!-- Load the PWA installer script -->
    <script src="/js/pwa-installer.js"></script>

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