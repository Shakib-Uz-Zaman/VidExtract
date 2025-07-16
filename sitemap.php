<?php
/**
 * Enhanced Dynamic Sitemap Generator
 * 
 * This script generates an XML sitemap for search engines with additional SEO metadata
 * Updated to include comprehensive video information and dynamic content examples
 */

// Set content type to XML
header('Content-Type: application/xml; charset=utf-8');

// Get server protocol and host
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $host;

// Current date in ISO 8601 format
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$lastWeek = date('Y-m-d', strtotime('-1 week'));

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
      xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
      xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
      xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
            http://www.google.com/schemas/sitemap-image/1.1
            http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd
            http://www.google.com/schemas/sitemap-video/1.1
            http://www.google.com/schemas/sitemap-video/1.1/sitemap-video.xsd">' . PHP_EOL;

// Define site pages with their properties
$pages = [
    [
        'loc' => '/',
        'lastmod' => $today,
        'changefreq' => 'daily',
        'priority' => '1.0',
        'image' => [
            'loc' => '/favicon/new/vidextract-tab-icon.png',
            'title' => 'VidExtract - Multimedia Information Extraction Platform',
            'caption' => 'Extract Video Thumbnails, Tags, Titles and Descriptions from Multiple Platforms'
        ]
    ],
    [
        'loc' => '/about/',
        'lastmod' => $lastWeek,
        'changefreq' => 'monthly',
        'priority' => '0.8',
        'image' => [
            'loc' => '/favicon/new/vidextract-tab-icon.png',
            'title' => 'About VidExtract',
            'caption' => 'Learn about our advanced video information extraction technology'
        ]
    ],
    [
        'loc' => '/privacy/',
        'lastmod' => $lastWeek,
        'changefreq' => 'monthly',
        'priority' => '0.8'
    ],
    [
        'loc' => '/help/',
        'lastmod' => $yesterday,
        'changefreq' => 'weekly',
        'priority' => '0.9',
        'image' => [
            'loc' => '/favicon/new/vidextract-tab-icon.png',
            'title' => 'How to Use VidExtract',
            'caption' => 'Step-by-step guides and tutorials for extracting video information'
        ]
    ]
];

// Define example video pages for better video sitemap representation
$videoPages = [
    [
        'loc' => '/?example=youtube',
        'lastmod' => $today,
        'changefreq' => 'weekly',
        'priority' => '0.8',
        'video' => [
            'thumbnail_loc' => $baseUrl . '/favicon/new/vidextract-tab-icon.png',
            'title' => 'How to Extract YouTube Video Information',
            'description' => 'Learn how to extract thumbnails, tags, and descriptions from any YouTube video using VidExtract',
            'player_loc' => 'https://www.youtube.com/embed/example',
            'duration' => 180, // 3 minutes in seconds
            'publication_date' => $lastWeek,
            'platform' => 'YouTube',
            'tag' => ['tutorial', 'youtube', 'video extraction', 'thumbnails']
        ]
    ],
    [
        'loc' => '/?example=facebook',
        'lastmod' => $yesterday,
        'changefreq' => 'weekly',
        'priority' => '0.8',
        'video' => [
            'thumbnail_loc' => $baseUrl . '/favicon/new/vidextract-tab-icon.png',
            'title' => 'Extracting Facebook Video Data',
            'description' => 'Complete tutorial on extracting information from Facebook videos without API',
            'player_loc' => 'https://www.facebook.com/plugins/video.php?href=example',
            'duration' => 240, // 4 minutes in seconds
            'publication_date' => $yesterday,
            'platform' => 'Facebook',
            'tag' => ['facebook', 'video data', 'extraction tool', 'no API']
        ]
    ],
    [
        'loc' => '/?example=instagram',
        'lastmod' => $today,
        'changefreq' => 'weekly',
        'priority' => '0.8',
        'video' => [
            'thumbnail_loc' => $baseUrl . '/favicon/new/vidextract-tab-icon.png',
            'title' => 'Instagram Reel Information Extraction',
            'description' => 'How to get information from Instagram Reels using VidExtract',
            'duration' => 120, // 2 minutes in seconds
            'publication_date' => $today,
            'platform' => 'Instagram',
            'tag' => ['instagram', 'reels', 'video information', 'social media']
        ]
    ],
    [
        'loc' => '/?example=twitter',
        'lastmod' => $today,
        'changefreq' => 'weekly',
        'priority' => '0.8',
        'video' => [
            'thumbnail_loc' => $baseUrl . '/favicon/new/vidextract-tab-icon.png',
            'title' => 'Extracting Twitter/X Video Data',
            'description' => 'Complete guide to extracting video information from Twitter/X posts',
            'duration' => 150, // 2.5 minutes in seconds
            'publication_date' => $today,
            'platform' => 'Twitter/X',
            'tag' => ['twitter', 'x', 'video extraction', 'social media data']
        ]
    ]
];

// Combine regular pages and video pages
$allPages = array_merge($pages, $videoPages);

// Generate XML for each page
foreach ($allPages as $page) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . $baseUrl . $page['loc'] . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . $page['lastmod'] . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>' . $page['changefreq'] . '</changefreq>' . PHP_EOL;
    echo '    <priority>' . $page['priority'] . '</priority>' . PHP_EOL;
    
    // Add mobile friendliness indicator
    echo '    <mobile:mobile/>' . PHP_EOL;
    
    // Add image data if available
    if (isset($page['image'])) {
        echo '    <image:image>' . PHP_EOL;
        echo '      <image:loc>' . $baseUrl . $page['image']['loc'] . '</image:loc>' . PHP_EOL;
        echo '      <image:title>' . htmlspecialchars($page['image']['title']) . '</image:title>' . PHP_EOL;
        echo '      <image:caption>' . htmlspecialchars($page['image']['caption']) . '</image:caption>' . PHP_EOL;
        echo '    </image:image>' . PHP_EOL;
    }
    
    // Add video data if available
    if (isset($page['video'])) {
        echo '    <video:video>' . PHP_EOL;
        echo '      <video:thumbnail_loc>' . $page['video']['thumbnail_loc'] . '</video:thumbnail_loc>' . PHP_EOL;
        echo '      <video:title>' . htmlspecialchars($page['video']['title']) . '</video:title>' . PHP_EOL;
        echo '      <video:description>' . htmlspecialchars($page['video']['description']) . '</video:description>' . PHP_EOL;
        
        if (isset($page['video']['player_loc'])) {
            echo '      <video:player_loc>' . $page['video']['player_loc'] . '</video:player_loc>' . PHP_EOL;
        }
        
        echo '      <video:duration>' . $page['video']['duration'] . '</video:duration>' . PHP_EOL;
        echo '      <video:publication_date>' . $page['video']['publication_date'] . '</video:publication_date>' . PHP_EOL;
        
        // Add platform as a custom tag
        if (isset($page['video']['platform'])) {
            echo '      <video:platform>' . htmlspecialchars($page['video']['platform']) . '</video:platform>' . PHP_EOL;
        }
        
        // Add video tags
        if (isset($page['video']['tag']) && is_array($page['video']['tag'])) {
            foreach ($page['video']['tag'] as $tag) {
                echo '      <video:tag>' . htmlspecialchars($tag) . '</video:tag>' . PHP_EOL;
            }
        }
        
        echo '    </video:video>' . PHP_EOL;
    }
    
    echo '  </url>' . PHP_EOL;
}

// Close XML
echo '</urlset>';
?>