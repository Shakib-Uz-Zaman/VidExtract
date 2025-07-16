<div class="results">
    <?php
    // Check if this is a YouTube post or channel by testing the URL or videoId
    $isYoutubePost = false;
    $isYoutubeChannel = false;
    
    if ($videoType === 'youtube' && isset($_POST['video_url'])) {
        $isYoutubePost = preg_match('/youtube\.com\/post\/([A-Za-z0-9_\-]+)(?:\?[^\/]*)?/', $_POST['video_url']);
    }
    
    // Check if this is a YouTube channel (by looking at videoId format)
    if ($videoType === 'youtube' && isset($videoId) && strpos($videoId, 'channel_') === 0) {
        $isYoutubeChannel = true;
    }
    ?>
    <h2>
        <?php 
        if ($isYoutubeChannel) {
            echo 'Channel Information';
        } elseif ($isYoutubePost) {
            echo 'Post Information';
        } else {
            echo 'Video Information';
        }
        ?>
    </h2>
    
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
    
    <!-- Description Section (if available and not Instagram) -->
    <?php if ($videoDescription && $videoType !== 'instagram'): ?>
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
    
    <!-- Thumbnail/Image Section - Single with dropdown -->
    <div class="thumbnail-section">
        <h3>
            <?php 
            if ($isYoutubeChannel) {
                echo 'Channel Profile Image';
            } elseif ($isYoutubePost) {
                echo 'Post Image';
            } else {
                echo 'Thumbnail';
            }
            ?>
        </h3>
        <div class="single-thumbnail-container">
            <?php if ($videoType === 'youtube'): ?>
            <?php 
            if ($isYoutubeChannel) {
                // For YouTube Channel, use the profile image
                $thumbnailUrl = !empty($videoThumbnail) ? $videoThumbnail : ""; 
                $thumbnailAlt = !empty($videoTitle) ? htmlspecialchars($videoTitle) : "YouTube Channel Image"; 
                $filenamePrefix = "youtube_channel";
            } elseif ($isYoutubePost) {
                // For YouTube Post, use the extracted image
                $thumbnailUrl = !empty($videoThumbnail) ? $videoThumbnail : ""; 
                $thumbnailAlt = !empty($videoTitle) ? htmlspecialchars($videoTitle) : "YouTube Post Image"; 
                $filenamePrefix = "youtube_post";
            } else {
                // For regular YouTube video, use the standard thumbnail
                $thumbnailUrl = !empty($videoThumbnail) ? $videoThumbnail : "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg"; 
                $thumbnailAlt = !empty($videoTitle) ? htmlspecialchars($videoTitle) : "YouTube Video Thumbnail"; 
                $filenamePrefix = "youtube_video";
            }
            ?>
            <img src="<?php echo $thumbnailUrl; ?>" alt="<?php echo $thumbnailAlt; ?>" class="main-thumbnail" id="main-thumbnail">
            
            <div class="thumbnail-download-options">
                <div class="download-dropdown">
                    <button class="download-btn dropdown-toggle">
                        <i class="fas fa-download"></i> Download 
                        <?php 
                        if ($isYoutubeChannel) {
                            echo 'Profile Image';
                        } elseif ($isYoutubePost) {
                            echo 'Image';
                        } else {
                            echo 'Thumbnail';
                        }
                        ?> <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <!-- Universal download solution that works across all browsers and devices -->
                        <a href="#" onclick="document.getElementById('youtube-original-dl-form').submit(); return false;" 
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> Original Resolution
                            <span class="resolution-info">Best Quality</span>
                        </a>
                        <form id="youtube-original-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="<?php echo htmlspecialchars($videoThumbnail); ?>">
                            <input type="hidden" name="filename" value="<?php echo $filenamePrefix; ?>_original_<?php echo $videoId; ?>.jpg">
                        </form>
                        
                        <?php if (!$isYoutubePost): // Only show these options for regular videos ?>
                        <div class="dropdown-divider"></div>
                        
                        <a href="#" onclick="document.getElementById('youtube-hd-dl-form').submit(); return false;"
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> HD Quality
                            <span class="resolution-info">1280×720</span>
                        </a>
                        <form id="youtube-hd-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="https://img.youtube.com/vi/<?php echo $videoId; ?>/maxresdefault.jpg">
                            <input type="hidden" name="filename" value="youtube_video_hd_<?php echo $videoId; ?>.jpg">
                        </form>
                        
                        <a href="#" onclick="document.getElementById('youtube-medium-dl-form').submit(); return false;"
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> Medium Quality
                            <span class="resolution-info">720×405</span>
                        </a>
                        <form id="youtube-medium-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="https://img.youtube.com/vi/<?php echo $videoId; ?>/mqdefault.jpg">
                            <input type="hidden" name="filename" value="youtube_video_medium_<?php echo $videoId; ?>.jpg">
                        </form>
                        
                        <a href="#" onclick="document.getElementById('youtube-low-dl-form').submit(); return false;"
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> Low Quality
                            <span class="resolution-info">480×270</span>
                        </a>
                        <form id="youtube-low-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="https://img.youtube.com/vi/<?php echo $videoId; ?>/sddefault.jpg">
                            <input type="hidden" name="filename" value="youtube_video_low_<?php echo $videoId; ?>.jpg">
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php elseif ($videoType === 'facebook' && !empty($videoThumbnail)): ?>
            <img src="<?php echo htmlspecialchars($videoThumbnail); ?>" alt="Video Thumbnail" class="main-thumbnail" id="main-thumbnail">
            
            <div class="thumbnail-download-options">
                <div class="download-dropdown">
                    <button class="download-btn dropdown-toggle">
                        <i class="fas fa-download"></i> Download Thumbnail <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <?php 
                            // Clean the Facebook thumbnail URL first to fix encoding issues
                            $cleanVideoThumbnail = $videoThumbnail;
                        ?>
                        <!-- Universal download solution that works across all browsers and devices -->
                        <a href="#" onclick="document.getElementById('facebook-original-dl-form').submit(); return false;" 
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> Original Resolution
                            <span class="resolution-info">Best Quality</span>
                        </a>
                        <form id="facebook-original-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="<?php echo htmlspecialchars($cleanVideoThumbnail); ?>">
                            <input type="hidden" name="filename" value="facebook_video_original_<?php echo $videoId; ?>.jpg">
                        </form>
                        
                        <div class="dropdown-divider"></div>
                        
                        <?php
                            $sizeParam = (strpos($videoThumbnail, '?') !== false) ? '&size=1280' : '?size=1280';
                            $hdUrl = $cleanVideoThumbnail . $sizeParam;
                        ?>
                        <a href="#" onclick="document.getElementById('facebook-hd-dl-form').submit(); return false;"
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> HD Quality
                            <span class="resolution-info">1280×720</span>
                        </a>
                        <form id="facebook-hd-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="<?php echo htmlspecialchars($hdUrl); ?>">
                            <input type="hidden" name="filename" value="facebook_video_hd_<?php echo $videoId; ?>.jpg">
                        </form>
                        
                        <?php 
                            $sizeParam = (strpos($videoThumbnail, '?') !== false) ? '&size=720' : '?size=720'; 
                            $mediumUrl = $cleanVideoThumbnail . $sizeParam;
                        ?>
                        <a href="#" onclick="document.getElementById('facebook-medium-dl-form').submit(); return false;"
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> Medium Quality
                            <span class="resolution-info">720×405</span>
                        </a>
                        <form id="facebook-medium-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="<?php echo htmlspecialchars($mediumUrl); ?>">
                            <input type="hidden" name="filename" value="facebook_video_medium_<?php echo $videoId; ?>.jpg">
                        </form>
                        
                        <?php 
                            $sizeParam = (strpos($videoThumbnail, '?') !== false) ? '&size=480' : '?size=480'; 
                            $lowUrl = $cleanVideoThumbnail . $sizeParam;
                        ?>
                        <a href="#" onclick="document.getElementById('facebook-low-dl-form').submit(); return false;"
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> Low Quality
                            <span class="resolution-info">480×270</span>
                        </a>
                        <form id="facebook-low-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="<?php echo htmlspecialchars($lowUrl); ?>">
                            <input type="hidden" name="filename" value="facebook_video_low_<?php echo $videoId; ?>.jpg">
                        </form>
                    </div>
                </div>
            </div>
            <?php elseif ($videoType === 'instagram' && !empty($videoThumbnail)): ?>
            <img src="<?php echo htmlspecialchars($videoThumbnail); ?>" alt="Instagram Post Image" class="main-thumbnail" id="main-thumbnail">
            
            <div class="thumbnail-download-options">
                <div class="download-dropdown">
                    <button class="download-btn dropdown-toggle">
                        <i class="fas fa-download"></i> Download Image <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <?php 
                            // Clean the Instagram thumbnail URL first to fix encoding issues
                            $cleanVideoThumbnail = $videoThumbnail;
                        ?>
                        <!-- Universal download solution that works across all browsers and devices -->
                        <a href="#" onclick="document.getElementById('instagram-original-dl-form').submit(); return false;" 
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> Original Resolution
                            <span class="resolution-info">Best Quality</span>
                        </a>
                        <form id="instagram-original-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="<?php echo htmlspecialchars($cleanVideoThumbnail); ?>">
                            <input type="hidden" name="filename" value="instagram_post_<?php echo $videoId; ?>.jpg">
                        </form>
                    </div>
                </div>
            </div>
            <?php elseif ($videoType === 'twitter' && !empty($videoThumbnail)): ?>
            <img src="<?php echo htmlspecialchars($videoThumbnail); ?>" alt="Twitter/X Post Image" class="main-thumbnail" id="main-thumbnail">
            
            <div class="thumbnail-download-options">
                <div class="download-dropdown">
                    <button class="download-btn dropdown-toggle">
                        <i class="fas fa-download"></i> Download Image <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <?php 
                            // Clean the Twitter thumbnail URL first to fix encoding issues
                            $cleanVideoThumbnail = $videoThumbnail;
                        ?>
                        <!-- Universal download solution that works across all browsers and devices -->
                        <a href="#" onclick="document.getElementById('twitter-original-dl-form').submit(); return false;" 
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> Original Resolution
                            <span class="resolution-info">Best Quality</span>
                        </a>
                        <form id="twitter-original-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="<?php echo htmlspecialchars($cleanVideoThumbnail); ?>">
                            <input type="hidden" name="filename" value="twitter_post_<?php echo $videoId; ?>.jpg">
                        </form>
                        
                        <?php
                            // Add high quality option (similar logic as Facebook)
                            $twitterHDUrl = $cleanVideoThumbnail;
                            // Replace any size parameters like ?format=jpg&name=small with large
                            if (strpos($twitterHDUrl, 'name=') !== false) {
                                $twitterHDUrl = preg_replace('/name=[^&]+/', 'name=large', $twitterHDUrl);
                            } else if (strpos($twitterHDUrl, '?') !== false) {
                                $twitterHDUrl .= '&name=large';
                            } else {
                                $twitterHDUrl .= '?name=large';
                            }
                        ?>
                        <div class="dropdown-divider"></div>
                        <a href="#" onclick="document.getElementById('twitter-hd-dl-form').submit(); return false;"
                           class="direct-download" data-no-spinner="true">
                            <i class="fas fa-image"></i> HD Quality
                            <span class="resolution-info">Large Size</span>
                        </a>
                        <form id="twitter-hd-dl-form" action="download.php" method="get" target="_blank" style="display:none;">
                            <input type="hidden" name="url" value="<?php echo htmlspecialchars($twitterHDUrl); ?>">
                            <input type="hidden" name="filename" value="twitter_post_hd_<?php echo $videoId; ?>.jpg">
                        </form>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="no-thumbnail">
                <i class="fas fa-image"></i>
                <p>No thumbnail available for this video</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Channel Banner Section (if applicable) -->
    <?php if ($isYoutubeChannel && !empty($channelBanner)): ?>
    <div class="info-section">
        <h3>Channel Banner</h3>
        <div class="content-box">
            <div class="channel-banner-container">
                <img src="<?php echo htmlspecialchars($channelBanner); ?>" alt="Channel Banner" class="channel-banner">
                
                <div class="thumbnail-download-options">
                    <div class="download-dropdown">
                        <button class="download-btn dropdown-toggle">
                            <i class="fas fa-download"></i> Download Banner <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="#" onclick="document.getElementById('youtube-banner-dl-form-original').submit(); return false;" 
                               class="direct-download" data-no-spinner="true">
                                <i class="fas fa-image"></i> Original Resolution
                                <span class="resolution-info">Best Quality</span>
                            </a>
                            <form id="youtube-banner-dl-form-original" action="download.php" method="get" target="_blank" style="display:none;">
                                <input type="hidden" name="url" value="<?php echo htmlspecialchars($channelBanner); ?>">
                                <input type="hidden" name="filename" value="youtube_channel_banner_<?php echo $channelUsername; ?>.jpg">
                            </form>
                            
                            <a href="#" onclick="document.getElementById('youtube-banner-dl-form-hd').submit(); return false;" 
                               class="direct-download" data-no-spinner="true">
                                <i class="fas fa-image"></i> HD Quality
                                <span class="resolution-info">1280×720</span>
                            </a>
                            <form id="youtube-banner-dl-form-hd" action="download.php" method="get" target="_blank" style="display:none;">
                                <input type="hidden" name="url" value="<?php echo htmlspecialchars($channelBanner); ?>">
                                <input type="hidden" name="filename" value="youtube_channel_banner_<?php echo $channelUsername; ?>_hd.jpg">
                                <input type="hidden" name="width" value="1280">
                                <input type="hidden" name="height" value="720">
                            </form>
                            
                            <a href="#" onclick="document.getElementById('youtube-banner-dl-form-medium').submit(); return false;" 
                               class="direct-download" data-no-spinner="true">
                                <i class="fas fa-image"></i> Medium Quality
                                <span class="resolution-info">720×405</span>
                            </a>
                            <form id="youtube-banner-dl-form-medium" action="download.php" method="get" target="_blank" style="display:none;">
                                <input type="hidden" name="url" value="<?php echo htmlspecialchars($channelBanner); ?>">
                                <input type="hidden" name="filename" value="youtube_channel_banner_<?php echo $channelUsername; ?>_medium.jpg">
                                <input type="hidden" name="width" value="720">
                                <input type="hidden" name="height" value="405">
                            </form>
                            
                            <a href="#" onclick="document.getElementById('youtube-banner-dl-form-low').submit(); return false;" 
                               class="direct-download" data-no-spinner="true">
                                <i class="fas fa-image"></i> Low Quality
                                <span class="resolution-info">480×270</span>
                            </a>
                            <form id="youtube-banner-dl-form-low" action="download.php" method="get" target="_blank" style="display:none;">
                                <input type="hidden" name="url" value="<?php echo htmlspecialchars($channelBanner); ?>">
                                <input type="hidden" name="filename" value="youtube_channel_banner_<?php echo $channelUsername; ?>_low.jpg">
                                <input type="hidden" name="width" value="480">
                                <input type="hidden" name="height" value="270">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    

    
    <!-- Tags Section (if applicable and not Instagram) -->
    <?php if (!$isYoutubePost && !$isYoutubeChannel && $videoType !== 'instagram'): // Only show tags section for videos (except Instagram), not for posts or channels ?>
    <div class="info-section">
        <h3>Tags</h3>
        <div class="content-box">
            <?php if (!empty($videoTags) && is_array($videoTags)): ?>
                <div class="tags-container">
                    <div class="tags" id="video-tags">
                        <?php foreach ($videoTags as $tag): ?>
                            <?php if (!empty($tag)): ?>
                                <?php $decodedTag = html_entity_decode($tag, ENT_QUOTES, 'UTF-8'); ?>
                                <span class="tag" data-tag="<?php echo htmlspecialchars($decodedTag); ?>"><?php echo htmlspecialchars($decodedTag); ?></span>
                            <?php endif; ?>
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
    <?php endif; ?>
</div>