<?php
/**
 * YouTube Video Info Extractor
 * 
 * This tool extracts video ID from a YouTube URL and displays the thumbnail
 * No API key required - works directly with video ID.
 */



// Initialize variables
$error = null;
$videoId = null;
$videoTitle = null;
$videoDescription = null;
$videoTags = [];
$videoSuccess = false;

// Check for platform selection in POST data for extraction
$videoType = 'youtube'; // Default platform

if (isset($_POST['video_type'])) {
    // Make sure it's a valid platform type
    $validTypes = ['youtube', 'facebook', 'instagram', 'twitter'];
    if (in_array($_POST['video_type'], $validTypes)) {
        $videoType = $_POST['video_type'];
    }
}

/**
 * Extract YouTube video ID from URL (including Shorts)
 * 
 * @param string $url YouTube video URL
 * @return string|false Video ID or false if not found
 */
function extractYoutubeVideoId($url) {
    // Pattern for standard YouTube videos
    $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    
    // Pattern for YouTube Shorts
    $shortsPattern = '/(?:youtube\.com\/shorts\/|youtube\.com\/(?:.+)\/shorts\/)([a-zA-Z0-9_-]{11})/';
    if (preg_match($shortsPattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted YouTube Shorts ID: " . $matches[1]);
        }
        return $matches[1];
    }
    
    // Pattern for YouTube Live videos
    $livePattern = '/youtube\.com\/live\/([a-zA-Z0-9_-]{11})/';
    if (preg_match($livePattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted YouTube Live ID: " . $matches[1]);
        }
        return $matches[1];
    }
    
    // Pattern for YouTube Post links (with or without query parameters)
    $postPattern = '/youtube\.com\/post\/([A-Za-z0-9_\-]+)(?:\?[^\/]*)?/';
    if (preg_match($postPattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted YouTube Post ID: " . $matches[1]);
        }
        return $matches[1];
    }
    
    // Pattern for YouTube Channel links (@username format)
    $channelPattern = '/youtube\.com\/@([A-Za-z0-9_\-]+)(?:\?[^\/]*)?/';
    if (preg_match($channelPattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted YouTube Channel Username: " . $matches[1]);
        }
        // Return with a special prefix to identify it as a channel
        return 'channel_' . $matches[1];
    }
    
    // If the URL is exactly 11 characters, it might be a direct video ID
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    
    return false;
}

/**
 * Extract Facebook video ID from URL
 * 
 * @param string $url Facebook video URL
 * @return string|false Video ID or false if not found
 */
function extractFacebookVideoId($url) {
    // Clean and normalize the URL
    $url = trim($url);
    
    // Special case for direct video ID input (just numbers)
    if (preg_match('/^\d{5,}$/', $url)) {
        return $url; // The input is already a video ID
    }
    
    // Handle URLs that might be incomplete or missing https://
    if (strpos($url, 'http') !== 0) {
        // Add https:// if the URL starts with facebook.com, www.facebook.com, m.facebook.com, or fb.watch
        if (preg_match('/^((?:www\.|m\.)?facebook\.com|fb\.watch)/', $url)) {
            $url = 'https://' . $url;
        }
    }
    
    // Special handling for fb.watch URLs with mibextid
    if (strpos($url, 'fb.watch/') !== false && strpos($url, 'mibextid=') !== false) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Processing special fb.watch URL with mibextid: " . $url);
        }
        
        // Extract the path part (after fb.watch/ and before any query params)
        if (preg_match('#fb\.watch/([^/\?]+)#i', $url, $pathMatches)) {
            $shortCode = $pathMatches[1];
            
            // If the short code is a number and reasonably long, it's likely the video ID
            if (is_numeric($shortCode) && strlen($shortCode) >= 5) {
                return $shortCode;
            }
            
            // If short code isn't a number, try getting mibextid
            if (preg_match('/[?&]mibextid=([^&]+)/i', $url, $mibMatches)) {
                // For fb.watch links, the mibextid is likely our best identifier
                return 'mib_' . $mibMatches[1]; // Prefix with 'mib_' to identify source
            }
            
            // Return the short code as a fallback
            return 'fbw_' . $shortCode; // Prefix with 'fbw_' to identify source
        }
        
        // If we couldn't extract the path but have mibextid
        if (preg_match('/[?&]mibextid=([^&]+)/i', $url, $mibMatches)) {
            return 'mib_' . $mibMatches[1];
        }
    }
    
    // Pattern for various Facebook video URL formats (comprehensive set)
    $patterns = [
        // Standard desktop formats
        '/facebook\.com\/(?:[^\/]+\/)?videos\/(?:vb\.\d+\/)?(\d+)/', // Regular video URL
        '/facebook\.com\/watch\/?\?v=(\d+)/', // Watch URL format
        '/facebook\.com\/watch\/\?ref=saved&v=(\d+)/', // Watch with ref parameter
        '/facebook\.com\/(?:[^\/]+\/)?videos\/(?:[^\/]+\/)?(\d+)/', // Alternate video URL with slash
        '/facebook\.com\/watch\/live\/\?v=(\d+)/', // Live video URL format
        '/facebook\.com\/(?:[^\/]+\/)?posts\/(?:[^\/]+\/)?(\d+)/', // Post with video
        '/facebook\.com\/\d+\/videos\/(\d+)/', // Numeric profile with video
        '/facebook\.com\/(?:[^\/]+\/)?video_id=(\d+)/', // Video ID in query parameter
        '/facebook\.com\/permalink\.php.*?(?:v|video_id|story_fbid)=(\d+)/', // Permalinks
        '/facebook\.com\/groups\/(?:[^\/]+\/)?permalink\/(\d+)/', // Group permalinks
        '/facebook\.com\/photo\.php\?(?:[^&]+&)*v=(\d+)/', // Photo page with video
        '/facebook\.com\/(?:[^\/]+\/)?photos\/(?:[^\/]+\/)?(\d+)/', // Photos page format
        '/facebook\.com\/reel\/(\d+)/', // Facebook Reels
        
        // Mobile formats
        '/m\.facebook\.com\/(?:[^\/]+\/)?videos\/(?:vb\.\d+\/)?(\d+)/', // Mobile video URL
        '/m\.facebook\.com\/watch\/?\?v=(\d+)/', // Mobile watch format
        '/m\.facebook\.com\/(?:[^\/]+\/)?story\.php.*?(?:story_fbid|id)=(\d+)/', // Mobile story
        '/m\.facebook\.com\/(?:[^\/]+\/)?reel\/(\d+)/', // Mobile reels
        '/m\.facebook\.com\/(?:[^\/]+\/)?video\.php\?v=(\d+)/', // Mobile video.php
        '/mbasic\.facebook\.com\/(?:[^\/]+\/)?videos\/(?:vb\.\d+\/)?(\d+)/', // Basic mobile
        
        // Short formats (only if not already handled by mibextid check)
        '/fb\.watch\/([^\/\?&]+)/', // Short fb.watch format without parameters
        '/facebook\.com\/watch\/(\d+)/', // Direct watch with ID
        '/video\.php\?v=(\d+)/', // Direct video.php URLs
        
        // Post IDs and story IDs that may contain videos
        '/story_fbid=(\d+)/', // Story fbid in query
        '/fbid=(\d+)/', // fbid in query  
        '/\/posts\/(\d+)/', // Posts format without domain
        
        // Special case for Instagram cross-posts
        '/instagram\.com\/(?:p|reel)\/([^\/]+)/', // Instagram post or reel
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    // Special case for Facebook URLs with various ID parameters
    $idParameters = [
        'mibextid', 'mbextid', 'v', 'video_id', 'id', 'story_fbid', 'story_id', 'post_id', 'photo_id'
    ];
    
    foreach ($idParameters as $param) {
        if (preg_match('/[?&]' . $param . '=([a-zA-Z0-9\.\_\-]+)/i', $url, $paramMatches)) {
            // For mibextid, add a prefix to identify the source
            if ($param === 'mibextid' || $param === 'mbextid') {
                return 'mib_' . $paramMatches[1];
            }
            return $paramMatches[1];
        }
    }
    
    // If we get here, try to extract any sequence of digits that might be a video ID
    // This is a last resort fallback
    if (preg_match('/[\/=:](\d{5,})[\/\?&]?/', $url, $digitMatches)) {
        return $digitMatches[1];
    }
    
    return false;
}

/**
 * Extract Instagram post ID from URL
 * 
 * @param string $url Instagram post URL
 * @return string|false Post ID or false if not found
 */
function extractInstagramPostId($url) {
    // Clean and normalize the URL
    $url = trim($url);
    
    // Handle URLs that might be incomplete or missing https://
    if (strpos($url, 'http') !== 0) {
        // Add https:// if the URL starts with instagram.com or www.instagram.com
        if (preg_match('/^((?:www\.)?instagram\.com)/', $url)) {
            $url = 'https://' . $url;
        }
    }
    
    // Pattern for Instagram post URLs (p/ format)
    $postPattern = '/instagram\.com\/p\/([^\/\?\&]+)/i';
    if (preg_match($postPattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted Instagram Post ID: " . $matches[1]);
        }
        return $matches[1];
    }
    
    // Pattern for Instagram reel URLs (reel/ format)
    $reelPattern = '/instagram\.com\/reel\/([^\/\?\&]+)/i';
    if (preg_match($reelPattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted Instagram Reel ID: " . $matches[1]);
        }
        return $matches[1];
    }
    
    // If the URL format is something else but contains what looks like an Instagram shortcode
    $shortcodePattern = '/([A-Za-z0-9_\-]{11})/';
    if (preg_match($shortcodePattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted potential Instagram ID from URL: " . $matches[1]);
        }
        return $matches[1];
    }
    
    return false;
}

/**
 * Extract Twitter/X video ID from URL
 * 
 * @param string $url Twitter/X video URL
 * @return string|false Video ID or false if not found
 */
function extractTwitterVideoId($url) {
    // Clean and normalize the URL
    $url = trim($url);
    
    // Store the original URL
    $originalUrl = $url;
    
    // Handle URLs that might be incomplete or missing https://
    if (strpos($url, 'http') !== 0) {
        // Add https:// if the URL starts with twitter.com, x.com or related domains
        if (preg_match('/^((?:www\.)?(twitter|x)\.com|pic\.twitter\.com)/', $url)) {
            $url = 'https://' . $url;
        }
    }
    
    // Check for query parameters that might interfere with extraction
    $urlParts = parse_url($url);
    $cleanUrl = isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '';
    $cleanUrl .= isset($urlParts['host']) ? $urlParts['host'] : '';
    $cleanUrl .= isset($urlParts['path']) ? $urlParts['path'] : '';
    
    // Pattern for Twitter/X status URLs (most common format for videos)
    $statusPattern = '/(twitter|x)\.com\/(?:[^\/]+)\/status\/(\d+)/i';
    if (preg_match($statusPattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted Twitter/X Status ID: " . $matches[2]);
        }
        // Return with url_prefix to indicate this is a full URL
        return 'url_' . urlencode($originalUrl); 
    }
    
    // Pattern for direct pic.twitter.com URLs
    $picPattern = '/pic\.twitter\.com\/([A-Za-z0-9]+)/i';
    if (preg_match($picPattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted Twitter pic ID: " . $matches[1]);
        }
        return 'pic_' . $matches[1]; // Prefix to indicate this is a direct pic URL
    }
    
    // Pattern for shortened Twitter/X URLs (t.co links)
    $shortUrlPattern = '/t\.co\/([A-Za-z0-9]+)/i';
    if (preg_match($shortUrlPattern, $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted Twitter shortened URL: " . $matches[1]);
        }
        return 'short_' . $matches[1]; // Prefix to indicate this is a short URL
    }
    
    // If the URL format is a plain number, it might be a direct tweet ID
    if (preg_match('/^(\d{10,})\D*$/', $url, $matches)) {
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Extracted Twitter/X ID directly: " . $matches[1]);
        }
        return $matches[1];
    }
    
    return false;
}



/**
 * Advanced function to extract tags from Facebook HTML content
 * This handles various ways tags might appear in the Facebook HTML
 * 
 * @param string $html The HTML content to analyze
 * @param string $videoId The Facebook video ID (for debugging)
 * @return array Array of tags or empty array if none found
 */
function extractFacebookTagsFromHTML($html, $videoId = '') {
    $tags = [];
    
    if (empty($html)) {
        return $tags;
    }
    
    // Method 1: Meta tags - These are the most reliable when available
    $metaTagPatterns = [
        '/<meta\s+property="(video:tag|og:video:tag)"\s+content="([^"]+)"/i',
        '/<meta\s+property=\'(video:tag|og:video:tag)\'\s+content=\'([^\']+)\'/i',
        '/<meta\s+property="article:tag"\s+content="([^"]+)"/i',
        '/<meta\s+property=\'article:tag\'\s+content=\'([^\']+)\'/i',
        '/<meta\s+name="keywords"\s+content="([^"]+)"/i'
    ];
    
    foreach ($metaTagPatterns as $pattern) {
        if (strpos($pattern, 'keywords') !== false) {
            // Keywords meta tag uses comma-separated format
            if (preg_match($pattern, $html, $matches)) {
                $keywords = explode(',', $matches[1]);
                foreach ($keywords as $keyword) {
                    $decodedTag = html_entity_decode(trim($keyword), ENT_QUOTES, 'UTF-8');
                    if (isValidTag($decodedTag) && !in_array($decodedTag, $tags)) {
                        $tags[] = $decodedTag;
                    }
                }
            }
        } else {
            // Standard meta tag pattern 
            preg_match_all($pattern, $html, $matches);
            if (!empty($matches) && isset($matches[2]) && !empty($matches[2])) {
                foreach ($matches[2] as $tag) {
                    $decodedTag = html_entity_decode(trim($tag), ENT_QUOTES, 'UTF-8');
                    if (isValidTag($decodedTag) && !in_array($decodedTag, $tags)) {
                        $tags[] = $decodedTag;
                    }
                }
            } elseif (!empty($matches) && isset($matches[1]) && !empty($matches[1]) && count($matches) == 2) {
                // This handles the article:tag pattern
                foreach ($matches[1] as $tag) {
                    $decodedTag = html_entity_decode(trim($tag), ENT_QUOTES, 'UTF-8');
                    if (isValidTag($decodedTag) && !in_array($decodedTag, $tags)) {
                        $tags[] = $decodedTag;
                    }
                }
            }
        }
    }
    
    // Method 2: JSON-LD structured data - used by some Facebook pages
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $jsonLdMatches);
    if (!empty($jsonLdMatches[1])) {
        foreach ($jsonLdMatches[1] as $jsonLd) {
            // Look for keywords in JSON-LD data
            if (preg_match('/"keywords":\s*\[(.*?)\]/is', $jsonLd, $keywordsMatch)) {
                preg_match_all('/"([^"]+)"/i', $keywordsMatch[1], $keywordItems);
                if (!empty($keywordItems[1])) {
                    foreach ($keywordItems[1] as $tag) {
                        $decodedTag = html_entity_decode(trim($tag), ENT_QUOTES, 'UTF-8');
                        if (isValidTag($decodedTag) && !in_array($decodedTag, $tags)) {
                            $tags[] = $decodedTag;
                        }
                    }
                }
            }
        }
    }
    
    // Method 3: Embedded JavaScript data structures
    $jsDataPatterns = [
        '/"VideoTags":\s*\[(.*?)\]/is' => '/"text":"([^"]+)"/i',
        '/"videoTags":\s*{.*?"edges":\s*\[(.*?)\]}/is' => '/"node":\s*{"name":"([^"]+)"/i',
        '/tagList":\s*\[(.*?)\]/is' => '/"text":"([^"]+)"/i',
        '/"tags":\s*\[(.*?)\]/is' => '/"name":"([^"]+)"/i'
    ];
    
    foreach ($jsDataPatterns as $containerPattern => $itemPattern) {
        if (preg_match($containerPattern, $html, $containerMatch)) {
            preg_match_all($itemPattern, $containerMatch[1], $itemMatches);
            if (!empty($itemMatches[1])) {
                foreach ($itemMatches[1] as $tag) {
                    $decodedTag = html_entity_decode(trim($tag), ENT_QUOTES, 'UTF-8');
                    if (isValidTag($decodedTag) && !in_array($decodedTag, $tags)) {
                        $tags[] = $decodedTag;
                    }
                }
            }
        }
    }
    
    // Method 4: Look for inline script with common tag data patterns
    $scriptPatterns = [
        '/<script>.*?"tag_expansion_data":\s*\[(.*?)\].*?<\/script>/is' => '/"tag_name":"([^"]+)"/i',
        '/<script>.*?"tags":\s*\[(.*?)\].*?<\/script>/is' => '/"name":"([^"]+)"/i'
    ];
    
    foreach ($scriptPatterns as $scriptPattern => $tagPattern) {
        if (preg_match($scriptPattern, $html, $scriptMatch)) {
            preg_match_all($tagPattern, $scriptMatch[1], $tagMatches);
            if (!empty($tagMatches[1])) {
                foreach ($tagMatches[1] as $tag) {
                    $decodedTag = html_entity_decode(trim($tag), ENT_QUOTES, 'UTF-8');
                    if (isValidTag($decodedTag) && !in_array($decodedTag, $tags)) {
                        $tags[] = $decodedTag;
                    }
                }
            }
        }
    }
    
    // Method 5: Look for description text with hashtags as a source for keyword extraction
    if (preg_match('/<meta\s+property="og:description"\s+content="([^"]+)"/i', $html, $descMatch)) {
        $description = html_entity_decode(trim($descMatch[1]), ENT_QUOTES, 'UTF-8');
        if (!empty($description)) {
            // Look for words that seem to be keywords or topics (common in Facebook videos)
            preg_match_all('/\b([A-Za-z\x{00C0}-\x{00FF}][A-Za-z\x{00C0}-\x{00FF}\'&\-]{2,20})\b/u', $description, $wordMatches);
            if (!empty($wordMatches[1])) {
                // Select likely topic words (proper nouns, etc.)
                foreach ($wordMatches[1] as $word) {
                    $word = trim($word);
                    // Only include words that look like proper nouns or subjects
                    if (isProperNounOrSubject($word) && !in_array($word, $tags)) {
                        $tags[] = $word;
                    }
                }
            }
        }
    }
    
    // Method 6: Look for specific Facebook data structures used for video tags
    if (preg_match('/\[\{"__typename":"VideoInfo".*?\}\]/s', $html, $fbDataMatch)) {
        if (preg_match_all('/"text":"([^"]+)"/i', $fbDataMatch[0], $fbTextMatches)) {
            foreach ($fbTextMatches[1] as $tag) {
                $decodedTag = html_entity_decode(trim($tag), ENT_QUOTES, 'UTF-8');
                if (isValidTag($decodedTag) && !in_array($decodedTag, $tags)) {
                    $tags[] = $decodedTag;
                }
            }
        }
    }
    
    // Method 7: Look for hashtags in the content
    if (preg_match_all('/#([A-Za-z0-9_\x{00C0}-\x{00FF}]{2,30})/u', $html, $hashtagMatches)) {
        foreach ($hashtagMatches[1] as $hashtag) {
            $decodedTag = html_entity_decode(trim($hashtag), ENT_QUOTES, 'UTF-8');
            if (isValidTag($decodedTag) && !in_array($decodedTag, $tags)) {
                $tags[] = $decodedTag;
            }
        }
    }
    
    // Remove duplicates
    $tags = array_unique($tags);
    
    // If we didn't find any tags, and this is a Facebook video
    // Let's look for any potential text candidates from specific sections
    if (empty($tags)) {
        // Look for user-facing text elements from common sections
        preg_match_all('/<div[^>]*>\s*([^<]{3,50})\s*<\/div>/i', $html, $divTextMatches);
        if (!empty($divTextMatches[1])) {
            foreach ($divTextMatches[1] as $textElement) {
                $text = trim($textElement);
                // Only use text that looks natural (not code)
                if (isNaturalLanguageText($text) && !in_array($text, $tags)) {
                    $tags[] = $text;
                }
            }
        }
    }
    
    // Limit tags to 10 to prevent overwhelming
    if (count($tags) > 10) {
        $tags = array_slice($tags, 0, 10);
    }
    
    return $tags;
}

/**
 * Check if a string is a valid tag
 * Filters out hex codes, numbers, and other non-tag content
 * 
 * @param string $tag The tag to check
 * @return bool True if valid, false otherwise
 */
function isValidTag($tag) {
    // Must not be empty
    if (empty($tag)) {
        return false;
    }
    
    // Must be at least 2 characters
    if (strlen($tag) < 2) {
        return false;
    }
    
    // Must not be too long to be a tag
    if (strlen($tag) > 40) {
        return false;
    }
    
    // Must not be a hex code (common false positive in Facebook DOM)
    if (preg_match('/^[0-9A-F]{4,}$/i', $tag)) {
        return false;
    }
    
    // Filter out CSS selectors (like x9aa, x9cd, etc.)
    if (preg_match('/^x[0-9a-f]{2,4}$/i', $tag)) {
        return false;
    }
    
    // Filter out patterns that look like CSS classes (common in Facebook DOM)
    if (preg_match('/^[a-z][a-z0-9_]*[0-9]{1,2}$/i', $tag)) {
        return false;
    }
    
    // Must not be only numbers
    if (preg_match('/^[0-9]+$/', $tag)) {
        return false;
    }
    
    // Filter out Facebook reaction counts that might be parsed incorrectly
    if (preg_match('/^\d+\.?\d*[KM]?\s+reactions$/i', $tag)) {
        return false;
    }
    
    // Filter out share counts
    if (preg_match('/^\d+\.?\d*[KM]?\s+shares$/i', $tag)) {
        return false;
    }
    
    // Filter out view counts
    if (preg_match('/^\d+\.?\d*[KM]?\s+views$/i', $tag)) {
        return false;
    }
    
    // Filter out comment counts
    if (preg_match('/^\d+\.?\d*[KM]?\s+comments$/i', $tag)) {
        return false;
    }
    
    // Filter out Facebook's internal technical abbreviations
    if (in_array(strtolower($tag), ['aria', 'href', 'img', 'src', 'alt', 'div', 'span', 'rel', 'btn', 'svg', 'url'])) {
        return false;
    }
    
    // Filter out common technical words
    if (in_array(strtolower($tag), ['null', 'undefined', 'nan', 'true', 'false', 'function', 'return', 'const', 'var'])) {
        return false;
    }
    
    // Filter out single letters (not meaningful as tags)
    if (preg_match('/^[a-z]$/i', $tag)) {
        return false;
    }
    
    // Filter out special Facebook markers
    if (in_array(strtolower($tag), ['by', 'on', 'in', 'at', 'from', 'facebook watch'])) {
        return false;
    }
    
    // Must contain at least one letter
    if (!preg_match('/[A-Za-z\x{00C0}-\x{00FF}]/u', $tag)) {
        return false;
    }
    
    // Must not be mostly special characters
    $specialCharCount = strlen(preg_replace('/[A-Za-z0-9\x{00C0}-\x{00FF}]/u', '', $tag));
    if ($specialCharCount > (strlen($tag) / 2)) {
        return false;
    }
    
    // Must not be all lowercase single letters or all uppercase single letters with numbers
    // (these are often variable names or abbreviations, not tags)
    if (preg_match('/^[a-z]$/', $tag) || preg_match('/^[A-Z][0-9]+$/', $tag)) {
        return false;
    }
    
    // Must not be short technical abbreviations often used in code
    $technicalAbbr = ['id', 'src', 'alt', 'div', 'img', 'url', 'href', 'rel', 'svg', 'var', 'css',
                     'js', 'dom', 'xml', 'api', 'btn', 'uid', 'idx', 'nav', 'src', 'cls', 'usr',
                     'tmp', 'fmt', 'str', 'num', 'obj', 'fn', 'arg', 'fs', 'sys', 'pkg', 'lib',
                     'cmd', 'dir', 'min', 'max', 'buf', 'ptr', 'mod', 'cfg', 'err', 'log', 'doc'];
    if (in_array(strtolower($tag), $technicalAbbr)) {
        return false;
    }
    
    return true;
}

/**
 * Check if a word looks like a proper noun or topic subject
 * These are more likely to be relevant tags
 * 
 * @param string $word The word to check
 * @return bool True if it looks like a proper noun/subject
 */
function isProperNounOrSubject($word) {
    // Skip common words that aren't likely to be tags
    $commonWords = [
        'the', 'and', 'that', 'have', 'for', 'not', 'this', 'with', 'you', 'but',
        'his', 'her', 'they', 'will', 'from', 'when', 'what', 'make', 'can', 'all',
        'get', 'just', 'been', 'like', 'into', 'time', 'than', 'some', 'very', 'now',
        'about', 'after', 'other', 'only', 'then', 'first', 'also', 'new', 'because',
        'day', 'more', 'these', 'want', 'look', 'thing', 'could', 'use', 'find', 'out'
    ];
    
    if (in_array(strtolower($word), $commonWords)) {
        return false;
    }
    
    // Words that start with capital letters are likely proper nouns
    if (preg_match('/^[A-Z\x{00C0}-\x{00FF}]/', $word)) {
        return true;
    }
    
    // Words with mixed case might be product names or special terms
    if (preg_match('/[a-z][A-Z]/', $word)) {
        return true;
    }
    
    // Words longer than 5 characters might be meaningful subjects
    if (strlen($word) > 5) {
        return true;
    }
    
    return false;
}

/**
 * Check if text appears to be natural language rather than code/ID
 * 
 * @param string $text The text to check
 * @return bool True if it looks like natural text
 */
function isNaturalLanguageText($text) {
    // Text should have spaces for natural language
    if (strpos($text, ' ') === false) {
        return false;
    }
    
    // Text shouldn't be mostly special characters or numbers
    $alphaCount = strlen(preg_replace('/[^A-Za-z\x{00C0}-\x{00FF}]/u', '', $text));
    if ($alphaCount < (strlen($text) / 3)) {
        return false;
    }
    
    // Text shouldn't have too many special characters
    $specialCharCount = strlen(preg_replace('/[A-Za-z0-9\s\x{00C0}-\x{00FF}]/u', '', $text));
    if ($specialCharCount > (strlen($text) / 5)) {
        return false;
    }
    
    return true;
}

/**
 * Normalize and validate Facebook URLs
 * This helps prepare URLs for metadata extraction
 *
 * @param string $url Raw Facebook URL input
 * @param bool $keepParams Whether to keep query parameters or clean them
 * @return string Normalized URL ready for processing
 */
function normalizeFacebookUrl($url, $keepParams = false) {
    // Clean the URL of any whitespace or trailing characters
    $url = trim($url);
    
    // Handle URLs without protocol
    if (strpos($url, 'http') !== 0) {
        if (preg_match('/^((?:www\.|m\.)?facebook\.com|fb\.watch)/', $url)) {
            $url = 'https://' . $url;
        }
    }
    
    // Decode URL if it's encoded
    if (strpos($url, '%') !== false) {
        $url = urldecode($url);
    }
    
    if (!$keepParams) {
        // Remove tracking parameters (common in shared links) that might interfere with extraction
        $trackingParams = [
            'fbclid', '__tn__', '__cft__', '__xts__', '_ft_', 'comment_id', 'comment_tracking',
            'hc_location', 'hc_ref', 'ftentidentifier', 'fref', 'notif_id', 'notif_t', 
            'ref', 'ref_type', '_rdr', 'refsrc', 'comment_tracking', 'referrer', 'redirect_uri',
            'entry_point', 'paipv'
        ];
        
        $urlParts = parse_url($url);
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $params);
            
            // Remove tracking parameters
            foreach ($trackingParams as $param) {
                if (isset($params[$param])) {
                    unset($params[$param]);
                }
            }
            
            // Rebuild the URL
            $newQuery = http_build_query($params);
            $url = $urlParts['scheme'] . '://' . $urlParts['host'];
            if (isset($urlParts['path'])) {
                $url .= $urlParts['path'];
            }
            if (!empty($newQuery)) {
                $url .= '?' . $newQuery;
            }
            if (isset($urlParts['fragment'])) {
                $url .= '#' . $urlParts['fragment'];
            }
        }
    }
    
    // Fix double ? in URL
    $url = str_replace('??', '?', $url);
    
    // Clean trailing special chars like ?, &, #
    $url = rtrim($url, '?&#');
    
    return $url;
}

/**
 * Comprehensive function to extract all metadata from a Facebook video
 * This combines multiple methods to maximize reliable data extraction
 * 
 * @param string $videoId Facebook video ID
 * @param string $videoUrl Full URL to the video
 * @return array Array containing [title, description, author, publish date, thumbnail, tags]
 */

/**
 * Get metadata from Instagram post using the post ID
 * 
 * @param string $postId Instagram post ID/shortcode
 * @param string $postUrl Original Instagram post URL for display
 * @return array An array containing [title, description, author, publishDate, thumbnail, tags]
 */
function getPostMetadataFromInstagram($postId, $postUrl = '') {
    // Initialize return values
    $postTitle = "Instagram Post: " . $postId;
    $postDescription = ""; // Will always remain empty for Instagram posts
    $postAuthor = "";
    $postPublishDate = "";
    $postThumbnail = "";
    $postTags = []; // Will always remain empty for Instagram posts
    
    // Always log for this feature until fixed
    $isDebugMode = true; // Forcing debug for troubleshooting
    
    error_log("Instagram Extractor Debug - Processing post ID: " . $postId);
    
    // Process and clean the input URL if provided
    if (!empty($postUrl)) {
        // Keep original URL for display
        $originalUrl = $postUrl;
        
        // For debug and troubleshooting
        error_log("Instagram Extractor Debug - Original URL: " . $originalUrl);
    }
    
    // If no post URL is provided, construct one
    if (empty($postUrl)) {
        $postUrl = "https://www.instagram.com/p/{$postId}/";
    }
    
    error_log("Instagram Extractor Debug - Fetching URL: " . $postUrl);
    
    // === ADVANCED METHOD - Instagram GRAPH URL ===
    // We'll try first with a direct query to a specific API-like URL that might return JSON data
    $graphUrl = "https://www.instagram.com/p/{$postId}/?__a=1&__d=dis";
    
    // Initialize cURL session with enhanced headers and settings
    $ch = curl_init();
    
    // Set a variety of headers to look more like a genuine browser request
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br',
        'Referer: https://www.instagram.com/',
        'sec-ch-ua: "Google Chrome";v="113", "Chromium";v="113", "Not-A.Brand";v="24"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'sec-fetch-dest: document',
        'sec-fetch-mode: navigate',
        'sec-fetch-site: same-origin',
        'sec-fetch-user: ?1',
        'upgrade-insecure-requests: 1',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36',
        'viewport-width: 1920'
    ];
    
    // Set cURL options for the JSON API endpoint
    curl_setopt($ch, CURLOPT_URL, $graphUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, ''); // Accept any encoding
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/instagram_cookies.txt'); // Store cookies
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/instagram_cookies.txt'); // Use cookies
    
    // Execute cURL session and get the JSON content
    $jsonResponse = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log("Instagram Extractor Debug - cURL Error (JSON attempt): " . curl_error($ch));
    } else {
        // Try to decode the JSON response
        $jsonData = json_decode($jsonResponse, true);
        if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
            error_log("Instagram Extractor Debug - Successfully retrieved JSON data");
            
            // JSON data processing would be here
            // This is a complex structure and we'd need to parse it based on Instagram's current format
            // For now, we'll try to extract the image URL from this
            
            // Example parsing (structure may change, this is a common path)
            if (isset($jsonData['items']) && is_array($jsonData['items']) && count($jsonData['items']) > 0) {
                $post = $jsonData['items'][0];
                
                // Extract image URL
                if (isset($post['image_versions2']['candidates']) && 
                    is_array($post['image_versions2']['candidates']) && 
                    count($post['image_versions2']['candidates']) > 0) {
                    $postThumbnail = $post['image_versions2']['candidates'][0]['url'];
                    error_log("Instagram Extractor Debug - Found image in JSON: " . $postThumbnail);
                }
                
                // We're skipping description extraction as per requirements
                // Commenting out to maintain code structure but not extracting description
                /*if (isset($post['caption']['text'])) {
                    $postDescription = $post['caption']['text'];
                }*/
                
                // Extract author/username
                if (isset($post['user']['username'])) {
                    $postAuthor = $post['user']['username'];
                }
            }
        } else {
            error_log("Instagram Extractor Debug - Failed to parse JSON: " . json_last_error_msg());
        }
    }
    
    // Fallback to traditional HTML method if we didn't get an image from JSON
    if (empty($postThumbnail)) {
        error_log("Instagram Extractor Debug - Falling back to HTML method");
        
        // Reset cURL session
        curl_close($ch);
        $ch = curl_init();
        
        // Set cURL options for HTML page
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/instagram_cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/instagram_cookies.txt');
        
        // Execute cURL session and get the HTML content
        $html = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            error_log("Instagram Extractor Debug - cURL Error (HTML attempt): " . curl_error($ch));
        } else {
            // Get HTTP status code
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            error_log("Instagram Extractor Debug - HTTP Status Code: " . $httpCode);
            
            // If we got HTML content, extract metadata
            if (!empty($html)) {
                // Log HTML size for debugging
                error_log("Instagram Extractor Debug - HTML Response Size: " . strlen($html) . " bytes");
                
                // Extract title from og:title meta tag
                if (preg_match('/<meta property="og:title" content="([^"]+)"/i', $html, $titleMatches)) {
                    $postTitle = html_entity_decode(trim($titleMatches[1]), ENT_QUOTES, 'UTF-8');
                    error_log("Instagram Extractor Debug - Found Title: " . $postTitle);
                }
                
                // We're skipping description extraction for Instagram posts as per requirements
                // But we'll still log if it exists for debugging purposes
                if (preg_match('/<meta property="og:description" content="([^"]+)"/i', $html, $descMatches)) {
                    $tempDescription = html_entity_decode(trim($descMatches[1]), ENT_QUOTES, 'UTF-8');
                    error_log("Instagram Extractor Debug - Found Description (not used): " . substr($tempDescription, 0, 100) . "...");
                    // Not setting $postDescription as we want it to remain empty
                }
                
                // Extract author from meta tags
                if (preg_match('/<meta property="og:title" content="([^"]+) on Instagram"/i', $html, $authorMatches)) {
                    $postAuthor = html_entity_decode(trim($authorMatches[1]), ENT_QUOTES, 'UTF-8');
                    error_log("Instagram Extractor Debug - Found Author: " . $postAuthor);
                }
                
                // Try different methods to extract image URLs 
                
                // Method 1: og:image meta tag
                if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $imgMatches)) {
                    $postThumbnail = trim($imgMatches[1]);
                    error_log("Instagram Extractor Debug - Found Thumbnail (og:image): " . $postThumbnail);
                } 
                // Method 2: og:image:secure_url meta tag
                elseif (preg_match('/<meta property="og:image:secure_url" content="([^"]+)"/i', $html, $imgMatches)) {
                    $postThumbnail = trim($imgMatches[1]);
                    error_log("Instagram Extractor Debug - Found Thumbnail (og:image:secure_url): " . $postThumbnail);
                } 
                // Method 3: JSON display_url in page
                elseif (preg_match('/"display_url":"([^"]+)"/i', $html, $imgMatches)) {
                    $postThumbnail = str_replace('\\u0026', '&', $imgMatches[1]);
                    error_log("Instagram Extractor Debug - Found Thumbnail (display_url): " . $postThumbnail);
                }
                // Method 4: Look for direct image links
                elseif (preg_match('/https:\/\/[^"\']+\.cdninstagram\.com\/[^"\']+/i', $html, $imgMatches)) {
                    $postThumbnail = $imgMatches[0];
                    error_log("Instagram Extractor Debug - Found Direct Image URL: " . $postThumbnail);
                }
                // Method 5: Look for .jpg URLs
                elseif (preg_match('/https:\/\/[^"\']+\.jpg[^"\']*/', $html, $imgMatches)) {
                    $postThumbnail = $imgMatches[0];
                    error_log("Instagram Extractor Debug - Found JPG URL: " . $postThumbnail);
                }
                // Method 6: Try using scontent keyword that often appears in IG URLs
                elseif (preg_match('/https:\/\/scontent[^"\']+/i', $html, $imgMatches)) {
                    $postThumbnail = $imgMatches[0];
                    if (strpos($postThumbnail, '"') !== false) {
                        $postThumbnail = substr($postThumbnail, 0, strpos($postThumbnail, '"'));
                    }
                    error_log("Instagram Extractor Debug - Found Scontent URL: " . $postThumbnail);
                }
                // Fallback method - hard coded for development
                else {
                    error_log("Instagram Extractor Debug - No image found with any method");
                    // Generate a fallback thumbnail URL based on the post ID
                    // No hardcoded fallback URL, we'll use a platform-specific fallback icon instead
                    $postThumbnail = "./vidextract-tab-icon.webp";
                }
                
                // Extract publish date if available
                if (preg_match('/<meta property="article:published_time" content="([^"]+)"/i', $html, $dateMatches)) {
                    $postPublishDate = trim($dateMatches[1]);
                    error_log("Instagram Extractor Debug - Found Publish Date: " . $postPublishDate);
                }
                
                // We're skipping tag extraction for Instagram posts as per requirements
                // Tags will remain as empty array
            } else {
                error_log("Instagram Extractor Debug - Empty HTML response");
            }
        }
    }
    
    // Close cURL session
    curl_close($ch);
    
    // Always set a title even if we couldn't extract one
    if (empty($postTitle) || $postTitle === "Instagram Post: " . $postId) {
        if (!empty($postAuthor)) {
            $postTitle = "Instagram Post by " . $postAuthor;
            error_log("Instagram Extractor Debug - Set fallback title with author: " . $postTitle);
        }
    }
    
    // Final debug log of what we're returning
    error_log("Instagram Extractor Debug - Final Thumbnail URL: " . $postThumbnail);
    error_log("Instagram Extractor Debug - Final Title: " . $postTitle);
    
    // Return all metadata
    return [$postTitle, $postDescription, $postAuthor, $postPublishDate, $postThumbnail, $postTags];
}

/**
 * Extract metadata from a Twitter/X video
 * 
 * @param string $tweetId The Twitter/X tweet ID containing the video
 * @param string $tweetUrl Optional. The original URL from which the ID was extracted
 * @return array An array containing [title, description, author, publishDate, thumbnail, tags]
 */
function getVideoMetadataFromTwitter($tweetId, $tweetUrl = '') {
    // Initialize return values
    $videoTitle = "Twitter Video: " . $tweetId;
    $videoDescription = "";
    $videoAuthor = "";
    $videoPublishDate = "";
    $videoThumbnail = "";
    $videoTags = [];
    
    // Log all Twitter calls for debugging
    if (isset($_GET['debug']) || isset($_POST['debug'])) {
        error_log("Twitter/X extraction started for ID: " . $tweetId);
        if (!empty($tweetUrl)) {
            error_log("Twitter/X URL provided: " . $tweetUrl);
        }
    }
    
    // If we have a valid Twitter/X URL, try the fxtwitter.com API first (most reliable method)
    if (!empty($tweetUrl) && (strpos($tweetUrl, 'twitter.com') !== false || strpos($tweetUrl, 'x.com') !== false)) {
        // Try to get metadata via fxtwitter API
        $fxMetadata = getTwitterMetadataViaFxTwitter($tweetUrl);
        
        if ($fxMetadata !== false) {
            // We got valid data from fxtwitter API, use it
            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                error_log("Successfully got Twitter/X data via fxtwitter API");
            }
            
            $videoTitle = !empty($fxMetadata['title']) ? $fxMetadata['title'] : $videoTitle;
            $videoDescription = !empty($fxMetadata['description']) ? $fxMetadata['description'] : $videoDescription;
            $videoAuthor = !empty($fxMetadata['author']) ? $fxMetadata['author'] : $videoAuthor;
            $videoPublishDate = !empty($fxMetadata['publish_date']) ? $fxMetadata['publish_date'] : $videoPublishDate;
            $videoThumbnail = !empty($fxMetadata['thumbnail']) ? $fxMetadata['thumbnail'] : $videoThumbnail;
            $videoTags = !empty($fxMetadata['tags']) ? $fxMetadata['tags'] : $videoTags;
            
            // Return early with this reliable data
            return [$videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags];
        } else {
            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                error_log("Failed to get Twitter/X data via fxtwitter API, falling back to alternative methods");
            }
        }
    }
    
    // Check if this is a special URL ID format
    $isSpecialUrl = false;
    
    // Handle direct URL extraction (prefixed with 'url_')
    if (strpos($tweetId, 'url_') === 0) {
        $isSpecialUrl = true;
        $fullUrl = urldecode(substr($tweetId, 4)); // Remove 'url_' prefix and decode the URL
        
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Processing direct Twitter URL: " . $fullUrl);
        }
        
        // Try fxtwitter API again with the full URL if we haven't tried it yet
        if (empty($tweetUrl) && (strpos($fullUrl, 'twitter.com') !== false || strpos($fullUrl, 'x.com') !== false)) {
            $fxMetadata = getTwitterMetadataViaFxTwitter($fullUrl);
            
            if ($fxMetadata !== false) {
                // We got valid data from fxtwitter API, use it
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Successfully got Twitter/X data via fxtwitter API from URL ID");
                }
                
                $videoTitle = !empty($fxMetadata['title']) ? $fxMetadata['title'] : $videoTitle;
                $videoDescription = !empty($fxMetadata['description']) ? $fxMetadata['description'] : $videoDescription;
                $videoAuthor = !empty($fxMetadata['author']) ? $fxMetadata['author'] : $videoAuthor;
                $videoPublishDate = !empty($fxMetadata['publish_date']) ? $fxMetadata['publish_date'] : $videoPublishDate;
                $videoThumbnail = !empty($fxMetadata['thumbnail']) ? $fxMetadata['thumbnail'] : $videoThumbnail;
                $videoTags = !empty($fxMetadata['tags']) ? $fxMetadata['tags'] : $videoTags;
                
                // Return early with this reliable data
                return [$videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags];
            }
        }
        
        // Attempt to load the page and extract OG metadata
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // Updated modern User-Agent for better compatibility
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36");
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Increased timeout for slower connections
        // Set additional headers to mimic a browser request better
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'sec-ch-ua: "Google Chrome";v="124", "Chromium";v="124", "Not-A.Brand";v="99"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Enhanced debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Twitter/X URL HTTP Response Code: " . $httpCode);
            error_log("URL Requested: " . $fullUrl);
            
            // Save detailed debug information to a file
            $debugInfo = [
                'timestamp' => date('Y-m-d H:i:s'),
                'url' => $fullUrl,
                'http_code' => $httpCode,
                'curl_error' => curl_error($ch),
                'response_length' => strlen($response),
                'response_sample' => substr($response, 0, 1000) // First 1000 chars
            ];
            
            file_put_contents('debug_output.html', 
                "<!-- Twitter X Debug Info -->\n" .
                "<h2>Twitter/X Debug Information</h2>\n" .
                "<pre>" . print_r($debugInfo, true) . "</pre>\n" .
                "<h3>Response Headers & Sample</h3>\n" .
                "<div style='white-space: pre-wrap; word-break: break-all;'>" . 
                htmlspecialchars(substr($response, 0, 5000)) . 
                "</div>\n", 
                FILE_APPEND);
        }
        
        if ($httpCode === 200 && !empty($response)) {
            // Extract Open Graph metadata
            $ogTitle = "";
            $ogDescription = "";
            $ogImage = "";
            $ogAuthor = "";
            
            // Title extraction - enhanced patterns to match X/Twitter's current HTML structure
            if (preg_match('/<meta\s+property="og:title"\s+content="([^"]+)"/i', $response, $titleMatch)) {
                $ogTitle = html_entity_decode($titleMatch[1], ENT_QUOTES, 'UTF-8');
            } else if (preg_match('/<meta\s+name="twitter:title"\s+content="([^"]+)"/i', $response, $titleMatch)) {
                $ogTitle = html_entity_decode($titleMatch[1], ENT_QUOTES, 'UTF-8');
            } else if (preg_match('/<title>([^<]+)<\/title>/i', $response, $titleMatch)) {
                $ogTitle = html_entity_decode($titleMatch[1], ENT_QUOTES, 'UTF-8');
                // Remove " / Twitter" or " / X" from the end if present
                $ogTitle = preg_replace('/ \/ (Twitter|X)$/', '', $ogTitle);
            }
            
            // Description extraction - enhanced patterns
            if (preg_match('/<meta\s+property="og:description"\s+content="([^"]+)"/i', $response, $descMatch)) {
                $ogDescription = html_entity_decode($descMatch[1], ENT_QUOTES, 'UTF-8');
            } else if (preg_match('/<meta\s+name="twitter:description"\s+content="([^"]+)"/i', $response, $descMatch)) {
                $ogDescription = html_entity_decode($descMatch[1], ENT_QUOTES, 'UTF-8');
            } else if (preg_match('/<meta\s+name="description"\s+content="([^"]+)"/i', $response, $descMatch)) {
                $ogDescription = html_entity_decode($descMatch[1], ENT_QUOTES, 'UTF-8');
            }
            
            // Image extraction - enhanced patterns for X/Twitter's image formats
            if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $response, $imageMatch)) {
                $ogImage = $imageMatch[1];
                // For debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Found Twitter/X og:image: " . $ogImage);
                }
            } else if (preg_match('/<meta\s+name="twitter:image"\s+content="([^"]+)"/i', $response, $imageMatch)) {
                $ogImage = $imageMatch[1];
                // For debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Found Twitter/X twitter:image: " . $ogImage);
                }
            } else if (preg_match('/<meta\s+property="twitter:image"\s+content="([^"]+)"/i', $response, $imageMatch)) {
                $ogImage = $imageMatch[1];
                // For debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Found Twitter/X twitter:image property: " . $ogImage);
                }
            }
            
            // If we still don't have an image, try more aggressive image extraction
            if (empty($ogImage)) {
                // Enhanced image extraction for Twitter - look for all possible image URLs in the HTML
                // Patterns for Twitter/X media URLs
                $imagePatterns = [
                    '/https:\/\/pbs\.twimg\.com\/media\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                    '/https:\/\/pbs\.twimg\.com\/tweet_video_thumb\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                    '/https:\/\/pbs\.twimg\.com\/ext_tw_video_thumb\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                    '/https:\/\/pbs\.twimg\.com\/profile_images\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                    '/https:\/\/pbs\.twimg\.com\/amplify_video_thumb\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                    '/https:\/\/pbs\.twimg\.com\/card_img\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                    // Fallback to any Twitter image URL
                    '/https:\/\/pbs\.twimg\.com\/[^\/]+\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i'
                ];
                
                foreach ($imagePatterns as $pattern) {
                    if (preg_match($pattern, $response, $imageMatch)) {
                        $ogImage = $imageMatch[0];
                        // Add quality parameters for better resolution
                        if (strpos($ogImage, '?') === false) {
                            $ogImage .= '?format=jpg&name=large';
                        } else if (strpos($ogImage, 'name=') === false) {
                            $ogImage .= '&name=large';
                        }
                        
                        if (isset($_GET['debug']) || isset($_POST['debug'])) {
                            error_log("Found Twitter image with pattern " . $pattern . ": " . $ogImage);
                        }
                        
                        break; // Found a match, stop searching
                    }
                }
                
                // If we still don't have an image, try to find it in <img> tags
                if (empty($ogImage)) {
                    if (preg_match_all('/<img[^>]*src=[\"\']([^\"\']*pbs\.twimg\.com[^\"\']+)[\"\'][^>]*>/i', $response, $imgMatches)) {
                        if (!empty($imgMatches[1])) {
                            $ogImage = $imgMatches[1][0];
                            // Add quality parameter if not present
                            if (strpos($ogImage, 'name=') === false) {
                                $ogImage .= (strpos($ogImage, '?') !== false) ? '&name=large' : '?name=large';
                            }
                            
                            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                                error_log("Found Twitter image from <img> tag: " . $ogImage);
                            }
                        }
                    }
                }
            }
            
            // Author extraction - from URL or page content
            if (preg_match('/(twitter|x)\.com\/([^\/]+)\/status/i', $fullUrl, $authorMatch)) {
                $ogAuthor = '@' . $authorMatch[2];
            } else if (preg_match('/<meta\s+name="twitter:creator"\s+content="([^"]+)"/i', $response, $creatorMatch)) {
                $ogAuthor = $creatorMatch[1];
            } else if (preg_match('/<meta\s+property="twitter:creator"\s+content="([^"]+)"/i', $response, $creatorMatch)) {
                $ogAuthor = $creatorMatch[1];
            }
            
            // Enhanced image extraction from HTML patterns if OG tags failed
            if (empty($ogImage)) {
                // Look for image URLs in the page - multiple potential patterns
                $imagePatterns = [
                    '/https:\/\/pbs\.twimg\.com\/media\/([^\?\"\'\s]+)/i',         // Standard media images
                    '/https:\/\/pbs\.twimg\.com\/ext_tw_video_thumb\/([^\?\"\'\s]+)/i', // Video thumbnails
                    '/https:\/\/pbs\.twimg\.com\/tweet_video_thumb\/([^\?\"\'\s]+)/i',  // Tweet video thumbnails
                    '/https:\/\/pbs\.twimg\.com\/profile_images\/([^\?\"\'\s]+)/i',     // Profile images
                    '/https:\/\/pbs\.twimg\.com\/amplify_video_thumb\/([^\?\"\'\s]+)/i' // Amplify video thumbnails
                ];
                
                foreach ($imagePatterns as $pattern) {
                    if (preg_match($pattern, $response, $mediaMatch)) {
                        // Get the base part of the URL that matched
                        $matchedUrl = $mediaMatch[0];
                        
                        // Extract clean URL up to the file extension
                        $cleanUrl = preg_replace('/(\.[a-zA-Z0-9]+)[\?\"\'\s].*$/', '$1', $matchedUrl);
                        
                        // For media URLs, append format parameters for best quality
                        if (strpos($pattern, 'media') !== false) {
                            $ogImage = $cleanUrl . '?format=jpg&name=large';
                        } else {
                            $ogImage = $cleanUrl;
                        }
                        
                        // For debugging
                        if (isset($_GET['debug']) || isset($_POST['debug'])) {
                            error_log("Found Twitter/X image via regex: " . $ogImage);
                        }
                        
                        break; // Exit loop once we find an image
                    }
                }
            }
            
            // If we have meaningful data, use it
            if (!empty($ogTitle) || !empty($ogImage)) {
                // Set video metadata from OG tags
                if (!empty($ogTitle)) $videoTitle = $ogTitle;
                if (!empty($ogDescription)) $videoDescription = $ogDescription;
                if (!empty($ogAuthor)) $videoAuthor = $ogAuthor;
                if (!empty($ogImage)) $videoThumbnail = $ogImage;
                
                // Add tags from content if possible
                preg_match_all('/hashtag\/([^"&\']+)/i', $response, $hashtagMatches);
                if (!empty($hashtagMatches[1])) {
                    foreach ($hashtagMatches[1] as $hashtag) {
                        $videoTags[] = $hashtag;
                    }
                }
                
                // Return with our extracted data
                return [$videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags];
            }
        }
        
        // If extraction failed, set URL as the title
        $videoTitle = "Twitter Post";
        $videoDescription = "Twitter post at: " . $fullUrl;
        
        // Extract user from URL if possible
        if (preg_match('/(twitter|x)\.com\/([^\/]+)\/status/i', $fullUrl, $userMatches)) {
            $videoAuthor = '@' . $userMatches[2];
        }
        
        // Try to extract the image directly from the URL using an API call
        $videoThumbnail = "";
        
        // Use the URL to try to get the image via direct HTML scraping
        if (!empty($fullUrl)) {
            // Initialize cURL for a simple request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            // Execute the request
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Debug info
            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                error_log("URL direct access HTTP code: " . $httpCode);
            }
            
            // If successful response, try to extract the image
            if ($httpCode === 200 && !empty($html)) {
                // Try to extract the image from meta tags first
                if (preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\'](.*?)["\']/si', $html, $imageMatch)) {
                    $videoThumbnail = $imageMatch[1];
                    
                    if (isset($_GET['debug']) || isset($_POST['debug'])) {
                        error_log("Found Twitter image from og:image in direct URL: " . $videoThumbnail);
                    }
                } else if (preg_match('/<meta[^>]*name=["\']twitter:image["\'][^>]*content=["\'](.*?)["\']/si', $html, $imageMatch)) {
                    $videoThumbnail = $imageMatch[1];
                    
                    if (isset($_GET['debug']) || isset($_POST['debug'])) {
                        error_log("Found Twitter image from twitter:image in direct URL: " . $videoThumbnail);
                    }
                }
                
                // If still empty, try more aggressive patterns
                if (empty($videoThumbnail)) {
                    $imagePatterns = [
                        '/https:\/\/pbs\.twimg\.com\/media\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                        '/https:\/\/pbs\.twimg\.com\/tweet_video_thumb\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                        '/https:\/\/pbs\.twimg\.com\/ext_tw_video_thumb\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                        '/https:\/\/pbs\.twimg\.com\/profile_images\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                        '/https:\/\/pbs\.twimg\.com\/amplify_video_thumb\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                        '/https:\/\/pbs\.twimg\.com\/card_img\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                        // Fallback to any Twitter image URL
                        '/https:\/\/pbs\.twimg\.com\/[^\/]+\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i'
                    ];
                    
                    foreach ($imagePatterns as $pattern) {
                        if (preg_match($pattern, $html, $imageMatch)) {
                            $videoThumbnail = $imageMatch[0];
                            // Add quality parameters for better resolution
                            if (strpos($videoThumbnail, '?') === false) {
                                $videoThumbnail .= '?format=jpg&name=large';
                            } else if (strpos($videoThumbnail, 'name=') === false) {
                                $videoThumbnail .= '&name=large';
                            }
                            
                            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                                error_log("Found Twitter image with pattern from direct URL: " . $videoThumbnail);
                            }
                            
                            break; // Found a match, stop searching
                        }
                    }
                }
                
                // If we still don't have an image, try to find it in <img> tags
                if (empty($videoThumbnail)) {
                    if (preg_match_all('/<img[^>]*src=[\"\']([^\"\']*pbs\.twimg\.com[^\"\']+)[\"\'][^>]*>/i', $html, $imgMatches)) {
                        if (!empty($imgMatches[1])) {
                            $videoThumbnail = $imgMatches[1][0];
                            // Add quality parameter if not present
                            if (strpos($videoThumbnail, 'name=') === false) {
                                $videoThumbnail .= (strpos($videoThumbnail, '?') !== false) ? '&name=large' : '?name=large';
                            }
                            
                            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                                error_log("Found Twitter image from <img> tag in direct URL: " . $videoThumbnail);
                            }
                        }
                    }
                }
            }
        }
        
        // Add basic tags
        $videoTags = ['twitter', 'post'];
        
        // Return with our fallback data
        return [$videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags];
    }
    
    // Handle short URLs (prefixed with 'short_')
    if (strpos($tweetId, 'short_') === 0) {
        $isSpecialUrl = true;
        $shortCode = substr($tweetId, 6); // Remove 'short_' prefix
        
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Processing Twitter short URL with code: " . $shortCode);
        }
        
        // Try to expand the short URL to get the full tweet URL
        $expandedUrl = expandTwitterShortUrl($shortCode);
        if ($expandedUrl !== false) {
            // Extract the tweet ID from the expanded URL
            $expandedTweetId = extractTwitterVideoId($expandedUrl);
            if ($expandedTweetId !== false && strpos($expandedTweetId, 'short_') !== 0) {
                $tweetId = $expandedTweetId;
                $tweetUrl = $expandedUrl;
                $isSpecialUrl = false;
                
                // For debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Expanded Twitter short URL to: " . $expandedUrl);
                    error_log("Extracted expanded Tweet ID: " . $tweetId);
                }
            }
        }
    }
    // Handle direct pic URLs (prefixed with 'pic_')
    else if (strpos($tweetId, 'pic_') === 0) {
        $isSpecialUrl = true;
        $picCode = substr($tweetId, 4); // Remove 'pic_' prefix
        
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Processing Twitter pic URL with code: " . $picCode);
        }
        
        // For direct pic.twitter.com links, we can use a URL like 
        // https://nitter.net/pic/orig/media/[MediaCode].[ext]
        // We'll use some alternate methods to display the image
        
        // Use Twitter X logo directly from Wikipedia (reliable source)
        $logoUrl = "https://upload.wikimedia.org/wikipedia/commons/5/57/X_logo_2023_%28white%29.png";
        
        // Set a direct thumbnail URL that's reliable
        $videoThumbnail = $logoUrl;
        
        // Create a title with the Twitter pic code
        $videoTitle = "Twitter Image: " . $picCode;
        
        // Create description that explains the image
        $videoDescription = "Twitter image with ID: " . $picCode;
        $videoDescription .= "\n\nOriginal URL: pic.twitter.com/" . $picCode;
        $videoDescription .= "\n\nThis appears to be a direct Twitter image link. Due to Twitter API restrictions, we can't access the direct image.";
        
        // Set a default author based on the URL if available
        if (!empty($tweetUrl) && preg_match('/twitter\.com\/([^\/]+)\//', $tweetUrl, $userMatches)) {
            $videoAuthor = '@' . $userMatches[1];
        }
        
        // Extract any embedded pic.twitter.com code as tags
        $picTags = ['twitter', 'image', $picCode];
        if (!empty($picTags)) {
            $videoTags = array_merge($videoTags, $picTags);
        }
        
        // Return with our best data
        return [$videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags];
    }
    
    // If no tweet URL is provided, construct one
    if (empty($tweetUrl)) {
        $tweetUrl = "https://twitter.com/i/status/{$tweetId}";
        
        // Try fxtwitter API with the constructed URL for direct ID input
        if (is_numeric($tweetId)) {
            $fxMetadata = getTwitterMetadataViaFxTwitter($tweetUrl);
            
            if ($fxMetadata !== false) {
                // We got valid data from fxtwitter API, use it
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Successfully got Twitter/X data via fxtwitter API from ID");
                }
                
                $videoTitle = !empty($fxMetadata['title']) ? $fxMetadata['title'] : $videoTitle;
                $videoDescription = !empty($fxMetadata['description']) ? $fxMetadata['description'] : $videoDescription;
                $videoAuthor = !empty($fxMetadata['author']) ? $fxMetadata['author'] : $videoAuthor;
                $videoPublishDate = !empty($fxMetadata['publish_date']) ? $fxMetadata['publish_date'] : $videoPublishDate;
                $videoThumbnail = !empty($fxMetadata['thumbnail']) ? $fxMetadata['thumbnail'] : $videoThumbnail;
                $videoTags = !empty($fxMetadata['tags']) ? $fxMetadata['tags'] : $videoTags;
                
                // Return early with this reliable data
                return [$videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags];
            }
        }
    }
    
    // ===== APPROACH 1: Use Twitter's public OEMBED API =====
    // This is more reliable for extracting tweet data without authentication
    // Try both Twitter and X domains for the oembed endpoint to maximize success
    $oembedUrls = [
        "https://publish.twitter.com/oembed?url={$tweetUrl}&dnt=true&omit_script=true",
        "https://publish.x.com/oembed?url={$tweetUrl}&dnt=true&omit_script=true"
    ];
    
    $jsonResponse = null;
    $httpCode = 0;
    
    // Try each URL until one works
    foreach ($oembedUrls as $oembedUrl) {
        // Initialize cURL session for Twitter/X's OEMBED API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oembedUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        
        // Set additional headers to mimic a browser request
        $headers = [
            'Accept: application/json',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Execute the cURL request
        $jsonResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Twitter/X OEmbed API HTTP Response Code: " . $httpCode . " for URL: " . $oembedUrl);
        }
        
        // If successful, break out of the loop
        if ($httpCode === 200 && !empty($jsonResponse)) {
            break;
        }
    }
    
    if ($jsonResponse && $httpCode === 200) {
        // Decode JSON response
        $oembedData = json_decode($jsonResponse, true);
        
        if ($oembedData) {
            // Extract author information
            if (isset($oembedData['author_name'])) {
                $videoAuthor = $oembedData['author_name'];
                
                // Add Twitter handle if available
                if (isset($oembedData['author_url'])) {
                    $handleMatch = [];
                    if (preg_match('/twitter\.com\/([^\/]+)$/i', $oembedData['author_url'], $handleMatch)) {
                        $videoAuthor .= ' (@' . $handleMatch[1] . ')';
                    }
                }
            }
            
            // Extract tweet HTML for content parsing
            if (isset($oembedData['html'])) {
                $tweetHtml = $oembedData['html'];
                
                // Extract tweet text/title from HTML
                // Remove HTML tags but keep the text content
                $strippedHtml = strip_tags($tweetHtml);
                if (!empty($strippedHtml)) {
                    // Clean up the text (remove excess whitespace)
                    $cleanText = preg_replace('/\s+/', ' ', $strippedHtml);
                    $cleanText = trim($cleanText);
                    
                    if (!empty($cleanText)) {
                        $videoTitle = $cleanText;
                        $videoDescription = $cleanText;
                        
                        // Limit title length to a reasonable size
                        if (strlen($videoTitle) > 100) {
                            $videoTitle = substr($videoTitle, 0, 97) . '...';
                        }
                    }
                }
                
                // Extract hashtags from HTML
                $hashtagMatches = [];
                if (preg_match_all('/#([a-zA-Z0-9_]+)/i', $tweetHtml, $hashtagMatches)) {
                    foreach ($hashtagMatches[1] as $tag) {
                        if (!in_array($tag, $videoTags)) {
                            $videoTags[] = $tag;
                        }
                    }
                }
            }
        }
    }
    
    // ===== APPROACH 2: Use direct image embedding from Twitter =====
    // Try to get a thumbnail image based on user profile or Twitter's logo
    if (empty($videoThumbnail)) {
        // Try to get the user handle from URL or author
        $userHandle = '';
        if (preg_match('/(twitter|x)\.com\/([^\/]+)\/status/i', $tweetUrl, $userMatches)) {
            $userHandle = $userMatches[2];
        } elseif (preg_match('/@([a-zA-Z0-9_]+)/i', $videoAuthor, $authorMatches)) {
            $userHandle = $authorMatches[1];
        }
        
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Twitter/X Extracted user handle: " . ($userHandle ? $userHandle : 'NONE'));
        }
        
        if (!empty($userHandle)) {
            // Try multiple avatar services to improve reliability
            $avatarServices = [
                "https://unavatar.io/x/{$userHandle}",
                "https://unavatar.io/twitter/{$userHandle}",
                "https://twitter.com/{$userHandle}/profile_image?size=original"
            ];
            
            $imageExists = false;
            
            foreach ($avatarServices as $serviceUrl) {
                // Test if this URL works
                $ch = curl_init($serviceUrl);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                // For debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Twitter/X Avatar service check: " . $serviceUrl . " - Status: " . $statusCode);
                }
                
                if ($statusCode === 200) {
                    $videoThumbnail = $serviceUrl;
                    $imageExists = true;
                    break;
                }
            }
            
            // If all avatar services failed, try an alternative approach: get the image directly from tweet
            if (!$imageExists && !empty($tweetId)) {
                // Try to fetch the tweet page to extract media
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $tweetUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $html = curl_exec($ch);
                curl_close($ch);
                
                if (!empty($html)) {
                    // Look for media in the HTML content
                    $imgPatterns = [
                        '/https:\/\/pbs\.twimg\.com\/media\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                        '/https:\/\/pbs\.twimg\.com\/tweet_video_thumb\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                        '/https:\/\/pbs\.twimg\.com\/card_img\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i',
                        '/https:\/\/pbs\.twimg\.com\/amplify_video_thumb\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i'
                    ];
                    
                    foreach ($imgPatterns as $pattern) {
                        if (preg_match($pattern, $html, $matches)) {
                            // For media URLs, append format parameters for best quality
                            if (strpos($matches[0], 'media') !== false) {
                                $videoThumbnail = $matches[0] . '?format=jpg&name=large';
                            } else {
                                $videoThumbnail = $matches[0];
                            }
                            
                            // For debugging
                            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                                error_log("Found Twitter/X image in tweet HTML: " . $videoThumbnail);
                            }
                            break;
                        }
                    }
                }
            }
            
            // If still no image found, use X logo
            if (empty($videoThumbnail)) {
                // Use Twitter X logo directly
                $videoThumbnail = "https://upload.wikimedia.org/wikipedia/commons/5/57/X_logo_2023_%28white%29.png";
            }
        } else {
            // Use Twitter X logo directly
            $videoThumbnail = "https://upload.wikimedia.org/wikipedia/commons/5/57/X_logo_2023_%28white%29.png";
        }
    }
    
    // ===== APPROACH 3: Direct HTML scraping as last resort =====
    // If we're still missing essential data, try a direct approach with the Twitter website
    if (empty($videoTitle) || empty($videoAuthor) || empty($videoThumbnail)) {
        // Try both twitter.com and x.com domains if needed
        $urlsToTry = [$tweetUrl];
        
        // If the URL contains twitter.com, also try with x.com and vice versa
        if (strpos($tweetUrl, 'twitter.com') !== false) {
            $xUrl = str_replace('twitter.com', 'x.com', $tweetUrl);
            $urlsToTry[] = $xUrl;
        } elseif (strpos($tweetUrl, 'x.com') !== false) {
            $twitterUrl = str_replace('x.com', 'twitter.com', $tweetUrl);
            $urlsToTry[] = $twitterUrl;
        }
        
        foreach ($urlsToTry as $urlToTry) {
            // Initialize cURL session for direct Twitter/X website
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlToTry);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_ENCODING, ''); // Accept all available encodings
            
            // Set headers to look like a browser request
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'sec-ch-ua: "Google Chrome";v="124", "Chromium";v="124", "Not-A.Brand";v="99"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1',
                'Upgrade-Insecure-Requests: 1'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            // Execute the cURL request
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // For debugging
            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                error_log("Twitter/X direct HTML approach - URL: " . $urlToTry . " - Status: " . $httpCode);
            }
            
            if ($html && $httpCode === 200) {
                // Try to get title from title tag if we still don't have it
                if (empty($videoTitle)) {
                    // First try with JSON-LD script which often contains more accurate data
                    if (preg_match('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $jsonMatches)) {
                        $jsonData = json_decode(trim($jsonMatches[1]), true);
                        if ($jsonData && isset($jsonData['headline'])) {
                            $videoTitle = $jsonData['headline'];
                        } elseif ($jsonData && isset($jsonData['name'])) {
                            $videoTitle = $jsonData['name'];
                        }
                    }
                    
                    // If still empty, try meta tags
                    if (empty($videoTitle)) {
                        if (preg_match('/<meta[^>]*property=["\']og:title["\'][^>]*content=["\'](.*?)["\']/si', $html, $metaTitleMatches)) {
                            $videoTitle = html_entity_decode(trim($metaTitleMatches[1]), ENT_QUOTES, 'UTF-8');
                        } elseif (preg_match('/<meta[^>]*name=["\']twitter:title["\'][^>]*content=["\'](.*?)["\']/si', $html, $metaTitleMatches)) {
                            $videoTitle = html_entity_decode(trim($metaTitleMatches[1]), ENT_QUOTES, 'UTF-8');
                        } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $titleMatches)) {
                            $videoTitle = html_entity_decode(trim($titleMatches[1]), ENT_QUOTES, 'UTF-8');
                            // Remove " / Twitter" or " / X" from the end if present
                            $videoTitle = preg_replace('/ \/ (Twitter|X)$/', '', $videoTitle);
                        }
                    }
                }
                
                // Try to get image if we still need it
                if (empty($videoThumbnail)) {
                    // First check for og:image or twitter:image
                    if (preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\'](.*?)["\']/si', $html, $imageMatches)) {
                        $videoThumbnail = $imageMatches[1];
                    } elseif (preg_match('/<meta[^>]*name=["\']twitter:image["\'][^>]*content=["\'](.*?)["\']/si', $html, $imageMatches)) {
                        $videoThumbnail = $imageMatches[1];
                    }
                    
                    // If still empty, try to find image URLs in the content
                    if (empty($videoThumbnail)) {
                        // Look for any Twitter media image URLs with different patterns
                        if (preg_match('/https:\/\/pbs\.twimg\.com\/media\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i', $html, $mediaMatches)) {
                            $videoThumbnail = $mediaMatches[0] . '?format=jpg&name=large';
                            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                                error_log("Found Twitter media image URL (pattern 1): " . $videoThumbnail);
                            }
                        } else if (preg_match('/https:\/\/pbs\.twimg\.com\/ext_tw_video_thumb\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i', $html, $videoMatches)) {
                            $videoThumbnail = $videoMatches[0] . '?format=jpg&name=large';
                            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                                error_log("Found Twitter video thumbnail URL: " . $videoThumbnail);
                            }
                        } else if (preg_match_all('/https:\/\/pbs\.twimg\.com\/[^\/]+\/([^\?\"\'\s]+\.(jpg|png|jpeg))/i', $html, $allMatches)) {
                            // If we find multiple matches, use the first one
                            if (!empty($allMatches[0])) {
                                $videoThumbnail = $allMatches[0][0] . '?format=jpg&name=large';
                                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                                    error_log("Found Twitter image URL (pattern 2): " . $videoThumbnail);
                                }
                            }
                        }
                        
                        // If still no image found, try to find any image in the tweet content
                        if (empty($videoThumbnail)) {
                            if (preg_match_all('/<img[^>]*src=[\"\']([^\"\']*pbs\.twimg\.com[^\"\']+)[\"\'][^>]*>/i', $html, $imgMatches)) {
                                if (!empty($imgMatches[1])) {
                                    $videoThumbnail = $imgMatches[1][0];
                                    // Add quality parameter if not present
                                    if (strpos($videoThumbnail, 'name=') === false) {
                                        $videoThumbnail .= (strpos($videoThumbnail, '?') !== false) ? '&name=large' : '?name=large';
                                    }
                                    if (isset($_GET['debug']) || isset($_POST['debug'])) {
                                        error_log("Found Twitter image from <img> tag: " . $videoThumbnail);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Try to extract username from URL if we don't have author yet
                if (empty($videoAuthor)) {
                    if (preg_match('/(twitter|x)\.com\/([^\/]+)\/status/i', $urlToTry, $userMatches)) {
                        $videoAuthor = '@' . $userMatches[2];
                    }
                    
                    // Also try json-ld data
                    if (empty($videoAuthor) && isset($jsonData) && isset($jsonData['author']['name'])) {
                        $videoAuthor = $jsonData['author']['name'];
                    }
                }
                
                // If we got all the data we need, break out of the loop
                if (!empty($videoTitle) && !empty($videoAuthor) && !empty($videoThumbnail)) {
                    break;
                }
            }
        }
    }
    
    // If thumbnail is empty, use a default Twitter X logo image
    if (empty($videoThumbnail)) {
        // Use Twitter X logo directly from Wikipedia (reliable source)
        $videoThumbnail = "https://upload.wikimedia.org/wikipedia/commons/5/57/X_logo_2023_%28white%29.png";
    }
    
    // Set default values if we couldn't extract them
    if (empty($videoTitle)) {
        $videoTitle = "Twitter/X Post: " . $tweetId;
    }
    
    if (empty($videoDescription)) {
        $videoDescription = "Twitter/X post content could not be extracted. View the original post for details.";
    }
    
    // Return all metadata
    return [$videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags];
}

/**
 * Expands a Twitter/X short URL to get the full URL
 * 
 * @param string $shortCode The short code from the t.co URL
 * @return string|false The expanded URL or false if it fails
 */
function expandTwitterShortUrl($shortCode) {
    $shortUrl = "https://t.co/" . $shortCode;
    
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $shortUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check for redirect (HTTP 301, 302)
    if ($httpCode == 301 || $httpCode == 302) {
        // Extract the redirect location
        if (preg_match('/^Location:\s*(.*)$/mi', $response, $matches)) {
            return trim($matches[1]);
        }
    }
    
    return false;
}

/**
 * Alternate method to fetch Twitter/X metadata via fxtwitter.com
 * This service provides a reliable JSON API for Twitter content
 * 
 * @param string $tweetUrl The Twitter post URL
 * @return array|false An array with metadata or false if failed
 */
function getTwitterMetadataViaFxTwitter($tweetUrl) {
    // Initialize the return array
    $metadata = [
        'title' => '',
        'description' => '',
        'author' => '',
        'author_username' => '',
        'publish_date' => '',
        'thumbnail' => '',
        'tags' => []
    ];
    
    // Clean the URL and extract the tweet ID
    $tweetId = '';
    if (preg_match('/(twitter|x)\.com\/[^\/]+\/status\/(\d+)/i', $tweetUrl, $matches)) {
        $tweetId = $matches[2];
    } else {
        return false; // Not a valid Twitter/X status URL
    }
    
    // Log for debugging
    if (isset($_GET['debug']) || isset($_POST['debug'])) {
        error_log("FxTwitter extraction for ID: " . $tweetId);
    }
    
    // Build the fxtwitter.com URL for easy API access
    $fxUrl = "https://api.fxtwitter.com/status/" . $tweetId;
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fxUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log results for debugging
    if (isset($_GET['debug']) || isset($_POST['debug'])) {
        error_log("FxTwitter API HTTP Response Code: " . $httpCode);
        if ($httpCode !== 200) {
            error_log("FxTwitter API Response: " . substr($response, 0, 500));
        }
    }
    
    // Parse the JSON response
    if ($httpCode === 200 && !empty($response)) {
        $data = json_decode($response, true);
        
        // Check if we have valid data
        if (isset($data['tweet'])) {
            $tweet = $data['tweet'];
            
            // Extract the tweet text
            if (isset($tweet['text'])) {
                $metadata['title'] = $tweet['text'];
                $metadata['description'] = $tweet['text'];
                
                // Limit title length
                if (strlen($metadata['title']) > 100) {
                    $metadata['title'] = substr($metadata['title'], 0, 97) . '...';
                }
            }
            
            // Extract author info
            if (isset($tweet['author'])) {
                $author = $tweet['author'];
                $metadata['author'] = $author['name'] ?? '';
                $metadata['author_username'] = $author['screen_name'] ?? '';
                
                if (!empty($metadata['author']) && !empty($metadata['author_username'])) {
                    $metadata['author'] = $metadata['author'] . ' (@' . $metadata['author_username'] . ')';
                }
            }
            
            // Extract image/thumbnail
            if (isset($tweet['media']['photos']) && !empty($tweet['media']['photos'])) {
                $metadata['thumbnail'] = $tweet['media']['photos'][0]['url'] ?? '';
                
                // Log this for debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("FxTwitter API found photo URL: " . $metadata['thumbnail']);
                }
            } elseif (isset($tweet['media']['videos']) && !empty($tweet['media']['videos'])) {
                $metadata['thumbnail'] = $tweet['media']['videos'][0]['thumbnail_url'] ?? '';
                
                // Log this for debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("FxTwitter API found video thumbnail URL: " . $metadata['thumbnail']);
                }
            }
            
            // Additional fallback for images - check tweet directly
            if (empty($metadata['thumbnail']) && isset($tweet['media']) && !empty($tweet['media'])) {
                // Try to extract from various potential structures
                if (isset($tweet['media']['all']) && !empty($tweet['media']['all'])) {
                    foreach ($tweet['media']['all'] as $media) {
                        if (isset($media['url'])) {
                            $metadata['thumbnail'] = $media['url'];
                            
                            // Log this for debugging
                            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                                error_log("FxTwitter API found media URL from 'all': " . $metadata['thumbnail']);
                            }
                            
                            break; // Found a usable image
                        }
                    }
                }
            }
            
            // Extract hashtags as tags
            if (isset($tweet['hashtags']) && is_array($tweet['hashtags'])) {
                $metadata['tags'] = $tweet['hashtags'];
            }
            
            // Extract publish date
            if (isset($tweet['created_at'])) {
                $metadata['publish_date'] = $tweet['created_at'];
            }
            
            return $metadata;
        }
    }
    
    return false;
}

function getVideoMetadataFromFacebook($videoId, $videoUrl = '') {
    // Initialize return values
    $videoTitle = "Facebook Video: " . $videoId;
    $videoDescription = "";
    $videoAuthor = "";
    $videoPublishDate = "";
    $videoThumbnail = "";
    $videoTags = [];
    
    // Process and clean the input URL if provided
    if (!empty($videoUrl)) {
        // Keep original URL for display but clean it for extraction
        $originalUrl = $videoUrl;
        $videoUrl = normalizeFacebookUrl($videoUrl);
        
        // For debug and troubleshooting
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Original URL: " . $originalUrl);
            error_log("Normalized URL: " . $videoUrl);
        }
    }
    
    // If no video URL is provided or it was empty after cleaning, construct one
    if (empty($videoUrl)) {
        $videoUrl = "https://www.facebook.com/watch/?v={$videoId}";
    }
    
    // Check if we have a mibextid prefixed ID (from our specialized extraction)
    $actualVideoId = $videoId;
    $isMibExtId = false;
    $isFbWatchId = false;
    
    if (strpos($videoId, 'mib_') === 0) {
        $isMibExtId = true;
        $mibextValue = substr($videoId, 4); // Remove 'mib_' prefix
        $actualVideoId = $mibextValue; // Use the mibext value
        
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Processing mibextid: " . $actualVideoId);
        }
    } elseif (strpos($videoId, 'fbw_') === 0) {
        $isFbWatchId = true;
        $fbWatchCode = substr($videoId, 4); // Remove 'fbw_' prefix
        $actualVideoId = $fbWatchCode;
        
        // For debugging
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Processing fb.watch code: " . $actualVideoId);
        }
    }
    
    // Try multiple URL formats to maximize chances of finding metadata
    // Each format serves a different purpose and might yield different results
    $urls = [];
    
    // Always try the original URL first if we have one
    if (!empty($videoUrl)) {
        $urls[] = $videoUrl;
    }
    
    // Special case for mibextid parameters
    if ($isMibExtId) {
        // Use multiple formats with the mibextid value
        $urls[] = "https://www.facebook.com/watch/?mibextid={$actualVideoId}";
        $urls[] = "https://fb.watch/?mibextid={$actualVideoId}";
        
        // Try looking for the video directly with the mibextid
        $urls[] = "https://www.facebook.com/search/videos/?q={$actualVideoId}";
    } 
    // Special case for fb.watch short codes
    elseif ($isFbWatchId) {
        // Try the fb.watch URL directly
        $urls[] = "https://fb.watch/{$actualVideoId}";
    }
    // Standard approach for numeric IDs
    else {
        $urls = array_merge($urls, [
            "https://www.facebook.com/watch/?v={$actualVideoId}",
            "https://www.facebook.com/video.php?v={$actualVideoId}",
            "https://www.facebook.com/reel/{$actualVideoId}",
            "https://www.facebook.com/story.php?story_fbid={$actualVideoId}&id=0",
            "https://m.facebook.com/watch/?v={$actualVideoId}&_rdr",
            "https://m.facebook.com/reel/{$actualVideoId}",
            "https://fb.watch/{$actualVideoId}"
        ]);
    }
    
    foreach ($urls as $url) {
        // Initialize cURL session for better control
        $ch = curl_init();
        
        // Set comprehensive cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // For development only
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        
        // Choose the best user agent based on URL
        if (strpos($url, 'm.facebook.com') !== false) {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/98.0.4758.85 Mobile/15E148 Safari/604.1');
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
        }
        
        // Set realistic headers to appear like a normal browser
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept-Language: en-US,en;q=0.9',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Referer: https://www.facebook.com/',
            'Cache-Control: max-age=0',
            'Connection: keep-alive',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Upgrade-Insecure-Requests: 1'
        ]);
        
        // Set dummy cookies to help with access
        curl_setopt($ch, CURLOPT_COOKIE, 'c_user=100000000000000; xs=1:_a_random_xs_value:2:1672559600; wd=1920x1080; fr=random_fr_value.1672559600; datr=random_datr_value;');
        
        // Execute cURL session and get the HTML content
        $html = curl_exec($ch);
        curl_close($ch);
        
        if ($html) {
            // Extract title with multiple methods
            if (preg_match('/<title>(.*?)<\/title>/i', $html, $title_matches)) {
                $extractedTitle = trim($title_matches[1]);
                // Remove Facebook suffix from end of title if present
                $extractedTitle = preg_replace('/ \| Facebook$/', '', $extractedTitle);
                $extractedTitle = preg_replace('/ - Facebook$/', '', $extractedTitle);
                $extractedTitle = preg_replace('/ - Meta$/', '', $extractedTitle);
                
                if (!empty($extractedTitle) && $extractedTitle !== "Facebook" && $extractedTitle !== "Meta") {
                    $videoTitle = $extractedTitle;
                }
            }
            
            // Try meta tags for title (more reliable)
            if (preg_match('/<meta\s+property="og:title"\s+content="([^"]+)"/i', $html, $meta_title_matches)) {
                $extractedTitle = html_entity_decode(trim($meta_title_matches[1]), ENT_QUOTES, 'UTF-8');
                if (!empty($extractedTitle) && $extractedTitle !== "Facebook" && $extractedTitle !== "Meta") {
                    $videoTitle = $extractedTitle;
                }
            }
            
            // Clean up title regardless of extraction method
            if (!empty($videoTitle)) {
                // Extract any hashtags before cleaning the title, so we can add them to tags later
                preg_match_all('/(#[A-Za-z0-9_\x{00C0}-\x{00FF}]+)/u', $videoTitle, $hashtagMatches);
                
                // Extract tags for later use
                $titleHashtags = [];
                if (!empty($hashtagMatches[1])) {
                    foreach ($hashtagMatches[1] as $hashtag) {
                        $titleHashtags[] = trim($hashtag);
                    }
                }
                
                // Remove reaction counts and share/comment counts anywhere in the title
                $videoTitle = preg_replace('/\d+\.?\d*[KM]?\s+reactions\s+·\s+\d+\.?\d*[KM]?\s+shares\s+\|\s+/i', '', $videoTitle);
                $videoTitle = preg_replace('/\d+\.?\d*[KM]?\s+reactions\s+·\s+\d+\.?\d*[KM]?\s+comments\s+\|\s+/i', '', $videoTitle);
                $videoTitle = preg_replace('/·\s+\d+\.?\d*[KM]?\s+reactions\s+\|\s+/i', '', $videoTitle);
                $videoTitle = preg_replace('/·\s+\d+\.?\d*[KM]?\s+reactions/i', '', $videoTitle);
                
                // Remove any Facebook page name at the end if it's after a pipe
                $videoTitle = preg_replace('/\s+\|\s+.*?\s+Page$/i', '', $videoTitle);
                
                // Clean up various reaction markers
                $videoTitle = preg_replace('/\(?\d+\.?\d*[KM]?\s+views\)?/i', '', $videoTitle);
                $videoTitle = preg_replace('/\d+\.?\d*[KM]?\s+views/i', '', $videoTitle);
                
                // Remove Facebook account info from end
                $videoTitle = preg_replace('/\|\s+By\s+.*?\s+\|\s+Facebook$/i', '', $videoTitle);
                $videoTitle = preg_replace('/\|\s+By\s+.*?$/i', '', $videoTitle);
                
                // Preserve hashtags by adding them to the videoTags array
                if (!empty($titleHashtags)) {
                    foreach ($titleHashtags as $hashtag) {
                        // Convert hashtag to a regular tag (remove # sign)
                        $tag = substr($hashtag, 1); 
                        if (isValidTag($tag) && !in_array($tag, $videoTags)) {
                            $videoTags[] = $tag;
                        }
                    }
                }
                
                // Split title at first line break or emoji for description
                if (preg_match('/^(.*?)(?:\r?\n|\s+[\x{1F300}-\x{1F6FF}\x{2600}-\x{26FF}])(.+)$/us', $videoTitle, $splitMatches)) {
                    // Found a line break or emoji, split the content
                    $firstLine = trim($splitMatches[1]);
                    $remainingContent = trim($splitMatches[2]);
                    
                    // Only update description if we actually have split content
                    if (!empty($remainingContent)) {
                        // Prepend this content to the description
                        if (empty($videoDescription) || $videoDescription === "No description available for this video.") {
                            $videoDescription = $remainingContent;
                        } else {
                            $videoDescription = $remainingContent . "\n\n" . $videoDescription;
                        }
                    }
                    
                    // Update title to be only the first line
                    $videoTitle = $firstLine;
                }
                
                // Trim again to remove any spaces left from our replacements
                $videoTitle = trim($videoTitle);
            }
            
            // Extract description with multiple methods
            if (preg_match('/<meta\s+property="og:description"\s+content="([^"]+)"/i', $html, $desc_matches)) {
                $videoDescription = html_entity_decode(trim($desc_matches[1]), ENT_QUOTES, 'UTF-8');
            } elseif (preg_match('/"description":"([^"]+)"/i', $html, $alt_desc_matches)) {
                $videoDescription = html_entity_decode(trim($alt_desc_matches[1]), ENT_QUOTES, 'UTF-8');
                // Convert escaped characters
                $videoDescription = str_replace('\\n', "\n", $videoDescription);
                $videoDescription = str_replace('\\"', '"', $videoDescription);
            }
            
            // Extract author with multiple methods
            if (preg_match('/<meta\s+property="og:site_name"\s+content="([^"]+)"/i', $html, $site_matches)) {
                $videoAuthor = html_entity_decode(trim($site_matches[1]), ENT_QUOTES, 'UTF-8');
            } elseif (preg_match('/"ownerName":\s*"([^"]+)"/i', $html, $owner_matches)) {
                $videoAuthor = html_entity_decode(trim($owner_matches[1]), ENT_QUOTES, 'UTF-8');
            }
            
            // Extract thumbnail with multiple methods
            if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $image_matches)) {
                $videoThumbnail = html_entity_decode(trim($image_matches[1]), ENT_QUOTES, 'UTF-8');
            } elseif (preg_match('/"thumbnailImage":\s*{\s*"uri":\s*"([^"]+)"/is', $html, $thumb_matches)) {
                $videoThumbnail = html_entity_decode(trim($thumb_matches[1]), ENT_QUOTES, 'UTF-8');
                // Facebook often escapes URLs in JSON, so fix that
                $videoThumbnail = str_replace('\\', '', $videoThumbnail);
            } elseif (preg_match('/background-image:url\(\'([^\']+)\'\)/', $html, $bg_matches)) {
                $videoThumbnail = str_replace('\\', '', $bg_matches[1]);
            }
            
            // Extract publish date
            if (preg_match('/<meta\s+property="article:published_time"\s+content="([^"]+)"/i', $html, $date_matches)) {
                $videoPublishDate = html_entity_decode(trim($date_matches[1]), ENT_QUOTES, 'UTF-8');
            }
            
            // Extract tags using our comprehensive method
            $extractedTags = extractFacebookTagsFromHTML($html, $videoId);
            
            // If we found good data, use it
            if (!empty($extractedTags)) {
                $videoTags = $extractedTags;
                // We found what we need, so break out of the loop
                break;
            }
        }
    }
    
    // After trying all URLs, use default fallback values if needed
    if (empty($videoTitle)) {
        $videoTitle = "Facebook Video: " . $videoId;
    }
    
    // Check if description is empty and set a default message if it is
    if (empty($videoDescription)) {
        $videoDescription = "No description available for this video.";
    }
    
    // Create different size thumbnail URLs for Facebook videos
    if (!empty($videoThumbnail)) {
        // Save the original thumbnail URL
        $originalThumbnail = $videoThumbnail;
        
        // Check if the URL contains Facebook image size parameters
        if (strpos($videoThumbnail, '?') !== false) {
            // Facebook URLs can be adjusted with size parameters
            $videoThumbnail = $originalThumbnail;
        }
    }
    
    // After extraction is done, manually check for CSS selectors in tags and remove them
    $cleanTags = [];
    foreach ($videoTags as $tag) {
        if (isValidTag($tag)) {
            $cleanTags[] = $tag;
        }
    }
    
    // Add default tag for Facebook if no tags found
    if (empty($cleanTags)) {
        // Try to build tags from title and description
        if (!empty($videoTitle) && $videoTitle !== "Facebook Video: " . $videoId) {
            // Extract potential keywords from title
            preg_match_all('/\b([A-Za-z\x{00C0}-\x{00FF}][A-Za-z\x{00C0}-\x{00FF}\-\']{2,20})\b/u', $videoTitle, $titleWords);
            if (!empty($titleWords[1])) {
                foreach ($titleWords[1] as $word) {
                    if (isProperNounOrSubject($word) && !in_array($word, $cleanTags)) {
                        $cleanTags[] = $word;
                    }
                }
            }
        }
        
        // If still no tags, use a generic tag
        if (empty($cleanTags)) {
            $cleanTags = ["Facebook Video"];
        }
    }
    
    // Return all metadata as an array
    return [$videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $cleanTags];
}

/**
 * Generate YouTube thumbnail URL for a given video ID
 * This function returns the thumbnail URL based on the specified quality
 * 
 * @param string $videoId YouTube video ID
 * @param string $quality Thumbnail quality ('maxres', 'hq', 'mq', 'sd', 'default')
 * @return string URL to the thumbnail image
 */
function getYoutubeThumbnailUrl($videoId, $quality = 'maxres') {
    if (empty($videoId)) {
        return '';
    }
    
    // Quality mapping to actual file names
    $qualityMap = [
        'maxres' => 'maxresdefault.jpg', // 1280x720 (HD)
        'hq' => 'hqdefault.jpg',         // 480x360
        'mq' => 'mqdefault.jpg',         // 320x180
        'sd' => 'sddefault.jpg',         // 640x480
        'default' => 'default.jpg'       // 120x90
    ];
    
    // If an invalid quality is specified, use maxres as default
    if (!isset($qualityMap[$quality])) {
        $quality = 'maxres';
    }
    
    return "https://img.youtube.com/vi/{$videoId}/{$qualityMap[$quality]}";
}

/**
 * Check if a YouTube thumbnail exists by making an HTTP HEAD request
 * 
 * @param string $url The thumbnail URL to check
 * @return bool True if the thumbnail exists, false otherwise
 */
function youtubeThumbnailExists($url) {
    if (empty($url)) {
        return false;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Accept 200 OK and 203 Non-Authoritative Information as valid responses
    return ($httpCode == 200 || $httpCode == 203);
}

/**
 * Get the best available YouTube thumbnail for a video
 * This tries multiple qualities starting from the highest until a valid one is found
 * 
 * @param string $videoId The YouTube video ID
 * @return string URL to the best available thumbnail
 */
function getBestYoutubeThumbnail($videoId) {
    if (empty($videoId)) {
        return '';
    }
    
    // Try different qualities in order of preference
    $qualities = ['maxres', 'sd', 'hq', 'mq', 'default'];
    
    foreach ($qualities as $quality) {
        $thumbnailUrl = getYoutubeThumbnailUrl($videoId, $quality);
        if (youtubeThumbnailExists($thumbnailUrl)) {
            return $thumbnailUrl;
        }
    }
    
    // If all checks failed, return the default thumbnail URL
    // This should almost never happen, as default.jpg should always exist
    return getYoutubeThumbnailUrl($videoId, 'default');
}

/**
 * Get metadata from a YouTube video
 * 
 * @param string $videoId YouTube video ID
 * @return array Array containing [title, description, author, date, thumbnail, tags]
 */
/**
 * Get metadata for a YouTube post
 * 
 * @param string $postId YouTube post ID
 * @return array Array containing [title, image, description, author]
 */
function getYoutubePostMetadata($postId) {
    // Initialize default values
    $postTitle = "YouTube Post: " . $postId;
    $postImage = "";
    $postDescription = "";
    $postAuthor = "";
    
    // Try to get post metadata from YouTube page using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.youtube.com/post/" . $postId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $html = curl_exec($ch);
    curl_close($ch);
    
    if ($html) {
        // Extract title from meta tags
        preg_match('/<meta\s+property="og:title"\s+content="([^"]+)"/i', $html, $title_matches);
        if (!empty($title_matches[1])) {
            $postTitle = html_entity_decode(trim($title_matches[1]), ENT_QUOTES, 'UTF-8');
        }
        
        // Extract image from meta tags or image tags
        preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $image_matches);
        if (!empty($image_matches[1])) {
            $postImage = html_entity_decode(trim($image_matches[1]), ENT_QUOTES, 'UTF-8');
        } else {
            // Try to find post image from HTML content
            preg_match('/src="(https:\/\/[^"]*?ytimg\.com\/[^"]*?\/post\/[^"]*?)"/i', $html, $img_matches);
            if (!empty($img_matches[1])) {
                $postImage = html_entity_decode(trim($img_matches[1]), ENT_QUOTES, 'UTF-8');
            }
        }
        
        // Extract description
        preg_match('/<meta\s+property="og:description"\s+content="([^"]+)"/i', $html, $desc_matches);
        if (!empty($desc_matches[1])) {
            $postDescription = html_entity_decode(trim($desc_matches[1]), ENT_QUOTES, 'UTF-8');
        }
        
        // Extract author
        preg_match('/<meta\s+property="og:site_name"\s+content="([^"]+)"/i', $html, $author_matches);
        if (!empty($author_matches[1])) {
            $postAuthor = html_entity_decode(trim($author_matches[1]), ENT_QUOTES, 'UTF-8');
        }
    }
    
    return [$postTitle, $postImage, $postDescription, $postAuthor];
}

/**
 * Extract metadata from a YouTube channel page
 * 
 * @param string $channelUsername The channel username (without the @ symbol)
 * @return array Array containing channel metadata (title, description, thumbnail, banner)
 */
function getYoutubeChannelMetadata($channelUsername) {
    // Initialize default values
    $channelTitle = "YouTube Channel: @" . $channelUsername;
    $channelDescription = "";
    $channelThumbnail = ""; // Logo/Profile picture
    $channelBanner = "";    // Cover photo/Banner image
    
    // Create the URL to fetch the channel page
    $channelUrl = "https://www.youtube.com/@" . $channelUsername;
    
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $channelUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    // Execute the cURL request
    $html = curl_exec($ch);
    
    // Close the cURL session
    curl_close($ch);
    
    if (!empty($html)) {
        // Extract channel title (name)
        if (preg_match('/<meta name="title" content="([^"]+)"/i', $html, $titleMatches)) {
            $channelTitle = html_entity_decode(trim($titleMatches[1]), ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/<meta property="og:title" content="([^"]+)"/i', $html, $titleMatches)) {
            $channelTitle = html_entity_decode(trim($titleMatches[1]), ENT_QUOTES, 'UTF-8');
        }
        
        // Extract channel description using multiple approaches for completeness
        // Method 1: Traditional meta tags (this often has truncated descriptions)
        if (preg_match('/<meta name="description" content="([^"]+)"/i', $html, $descMatches)) {
            $metaDescription = html_entity_decode(trim($descMatches[1]), ENT_QUOTES, 'UTF-8');
            $channelDescription = $metaDescription;
        } elseif (preg_match('/<meta property="og:description" content="([^"]+)"/i', $html, $descMatches)) {
            $metaDescription = html_entity_decode(trim($descMatches[1]), ENT_QUOTES, 'UTF-8');
            $channelDescription = $metaDescription;
        }
        
        // Method 2: Look for description in JSON data - this often has the full description
        if (preg_match('/"description":\s*"((?:\\\\"|[^"])*?)"/s', $html, $jsonDescMatches) || 
            preg_match('/"channelDescription":\s*"((?:\\\\"|[^"])*?)"/s', $html, $jsonDescMatches) ||
            preg_match('/"ownerChannelDescription":\s*"((?:\\\\"|[^"])*?)"/s', $html, $jsonDescMatches)) {
            $jsonDescription = html_entity_decode(trim($jsonDescMatches[1]), ENT_QUOTES, 'UTF-8');
            // Replace JSON escapes
            $jsonDescription = str_replace('\\"', '"', $jsonDescription);
            $jsonDescription = str_replace('\\n', "\n", $jsonDescription);
            $jsonDescription = str_replace('\\r', "", $jsonDescription);
            $jsonDescription = str_replace('\\t', "\t", $jsonDescription);
            $jsonDescription = str_replace('\\\\', "\\", $jsonDescription);
            
            // For debugging
            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                error_log("Channel description from JSON: " . substr($jsonDescription, 0, 100) . "... (Length: " . strlen($jsonDescription) . ")");
                error_log("Channel description from meta: " . substr($channelDescription, 0, 100) . "... (Length: " . strlen($channelDescription) . ")");
            }
            
            // If JSON description is significantly more complete than meta description, use it
            if (strlen($jsonDescription) > strlen($channelDescription) * 1.2) {
                $channelDescription = $jsonDescription;
                
                // For debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Using JSON description (longer)");
                }
            }
        }
        
        // Method 3: Look for description in <script type="application/ld+json"> blocks
        if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $ldJsonMatches)) {
            foreach ($ldJsonMatches[1] as $ldJson) {
                if (preg_match('/"description":\s*"((?:\\\\"|[^"])*?)"/s', $ldJson, $ldDescMatches)) {
                    $ldDescription = html_entity_decode(trim($ldDescMatches[1]), ENT_QUOTES, 'UTF-8');
                    // Replace JSON escapes
                    $ldDescription = str_replace('\\"', '"', $ldDescription);
                    $ldDescription = str_replace('\\n', "\n", $ldDescription);
                    $ldDescription = str_replace('\\r', "", $ldDescription);
                    
                    // For debugging
                    if (isset($_GET['debug']) || isset($_POST['debug'])) {
                        error_log("Channel description from LD+JSON: " . substr($ldDescription, 0, 100) . "... (Length: " . strlen($ldDescription) . ")");
                    }
                    
                    // If LD+JSON description is more complete, use it
                    if (strlen($ldDescription) > strlen($channelDescription) * 1.2) {
                        $channelDescription = $ldDescription;
                        
                        // For debugging
                        if (isset($_GET['debug']) || isset($_POST['debug'])) {
                            error_log("Using LD+JSON description (longer)");
                        }
                    }
                }
            }
        }
        
        // Method 4: Check for structured data in newer YouTube format
        if (preg_match('/"metadata":\s*{.*?"description":\s*"((?:\\\\"|[^"])*?)"/s', $html, $structMatches)) {
            $structDescription = html_entity_decode(trim($structMatches[1]), ENT_QUOTES, 'UTF-8');
            // Replace JSON escapes
            $structDescription = str_replace('\\"', '"', $structDescription);
            $structDescription = str_replace('\\n', "\n", $structDescription);
            $structDescription = str_replace('\\r', "", $structDescription);
            
            // For debugging
            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                error_log("Channel description from structured data: " . substr($structDescription, 0, 100) . "... (Length: " . strlen($structDescription) . ")");
            }
            
            // If structured description is more complete, use it
            if (strlen($structDescription) > strlen($channelDescription) * 1.2) {
                $channelDescription = $structDescription;
                
                // For debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Using structured data description (longer)");
                }
            }
        }
        
        // Method 5: Try to find the description in the about text section
        if (preg_match('/<div[^>]*?id="description-container"[^>]*?>(.*?)<\/div>/s', $html, $aboutMatches) || 
            preg_match('/<div[^>]*?id="channel-description"[^>]*?>(.*?)<\/div>/s', $html, $aboutMatches) ||
            preg_match('/<div[^>]*?class="[^"]*?about-description[^"]*?"[^>]*?>(.*?)<\/div>/s', $html, $aboutMatches)) {
            
            // Clean up HTML and extract text
            $aboutText = strip_tags($aboutMatches[1]);
            $aboutText = html_entity_decode($aboutText, ENT_QUOTES, 'UTF-8');
            $aboutText = trim($aboutText);
            
            // For debugging
            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                error_log("Channel description from About section: " . substr($aboutText, 0, 100) . "... (Length: " . strlen($aboutText) . ")");
            }
            
            // If about text is significantly longer, use it
            if (strlen($aboutText) > strlen($channelDescription) * 1.2) {
                $channelDescription = $aboutText;
                
                // For debugging
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Using About section description (longer)");
                }
            }
        }
        
        // Extract channel thumbnail (logo/profile picture)
        if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $thumbMatches)) {
            $channelThumbnail = trim($thumbMatches[1]);
        } elseif (preg_match('/"avatar":\s*{"thumbnails":\s*\[\s*{"url":\s*"([^"]+)"/i', $html, $thumbMatches)) {
            $channelThumbnail = trim($thumbMatches[1]);
        }
        
        // Try to extract banner image (cover photo) - using multiple patterns for better success
        if (preg_match('/"banner":\s*{"thumbnails":\s*\[\s*{"url":\s*"([^"]+)"/i', $html, $bannerMatches)) {
            $channelBanner = trim($bannerMatches[1]);
        } else if (preg_match('/"banner":\s*{.*?"url":\s*"([^"]+)"/is', $html, $bannerMatches)) {
            $channelBanner = trim($bannerMatches[1]);
        } else if (preg_match('/https:\/\/yt3\.googleusercontent\.com\/[a-zA-Z0-9_\-\/]+banner/i', $html, $bannerMatches)) {
            $channelBanner = trim($bannerMatches[0]);
        } 
        
        // If no banner found, use thumbnail as fallback
        if (empty($channelBanner) && !empty($channelThumbnail)) {
            $channelBanner = $channelThumbnail;
        }
        
        // Add debug logging for banner
        if (isset($_GET['debug']) || isset($_POST['debug'])) {
            error_log("Channel Banner URL: " . ($channelBanner ? $channelBanner : "Not found"));
            
            // Log final description for debugging
            $finalDescriptionPreview = strlen($channelDescription) > 150 ? 
                substr($channelDescription, 0, 150) . "..." : 
                $channelDescription;
            error_log("FINAL Channel Description: " . $finalDescriptionPreview . " (Length: " . strlen($channelDescription) . ")");
        }
    }
    
    // Return only the requested metadata as an array
    return [
        $channelTitle,
        $channelDescription,
        $channelThumbnail,
        $channelBanner,
        $channelUrl
    ];
}

function getVideoMetadataFromYoutube($videoId) {
    // Initialize default values
    $videoTitle = "YouTube Video: " . $videoId;
    $videoDescription = "";
    $videoAuthor = "";
    $videoPublishDate = "";
    $videoThumbnail = "";
    $videoTags = [];
    
    // Get the best available thumbnail for this video
    $videoThumbnail = getBestYoutubeThumbnail($videoId);
    
    // Try to get video metadata from YouTube page using cURL instead of file_get_contents for better reliability
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.youtube.com/watch?v=" . $videoId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $html = curl_exec($ch);
    curl_close($ch);
    if ($html) {
        // Extract title
        preg_match('/<title>(.*?)<\/title>/i', $html, $title_matches);
        if (!empty($title_matches[1])) {
            $extractedTitle = trim($title_matches[1]);
            // Remove " - YouTube" from the end if present
            $extractedTitle = preg_replace('/ - YouTube$/', '', $extractedTitle);
            if (!empty($extractedTitle)) {
                $videoTitle = $extractedTitle;
                
                // Split title at first line break or emoji for description
                if (preg_match('/^(.*?)(?:\r?\n|\s+[\x{1F300}-\x{1F6FF}\x{2600}-\x{26FF}])(.+)$/us', $videoTitle, $splitMatches)) {
                    // Found a line break or emoji, split the content
                    $firstLine = trim($splitMatches[1]);
                    $remainingContent = trim($splitMatches[2]);
                    
                    // Only update description if we actually have split content
                    if (!empty($remainingContent)) {
                        // Prepend this content to the description
                        if (empty($videoDescription) || $videoDescription === "No description available for this video.") {
                            $videoDescription = $remainingContent;
                        } else {
                            $videoDescription = $remainingContent . "\n\n" . $videoDescription;
                        }
                    }
                    
                    // Update title to be only the first line
                    $videoTitle = $firstLine;
                }
            }
        }
        
        // Extract description (this is a simple approach and might not always work)
        preg_match('/"description":{"simpleText":"(.*?)"}/', $html, $desc_matches);
        if (!empty($desc_matches[1])) {
            $videoDescription = trim($desc_matches[1]);
            // Convert escaped characters
            $videoDescription = str_replace('\\n', "\n", $videoDescription);
        }
        
        // Extract author/channel name
        preg_match('/"ownerChannelName":"(.*?)"/', $html, $author_matches);
        if (!empty($author_matches[1])) {
            $videoAuthor = trim($author_matches[1]);
        }
        
        // Extract publish date
        preg_match('/"publishDate":"(.*?)"/', $html, $date_matches);
        if (!empty($date_matches[1])) {
            $videoPublishDate = trim($date_matches[1]);
        }
        
        // Extract tags (keywords)
        preg_match('/"keywords":\["(.+?)"\]/', $html, $tag_matches);
        if (!empty($tag_matches[1])) {
            // Tags are typically comma-separated in a JSON array
            $rawTags = $tag_matches[1];
            $rawTags = str_replace('\\"', '"', $rawTags); // Fix escaped quotes
            $videoTags = explode('","', $rawTags);
        }
    }
    
    // Check if description is empty and set a default message if it is
    if (empty($videoDescription)) {
        $videoDescription = "No description available for this video.";
    }
    
    // Clean the tags just like we do for Facebook
    $cleanTags = [];
    foreach ($videoTags as $tag) {
        if (isValidTag($tag)) {
            $cleanTags[] = $tag;
        }
    }
    
    // Return all extracted metadata
    return [$videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $cleanTags];
}

/**
 * Get tags from Facebook using the Graph API approach
 *
 * @param string $videoId Facebook video ID
 * @return array Array of tags or empty array if none found
 */
function getFacebookGraphTags($videoId) {
    // Initialize tags
    $tags = [];
    
    // Don't proceed with invalid videoId
    if (empty($videoId)) {
        return $tags;
    }
    
    // Get all metadata including tags
    list($title, $description, $author, $date, $thumbnail, $extractedTags) = 
        getVideoMetadataFromFacebook($videoId);
    
    // Return just the tags portion
    return $extractedTags;
}

// Check if this is an AJAX request
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_url'])) {
    $videoUrl = trim($_POST['video_url']);
    
    // Special case: Handle direct video ID input
    if (preg_match('/^\d{5,}$/', $videoUrl)) {
        // User has entered just a video ID rather than a URL
        if ($videoType === 'facebook') {
            $videoId = $videoUrl; // Use directly as video ID
            $videoUrl = "https://www.facebook.com/watch/?v={$videoId}"; // Create a URL for display purposes
            $videoSuccess = true;
        }
    }
    
    if (empty($videoUrl)) {
        $error = 'Please enter a valid video URL or ID to proceed with information extraction.';
    } else {
        // Choose the appropriate extraction function based on video type
        if ($videoType === 'youtube') {
            $videoId = extractYoutubeVideoId($videoUrl);
            
            if ($videoId === false) {
                $error = 'We were unable to identify a valid YouTube video or post in the provided URL. Please ensure you are using a complete YouTube URL format.';
            } else {
                // Successfully extracted YouTube ID
                $videoSuccess = true;
                
                // Check if this is a YouTube channel (@username format)
                if (strpos($videoId, 'channel_') === 0) {
                    // This is a YouTube channel
                    // Extract the username from the ID (remove the 'channel_' prefix)
                    $channelUsername = substr($videoId, 8);
                    
                    // Get comprehensive metadata using our specialized channel function
                    list($videoTitle, $videoDescription, $videoThumbnail, $channelBanner, $channelUrl) = 
                        getYoutubeChannelMetadata($channelUsername);
                        
                    // Make the channel username available for the results template
                    $channelUsername = $channelUsername;
                    
                    // Set flag to indicate this is a YouTube channel for the results template
                    $isYoutubeChannel = true;
                    
                    // Set other metadata fields
                    $videoAuthor = $videoTitle; // Channel name is the author
                    $videoPublishDate = ""; // Channels don't have a publish date
                    $videoTags = []; // Initialize empty tags array
                    
                    // For debugging purposes
                    $isDebugMode = isset($_GET['debug']) || isset($_POST['debug']);
                    if ($isDebugMode) {
                        error_log("YouTube Channel Username: " . $channelUsername);
                        error_log("Channel Title: " . $videoTitle);
                        error_log("Channel Thumbnail: " . $videoThumbnail);
                    }
                }
                // Check if this is a YouTube post
                else if (preg_match('/youtube\.com\/post\/([A-Za-z0-9_\-]+)(?:\?[^\/]*)?/', $videoUrl)) {
                    // This is a YouTube post
                    // Get comprehensive metadata using our specialized post function
                    list($videoTitle, $videoThumbnail, $videoDescription, $videoAuthor) = 
                        getYoutubePostMetadata($videoId);
                    
                    // Empty values for fields that only apply to videos
                    $videoPublishDate = "";
                    $videoTags = [];
                    
                    // For debugging purposes
                    $isDebugMode = isset($_GET['debug']) || isset($_POST['debug']);
                    if ($isDebugMode) {
                        error_log("YouTube Post ID: " . $videoId);
                        error_log("Post Image: " . $videoThumbnail);
                    }
                } else {
                    // This is a regular YouTube video
                    // Get comprehensive metadata using our specialized function
                    list($videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags) = 
                        getVideoMetadataFromYoutube($videoId);
                    
                    // For debugging purposes
                    $isDebugMode = isset($_GET['debug']) || isset($_POST['debug']);
                    if ($isDebugMode) {
                        error_log("YouTube Video ID: " . $videoId);
                        error_log("Selected Thumbnail: " . $videoThumbnail);
                    }
                }
                
                // Update page title and metadata for SEO
                if (!empty($videoTitle)) {
                    $pageTitle = htmlspecialchars($videoTitle) . " - YouTube Content Info";
                    $pageDescription = "View and download information for YouTube content: " . htmlspecialchars($videoTitle);
                }
            }
        } else if ($videoType === 'facebook') {
            // Check if we already have a videoId (from direct ID input) and videoSuccess is true
            if (!isset($videoId) || !$videoSuccess) {
                // Clean and normalize the URL before extraction
                $normalizedUrl = normalizeFacebookUrl($videoUrl);
                $videoId = extractFacebookVideoId($normalizedUrl);
                
                if ($videoId === false) {
                    $error = 'We were unable to identify a valid Facebook video in the provided URL. Please ensure you are using a complete Facebook video URL format, or try entering the numeric video ID directly.';
                } else {
                    $videoSuccess = true;
                }
            }
            
            if ($videoSuccess) {
                // Now that we have a valid video ID, process the video
                
                // Extract all metadata from the video using our comprehensive function
                list($videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags) = 
                    getVideoMetadataFromFacebook($videoId, $videoUrl);
                
                // Debug data to help identify all available tags in the page
                $debugData = "";
                $isDebugMode = isset($_GET['debug']) || isset($_POST['debug']);
                
                // Get HTML content again to show debug information if needed
                if ($isDebugMode) {
                    // Initialize cURL session to get HTML for debugging
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $videoUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
                    $html = curl_exec($ch);
                    curl_close($ch);
                    
                    if ($html) {
                        // Extract all meta tags to analyze what Facebook provides
                        preg_match_all('/<meta[^>]+>/i', $html, $all_meta_tags);
                        
                        if (!empty($all_meta_tags[0])) {
                            foreach ($all_meta_tags[0] as $meta_tag) {
                                if (strpos($meta_tag, 'video:') !== false || 
                                    strpos($meta_tag, 'og:') !== false || 
                                    strpos($meta_tag, 'article:tag') !== false ||
                                    strpos($meta_tag, 'keywords') !== false) {
                                    $debugData .= htmlspecialchars($meta_tag) . "\n";
                                }
                            }
                        }
                        
                        // Look for any JSON-LD data that might contain tags
                        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $json_ld_matches);
                        if (!empty($json_ld_matches[1])) {
                            foreach ($json_ld_matches[1] as $json_ld) {
                                if (strpos($json_ld, 'keyword') !== false || strpos($json_ld, 'tag') !== false) {
                                    $debugData .= "JSON-LD: " . htmlspecialchars(substr($json_ld, 0, 500)) . "...\n";
                                }
                            }
                        }
                    }
                }
                
                // If we have a video title, update page title and metadata for SEO
                if (!empty($videoTitle)) {
                    // Variables for SEO that will be used in the <head> section
                    $pageTitle = htmlspecialchars($videoTitle) . " - Facebook Video Info";
                    $pageDescription = "View and download thumbnails, description, and tags for Facebook video: " . htmlspecialchars($videoTitle);
                }
                
                // Add debug data to video tags if in debug mode
                if ($isDebugMode && !empty($debugData)) {
                    $videoDescription .= "\n\n[DEBUG META DATA]\n" . $debugData;
                }
            }
        } else if ($videoType === 'instagram') {
            // First check if this is an Instagram URL
            if (strpos(strtolower($videoUrl), 'instagram.com') === false) {
                $error = 'Please enter an Instagram URL. Other platform URLs are not supported in Instagram mode.';
            } else {
                // Extract Instagram post ID
                $postId = extractInstagramPostId($videoUrl);
                
                if ($postId === false) {
                    $error = 'We were unable to identify a valid Instagram post in the provided URL. Please ensure you are using a complete Instagram post URL format.';
                } else {
                    $videoSuccess = true;
                    
                    // Now that we have a valid post ID, process the Instagram post
                    // Extract all metadata from the post
                    list($videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags) = 
                        getPostMetadataFromInstagram($postId, $videoUrl);
                    
                    // Add explicit debugging for thumbnail
                    error_log("Instagram Extraction - After list unpacking - videoThumbnail: " . ($videoThumbnail ? $videoThumbnail : 'EMPTY'));
                    
                    // Debug data to help identify all available meta tags
                    $debugData = "";
                    $isDebugMode = isset($_GET['debug']) || isset($_POST['debug']);
                    
                    // Get HTML content again to show debug information if needed
                    if ($isDebugMode) {
                        // Initialize cURL session to get HTML for debugging
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $videoUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
                        $html = curl_exec($ch);
                        curl_close($ch);
                        
                        if ($html) {
                            // Extract all meta tags to analyze what Instagram provides
                            preg_match_all('/<meta[^>]+>/i', $html, $all_meta_tags);
                            
                            if (!empty($all_meta_tags[0])) {
                                foreach ($all_meta_tags[0] as $meta_tag) {
                                    if (strpos($meta_tag, 'og:') !== false || 
                                        strpos($meta_tag, 'instapp:') !== false || 
                                        strpos($meta_tag, 'article:') !== false) {
                                        $debugData .= htmlspecialchars($meta_tag) . "\n";
                                    }
                                }
                            }
                        }
                    }
                    
                    // If we have a post title, update page title and metadata for SEO
                    if (!empty($videoTitle)) {
                        // Variables for SEO that will be used in the <head> section
                        $pageTitle = htmlspecialchars($videoTitle) . " - Instagram Post Info";
                        $pageDescription = "View and download thumbnails and information for Instagram post: " . htmlspecialchars($videoTitle);
                    }
                    
                    // Add debug data to video description if in debug mode
                    if ($isDebugMode && !empty($debugData)) {
                        $videoDescription .= "\n\n[DEBUG META DATA]\n" . $debugData;
                    }
                }
            }
        } else if ($videoType === 'twitter') {
            // Extract Twitter/X video ID
            $tweetId = extractTwitterVideoId($videoUrl);
            
            if ($tweetId === false) {
                $error = 'We were unable to identify a valid Twitter/X video or post in the provided URL. Please ensure you are using a complete Twitter/X URL format.';
            } else {
                $videoSuccess = true;
                
                // Now that we have a valid tweet ID, process the Twitter/X post
                // Extract all metadata from the post
                list($videoTitle, $videoDescription, $videoAuthor, $videoPublishDate, $videoThumbnail, $videoTags) = 
                    getVideoMetadataFromTwitter($tweetId, $videoUrl);
                
                // Add explicit debugging for thumbnail
                if (isset($_GET['debug']) || isset($_POST['debug'])) {
                    error_log("Twitter/X Extraction - After list unpacking - videoThumbnail: " . ($videoThumbnail ? $videoThumbnail : 'EMPTY'));
                }
                
                // Debug data to help identify all available meta tags
                $debugData = "";
                $isDebugMode = isset($_GET['debug']) || isset($_POST['debug']);
                
                // Get HTML content again to show debug information if needed
                if ($isDebugMode) {
                    // Initialize cURL session to get HTML for debugging
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $videoUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
                    $html = curl_exec($ch);
                    curl_close($ch);
                    
                    if ($html) {
                        // Extract all meta tags to analyze what Twitter provides
                        preg_match_all('/<meta[^>]+>/i', $html, $all_meta_tags);
                        
                        if (!empty($all_meta_tags[0])) {
                            foreach ($all_meta_tags[0] as $meta_tag) {
                                if (strpos($meta_tag, 'og:') !== false || 
                                    strpos($meta_tag, 'twitter:') !== false || 
                                    strpos($meta_tag, 'article:') !== false) {
                                    $debugData .= htmlspecialchars($meta_tag) . "\n";
                                }
                            }
                        }
                    }
                }
                
                // If we have a post title, update page title and metadata for SEO
                if (!empty($videoTitle)) {
                    // Variables for SEO that will be used in the <head> section
                    $pageTitle = htmlspecialchars($videoTitle) . " - Twitter/X Video Info";
                    $pageDescription = "View and download thumbnails and information for Twitter/X post: " . htmlspecialchars($videoTitle);
                }
                
                // Add debug data to video description if in debug mode
                if ($isDebugMode && !empty($debugData)) {
                    $videoDescription .= "\n\n[DEBUG META DATA]\n" . $debugData;
                }
            }
        }
    }
    
    // If this is an AJAX request, return JSON data instead of full HTML
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        
        if ($error) {
            echo json_encode([
                'success' => false,
                'error' => $error
            ]);
        } else {
            // Capture the HTML output for the results section
            ob_start();
            include 'results_template.php';
            $resultsHtml = ob_get_clean();
            
            echo json_encode([
                'success' => true,
                'html' => $resultsHtml
            ]);
        }
        
        exit; // Stop execution after sending JSON response
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <!-- Primary Meta Tags -->
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Thumbnail Downloader from Youtube, Facebook, Instagram & X'; ?></title>
    <meta name="title" content="<?php echo isset($pageTitle) ? $pageTitle : 'Thumbnail Downloader from Youtube, Facebook, Instagram & X'; ?>">
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Vidextract - The best free online tool to instantly extract video thumbnails, tags, titles, and descriptions from YouTube, Facebook, Instagram and Twitter videos without API key. Download high-quality thumbnails in seconds.'; ?>">
    <meta name="keywords" content="<?php echo !empty($videoTags) ? implode(', ', array_slice((is_array($videoTags) ? $videoTags : []), 0, 10)) . ', ' . ($videoType === 'youtube' ? 'YouTube' : ucfirst($videoType)) . ', thumbnail, extractor, Vidextract' : 'YouTube thumbnail extractor, Facebook video downloader, Instagram reel extractor, Twitter video info, Vidextract, video metadata, video tags, video description, thumbnail download, free online tool, video info'; ?>">
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
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Vidextract">
    <meta name="format-detection" content="telephone=no">
    
    <!-- Structured Data Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Vidextract",
        "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>",
        "description": "Extract video thumbnails, tags, titles, and descriptions from YouTube, Facebook, Instagram and Twitter videos without API key",
        "applicationCategory": "MultimediaApplication",
        "operatingSystem": "Web",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "author": {
            "@type": "Organization",
            "name": "Vidextract"
        }
    }
    </script>
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'); ?>">
    
    <!-- Enhanced Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo $videoSuccess && $videoId ? 'video.other' : 'website'; ?>">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle : 'Thumbnail Downloader from Youtube, Facebook, Instagram & X'; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Extract video thumbnails, tags, titles, and descriptions from YouTube, Facebook, Instagram and Twitter videos. No API key needed.'; ?>">
    <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp?v=<?php echo time(); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Vidextract">
    <meta property="og:locale" content="en_US">
    <?php if ($videoSuccess && $videoId && isset($videoThumbnail)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($videoThumbnail); ?>">
    <?php endif; ?>
    
    <!-- Enhanced Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@vidextract">
    <meta name="twitter:creator" content="@vidextract">
    <meta name="twitter:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle : 'Thumbnail Downloader from Youtube, Facebook, Instagram & X'; ?>">
    <meta name="twitter:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Extract video thumbnails, tags, titles, and descriptions from YouTube, Facebook, Instagram and Twitter videos. No API key needed.'; ?>">
    <meta name="twitter:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp?v=<?php echo time(); ?>">
    <?php if ($videoSuccess && $videoId && isset($videoThumbnail)): ?>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($videoThumbnail); ?>">
    <?php endif; ?>
    
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
    
    <!-- Resource Preloading for Better Performance -->
    <link rel="preload" href="/vidextract-tab-icon.webp" as="image" type="image/webp">
    <link rel="preload" href="/vidx-logo.webp" as="image" type="image/webp">
    <link rel="preload" href="/vidextract-pwa-icon.webp" as="image" type="image/webp">
    
    <!-- Performance & Security -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <!-- PWA Support -->
    <meta name="apple-touch-fullscreen" content="yes">
    <link rel="manifest" href="/manifest.json">
    
    <!-- Advanced SEO -->
    <meta name="geo.region" content="US">
    <meta name="geo.position" content="37.7749;-122.4194">
    <meta name="ICBM" content="37.7749, -122.4194">
    <meta name="thumbnail" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp">
    <meta name="generator" content="Vidextract">
    <meta name="copyright" content="Copyright © <?php echo date('Y'); ?> Vidextract. All Rights Reserved.">
    
    <!-- HowTo Schema for SEO - Better representation of how to use the tool -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "HowTo",
      "name": "How to Extract Video Information with Vidextract",
      "description": "Learn how to extract thumbnails, tags, titles, and descriptions from YouTube, Facebook, Instagram and Twitter videos quickly and easily.",
      "totalTime": "PT1M",
      "step": [
        {
          "@type": "HowToStep",
          "name": "Select Platform",
          "text": "Choose the video platform - YouTube, Facebook, Instagram or Twitter",
          "image": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp",
          "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>"
        },
        {
          "@type": "HowToStep",
          "name": "Paste URL",
          "text": "Copy and paste the video URL from your chosen platform",
          "image": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp",
          "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>"
        },
        {
          "@type": "HowToStep",
          "name": "Extract Information",
          "text": "Click the extract button to get thumbnails, tags, and descriptions",
          "image": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp",
          "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>"
        },
        {
          "@type": "HowToStep",
          "name": "Download Content",
          "text": "Download the thumbnails or copy the extracted information",
          "image": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp",
          "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>"
        }
      ],
      "tool": {
        "@type": "HowToTool",
        "name": "Web Browser"
      }
    }
    </script>
    
    <!-- FAQ Schema - Improves SEO with frequently asked questions -->
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "How do I extract information from a YouTube video?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Simply paste the YouTube URL in the input field and click 'Extract Info'. The tool will display the video's thumbnail, title, description, and tags."
      }
    },
    {
      "@type": "Question", 
      "name": "Do I need an API key to use Vidextract?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No, Vidextract works without requiring any API keys. You can extract video information instantly without registration."
      }
    },
    {
      "@type": "Question",
      "name": "Which video platforms does Vidextract support?", 
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Vidextract supports YouTube, Facebook, Instagram, and Twitter/X platforms, allowing you to extract information from videos on all these popular sites."
      }
    },
    {
      "@type": "Question",
      "name": "What thumbnail resolutions are available?",
      "acceptedAnswer": {
        "@type": "Answer", 
        "text": "The tool provides thumbnails in multiple resolutions: Maximum Resolution (1280×720), Standard Definition (640×480), High Quality (480×360), and Medium Quality (320×180)."
      }
    },
    {
      "@type": "Question",
      "name": "Can I download video thumbnails with Vidextract?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, Vidextract allows you to view and download high-quality thumbnails from videos across all supported platforms."
      }
    },
    {
      "@type": "Question",
      "name": "Is Vidextract free to use?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, Vidextract is completely free to use. There are no hidden fees or premium features."
      }
    }
  ]
}
    </script>
    
    <!-- Local Business Schema (for local SEO) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Vidextract",
      "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>",
      "logo": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp",
      "sameAs": [
        "https://twitter.com/vidextract",
        "https://www.facebook.com/vidextract",
        "https://www.instagram.com/vidextract"
      ]
    }
    </script>
    

    
    <!-- Custom Input Styles -->
    <link rel="stylesheet" href="css/input-style.css?v=1746760002">
    
    <!-- Preloading Critical Resources -->
    <link rel="preload" href="/js/script.js" as="script">
    <link rel="preload" href="/css/style.css" as="style">
    <link rel="preload" href="/vidextract-tab-icon.webp" as="image" type="image/webp">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    
    <!-- DNS Prefetch for external resources -->
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    
    <!-- Accessibility -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="HandheldFriendly" content="true">
    <meta name="MobileOptimized" content="width">
    <meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no">
    
    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>"
            }
        ]
    }
    </script>
    

    
    <!-- Apply DOM loaded class when ready, this makes HTML visible -->
    <script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            document.documentElement.classList.add('dom-loaded');
        });
        
        // Instantly make the page visible if it was already loaded 
        // This handles cases where cached pages are shown
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            document.documentElement.classList.add('dom-loaded');
        }
    })();
    </script>
    

    


    <link rel="stylesheet" href="css/style.css?v=1746760006">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Global Styles -->
    <style>
        /* Global max z-index element class */
        .max-z-index-element {
            z-index: 2147483647 !important; 
        }
    </style>
    

    

    
    <!-- BreadcrumbList Schema for better navigation representation -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>"
            }<?php if (isset($_GET['page']) && $_GET['page'] !== 'home'): ?>,
            {
                "@type": "ListItem",
                "position": 2,
                "name": "<?php echo ucfirst($_GET['page']); ?>",
                "item": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/?page=' . $_GET['page']; ?>"
            }
            <?php endif; ?>
        ]
    }
    </script>
    

    
    <!-- Structured data for Google rich snippets (JSON-LD) -->
    <?php if ($videoSuccess && $videoId): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "VideoObject",
        "name": "<?php echo htmlspecialchars($videoTitle); ?>",
        "description": "<?php echo htmlspecialchars(substr($videoDescription, 0, 500) . (strlen($videoDescription) > 500 ? '...' : '')); ?>",
        <?php if ($videoType === 'youtube'): ?>
        "thumbnailUrl": "https://img.youtube.com/vi/<?php echo $videoId; ?>/maxresdefault.jpg",
        "contentUrl": "https://www.youtube.com/watch?v=<?php echo $videoId; ?>",
        "embedUrl": "https://www.youtube.com/embed/<?php echo $videoId; ?>",
        <?php elseif ($videoType === 'facebook' && !empty($videoThumbnail)): ?>
        "thumbnailUrl": "<?php echo htmlspecialchars($videoThumbnail); ?>",
        "contentUrl": "https://www.facebook.com/watch/?v=<?php echo $videoId; ?>",
        <?php elseif ($videoType === 'twitter' && !empty($videoThumbnail)): ?>
        "thumbnailUrl": "<?php echo htmlspecialchars($videoThumbnail); ?>",
        "contentUrl": "https://twitter.com/i/status/<?php echo $videoId; ?>",
        <?php endif; ?>
        "uploadDate": "<?php echo !empty($videoPublishDate) ? $videoPublishDate : date('Y-m-d'); ?>",
        <?php if (!empty($videoAuthor)): ?>
        "author": {
            "@type": "Person",
            "name": "<?php echo htmlspecialchars($videoAuthor); ?>"
        },
        <?php endif; ?>
        "potentialAction": {
            "@type": "ViewAction",
            <?php if ($videoType === 'youtube'): ?>
            "target": "https://www.youtube.com/watch?v=<?php echo $videoId; ?>"
            <?php elseif ($videoType === 'facebook'): ?>
            "target": "https://www.facebook.com/watch/?v=<?php echo $videoId; ?>"
            <?php endif; ?>
        }
    }
    </script>

    <?php else: ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Vidextract",
        "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'); ?>",
        "description": "Free online tool to extract YouTube video thumbnails, tags, titles, and descriptions without API key. Download high-quality YouTube thumbnails instantly.",
        "applicationCategory": "UtilityApplication",
        "operatingSystem": "Any",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "creator": {
            "@type": "Organization",
            "name": "SbX Group",
            "logo": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp"
        },
        "screenshot": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/vidextract-tab-icon.webp",
        "featureList": [
            "Download high quality YouTube thumbnails",
            "Extract video titles and descriptions",
            "Get video tags and keywords",
            "No API key required",
            "Free to use"
        ],
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "256",
            "reviewCount": "124",
            "bestRating": "5"
        }
    }
    </script>

    <?php endif; ?>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Vidextract">
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
                    <li><a href="/" class="active">Home</a></li>
                    <li><a href="/about/">About</a></li>
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
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <h1 class="hero-title">
                <span class="hero-title-mobile">Extract Video & Download Thumbnail For Free</span>
                <span class="hero-title-desktop">
                    <span class="hero-title-line1">Extract Video & Download Thumbnail</span>
                    <span class="hero-title-line2">For Free</span>
                </span>
            </h1>
            <button class="hero-cta-button" onclick="document.getElementById('video_url').focus();">
                Start for free <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </section>
    
    <div class="container">

        
        <main>
            <form id="video-form" method="post" action="">
                <div class="input-group">
                    <textarea 
                        name="video_url" 
                        id="video_url" 
                        placeholder="<?php 
                            if ($videoType === 'youtube') {
                                echo 'Enter Youtube URL';
                            } elseif ($videoType === 'facebook') {
                                echo 'Enter Facebook URL';
                            } elseif ($videoType === 'instagram') {
                                echo 'Enter Instagram URL';
                            } elseif ($videoType === 'twitter') {
                                echo 'Enter Twitter/X URL';
                            } else {
                                echo 'Enter URL';
                            }
                        ?>" 
                        required
                        autocomplete="off"
                        style="border: none; outline: none; -webkit-appearance: none; -moz-appearance: none; appearance: none; overflow-y: auto; resize: none; min-height: 50px; max-height: 120px; line-height: 1.5; padding-top: 0; margin-top: 10px; width: 100%; font-family: inherit; font-size: 0.95rem; color: #777;"
                    ><?php echo isset($_POST['video_url']) ? htmlspecialchars($_POST['video_url']) : ''; ?></textarea>
                    <div class="input-actions" style="border-top: none !important; height: auto !important; margin-top: 15px !important; padding-top: 0 !important; margin-bottom: 2px !important; padding-bottom: 2px !important; display: flex; justify-content: space-between; align-items: center; line-height: 1;">
                        <div class="left-actions" style="margin-left: 0px; display: flex; align-items: center; height: 30px;">

                            <button type="button" class="action-button copy-button" id="copy-button" title="Copy" style="border: none !important; border-radius: 50% !important; outline: none !important; box-shadow: none !important; background: transparent !important; position: relative; display: inline-flex; justify-content: center; align-items: center; margin: 0; width: 30px; height: 30px;">
                                <i class="far fa-copy" style="position: absolute; transform: translate(-50%, -50%); top: 50%; left: 50%; color: #555555 !important;"></i>
                            </button>
                            <button type="button" class="action-button paste-button" id="paste-button" title="Paste" style="border: none !important; border-radius: 50% !important; outline: none !important; box-shadow: none !important; background: transparent !important; position: relative; display: inline-flex; justify-content: center; align-items: center; margin: 0 0 0 12px; width: 30px; height: 30px;">
                                <i class="far fa-clipboard" style="position: absolute; transform: translate(-50%, -50%); top: 50%; left: 50%; color: #555555 !important;"></i>
                            </button>
                            <button type="button" class="action-button erase-button" id="erase-button" title="Erase" style="border: none !important; border-radius: 50% !important; outline: none !important; box-shadow: none !important; background: transparent !important; position: relative; display: inline-flex; justify-content: center; align-items: center; margin: 0 0 0 12px; width: 30px; height: 30px;">
                                <i class="fas fa-trash-alt" style="position: absolute; transform: translate(-50%, -50%); top: 50%; left: 50%; color: #555555 !important;"></i>
                            </button>
                            
                            <!-- YouTube/Facebook selector removed from here and moved below the input group -->
                        </div>
                        <div class="right-actions" style="position: relative; width: 30px; height: 30px; margin-right: 0px; margin-left: 5px; display: flex; align-items: center;">
                            <!-- Clear button that will be hidden initially -->
                            <button type="button" class="action-button submit-button clear-button" id="clear-button" title="Clear" style="background-color: var(--primary-color); color: white !important; border: none; border-radius: 50%; display: none; position: absolute; width: 30px; height: 30px; z-index: 1; margin: 0; right: 0;">
                                <i class="fas fa-times" style="color: white !important; opacity: 1; position: absolute; transform: translate(-50%, -50%); top: 50%; left: 50%;"></i>
                            </button>
                            <!-- Extract button shown by default -->
                            <button type="submit" class="action-button submit-button" id="extract-button" title="Extract" style="background-color: var(--primary-color); color: white !important; border: none; border-radius: 50%; width: 30px; height: 30px; position: absolute; z-index: 2; right: 0; display: flex; justify-content: center; align-items: center; margin: 0;">
                                <i class="fas fa-arrow-up" id="extract-icon" style="color: white !important; opacity: 1; position: absolute; transform: translate(-50%, -50%); top: 50%; left: 50%;"></i>
                                <span class="extract-spinner" id="extract-spinner" style="display: none;">
                                    <div class="extract-spinner-container">
                                        <div class="extract-spinner-circle"></div>
                                    </div>
                                </span>
                            </button>
                        </div>
                    </div>

                </div>
            </form>
            
            <!-- YouTube/Facebook selector buttons as selection chips/tabs below input group with horizontal scrolling -->
            <div class="video-platforms-selector-container" style="text-align: left; margin-left: 0; margin-right: auto;">
                <div class="scroll-indicator scroll-indicator-left">
                    <i class="fas fa-chevron-left"></i>
                </div>
                <div class="scroll-indicator scroll-indicator-right">
                    <i class="fas fa-chevron-right"></i>
                </div>
                <div class="video-platforms-selector" style="margin-left: 0 !important; margin-right: auto !important; justify-content: flex-start !important;">
                    <label class="platform-option youtube-platform <?php echo $videoType === 'youtube' ? ' active' : ''; ?>">
                        <input type="radio" name="video_type" value="youtube" form="video-form" <?php echo $videoType === 'youtube' ? 'checked' : ''; ?>>
                        <i class="fab fa-youtube"></i>
                        Youtube
                    </label>
                    <label class="platform-option facebook-platform <?php echo $videoType === 'facebook' ? ' active' : ''; ?>">
                        <input type="radio" name="video_type" value="facebook" form="video-form" <?php echo $videoType === 'facebook' ? 'checked' : ''; ?>>
                        <i class="fab fa-facebook"></i>
                        Facebook
                    </label>
                    <!-- Instagram platform option -->
                    <label class="platform-option instagram-platform <?php echo $videoType === 'instagram' ? ' active' : ''; ?>">
                        <input type="radio" name="video_type" value="instagram" form="video-form" <?php echo $videoType === 'instagram' ? 'checked' : ''; ?>>
                        <i class="fab fa-instagram"></i>
                        Instagram
                    </label>
                    <!-- X (Twitter) platform option -->
                    <label class="platform-option x-platform <?php echo $videoType === 'twitter' ? ' active' : ''; ?>">
                        <input type="radio" name="video_type" value="twitter" form="video-form" <?php echo $videoType === 'twitter' ? 'checked' : ''; ?>>
                        <svg class="x-logo" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        X
                    </label>

                </div>
            </div>
            
            <div id="error-container" <?php echo $error ? '' : 'style="display: none;"'; ?>>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <span id="error-message"><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
            
            <div class="content-layout">
                <!-- Recent Extractions Panel -->
                <div id="recent-extractions-panel" class="recent-extractions-panel collapsed">
                    <h3>Recent Extractions</h3>
                    <div class="recent-extractions-container">
                        <div id="youtube-extractions" class="extraction-list">
                            <!-- YouTube extractions will be added here by JavaScript -->
                            <div class="no-extractions-message">No recent YouTube extractions</div>
                        </div>
                        <div id="facebook-extractions" class="extraction-list" style="display: none;">
                            <!-- Facebook extractions will be added here by JavaScript -->
                            <div class="no-extractions-message">No recent Facebook extractions</div>
                        </div>
                        <div id="instagram-extractions" class="extraction-list" style="display: none;">
                            <!-- Instagram extractions will be added here by JavaScript -->
                            <div class="no-extractions-message">No recent Instagram extractions</div>
                        </div>
                        <div id="twitter-extractions" class="extraction-list" style="display: none;">
                            <!-- X (Twitter) extractions will be added here by JavaScript -->
                            <div class="no-extractions-message">No recent X extractions</div>
                        </div>

                    </div>
                </div>
                
                <!-- Results Container -->
                <div id="results-container">
                    <?php if ($videoSuccess && $videoId): ?>
                    <div class="results">
                        <h2>Video Information</h2>
                    
                    <!-- Title Section (if available) -->
                    <?php if ($videoTitle): ?>
                    <div class="info-section">
                        <h3>Title</h3>
                        <div class="content-box">
                            <p id="video-title"><?php echo htmlspecialchars($videoTitle); ?></p>
                            <button class="copy-btn" data-clipboard-target="#video-title">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Description Section (if available) -->
                    <?php if ($videoDescription): ?>
                    <div class="info-section">
                        <h3>Description</h3>
                        <div class="content-box">
                            <pre id="video-description"><?php echo htmlspecialchars($videoDescription); ?></pre>
                            <button class="copy-btn" data-clipboard-target="#video-description">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Thumbnail Section - Single with dropdown -->
                    <div class="thumbnail-section">
                        <h3>Thumbnail</h3>
                        <div class="single-thumbnail-container">
                            <img src="https://img.youtube.com/vi/<?php echo $videoId; ?>/maxresdefault.jpg" alt="Video Thumbnail" class="main-thumbnail" id="main-thumbnail">
                            
                            <div class="thumbnail-download-options">
                                <div class="download-dropdown">
                                    <button class="download-btn dropdown-toggle">
                                        <i class="fas fa-download"></i> Download Thumbnail <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="https://img.youtube.com/vi/<?php echo $videoId; ?>/maxresdefault.jpg" download="youtube_maxres_<?php echo $videoId; ?>.jpg">
                                            <i class="fas fa-image"></i> Maximum Resolution
                                            <span class="resolution-info">1280×720</span>
                                        </a>
                                        <a href="https://img.youtube.com/vi/<?php echo $videoId; ?>/sddefault.jpg" download="youtube_sd_<?php echo $videoId; ?>.jpg">
                                            <i class="fas fa-image"></i> Standard Definition
                                            <span class="resolution-info">640×480</span>
                                        </a>
                                        <a href="https://img.youtube.com/vi/<?php echo $videoId; ?>/hqdefault.jpg" download="youtube_hq_<?php echo $videoId; ?>.jpg">
                                            <i class="fas fa-image"></i> High Quality
                                            <span class="resolution-info">480×360</span>
                                        </a>
                                        <a href="https://img.youtube.com/vi/<?php echo $videoId; ?>/mqdefault.jpg" download="youtube_mq_<?php echo $videoId; ?>.jpg">
                                            <i class="fas fa-image"></i> Medium Quality
                                            <span class="resolution-info">320×180</span>
                                        </a>
                                        <a href="https://img.youtube.com/vi/<?php echo $videoId; ?>/default.jpg" download="youtube_default_<?php echo $videoId; ?>.jpg">
                                            <i class="fas fa-image"></i> Default
                                            <span class="resolution-info">120×90</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tags Section (if available) -->
                    <div class="info-section">
                        <h3>Tags</h3>
                        <div class="content-box">
                            <?php if (!empty($videoTags)): ?>
                                <div class="tags-container">
                                    <div class="tags" id="video-tags">
                                        <?php foreach ($videoTags as $tag): ?>
                                            <span class="tag" data-tag="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button class="copy-btn" data-clipboard-target="#video-tags">
                                    <i class="fas fa-copy"></i> Copy All Tags
                                </button>
                            <?php else: ?>
                                <p class="no-data">No tags available for this video.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            </div>
        </main>
        
        <!-- Additional Site Information for SEO -->
        <div class="seo-info" style="display:none">
            <p>VidExtract is a powerful video information extractor for YouTube and Facebook videos, allowing you to extract thumbnails, titles, descriptions, and tags without requiring an API key.</p>
            <p>Features include: downloading thumbnails in multiple resolutions (maxres, standard, high quality, medium quality), copying video titles and descriptions, and extracting video tags.</p>
            <p>Cross-platform support for all major browsers including Chrome, Firefox, Safari, and Edge. Works with standard YouTube videos, YouTube Shorts, and Facebook videos including Reels.</p>
        </div>
    </div>
    

    
    <script src="js/script.js"></script>
    <script src="js/favicon-switcher.js"></script>
    <script src="js/adaptive-spinner.js"></script>
    <script src="js/page-transitions.js"></script>
    <script src="js/input-actions.js"></script>
    <script src="js/spa-navigation.js"></script>

    <script src="js/scroll-indicators.js"></script>
    
    <style>
        /* Global highest z-index class for elements that should always be on top */
        .max-z-index-element {
            z-index: 2147483647 !important; /* Maximum possible z-index value */
            position: relative;
        }
        
        /* Webkit scrollbar styling for textarea */
        textarea::-webkit-scrollbar {
            width: 5px;
        }
        
        textarea::-webkit-scrollbar-track {
            background: transparent;
        }
        
        textarea::-webkit-scrollbar-thumb {
            background-color: rgba(var(--primary-color-rgb), 0.5);
            border-radius: 10px;
        }
        
        /* Button Icon Centering */
        .clear-button, .submit-button {
            position: relative;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        .clear-button i, .submit-button i {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
        }
        
        /* Firefox scrollbar styling and smooth scroll behavior */
        textarea {
            scrollbar-width: thin;
            scrollbar-color: rgba(var(--primary-color-rgb), 0.5) transparent;
            scroll-behavior: smooth;
            overflow-y: auto;
            transition: all 0.3s ease-in-out;
            height: 50px;
            margin-top: 10px;
            padding-top: 0;
        }
        
        /* Emergency override for the input group border */
        .input-actions {
            border-top: none !important;
            height: 40px !important;
            padding-top: 0 !important;
            margin-top: 0 !important;
            padding-bottom: 15px !important;
        }
        
        /* Restored box-shadow for input groups */
        /* .input-group {
            box-shadow: none !important;
        } */
        
        /* Extract Button Spinner Styles */
        .extract-spinner {
            display: inline-block;
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-top: -8px;
            margin-left: -8px;
        }
        
        .extract-spinner-container {
            position: absolute;
            width: 100%;
            height: 100%;
        }
        
        .extract-spinner-circle {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: extract-spinner-animation 0.8s infinite linear;
        }
        
        @keyframes extract-spinner-animation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Make room for the spinner in the button */
        .submit-button {
            position: relative;
        }
        
        .submit-button i.fa-arrow-up {
            transition: opacity 0.3s ease;
        }
        
        .submit-button.is-loading i.fa-arrow-up {
            opacity: 0;
        }
    </style>
    
    <script>
        // Initialize active navigation highlighting
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('.footer-nav a').forEach(link => {
            const linkPage = link.getAttribute('href');
            if (linkPage === currentPage) {
                link.classList.add('active');
            }
        });
        document.addEventListener('DOMContentLoaded', function() {
            
            // Show the clear button and hide the extract button when video data is displayed
            const resultsContainer = document.getElementById('results-container');
            const clearButton = document.getElementById('clear-button');
            const extractButton = document.getElementById('extract-button');
            
            // If there's content in the results container, show the clear button and hide extract button
            if (resultsContainer && resultsContainer.querySelector('.results')) {
                if (clearButton) clearButton.style.display = 'block';
                if (extractButton) extractButton.style.display = 'none';
                // Update z-index to ensure clear button is on top
                if (clearButton) clearButton.style.zIndex = '2'; 
                if (extractButton) extractButton.style.zIndex = '1';
            } else {
                // Otherwise, show extract button and hide clear button
                if (clearButton) clearButton.style.display = 'none';
                if (extractButton) extractButton.style.display = 'block';
                // Update z-index to ensure extract button is on top
                if (clearButton) clearButton.style.zIndex = '1';
                if (extractButton) extractButton.style.zIndex = '2';
            }
        });
    </script>
    

    
    <!-- FAQ Section -->
    <section class="faq-section container" id="faq">
        <h3 style="text-align: center; display: block; margin-bottom: 20px;">Frequently Asked Questions</h3>
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question">
                    <h3>How do I extract YouTube video thumbnails?</h3>
                    <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="faq-answer">
                    <p>Simply paste the YouTube video URL into Vidextract, select YouTube platform, and click Extract. You'll instantly get all available thumbnails in different resolutions that you can download with a single click.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Does Vidextract work with Facebook videos?</h3>
                    <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="faq-answer">
                    <p>Yes! Vidextract fully supports Facebook videos. You can extract thumbnails, titles, and descriptions from any public Facebook video by pasting the URL. Our tool handles all types of Facebook video links including posts, watch pages, and shared content.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Can I extract video tags from Instagram reels?</h3>
                    <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="faq-answer">
                    <p>Absolutely. Vidextract can extract metadata from Instagram reels including thumbnails and available tag information. Simply select Instagram from the platform options and paste your reel URL to get all the available data.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>How do I extract information from Twitter/X videos?</h3>
                    <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="faq-answer">
                    <p>To extract information from Twitter/X videos, simply select Twitter/X from the platform options and paste the video tweet URL into the input field. Vidextract will retrieve available thumbnail images and metadata from the tweet. This works with both standard tweets containing videos and media-focused posts.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Do I need an API key to use Vidextract?</h3>
                    <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="faq-answer">
                    <p>No, Vidextract works without any API keys. It's completely free to use with no registration required. Our service extracts video information directly, saving you time and resources while providing high-quality results.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Which video platforms does Vidextract support?</h3>
                    <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="faq-answer">
                    <p>Vidextract currently supports YouTube, Facebook, Instagram, and Twitter/X. We're constantly working to add more platforms to provide the most comprehensive video extraction service available.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Is there a limit to how many videos I can extract?</h3>
                    <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="faq-answer">
                    <p>There are no strict limits on the number of extractions. However, we recommend reasonable use to ensure the service remains fast and available for everyone. Your recent extractions are saved locally in your browser for convenient access.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- FAQ Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                const answer = item.querySelector('.faq-answer');
                const toggle = item.querySelector('.faq-toggle');
                const paragraph = answer.querySelector('p');
                
                // Set RGB variable for primary color to enable rgba in CSS
                const style = getComputedStyle(document.documentElement);
                const primaryColor = style.getPropertyValue('--primary-color').trim();
                
                if (primaryColor && primaryColor.startsWith('#')) {
                    // Convert hex to RGB and set it as a CSS variable
                    const r = parseInt(primaryColor.slice(1, 3), 16);
                    const g = parseInt(primaryColor.slice(3, 5), 16);
                    const b = parseInt(primaryColor.slice(5, 7), 16);
                    document.documentElement.style.setProperty('--primary-color-rgb', `${r}, ${g}, ${b}`);
                }
                
                // Initialize toggle icon (force using Font Awesome properly)
                toggle.innerHTML = '<i class="fas fa-plus"></i>';
                
                question.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Check if this answer is currently open
                    const isOpen = answer.classList.contains('open');
                    
                    // Close all other open answers first
                    faqItems.forEach(otherItem => {
                        const otherAnswer = otherItem.querySelector('.faq-answer');
                        const otherToggle = otherItem.querySelector('.faq-toggle');
                        const otherParagraph = otherAnswer.querySelector('p');
                        
                        if (otherItem !== item && otherAnswer.classList.contains('open')) {
                            otherAnswer.classList.remove('open');
                            otherParagraph.style.opacity = '0';
                            otherToggle.innerHTML = '<i class="fas fa-plus"></i>';
                            otherItem.style.zIndex = '1';
                            otherItem.style.transform = 'translateY(0)';
                        }
                    });
                    
                    // Toggle the current answer with a smooth animation
                    if (isOpen) {
                        // Close this answer
                        answer.classList.remove('open');
                        paragraph.style.opacity = '0';
                        toggle.innerHTML = '<i class="fas fa-plus"></i>';
                        item.style.zIndex = '1';
                        item.style.transform = 'translateY(0)';
                    } else {
                        // Open this answer
                        answer.classList.add('open');
                        paragraph.style.opacity = '1';
                        toggle.innerHTML = '<i class="fas fa-minus"></i>';
                        item.style.zIndex = '2';
                        
                        // Scroll into view if needed
                        const rect = answer.getBoundingClientRect();
                        const isInView = (
                            rect.top >= 0 &&
                            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight)
                        );
                        
                        if (!isInView) {
                            setTimeout(() => {
                                answer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }, 300);
                        }
                    }
                });
                
                // Add hover effect for better interactivity
                question.addEventListener('mouseenter', () => {
                    item.style.transform = 'translateY(-2px)';
                });
                
                question.addEventListener('mouseleave', () => {
                    if (!answer.classList.contains('open')) {
                        item.style.transform = 'translateY(0)';
                    }
                });
            });
        });
    </script>
    
    <!-- FAQ Styles -->
    <style>
        .faq-section {
            margin: 50px auto;
            max-width: 1000px;
            padding: 20px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            color: var(--primary-color);
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .faq-container {
            border-radius: 0;
            overflow: hidden;
        }
        
        .faq-item {
            margin-bottom: 15px;
            background-color: transparent;
            transition: all 0.3s ease;
        }
        
        .faq-item:hover {
            /* Removed black border on hover as requested */
        }
        
        .faq-question {
            padding: 18px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s ease;
            background-color: transparent;
        }
        
        .faq-question:hover {
            background-color: transparent;
        }
        
        .faq-question h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 500;
            color: #333;
            flex: 1;
            padding-right: 15px;
        }
        
        .faq-toggle {
            display: none;
        }
        
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            padding: 0 20px;
            background-color: transparent;
            transition: all 0.4s ease;
            border-top: 1px dashed #e0e0e0;
            line-height: 1.6;
        }
        
        .faq-answer.open {
            max-height: 500px;
            padding: 20px;
        }
        
        .faq-answer p {
            margin: 0;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: #444;
        }
        
        .faq-answer.open p {
            opacity: 1;
        }
        
        /* Platform-specific colors */
        .faq-item:nth-child(1) {
            /* Removed YouTube red left border */
        }
        

        
        /* Dark mode adjustments */
        @media (prefers-color-scheme: dark) {
            .faq-section {
                border-color: var(--primary-color);
                background-color: transparent;
            }
            
            .faq-item {
                background-color: transparent;
            }
            
            .faq-question {
                background-color: transparent;
            }
            
            .faq-question:hover {
                background-color: transparent;
            }
            
            .faq-question h3 {
                color: #333;
            }
            
            .faq-answer {
                background-color: transparent;
                border-top-color: #eeeeee;
            }
            
            .faq-answer p {
                color: #444;
            }
        }
    </style>
    

    
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
