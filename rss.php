<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

// Website information
$site_title = "SEOULSARAM";
$site_url = "https://seoulsaram.org"; // Replace with your actual domain
$site_description = "Sungjoon Moon's Personal Blog";
$site_language = "en-us"; // Change to your language code if needed

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
echo '<channel>' . "\n";
echo '<title>' . $site_title . '</title>' . "\n";
echo '<link>' . $site_url . '</link>' . "\n";
echo '<description>' . $site_description . '</description>' . "\n";
echo '<language>' . $site_language . '</language>' . "\n";
echo '<atom:link href="' . $site_url . '/rss.xml" rel="self" type="application/rss+xml" />' . "\n";

// Function to scan directories for articles
function scanArticles($base_dir, $site_url) {
    $articles = array();

    // Scan the articles directory
    if (is_dir($base_dir . '/articles')) {
        $dirs = scandir($base_dir . '/articles');
        foreach ($dirs as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir($base_dir . '/articles/' . $dir)) {
                $article_dir = $base_dir . '/articles/' . $dir;
                $src_file = $article_dir . '/src.php';

                if (file_exists($src_file)) {
                    // Get article metadata
                    $content = file_get_contents($src_file);
                    
                    // Extract title (assuming it's in genheader function)
                    preg_match('/genheader\("([^"]+)"/', $content, $title_matches);
                    $title = isset($title_matches[1]) ? $title_matches[1] : $dir;
                    
                    // Extract date if available
                    preg_match('/genheader\([^,]+,\s*"([^"]+)"/', $content, $date_matches);
                    $date = isset($date_matches[1]) ? $date_matches[1] : date("r", filemtime($src_file));
                    
                    // Create article object
                    $article = array(
                        'title' => $title,
                        'link' => $site_url . '/articles/' . $dir . '/',
                        'date' => $date,
                        'timestamp' => filemtime($src_file)
                    );
                    
                    $articles[] = $article;
                }
            }
        }
    }

    // Sort articles by timestamp (newest first)
    usort($articles, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    return $articles;
}

// Get articles
$articles = scanArticles(__DIR__, $site_url);


date_default_timezone_set('Asia/Seoul');

// Add items to RSS feed
foreach ($articles as $article) {
    echo '<item>' . "\n";
    echo '<title>' . htmlspecialchars($article['title']) . '</title>' . "\n";
    echo '<link>' . $article['link'] . '</link>' . "\n";
    echo '<guid>' . $article['link'] . '</guid>' . "\n";
    echo '<pubDate>' . date('r', $article['timestamp']) . '</pubDate>' . "\n";
    echo '</item>' . "\n";
}

// Close channel and RSS tags
echo '</channel>' . "\n";
echo '</rss>';
?>
