<?php
/**
 * Direct Download Script for VidExtract
 * 
 * This script handles direct download requests for video thumbnails.
 * It fetches the image from the source URL and forces the browser to 
 * download it with the specified filename.
 * 
 * This is a universal solution that works across all browsers and devices.
 */

// Allow cross-origin requests (if needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Check if the URL and filename are provided
if (isset($_GET['url']) && !empty($_GET['url'])) {
    // Get URL and sanitize
    $url = urldecode($_GET['url']);
    
    // Replace double encoded characters
    $url = html_entity_decode($url);
    
    // Fix improperly encoded ampersands in Facebook/Instagram URLs
    // First convert HTML encoded "&amp;" back to "&" if present
    $url = str_replace("&amp;", "&", $url);
    
    // Add Instagram to the platforms we check for
    $isInstagramImg = (stripos($url, 'cdninstagram.com') !== false || 
                       stripos($url, 'cdninstagram') !== false || 
                       stripos($url, 'instagram.com') !== false);
    
    // Set default filename if not provided
    $filename = isset($_GET['filename']) && !empty($_GET['filename']) 
                ? $_GET['filename'] 
                : 'thumbnail.jpg';
    
    // Clean up the filename to ensure valid characters only
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
    
    // Make sure we have at least some valid filename
    if (empty($filename) || $filename === '.') {
        $filename = 'thumbnail.jpg';
    }
    
    // Get file extension from the filename
    $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Make sure extension is valid, default to jpg if not
    if (empty($fileExt) || strlen($fileExt) > 4) {
        $fileExt = 'jpg';
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
    }
    
    // Set appropriate content type based on file extension
    switch (strtolower($fileExt)) {
        case 'jpg':
        case 'jpeg':
            $contentType = 'image/jpeg';
            break;
        case 'png':
            $contentType = 'image/png';
            break;
        case 'gif':
            $contentType = 'image/gif';
            break;
        case 'webp':
            $contentType = 'image/webp';
            break;
        default:
            $contentType = 'application/octet-stream';
    }
    
    // Check if URL starts with https://scontent or facebook CDN URL
    $isFacebookImg = (stripos($url, 'scontent') !== false || stripos($url, 'fbcdn.net') !== false);
    
    // Create cURL handle
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    // Set appropriate headers to avoid referer checks on social media platforms
    $headers = array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br'
    );
    
    // Add appropriate referer based on the URL
    if ($isFacebookImg) {
        $headers[] = 'Referer: https://www.facebook.com/';
    } elseif ($isInstagramImg || stripos($url, 'instagram.com') !== false) {
        $headers[] = 'Referer: https://www.instagram.com/';
        
        // Instagram often requires additional headers to appear more like a browser
        $headers = array_merge($headers, [
            'sec-ch-ua: "Google Chrome";v="120", "Chromium";v="120"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'Sec-Fetch-Dest: image',
            'Sec-Fetch-Mode: no-cors',
            'Sec-Fetch-Site: cross-site',
            'Origin: https://www.instagram.com'
        ]);
        
        // Add cookies to help with Instagram authentication
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/instagram_cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/instagram_cookies.txt');
    } elseif (strpos($url, 'youtube') !== false || strpos($url, 'ytimg') !== false) {
        $headers[] = 'Referer: https://www.youtube.com/';
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Get the file content
    $fileContent = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log("cURL error in download.php: " . curl_error($ch) . " for URL: " . $url);
        header("HTTP/1.1 500 Internal Server Error");
        echo "Error fetching the file: " . curl_error($ch);
        exit;
    }
    
    // Get information about the request
    $info = curl_getinfo($ch);
    
    // Close cURL handle
    curl_close($ch);
    
    // Check if the request was successful
    if ($info['http_code'] != 200) {
        // Define variables for the retry
        $retryWithDifferentSettings = false;
        $retryReferer = '';
        $retryErrorMessage = '';
        
        // If Facebook image failed, try again with different referer approach
        if ($isFacebookImg && ($info['http_code'] == 403 || $info['http_code'] == 401)) {
            $retryWithDifferentSettings = true;
            $retryReferer = 'https://www.facebook.com/';
            $retryErrorMessage = "Error: Cannot access Facebook image. The image may be protected or the link may have expired.";
        } 
        // If Instagram image failed, try again with different referer approach
        else if (($isInstagramImg || stripos($url, 'cdninstagram') !== false) && 
                 ($info['http_code'] == 403 || $info['http_code'] == 401 || $info['http_code'] == 429)) {
            $retryWithDifferentSettings = true;
            $retryReferer = 'https://www.instagram.com/';
            
            // For Instagram, also modify the URL to remove query parameters which might be causing issues
            // This is a common issue with Instagram CDN URLs
            if (strpos($url, '?') !== false) {
                $url = strtok($url, '?');
            }
            
            // Fix Instagram URL format if needed - they sometimes change their CDN URL structure
            if (stripos($url, 'cdninstagram') !== false || stripos($url, 'instagram') !== false) {
                // Make sure URL doesn't have invalid escape sequences or encoding issues
                $url = str_replace('\\/', '/', $url);
                
                // Additional Instagram URL corrections that may be needed
                // Fix doubly-encoded URLs that might occur with CDN links
                $url = str_replace('%252F', '%2F', $url);
                $url = str_replace('%253A', '%3A', $url);
                
                // Ensure https:// prefix if missing
                if (!preg_match('/^https?:\/\//i', $url)) {
                    if (strpos($url, '//') === 0) {
                        $url = 'https:' . $url;
                    } elseif (strpos($url, '/') !== 0) {
                        $url = 'https://' . $url;
                    }
                }
                
                // Log for debugging
                error_log("Instagram image access - Corrected URL: " . $url);
            }
            
            $retryErrorMessage = "Error: Cannot access Instagram image. The image may be protected or the link may have expired.";
        }
        // If YouTube image failed, try again with different referer approach
        else if ((strpos($url, 'youtube') !== false || strpos($url, 'ytimg') !== false) && 
                 ($info['http_code'] == 403 || $info['http_code'] == 401)) {
            $retryWithDifferentSettings = true;
            $retryReferer = 'https://www.youtube.com/';
            $retryErrorMessage = "Error: Cannot access YouTube image. The image may be protected or no longer available.";
        }
        
        // If we should retry with different settings
        if ($retryWithDifferentSettings) {
            // Try alternative approach with modified headers
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_REFERER, $retryReferer);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            
            $fileContent = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            
            if ($info['http_code'] != 200) {
                // If still failed, show error
                header("HTTP/1.1 " . $info['http_code'] . " Error");
                echo $retryErrorMessage;
                exit;
            }
        } else {
            header("HTTP/1.1 " . $info['http_code'] . " Error");
            echo "Error: HTTP status code " . $info['http_code'];
            exit;
        }
    }
    
    // Choose between download and display mode
    $displayMode = isset($_GET['display']) && $_GET['display'] === 'true';
    
    // Set appropriate headers based on mode
    header("Content-Type: $contentType");
    
    if (!$displayMode) {
        // Download mode - force download with filename
        header("Content-Disposition: attachment; filename=\"$filename\"");
    } else {
        // Display mode - allow browser to show the image inline
        header("Content-Disposition: inline");
    }
    
    header("Content-Length: " . strlen($fileContent));
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    
    // Check if we need to resize the image
    if (isset($_GET['width']) && isset($_GET['height']) && is_numeric($_GET['width']) && is_numeric($_GET['height'])) {
        $width = (int)$_GET['width'];
        $height = (int)$_GET['height'];
        
        // Only resize if we have valid dimensions
        if ($width > 0 && $height > 0) {
            // Create image from string
            $sourceImage = imagecreatefromstring($fileContent);
            
            if ($sourceImage !== false) {
                // Create a new true color image with the requested dimensions
                $targetImage = imagecreatetruecolor($width, $height);
                
                // Handle transparent PNG properly
                if ($contentType === 'image/png') {
                    imagealphablending($targetImage, false);
                    imagesavealpha($targetImage, true);
                    $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
                    imagefilledrectangle($targetImage, 0, 0, $width, $height, $transparent);
                }
                
                // Resize to the specified dimensions
                imagecopyresampled(
                    $targetImage, 
                    $sourceImage, 
                    0, 0, 0, 0, 
                    $width, $height, 
                    imagesx($sourceImage), 
                    imagesy($sourceImage)
                );
                
                // Start output buffering to capture the image data
                ob_start();
                
                // Generate the image based on content type
                switch ($contentType) {
                    case 'image/jpeg':
                        imagejpeg($targetImage, null, 90); // 90% quality
                        break;
                    case 'image/png':
                        imagepng($targetImage, null, 9); // Highest compression
                        break;
                    case 'image/gif':
                        imagegif($targetImage);
                        break;
                    case 'image/webp':
                        imagewebp($targetImage, null, 90); // 90% quality
                        break;
                    default:
                        imagejpeg($targetImage, null, 90); // Default to JPEG
                }
                
                // Get the image data from the output buffer
                $fileContent = ob_get_clean();
                
                // Free up memory
                imagedestroy($sourceImage);
                imagedestroy($targetImage);
                
                // Update the content length header
                header("Content-Length: " . strlen($fileContent));
            }
        }
    }
    
    // Output the file content (either original or resized)
    echo $fileContent;
} else {
    // If URL is not provided, show an error
    header("HTTP/1.1 400 Bad Request");
    echo "Error: URL parameter is required";
}